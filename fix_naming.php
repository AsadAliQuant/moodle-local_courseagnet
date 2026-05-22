<?php

/**
 * Auto-fix snake_case method/class names to camelCase/PascalCase
 * Converts all snake_case identifiers in PHP files to match PSR-12
 * Usage: php fix_naming.php
 */

$conversions = [
    // Class name conversions
    'class api ' => 'class Api ',
    'class provider ' => 'class Provider ',
    'class provider_form ' => 'class ProviderForm ',
    'class navigation ' => 'class Navigation ',
    'class output ' => 'class Output ',
    'class extractor ' => 'class Extractor ',

    // Method name conversions (partial list - will expand)
    'function generate_course_outline' => 'function generateCourseOutline',
    'function plan_course_outline' => 'function planCourseOutline',
    'function build_plan_prompt' => 'function buildPlanPrompt',
    'function build_fallback_providers' => 'function buildFallbackProviders',
    'function is_rate_limit_error' => 'function isRateLimitError',
    'function build_generation_prompt' => 'function buildGenerationPrompt',
    'function publish_course' => 'function publishCourse',
    'function create_cm_stub' => 'function createCmStub',
    'function place_cm_in_section' => 'function placeCmInSection',
    'function create_lesson_page' => 'function createLessonPage',
    'function create_quiz' => 'function createQuiz',
    'function get_or_create_question_category' => 'function getOrCreateQuestionCategory',
    'function create_multichoice_question' => 'function createMultichoiceQuestion',
    'function create_assignment' => 'function createAssignment',
    'function create_h5p_activities' => 'function createH5pActivities',
    'function build_h5p_package' => 'function buildH5pPackage',
    'function create_h5p_module' => 'function createH5pModule',
    'function write_progress' => 'function writeProgress',
    'function edit_item' => 'function editItem',
    'function build_edit_prompt' => 'function buildEditPrompt',
    'function merge_edited_item' => 'function mergeEditedItem',
    'function ai_assist' => 'function aiAssist',
    'function detect_section_reference' => 'function detectSectionReference',
    'function build_context_prompt' => 'function buildContextPrompt',
    'function format_section_full' => 'function formatSectionFull',
    'function get_assistant_system_prompt' => 'function getAssistantSystemPrompt',
    'function extract_json_object' => 'function extractJsonObject',
    'function sanitize_json_strings' => 'function sanitizeJsonStrings',
    'function parse_json_response' => 'function parseJsonResponse',
    'function merge_delta' => 'function mergeDelta',
    'function build_success_message' => 'function buildSuccessMessage',
    'function get_encryption_key' => 'function getEncryptionKey',
    'function encrypt_apikey' => 'function encryptApikey',
    'function decrypt_apikey' => 'function decryptApikey',
    'function get_all' => 'function getAll',
    'function get_default' => 'function getDefault',
    'function get_config' => 'function getConfig',
    'function set_default' => 'function setDefault',
    'function set_enabled' => 'function setEnabled',
    'function test_connection_raw' => 'function testConnectionRaw',
    'function test_connection' => 'function testConnection',
    'function call_api' => 'function callApi',
    'function call_api_with_history' => 'function callApiWithHistory',
    'function render_models_widget' => 'function renderModelsWidget',
    'function render_test_button' => 'function renderTestButton',
    'function extend_primary_navigation' => 'function extendPrimaryNavigation',
    'function before_footer' => 'function beforeFooter',
    'function get_metadata' => 'function getMetadata',
    'function get_contexts_for_userid' => 'function getContextsForUserid',
    'function export_user_data' => 'function exportUserData',
    'function delete_data_for_all_users_in_context' => 'function deleteDataForAllUsersInContext',
    'function delete_data_for_user' => 'function deleteDataForUser',
];

$files = glob(__DIR__ . '/classes/**/*.php', GLOB_RECURSIVE);
$files = array_merge($files, glob(__DIR__ . '/*.php'));
$files = array_merge($files, glob(__DIR__ . '/db/**/*.php', GLOB_RECURSIVE));

$total_replacements = 0;

foreach ($files as $file) {
    if (basename($file) === 'fix_naming.php') {
        continue; // Skip this script
    }

    $content = file_get_contents($file);
    $original = $content;

    // Case-insensitive replacements for method/function definitions and calls
    foreach ($conversions as $old => $new) {
        // For function definitions (case-insensitive)
        $pattern = '/' . preg_quote($old, '/') . '/i';
        $count = 0;
        $content = preg_replace_callback(
            $pattern,
            function ($m) use ($new) {
                // Preserve case of 'function' keyword but apply new name
                return (strpos($m[0], 'function') !== false ? 'function ' : 'class ') .
                       substr($new, strlen(explode(' ', $new)[0]) + 1);
            },
            $content,
            -1,
            $count
        );

        if ($count > 0) {
            echo "Updated $count occurrences of '$old' in " . basename($file) . "\n";
            $total_replacements += $count;
        }
    }

    // Also do method call replacements
    // e.g., ->generate_course_outline() becomes ->generateCourseOutline()
    $method_patterns = [
        '/->generate_course_outline/' => '->generateCourseOutline',
        '/->plan_course_outline/' => '->planCourseOutline',
        '/->build_plan_prompt/' => '->buildPlanPrompt',
        '/->is_rate_limit_error/' => '->isRateLimitError',
        '/->write_progress/' => '->writeProgress',
    ];

    foreach ($method_patterns as $pattern => $replacement) {
        $count = 0;
        $content = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($count > 0) {
            echo "Updated $count method calls for " . trim($pattern, '/') . " in " . basename($file) . "\n";
            $total_replacements += $count;
        }
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Updated " . basename($file) . "\n";
    }
}

echo "\n✓ Total replacements: $total_replacements\n";
