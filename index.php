<?php

// This file is part of Course Agent - AI Course Creator Plugin for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Main course creation page for local_courseagent.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Commenting.MissingDocblock.File

require_once(__DIR__ . '/../../config.php');

use local_courseagent\provider;

// Require login and check capability.
$context = context_system::instance();
require_login();
require_capability('local/courseagent:createcourse', $context);

// Setup page.
$pageurl = new moodle_url('/local/courseagent/index.php');
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('create_course', 'local_courseagent'));
$PAGE->set_heading(get_string('create_course', 'local_courseagent'));
$PAGE->set_pagelayout('base');
// The redesigned page renders its own in-content header (breadcrumb + title + subtitle),
// so suppress the default Moodle page header to avoid a duplicate heading.
$PAGE->add_body_class('local-courseagent-create');

// Get plugin configuration.
$maxsections      = get_config('local_courseagent', 'max_sections') ?: 8;
$maxquiz          = get_config('local_courseagent', 'max_quiz_questions') ?: 7;
$enableassignments = get_config('local_courseagent', 'enable_assignments') ?: 1;
$saasapikey       = get_config('local_courseagent', 'saas_api_key') ?: '';
$saasplan         = get_config('local_courseagent', 'saas_plan') ?: 'none';
// Paid features require an *activated* paid plan, not just a key. The plan is stored
// at activation time (settings save / "Activate License" button) after a backend check.
$hassaaskey       = !empty($saasapikey) && in_array($saasplan, ['starter', 'pro'], true);

// Get available providers.
$providers       = provider::getAll(true);
$defaultprovider = provider::getDefault();

// Check if any provider is configured.
if (empty($providers)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_no_provider', 'local_courseagent'), 'notifyproblem');
    echo $OUTPUT->single_button(
        new moodle_url('/local/courseagent/providers.php'),
        get_string('provider_manage_link', 'local_courseagent'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

// Build provider config for JavaScript.
$providerconfig = [];
foreach ($providers as $p) {
    $models = json_decode($p->models, true) ?: [];
    $providerconfig[$p->id] = [
        'name'      => $p->name,
        'models'    => $models,
        'isdefault' => $p->isdefault,
    ];
}

// Pass only small config to JavaScript â€” strings/wwwroot/sesskey loaded in JS via core/str and core/config.
$jsconfig = [
    'maxSections'       => (int)  $maxsections,
    'maxQuizQuestions'  => (int)  $maxquiz,
    'enableAssignments' => (bool) $enableassignments,
    'hasSaasKey'        => (bool) $hassaaskey,
    'saasPlan'          => $saasplan,
    'providers'         => $providerconfig,
    'defaultProviderId' => $defaultprovider ? $defaultprovider->id : 0,
];
$PAGE->requires->css(new moodle_url('/local/courseagent/styles.css'));
$PAGE->requires->js(new moodle_url('/local/courseagent/amd/build/mermaid.min.js'));
$PAGE->requires->js_call_amd('local_courseagent/coursecreator', 'init', [$jsconfig]);

echo $OUTPUT->header();
?>

<div id="courseagent-app">

    <!-- Header & breadcrumb -->
    <div class="ca-page-head">
        <nav class="ca-breadcrumb" aria-label="breadcrumb">
            <a href="<?php echo new moodle_url('/my/'); ?>"><?php print_string('myhome'); ?></a>
            <i class="fa fa-angle-right ca-breadcrumb-sep" aria-hidden="true"></i>
            <a href="<?php echo new moodle_url('/local/courseagent/mycourses.php'); ?>"><?php print_string('my_courses', 'local_courseagent'); ?></a>
            <i class="fa fa-angle-right ca-breadcrumb-sep" aria-hidden="true"></i>
            <span class="ca-breadcrumb-current"><?php print_string('create_course', 'local_courseagent'); ?></span>
        </nav>
        <h1 class="ca-page-title"><?php print_string('create_course', 'local_courseagent'); ?></h1>
        <p class="ca-page-subtitle"><?php print_string('configure_settings', 'local_courseagent'); ?></p>
    </div>

    <div class="row">
        <!-- MAIN COLUMN -->
        <div class="col-lg-8">
            <form id="courseagent-form">

                <!-- Card 1: Core Settings -->
                <div class="card ca-card mb-4">
                    <div class="card-body">
                        <h2 class="ca-card-title">
                            <i class="fa fa-sliders ca-card-title-icon" aria-hidden="true"></i>
                            <?php print_string('core_settings', 'local_courseagent'); ?>
                        </h2>

                        <!-- Course Topic -->
                        <div class="form-group">
                            <label for="course-topic" class="ca-field-label">
                                <?php print_string('coursetopic', 'local_courseagent'); ?> <span class="text-danger">*</span>
                            </label>
                            <textarea id="course-topic" class="form-control" rows="3" maxlength="500"
                                      placeholder="<?php print_string('coursetopic_placeholder', 'local_courseagent'); ?>"></textarea>
                            <small class="form-text text-muted d-flex justify-content-between">
                                <span><?php print_string('coursetopic_help', 'local_courseagent'); ?></span>
                                <span id="course-topic-counter" class="text-muted">0 / 500</span>
                            </small>
                        </div>

                        <!-- Course Title -->
                        <div class="form-group">
                            <label for="course-custom-title" class="ca-field-label">
                                <?php print_string('course_title', 'local_courseagent'); ?>
                                <span class="text-muted font-weight-normal small ml-1"><?php print_string('optional_override', 'local_courseagent'); ?></span>
                            </label>
                            <input type="text" id="course-custom-title" class="form-control"
                                   placeholder="<?php print_string('course_title_placeholder', 'local_courseagent'); ?>">
                        </div>

                        <div class="row">
                            <!-- Level -->
                            <div class="form-group col-md-6 mb-md-0">
                                <label for="course-level" class="ca-field-label"><?php print_string('difficulty_level', 'local_courseagent'); ?></label>
                                <select id="course-level" class="custom-select">
                                    <option value="beginner"><?php print_string('level_beginner', 'local_courseagent'); ?></option>
                                    <option value="intermediate" selected><?php print_string('level_intermediate', 'local_courseagent'); ?></option>
                                    <option value="advanced"><?php print_string('level_advanced', 'local_courseagent'); ?></option>
                                </select>
                            </div>
                            <!-- Number of Sections -->
                            <div class="form-group col-md-6 mb-0">
                                <label for="num-sections" class="ca-field-label"><?php print_string('num_sections', 'local_courseagent'); ?></label>
                                <input type="number" id="num-sections" class="form-control"
                                       min="2" max="<?php echo $maxsections; ?>" value="4">
                                <small class="form-text text-muted"><?php print_string('sections_range', 'local_courseagent', $maxsections); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Content Generation Rules -->
                <div class="card ca-card mb-4">
                    <div class="card-body">
                        <h2 class="ca-card-title">
                            <i class="fa fa-puzzle-piece ca-card-title-icon" aria-hidden="true"></i>
                            <?php print_string('content_generation_rules', 'local_courseagent'); ?>
                        </h2>
                        <p class="ca-card-subtitle"><?php print_string('content_generation_rules_desc', 'local_courseagent'); ?></p>

                        <div class="ca-rules">

                            <!-- Quizzes rule -->
                            <div class="ca-rule">
                                <div class="ca-rule-head">
                                    <div class="ca-rule-info">
                                        <span class="ca-rule-icon"><i class="fa fa-question-circle" aria-hidden="true"></i></span>
                                        <div>
                                            <div class="ca-rule-title"><?php print_string('include_quizzes', 'local_courseagent'); ?></div>
                                            <div class="ca-rule-desc"><?php print_string('include_quizzes_desc', 'local_courseagent'); ?></div>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-quiz">
                                        <label class="custom-control-label" for="include-quiz"></label>
                                    </div>
                                </div>
                                <div id="quiz-minmax" class="ca-rule-body">
                                    <div class="ca-subopts">
                                        <div class="ca-subopt ca-subopt--off" id="quiz-per-section-subopt">
                                            <div class="ca-subopt-head">
                                                <div class="ca-subopt-info">
                                                    <div class="ca-subopt-title"><?php print_string('per_section_label', 'local_courseagent'); ?></div>
                                                    <div class="ca-subopt-desc"><?php print_string('per_section_desc', 'local_courseagent'); ?></div>
                                                </div>
                                                <div class="custom-control custom-switch ca-subopt-switch">
                                                    <input type="checkbox" class="custom-control-input ca-subopt-toggle" id="quiz-per-section-enabled" data-activity="quiz">
                                                    <label class="custom-control-label" for="quiz-per-section-enabled"></label>
                                                </div>
                                            </div>
                                            <div class="ca-subopt-body">
                                                <div class="ca-range">
                                                    <span class="ca-range-val ca-range-val--min">1</span>
                                                    <div class="ca-range-track">
                                                        <div class="ca-range-fill"></div>
                                                        <input type="range" class="ca-range-input ca-range-input--min" id="quiz-min-per-section" min="1" max="10" value="1">
                                                        <input type="range" class="ca-range-input ca-range-input--max" id="quiz-max-per-section" min="1" max="10" value="3">
                                                    </div>
                                                    <span class="ca-range-val ca-range-val--max">3</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ca-subopt ca-subopt--off" id="quiz-total-subopt">
                                            <div class="ca-subopt-head">
                                                <div class="ca-subopt-info">
                                                    <div class="ca-subopt-title"><?php print_string('total_course_label', 'local_courseagent'); ?></div>
                                                    <div class="ca-subopt-desc"><?php print_string('total_course_desc', 'local_courseagent'); ?></div>
                                                </div>
                                                <div class="custom-control custom-switch ca-subopt-switch">
                                                    <input type="checkbox" class="custom-control-input ca-subopt-toggle" id="quiz-total-enabled" data-activity="quiz">
                                                    <label class="custom-control-label" for="quiz-total-enabled"></label>
                                                </div>
                                            </div>
                                            <div class="ca-subopt-body">
                                                <div class="ca-range">
                                                    <span class="ca-range-val ca-range-val--min">1</span>
                                                    <div class="ca-range-track">
                                                        <div class="ca-range-fill"></div>
                                                        <input type="range" class="ca-range-input ca-range-input--min" id="quiz-min-total" min="1" max="20" value="1">
                                                        <input type="range" class="ca-range-input ca-range-input--max" id="quiz-max-total" min="1" max="20" value="6">
                                                    </div>
                                                    <span class="ca-range-val ca-range-val--max">6</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($enableassignments) : ?>
                            <!-- Assignments rule -->
                            <div class="ca-rule">
                                <div class="ca-rule-head">
                                    <div class="ca-rule-info">
                                        <span class="ca-rule-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></span>
                                        <div>
                                            <div class="ca-rule-title"><?php print_string('include_assignments_label', 'local_courseagent'); ?></div>
                                            <div class="ca-rule-desc"><?php print_string('include_assignments_desc_ui', 'local_courseagent'); ?></div>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-assignment">
                                        <label class="custom-control-label" for="include-assignment"></label>
                                    </div>
                                </div>
                                <div id="assignment-minmax" class="ca-rule-body">
                                    <div class="ca-subopts">
                                        <div class="ca-subopt ca-subopt--off" id="assignment-per-section-subopt">
                                            <div class="ca-subopt-head">
                                                <div class="ca-subopt-info">
                                                    <div class="ca-subopt-title"><?php print_string('per_section_label', 'local_courseagent'); ?></div>
                                                    <div class="ca-subopt-desc"><?php print_string('per_section_desc', 'local_courseagent'); ?></div>
                                                </div>
                                                <div class="custom-control custom-switch ca-subopt-switch">
                                                    <input type="checkbox" class="custom-control-input ca-subopt-toggle" id="assignment-per-section-enabled" data-activity="assignment">
                                                    <label class="custom-control-label" for="assignment-per-section-enabled"></label>
                                                </div>
                                            </div>
                                            <div class="ca-subopt-body">
                                                <div class="ca-range">
                                                    <span class="ca-range-val ca-range-val--min">1</span>
                                                    <div class="ca-range-track">
                                                        <div class="ca-range-fill"></div>
                                                        <input type="range" class="ca-range-input ca-range-input--min" id="assignment-min-per-section" min="1" max="10" value="1">
                                                        <input type="range" class="ca-range-input ca-range-input--max" id="assignment-max-per-section" min="1" max="10" value="2">
                                                    </div>
                                                    <span class="ca-range-val ca-range-val--max">2</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ca-subopt ca-subopt--off" id="assignment-total-subopt">
                                            <div class="ca-subopt-head">
                                                <div class="ca-subopt-info">
                                                    <div class="ca-subopt-title"><?php print_string('total_course_label', 'local_courseagent'); ?></div>
                                                    <div class="ca-subopt-desc"><?php print_string('total_course_desc', 'local_courseagent'); ?></div>
                                                </div>
                                                <div class="custom-control custom-switch ca-subopt-switch">
                                                    <input type="checkbox" class="custom-control-input ca-subopt-toggle" id="assignment-total-enabled" data-activity="assignment">
                                                    <label class="custom-control-label" for="assignment-total-enabled"></label>
                                                </div>
                                            </div>
                                            <div class="ca-subopt-body">
                                                <div class="ca-range">
                                                    <span class="ca-range-val ca-range-val--min">1</span>
                                                    <div class="ca-range-track">
                                                        <div class="ca-range-fill"></div>
                                                        <input type="range" class="ca-range-input ca-range-input--min" id="assignment-min-total" min="1" max="20" value="1">
                                                        <input type="range" class="ca-range-input ca-range-input--max" id="assignment-max-total" min="1" max="20" value="4">
                                                    </div>
                                                    <span class="ca-range-val ca-range-val--max">4</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Visuals: Emojis + SVG -->
                            <div class="ca-visuals">
                                <div class="ca-rule ca-rule--simple">
                                    <div class="ca-rule-info">
                                        <span class="ca-rule-icon"><i class="fa fa-smile-o" aria-hidden="true"></i></span>
                                        <div>
                                            <div class="ca-rule-title"><?php print_string('use_emojis_label', 'local_courseagent'); ?></div>
                                            <div class="ca-rule-desc"><?php print_string('use_emojis_desc_ui', 'local_courseagent'); ?></div>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="use-emojis">
                                        <label class="custom-control-label" for="use-emojis"></label>
                                    </div>
                                </div>
                                <div class="ca-rule ca-rule--simple">
                                    <div class="ca-rule-info">
                                        <span class="ca-rule-icon"><i class="fa fa-sitemap" aria-hidden="true"></i></span>
                                        <div>
                                            <div class="ca-rule-title"><?php print_string('include_diagrams', 'local_courseagent'); ?></div>
                                            <div class="ca-rule-desc"><?php print_string('include_diagrams_desc_ui', 'local_courseagent'); ?></div>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="use-diagrams">
                                        <label class="custom-control-label" for="use-diagrams"></label>
                                    </div>
                                </div>
                            </div>

                            <?php if ($hassaaskey) : ?>
                            <!-- Generate H5P Activities (CourseAgent) -->
                            <div id="h5p-toggle-container" class="ca-rule ca-rule--accent">
                                <div class="ca-rule-head">
                                    <div class="ca-rule-info">
                                        <span class="ca-rule-icon ca-rule-icon--accent"><i class="fa fa-cubes" aria-hidden="true"></i></span>
                                        <div>
                                            <div class="ca-rule-title ca-rule-title--accent"><?php print_string('include_h5p', 'local_courseagent'); ?></div>
                                            <div class="ca-rule-desc"><?php print_string('include_h5p_desc', 'local_courseagent'); ?></div>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-h5p">
                                        <label class="custom-control-label" for="include-h5p"></label>
                                    </div>
                                </div>
                                <!-- H5P type selector + min/max injected here by JS -->
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- Card 3: AI Engine & Actions -->
                <div class="card ca-card mb-4">
                    <div class="card-body">
                        <div class="ca-engine">
                            <div class="row ca-engine-fields">
                                <!-- AI Provider -->
                                <div class="form-group col-md-6 mb-md-0">
                                    <label for="ai-provider" class="ca-field-label"><?php print_string('ai_provider', 'local_courseagent'); ?></label>
                                    <select id="ai-provider" class="custom-select">
                                        <?php foreach ($providers as $p) : ?>
                                            <option value="<?php echo $p->id; ?>"
                                                <?php echo $p->isdefault ? 'selected' : ''; ?>>
                                                <?php echo format_string($p->name);
                                                      echo $p->isdefault ? ' (' . get_string('provider_default', 'local_courseagent') . ')' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- AI Model -->
                                <div class="form-group col-md-6 mb-0">
                                    <label for="ai-model" class="ca-field-label"><?php print_string('model_selection', 'local_courseagent'); ?></label>
                                    <select id="ai-model" class="custom-select">
                                        <option value=""><?php print_string('provider_autoselect', 'local_courseagent'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="ca-engine-action">
                                <button type="button" id="btn-generate" class="btn btn-primary btn-lg">
                                    <i class="fa fa-magic fa-fw" aria-hidden="true"></i>
                                    <?php print_string('generate_course_btn', 'local_courseagent'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </form>
        </div>

        <!-- RIGHT PANEL: How it works + Pro tip -->
        <div class="col-lg-4">
            <div class="card ca-card ca-sidebar-card mb-4">
                <div class="card-body">
                    <h3 class="ca-sidebar-title"><?php print_string('how_it_works', 'local_courseagent'); ?></h3>
                    <p class="text-muted small mb-4"><?php print_string('how_it_works_desc', 'local_courseagent'); ?></p>
                    <div class="ca-timeline">
                        <div class="ca-timeline-step">
                            <div class="ca-timeline-dot">1</div>
                            <h4 class="ca-timeline-step-title"><?php print_string('structuring', 'local_courseagent'); ?></h4>
                            <p class="ca-timeline-step-desc"><?php print_string('structuring_desc', 'local_courseagent'); ?></p>
                        </div>
                        <div class="ca-timeline-step">
                            <div class="ca-timeline-dot">2</div>
                            <h4 class="ca-timeline-step-title"><?php print_string('content_generation', 'local_courseagent'); ?></h4>
                            <p class="ca-timeline-step-desc"><?php print_string('content_generation_desc', 'local_courseagent'); ?></p>
                        </div>
                        <div class="ca-timeline-step">
                            <div class="ca-timeline-dot">3</div>
                            <h4 class="ca-timeline-step-title"><?php print_string('review_refine', 'local_courseagent'); ?></h4>
                            <p class="ca-timeline-step-desc"><?php print_string('review_refine_desc', 'local_courseagent'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ca-protip">
                <i class="fa fa-lightbulb-o ca-protip-icon" aria-hidden="true"></i>
                <div>
                    <h4 class="ca-protip-title"><?php print_string('pro_tip', 'local_courseagent'); ?></h4>
                    <p class="ca-protip-text"><?php print_string('pro_tip_desc', 'local_courseagent'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading modal overlay -->
<div id="ca-loading-modal" class="ca-loading-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="ca-loading-modal-content">

        <!-- In-progress state -->
        <div id="ca-loading-running">
            <!-- Spinner -->
            <div class="ca-loading-ring mb-4">
                <div class="ca-loading-ring-track"></div>
                <div class="ca-loading-ring-fill"></div>
                <i class="fa fa-magic ca-loading-ring-icon" aria-hidden="true"></i>
            </div>

            <!-- Header -->
            <h4 id="ca-loading-title" class="mb-2"><?php print_string('generating_course', 'local_courseagent'); ?></h4>
            <p id="ca-loading-desc" class="text-muted mb-4"><?php print_string('generating_course_desc', 'local_courseagent'); ?></p>

            <!-- Indeterminate progress bar: a single AI call has no real sub-progress to report. -->
            <div class="progress mb-4" style="height:6px;">
                <div id="ca-loading-progress" class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
            </div>

            <!-- Cancel button -->
            <div class="text-center mt-4">
                <button type="button" id="btn-cancel-generate" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-times fa-fw" aria-hidden="true"></i> <?php print_string('cancel', 'local_courseagent'); ?>
                </button>
            </div>
        </div>

        <!-- Error state — shown in-place when generation fails so user doesn't lose context -->
        <div id="ca-loading-error" style="display:none;" class="text-center py-2">
            <div class="mb-3">
                <i class="fa fa-exclamation-circle text-danger" style="font-size:2.5rem;" aria-hidden="true"></i>
            </div>
            <p id="ca-loading-error-msg" class="mb-4" style="max-width:400px;margin:0 auto 1.5rem;"></p>
            <button type="button" id="btn-loading-dismiss" class="btn btn-outline-secondary btn-sm">Dismiss</button>
        </div>

    </div>
</div>

<!-- Plan approval modal (paid users only â€” populated by JS after plan API call) -->
<div id="ca-plan-modal" class="ca-loading-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="ca-plan-modal-title">
    <div class="ca-loading-modal-content" style="max-width:660px;max-height:82vh;overflow-y:auto;">
        <div class="mb-3">
            <span class="badge badge-success px-3 py-2 mb-2" style="font-size:0.75em;letter-spacing:.04em;">
                <i class="fa fa-magic fa-fw" aria-hidden="true"></i> AI Curriculum Plan
            </span>
            <h4 id="ca-plan-modal-title" class="mb-1 font-weight-bold"></h4>
            <p id="ca-plan-summary" class="text-muted small mb-0"></p>
        </div>

        <hr class="my-3">

        <p class="small text-muted mb-3"><?php print_string('course_plan_subtitle', 'local_courseagent'); ?></p>

        <div id="ca-plan-sections" class="list-group list-group-flush mb-4"></div>

        <div class="d-flex justify-content-between align-items-center mt-2">
            <button type="button" id="btn-plan-edit" class="btn btn-outline-secondary">
                <i class="fa fa-pencil fa-fw" aria-hidden="true"></i>
                <?php print_string('edit_topic_btn', 'local_courseagent'); ?>
            </button>
            <button type="button" id="btn-plan-approve" class="btn btn-success btn-lg">
                <i class="fa fa-check fa-fw" aria-hidden="true"></i>
                <?php print_string('approve_generate_btn', 'local_courseagent'); ?>
            </button>
        </div>
    </div>
</div>

<?php echo $OUTPUT->footer();
