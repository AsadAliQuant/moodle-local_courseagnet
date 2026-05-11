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
 * Course preview page for local_courseagent.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Commenting.MissingDocblock.File

require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();
require_login();
require_capability('local/courseagent:createcourse', $context);

$pageurl = new moodle_url('/local/courseagent/preview.php');
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('preview_course', 'local_courseagent'));
$PAGE->set_heading(get_string('preview_course', 'local_courseagent'));
$PAGE->set_pagelayout('base');

$previewdata = isset($SESSION->courseagent_preview) ? $SESSION->courseagent_preview : null;

$jsconfig = [
    'wwwroot' => $CFG->wwwroot,
    'sesskey' => sesskey(),
    'strings' => [
        'sectionLabel'          => get_string('js:section_label', 'local_courseagent'),
        'lessonLabel'           => get_string('js:lesson_label', 'local_courseagent'),
        'quizCount'             => get_string('js:quiz_count', 'local_courseagent'),
        'assignmentLabel'       => get_string('js:assignment_label', 'local_courseagent'),
        'contentTab'            => get_string('js:content_tab', 'local_courseagent'),
        'quizQuestionsTab'      => get_string('js:quiz_questions_tab', 'local_courseagent'),
        'assignmentDetailsTab'  => get_string('js:assignment_details_tab', 'local_courseagent'),
        'aiGenerated'           => get_string('js:ai_generated', 'local_courseagent'),
        'noLessonContent'       => get_string('js:no_lesson_content', 'local_courseagent'),
        'noContentAvailable'    => get_string('js:no_content_available', 'local_courseagent'),
        'noQuiz'                => get_string('js:no_quiz', 'local_courseagent'),
        'noAssignment'          => get_string('js:no_assignment', 'local_courseagent'),
        'correct'               => get_string('js:correct', 'local_courseagent'),
        'instructions'          => get_string('js:instructions', 'local_courseagent'),
        'expectedLength'        => get_string('js:expected_length', 'local_courseagent'),
        'words'                 => get_string('js:words', 'local_courseagent'),
        'aiInitialMsg'          => get_string('js:ai_initial_msg', 'local_courseagent'),
        'quickaction1'          => get_string('js:quickaction_1', 'local_courseagent'),
        'quickaction2'          => get_string('js:quickaction_2', 'local_courseagent'),
        'quickaction3'          => get_string('js:quickaction_3', 'local_courseagent'),
        'aiChatPlaceholder'     => get_string('js:ai_chat_placeholder', 'local_courseagent'),
        'noCourseData'          => get_string('js:no_course_data', 'local_courseagent'),
        'publishing'            => get_string('js:publishing', 'local_courseagent'),
        'publishToMoodle'       => get_string('publish_to_moodle', 'local_courseagent'),
        'coursePublished'       => get_string('js:course_published', 'local_courseagent'),
        'failedPublish'         => get_string('js:failed_publish', 'local_courseagent'),
        'errorPublishing'       => get_string('js:error_publishing', 'local_courseagent'),
        'untitledCourse'        => get_string('untitled_course', 'local_courseagent'),
    ],
];
$PAGE->requires->css(new moodle_url('/local/courseagent/styles.css'));

// Use inline script tag for large config data (Moodle best practice).
// js_call_amd has 1024 char limit on argument string.
$jsconfig = [
    'wwwroot' => $CFG->wwwroot,
    'sesskey' => sesskey(),
    'strings' => [
        'sectionLabel'          => get_string('js:section_label', 'local_courseagent'),
        'lessonLabel'           => get_string('js:lesson_label', 'local_courseagent'),
        'quizCount'             => get_string('js:quiz_count', 'local_courseagent'),
        'assignmentLabel'       => get_string('js:assignment_label', 'local_courseagent'),
        'contentTab'            => get_string('js:content_tab', 'local_courseagent'),
        'quizQuestionsTab'      => get_string('js:quiz_questions_tab', 'local_courseagent'),
        'assignmentDetailsTab'  => get_string('js:assignment_details_tab', 'local_courseagent'),
        'aiGenerated'           => get_string('js:ai_generated', 'local_courseagent'),
        'noLessonContent'       => get_string('js:no_lesson_content', 'local_courseagent'),
        'noContentAvailable'    => get_string('js:no_content_available', 'local_courseagent'),
        'noQuiz'                => get_string('js:no_quiz', 'local_courseagent'),
        'noAssignment'          => get_string('js:no_assignment', 'local_courseagent'),
        'correct'               => get_string('js:correct', 'local_courseagent'),
        'instructions'          => get_string('js:instructions', 'local_courseagent'),
        'expectedLength'        => get_string('js:expected_length', 'local_courseagent'),
        'words'                 => get_string('js:words', 'local_courseagent'),
        'aiInitialMsg'          => get_string('js:ai_initial_msg', 'local_courseagent'),
        'quickaction1'          => get_string('js:quickaction_1', 'local_courseagent'),
        'quickaction2'          => get_string('js:quickaction_2', 'local_courseagent'),
        'quickaction3'          => get_string('js:quickaction_3', 'local_courseagent'),
        'aiChatPlaceholder'     => get_string('js:ai_chat_placeholder', 'local_courseagent'),
        'noCourseData'          => get_string('js:no_course_data', 'local_courseagent'),
        'publishing'            => get_string('js:publishing', 'local_courseagent'),
        'publishToMoodle'       => get_string('publish_to_moodle', 'local_courseagent'),
        'coursePublished'       => get_string('js:course_published', 'local_courseagent'),
        'failedPublish'         => get_string('js:failed_publish', 'local_courseagent'),
        'errorPublishing'       => get_string('js:error_publishing', 'local_courseagent'),
        'untitledCourse'        => get_string('untitled_course', 'local_courseagent'),
    ],
];
echo '<script type="application/json" id="ca-config-data">' . json_encode($jsconfig) . '</script>';
$PAGE->requires->js_call_amd('local_courseagent/preview', 'init', []);

echo $OUTPUT->header();
?>

<div id="courseagent-preview-app">

    <?php if (empty($previewdata)) : ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle mr-2" aria-hidden="true"></i>
            <?php echo get_string('no_preview_data', 'local_courseagent'); ?>
            <a href="<?php echo new moodle_url('/local/courseagent/index.php'); ?>"><?php echo get_string('create_course', 'local_courseagent'); ?></a>.
        </div>
    <?php endif; ?>

    <?php if (!empty($previewdata)) : ?>
        <script type="application/json" id="ca-preview-data"><?php echo json_encode($previewdata); ?></script>

        <!-- Top Bar -->
        <div class="ca-preview-topbar">
            <div class="ca-preview-title-area">
                <h4 id="ca-course-title" class="mb-0"></h4>
                <span class="badge badge-warning ml-2"><?php echo get_string('draft_mode', 'local_courseagent'); ?></span>
            </div>
            <div class="ca-preview-actions">
                <a href="<?php echo new moodle_url('/local/courseagent/index.php'); ?>" class="btn btn-outline-secondary btn-sm mr-2">
                    <i class="fa fa-arrow-left fa-fw" aria-hidden="true"></i>
                    <?php echo get_string('back_to_create', 'local_courseagent'); ?>
                </a>
                <button id="btn-publish" class="btn btn-success btn-sm">
                    <i class="fa fa-upload fa-fw" aria-hidden="true"></i>
                    <?php echo get_string('publish_to_moodle', 'local_courseagent'); ?>
                </button>
            </div>
        </div>

        <!-- 3-Pane Layout -->
        <div class="ca-preview-panes">
            <!-- Left Sidebar: Course Tree -->
            <aside class="ca-sidebar" id="ca-sidebar">
                <div class="ca-sidebar-header">
                    <h5 class="mb-1"><?php echo get_string('course_builder', 'local_courseagent'); ?></h5>
                    <p class="ca-sidebar-status">
                        <span class="ca-status-dot"></span> <?php echo get_string('ai_assistant_active', 'local_courseagent'); ?>
                    </p>
                </div>
                <div class="ca-sidebar-tree" id="ca-sidebar-tree"></div>
            </aside>

            <!-- Center: Main Content -->
            <main class="ca-main" id="ca-main"></main>

            <!-- Right Sidebar: AI Chat -->
            <aside class="ca-chat" id="ca-chat">
                <div class="ca-chat-header">
                    <div class="ca-chat-header-left">
                        <i class="fa fa-robot ca-chat-icon" aria-hidden="true"></i>
                        <h5 class="mb-0"><?php echo get_string('agent_assistant', 'local_courseagent'); ?></h5>
                    </div>
                    <button class="ca-chat-clear-btn" id="ca-chat-clear" title="Clear chat history">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="ca-chat-messages" id="ca-chat-messages"></div>
                <div class="ca-chat-quickactions" id="ca-chat-quickactions"></div>
                <div class="ca-chat-input-area">
                    <div class="ca-chat-input-wrapper">
                        <textarea
                            id="ca-chat-input"
                            class="ca-chat-input"
                            rows="2"
                            placeholder="<?php echo get_string('chat_placeholder', 'local_courseagent'); ?>"></textarea>
                        <button id="ca-chat-send" class="ca-chat-send" title="<?php print_string('send', 'local_courseagent'); ?>">
                            <span class="ca-icon-send" aria-hidden="true"><img src="<?php echo $OUTPUT->image_url('arrow-up', 'local_courseagent'); ?>" alt=""></span>
                            <span class="ca-icon-stop" aria-hidden="true" style="display:none"><img src="<?php echo $OUTPUT->image_url('square-fill', 'local_courseagent'); ?>" alt=""></span>
                        </button>
                    </div>
                </div>
                <p class="ca-chat-disclaimer"><?php echo get_string('chat_disclaimer', 'local_courseagent'); ?></p>
            </aside>
        </div>

        <div id="ca-provider-info" style="display:none;"></div>
    <?php endif; ?>
</div>

<?php echo $OUTPUT->footer();

