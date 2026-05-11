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

// Get plugin configuration.
$maxsections      = get_config('local_courseagent', 'max_sections') ?: 8;
$maxquiz          = get_config('local_courseagent', 'max_quiz_questions') ?: 7;
$enableassignments = get_config('local_courseagent', 'enable_assignments') ?: 1;

// Get available providers.
$providers       = provider::get_all(true);
$defaultprovider = provider::get_default();

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

// Pass only small config to JavaScript — strings/wwwroot/sesskey loaded in JS via core/str and core/config.
$jsconfig = [
    'maxSections'       => (int)  $maxsections,
    'maxQuizQuestions'  => (int)  $maxquiz,
    'enableAssignments' => (bool) $enableassignments,
    'providers'         => $providerconfig,
    'defaultProviderId' => $defaultprovider ? $defaultprovider->id : 0,
];
$PAGE->requires->css(new moodle_url('/local/courseagent/styles.css'));
$PAGE->requires->js_call_amd('local_courseagent/coursecreator', 'init', [$jsconfig]);

echo $OUTPUT->header();
?>

<div id="courseagent-app">
    <div class="row">
        <div class="col-lg-8">
            <p class="text-muted mb-3"><?php print_string('configure_settings', 'local_courseagent'); ?></p>
            <div class="card mb-4">
                <div class="card-body">
                    <form id="courseagent-form">
                        <!-- Course Title -->
                        <div class="form-group">
                            <label for="course-custom-title" class="font-weight-bold">
                                <?php print_string('course_title', 'local_courseagent'); ?>
                                <span class="text-muted font-weight-normal small ml-1"><?php print_string('optional_override', 'local_courseagent'); ?></span>
                            </label>
                            <input type="text" id="course-custom-title" class="form-control"
                                   placeholder="<?php print_string('course_title_placeholder', 'local_courseagent'); ?>">
                        </div>

                        <!-- Course Topic -->
                        <div class="form-group">
                            <label for="course-topic" class="font-weight-bold">
                                <?php print_string('coursetopic', 'local_courseagent'); ?> <span class="text-danger">*</span>
                            </label>
                            <textarea id="course-topic" class="form-control" rows="4" maxlength="500"
                                      placeholder="<?php print_string('coursetopic_placeholder', 'local_courseagent'); ?>"></textarea>
                            <small class="form-text text-muted d-flex justify-content-between">
                                <span><?php print_string('coursetopic_help', 'local_courseagent'); ?></span>
                                <span id="course-topic-counter" class="text-muted">0 / 500</span>
                            </small>
                        </div>

                        <!-- Upload content — PRO lock -->
                        <div class="form-group mt-3">
                            <label class="font-weight-bold d-flex align-items-center">
                                <?php print_string('upload_content', 'local_courseagent'); ?>
                                <span class="badge badge-warning ml-2" style="font-size:0.7em;">
                                    <i class="fa fa-lock" aria-hidden="true"></i>&nbsp;<?php print_string('pro_badge', 'local_courseagent'); ?>
                                </span>
                            </label>
                            <p class="text-muted small mb-2">
                                <?php print_string('upload_content_desc', 'local_courseagent'); ?>
                            </p>
                            <div class="courseagent-pro-wrapper">
                                <div class="courseagent-dropzone courseagent-dropzone--locked" aria-hidden="true">
                                    <i class="fa fa-cloud-upload fa-2x text-muted" aria-hidden="true"></i>
                                    <p class="mb-1 mt-2"><strong><?php print_string('click_to_upload', 'local_courseagent'); ?></strong> <?php print_string('or_drag_drop', 'local_courseagent'); ?></p>
                                    <p class="small text-muted mb-0">
                                        <?php print_string('accepted_file_types', 'local_courseagent'); ?>
                                    </p>
                                </div>
                                <div class="courseagent-pro-overlay">
                                    <div class="text-center px-4">
                                        <i class="fa fa-lock fa-2x text-warning mb-2" aria-hidden="true"></i>
                                        <p class="font-weight-bold mb-1"><?php print_string('pro_feature', 'local_courseagent'); ?></p>
                                        <p class="small text-muted mb-0">
                                            <?php print_string('pro_feature_desc', 'local_courseagent'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="upload-file-input" class="d-none" disabled
                                   accept=".txt,.pdf,.docx,.pptx,.odt,.rtf,.md,.csv,.epub">
                            <input type="hidden" id="upload-extracted-text" name="extracted_content">
                        </div>

                        <div class="row">
                            <!-- Level -->
                            <div class="form-group col-md-6">
                                <label for="course-level" class="font-weight-bold"><?php print_string('difficulty_level', 'local_courseagent'); ?></label>
                                <select id="course-level" class="custom-select">
                                    <option value="beginner"><?php print_string('level_beginner', 'local_courseagent'); ?></option>
                                    <option value="intermediate" selected><?php print_string('level_intermediate', 'local_courseagent'); ?></option>
                                    <option value="advanced"><?php print_string('level_advanced', 'local_courseagent'); ?></option>
                                </select>
                            </div>
                            <!-- Number of Sections -->
                            <div class="form-group col-md-6">
                                <label for="num-sections" class="font-weight-bold"><?php print_string('num_sections', 'local_courseagent'); ?></label>
                                <input type="number" id="num-sections" class="form-control"
                                       min="2" max="<?php echo $maxsections; ?>" value="4">
                                <small class="form-text text-muted"><?php print_string('sections_range', 'local_courseagent', $maxsections); ?></small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Included Components -->
                        <div class="form-group">
                            <label class="font-weight-bold mb-3"><?php print_string('included_components', 'local_courseagent'); ?></label>

                            <!-- Include Quizzes -->
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <span class="badge badge-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                              style="width:2.5rem;height:2.5rem;">
                                            <i class="fa fa-question-circle"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php print_string('include_quizzes', 'local_courseagent'); ?></div>
                                        <div class="small text-muted"><?php print_string('include_quizzes_desc', 'local_courseagent'); ?></div>
                                    </div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="include-quiz" checked>
                                    <label class="custom-control-label" for="include-quiz"></label>
                                </div>
                            </div>

                            <?php if ($enableassignments) : ?>
                            <!-- Include Assignments -->
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <span class="badge badge-secondary rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                              style="width:2.5rem;height:2.5rem;">
                                            <i class="fa fa-pencil-square-o"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php print_string('include_assignments_label', 'local_courseagent'); ?></div>
                                        <div class="small text-muted"><?php print_string('include_assignments_desc_ui', 'local_courseagent'); ?></div>
                                    </div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="include-assignment" checked>
                                    <label class="custom-control-label" for="include-assignment"></label>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Use Emojis -->
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <span class="badge badge-info rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                              style="width:2.5rem;height:2.5rem;">
                                            <i class="fa fa-smile-o"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php print_string('use_emojis_label', 'local_courseagent'); ?></div>
                                        <div class="small text-muted"><?php print_string('use_emojis_desc_ui', 'local_courseagent'); ?></div>
                                    </div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="use-emojis">
                                    <label class="custom-control-label" for="use-emojis"></label>
                                </div>
                            </div>

                            <!-- Include SVG -->
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <span class="badge badge-info rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                              style="width:2.5rem;height:2.5rem;">
                                            <i class="fa fa-picture-o"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php print_string('include_svg_diagrams', 'local_courseagent'); ?></div>
                                        <div class="small text-muted"><?php print_string('include_svg_desc_ui', 'local_courseagent'); ?></div>
                                    </div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="use-svg">
                                    <label class="custom-control-label" for="use-svg"></label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <!-- AI Provider -->
                            <div class="form-group col-md-6">
                                <label for="ai-provider" class="font-weight-bold"><?php print_string('ai_provider', 'local_courseagent'); ?></label>
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
                            <div class="form-group col-md-6">
                                <label for="ai-model" class="font-weight-bold"><?php print_string('model_selection', 'local_courseagent'); ?></label>
                                <select id="ai-model" class="custom-select">
                                    <option value=""><?php print_string('provider_autoselect', 'local_courseagent'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" id="btn-generate" class="btn btn-primary btn-lg">
                                <i class="fa fa-magic fa-fw" aria-hidden="true"></i>
                                <?php print_string('generate_course_btn', 'local_courseagent'); ?>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: How it works -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-4 d-flex align-items-center">
                        <i class="fa fa-info-circle text-primary mr-2"></i>
                        <?php print_string('how_it_works', 'local_courseagent'); ?>
                    </h4>
                    <p class="text-muted small mb-4">
                        <?php print_string('how_it_works_desc', 'local_courseagent'); ?>
                    </p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex align-items-start">
                            <span class="badge badge-light rounded-circle p-2 mr-3 border d-inline-flex align-items-center justify-content-center"
                                  style="width:2.5rem;height:2.5rem;">
                                <i class="fa fa-list-ol text-primary"></i>
                            </span>
                            <div>
                                <h6 class="mb-1"><?php print_string('structuring', 'local_courseagent'); ?></h6>
                                <p class="small text-muted mb-0"><?php print_string('structuring_desc', 'local_courseagent'); ?></p>
                            </div>
                        </div>
                        <div class="list-group-item px-0 d-flex align-items-start">
                            <span class="badge badge-light rounded-circle p-2 mr-3 border d-inline-flex align-items-center justify-content-center"
                                  style="width:2.5rem;height:2.5rem;">
                                <i class="fa fa-file-text-o text-primary"></i>
                            </span>
                            <div>
                                <h6 class="mb-1"><?php print_string('content_generation', 'local_courseagent'); ?></h6>
                                <p class="small text-muted mb-0"><?php print_string('content_generation_desc', 'local_courseagent'); ?></p>
                            </div>
                        </div>
                        <div class="list-group-item px-0 d-flex align-items-start">
                            <span class="badge badge-light rounded-circle p-2 mr-3 border d-inline-flex align-items-center justify-content-center"
                                  style="width:2.5rem;height:2.5rem;">
                                <i class="fa fa-check-circle text-primary"></i>
                            </span>
                            <div>
                                <h6 class="mb-1"><?php print_string('review_refine', 'local_courseagent'); ?></h6>
                                <p class="small text-muted mb-0"><?php print_string('review_refine_desc', 'local_courseagent'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-lightbulb-o text-warning mr-2 mt-1"></i>
                            <p class="small text-muted mb-0">
                                <strong><?php print_string('pro_tip', 'local_courseagent'); ?></strong> <?php print_string('pro_tip_desc', 'local_courseagent'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading modal overlay -->
<div id="ca-loading-modal" class="ca-loading-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="ca-loading-modal-content">
        <!-- Spinner -->
        <div class="ca-loading-ring mb-4">
            <div class="ca-loading-ring-track"></div>
            <div class="ca-loading-ring-fill"></div>
            <i class="fa fa-magic ca-loading-ring-icon" aria-hidden="true"></i>
        </div>

        <!-- Header -->
        <h4 class="mb-2"><?php print_string('generating_course', 'local_courseagent'); ?></h4>
        <p class="text-muted mb-4"><?php print_string('generating_course_desc', 'local_courseagent'); ?></p>

        <!-- Progress bar -->
        <div class="progress mb-1" style="height:6px;">
            <div id="ca-loading-progress" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
        </div>
        <div class="text-right mb-4">
            <small class="text-muted" id="ca-loading-percent">0%</small>
        </div>

        <!-- Steps -->
        <div class="ca-steps-list">
            <div id="ca-step-outline" class="ca-step ca-step-active">
                <div class="ca-step-bubble">
                    <i class="fa fa-hourglass-half" aria-hidden="true"></i>
                </div>
                <span class="ca-step-label"><?php print_string('step_outline', 'local_courseagent'); ?></span>
            </div>
            <div id="ca-step-lessons" class="ca-step ca-step-pending">
                <div class="ca-step-bubble">2</div>
                <span class="ca-step-label"><?php print_string('step_lessons', 'local_courseagent'); ?></span>
            </div>
            <div id="ca-step-extras" class="ca-step ca-step-pending">
                <div class="ca-step-bubble">3</div>
                <span class="ca-step-label"><?php print_string('step_extras', 'local_courseagent'); ?></span>
            </div>
        </div>

        <!-- Cancel button -->
        <div class="text-center mt-4">
            <button type="button" id="btn-cancel-generate" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-times fa-fw" aria-hidden="true"></i> <?php print_string('cancel', 'local_courseagent'); ?>
            </button>
        </div>
    </div>
</div>

<?php echo $OUTPUT->footer();
