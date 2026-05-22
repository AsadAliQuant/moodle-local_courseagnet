<?php
// This file is part of Course Agent - AI Course Creator Plugin for Moodle.
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
 * AJAX endpoint for local_courseagent actions.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/api.php');
require_once(__DIR__ . '/classes/extractor.php');

use local_courseagent\provider;
use local_courseagent\api;

/**
 * Write progress update to a temp file for client polling.
 */
function courseagent_write_progress($step, $percent, $message) {
    global $USER;
    $dir = make_temp_directory('courseagent');
    $file = $dir . '/progress_' . $USER->id . '.json';
    $data = [
        'step'     => $step,
        'percent'  => $percent,
        'message'  => $message,
        'time'     => time(),
    ];
    file_put_contents($file, json_encode($data) . "\n", FILE_APPEND);
}

// Require login and capability.
$context = context_system::instance();
require_login();
$PAGE->set_context($context);
require_capability('local/courseagent:createcourse', $context);
require_sesskey();

// Get action parameter.
$action = required_param('action', PARAM_ALPHAEXT);

header('Content-Type: application/json');

// Catch PHP fatal errors (OOM, timeout, etc.) and return JSON instead of empty 500.
ob_start();
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => false,
            'error'        => '[FATAL] ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line'],
            'fallback_log' => [],
        ]);
    } else {
        ob_end_flush();
    }
});

try {
    switch ($action) {
        case 'plan':
            // Generate lightweight course plan (paid users only — called before full generation).
            $topic             = optional_param('topic', '', PARAM_TEXT);
            $level             = optional_param('level', 'intermediate', PARAM_TEXT);
            $numsections       = optional_param('numsections', 4, PARAM_INT);
            $includequiz       = optional_param('includequiz', true, PARAM_BOOL);
            $includeassignment = optional_param('includeassignment', false, PARAM_BOOL);
            $includeh5p        = optional_param('includeh5p', false, PARAM_BOOL);
            $h5ptypes          = optional_param('h5p_types', '', PARAM_TEXT);
            $providerid        = optional_param('provider', 0, PARAM_INT);
            $model             = optional_param('model', '', PARAM_TEXT);
            $extractedcontent  = optional_param('extracted_content', '', PARAM_RAW);
            $customtitle       = optional_param('custom_title', '', PARAM_TEXT);

            if (empty(trim($topic)) && empty(trim($extractedcontent))) {
                throw new Exception(get_string('error_no_topic', 'local_courseagent'));
            }

            $api  = new api();
            $plan = $api->plan_course_outline(
                $topic,
                $level,
                $numsections,
                $includequiz,
                $includeassignment,
                $includeh5p,
                $providerid > 0 ? $providerid : null,
                $model ?: null,
                $extractedcontent ?: null,
                $customtitle ?: null,
                $h5ptypes
            );

            global $SESSION;
            $SESSION->courseagent_plan = $plan;

            echo json_encode(['success' => true, 'plan' => $plan]);
            break;

        case 'generate':
            // Generate course outline using AI.
            $topic = optional_param('topic', '', PARAM_TEXT);
            $level = optional_param('level', 'intermediate', PARAM_TEXT);
            $numsections = optional_param('numsections', 4, PARAM_INT);
            $includequiz = optional_param('includequiz', true, PARAM_BOOL);
            $includeassignment = optional_param('includeassignment', false, PARAM_BOOL);
            $useemojis = optional_param('useemojis', false, PARAM_BOOL);
            $usesvg = optional_param('usesvg', false, PARAM_BOOL);
            $includeh5p = optional_param('includeh5p', false, PARAM_BOOL);
            $h5ptypes   = optional_param('h5p_types', '', PARAM_TEXT);
            $useplan = optional_param('use_plan', false, PARAM_BOOL);
            $providerid = optional_param('provider', 0, PARAM_INT);
            $model = optional_param('model', '', PARAM_TEXT);
            $extractedcontent = optional_param('extracted_content', '', PARAM_RAW);
            $customtitle = optional_param('custom_title', '', PARAM_TEXT);

            // Require at least a topic or uploaded content.
            if (empty(trim($topic)) && empty(trim($extractedcontent))) {
                throw new Exception(get_string('error_no_topic', 'local_courseagent'));
            }

            // Clear previous progress file.
            $progdir = make_temp_directory('courseagent');
            $progfile = $progdir . '/progress_' . $USER->id . '.json';
            @unlink($progfile);

            courseagent_write_progress(1, 10, get_string('progress_preparing', 'local_courseagent'));

            // Load approved plan from session when use_plan=1.
            global $SESSION;
            $approvedplan = ($useplan && !empty($SESSION->courseagent_plan))
                ? $SESSION->courseagent_plan
                : null;

            // Generate course using AI.
            $api = new api();
            $coursedata = $api->generate_course_outline(
                $topic,
                $level,
                $numsections,
                $includequiz,
                $includeassignment,
                $providerid > 0 ? $providerid : null,
                $model ?: null,
                $extractedcontent ?: null,
                $customtitle ?: null,
                $useemojis,
                $usesvg,
                $approvedplan
            );

            // Carry H5P preferences through to publish.
            $coursedata->_include_h5p  = $includeh5p;
            $coursedata->_h5p_types    = $h5ptypes;

            courseagent_write_progress(3, 95, get_string('progress_finalizing', 'local_courseagent'));

            // Store in session for preview page.
            global $SESSION;
            $SESSION->courseagent_preview = $coursedata;

            courseagent_write_progress(3, 100, get_string('progress_complete', 'local_courseagent'));

            echo json_encode([
                'success'       => true,
                'data'          => $coursedata,
                'used_provider' => $coursedata->_used_provider_name ?? null,
                'used_model'    => $coursedata->_used_model ?? null,
                'fallback_log'  => $coursedata->_fallback_log ?? [],
            ]);
            break;

        case 'publish':
            // Publish course to Moodle.
            $jsondata = file_get_contents('php://input');
            $coursedata = json_decode($jsondata);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(get_string('error_invalid_json', 'local_courseagent'));
            }

            $api = new api();
            $result = $api->publish_course($coursedata);
            $courseurl = new moodle_url('/course/view.php', ['id' => $result['courseid']]);

            echo json_encode([
                'success'      => true,
                'course_id'    => $result['courseid'],
                'course_url'   => $courseurl->out(false),
                'h5p_warnings' => $result['h5p_warnings'],
            ]);
            break;

        case 'test_provider':
            // Test AI provider connection.
            $providerid = required_param('providerid', PARAM_INT);

            $result = provider::test_connection($providerid);

            echo json_encode([
                'success' => $result->success,
                'message' => $result->message,
                'httpcode' => $result->httpcode,
                'ai_response' => $result->ai_response ?? null,
                'response' => $result->response,
                'debug' => $result->debug ?? null,
            ]);
            break;

        case 'test_provider_raw':
            // Clean any output buffer to prevent JSON corruption
            if (ob_get_length() > 0) {
                ob_clean();
            }

            // Test provider connection with raw parameters (for unsaved forms).
            $baseurl   = required_param('baseurl', PARAM_RAW_TRIMMED);
            $endpoint  = optional_param('endpoint', '', PARAM_RAW_TRIMMED);
            $apikey    = required_param('apikey', PARAM_RAW_TRIMMED);
            $model     = optional_param('model', '', PARAM_TEXT);
            $apiformat = optional_param('api_format', 'openai', PARAM_ALPHA);

            $result = provider::test_connection_raw($baseurl, $endpoint, $apikey, $model ?: null, $apiformat);

            // TEMPORARY DEBUG: Capture the actual response
            error_log("Course Agent - test_provider_raw result object: " . print_r($result, true));

            // Also validate result has required fields
            if (!isset($result->success)) {
                error_log("Course Agent - ERROR: result->success is not set!");
                $result->success = false;
            }
            if (!isset($result->message)) {
                error_log("Course Agent - ERROR: result->message is not set!");
                $result->message = 'No message set';
            }
            if (!isset($result->httpcode)) {
                error_log("Course Agent - ERROR: result->httpcode is not set!");
                $result->httpcode = 0;
            }

            echo json_encode([
                'success' => $result->success,
                'message' => $result->message,
                'httpcode' => $result->httpcode,
                'ai_response' => $result->ai_response ?? null,
                'response' => $result->response,
                'debug' => $result->debug ?? null,
            ]);
            break;

        case 'get_progress':
            // Read progress updates from temp file.
            $progdir = make_temp_directory('courseagent');
            $progfile = $progdir . '/progress_' . $USER->id . '.json';
            $lines = [];
            if (file_exists($progfile)) {
                $content = file_get_contents($progfile);
                $rawlines = array_filter(explode("\n", trim($content)));
                foreach ($rawlines as $line) {
                    $decoded = json_decode($line);
                    if ($decoded) {
                        $lines[] = $decoded;
                    }
                }
            }
            // Return the latest entry only.
            $latest = !empty($lines) ? $lines[count($lines) - 1] : null;
            echo json_encode([
                'success'  => true,
                'progress' => $latest,
            ]);
            break;

        case 'get_models':
            // Get models for a provider.
            $providerid = required_param('providerid', PARAM_INT);

            $provider = provider::get($providerid);
            if (!$provider) {
                throw new Exception(get_string('provider_not_found', 'local_courseagent'));
            }

            $models = json_decode($provider->models, true) ?: [];

            echo json_encode([
                'success' => true,
                'models' => $models,
            ]);
            break;

        case 'extract_content':
            // Extract text from an uploaded file.
            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errcodes = [
                    UPLOAD_ERR_INI_SIZE   => get_string('upload_err_ini_size', 'local_courseagent'),
                    UPLOAD_ERR_FORM_SIZE  => get_string('upload_err_form_size', 'local_courseagent'),
                    UPLOAD_ERR_PARTIAL    => get_string('upload_err_partial', 'local_courseagent'),
                    UPLOAD_ERR_NO_FILE    => get_string('upload_err_no_file', 'local_courseagent'),
                    UPLOAD_ERR_NO_TMP_DIR => get_string('upload_err_no_tmp_dir', 'local_courseagent'),
                    UPLOAD_ERR_CANT_WRITE => get_string('upload_err_cant_write', 'local_courseagent'),
                    UPLOAD_ERR_EXTENSION  => get_string('upload_err_extension', 'local_courseagent'),
                ];
                $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
                throw new Exception($errcodes[$code] ?? get_string('upload_err_generic', 'local_courseagent', $code));
            }

            $tmppath  = $_FILES['file']['tmp_name'];
            $filename = $_FILES['file']['name'];
            $maxchars = 100000;

            $extractor = new local_courseagent\extractor();
            $text = $extractor->extract($tmppath, $filename, $maxchars);

            echo json_encode([
                'success'  => true,
                'text'     => $text,
                'charcount' => mb_strlen($text),
                'filename' => $filename,
            ]);
            break;

        case 'edit_item':
        case 'ai_assist':
            // AI chat assistant or legacy targeted edit.
            $jsondata = file_get_contents('php://input');
            $input = json_decode($jsondata);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(get_string('error_invalid_json', 'local_courseagent'));
            }

            $userprompt = $input->user_prompt ?? '';
            $coursedata = $input->course_data ?? null;

            // Prefer session over payload (avoids large POST body).
            global $SESSION;
            if (!$coursedata) {
                $coursedata = $SESSION->courseagent_preview ?? null;
            }
            if (!$coursedata) {
                throw new \Exception(get_string('error_no_preview_data', 'local_courseagent'));
            }

            $api = new api();

            if ($action === 'ai_assist') {
                if (empty($userprompt)) {
                    throw new \Exception('User prompt is required.');
                }
                $result = $api->ai_assist($coursedata, $userprompt);
            } else {
                // Legacy targeted edit.
                $targettype    = $input->target_type    ?? '';
                $targetindex   = $input->target_index   ?? 0;
                $questionindex = $input->question_index ?? null;
                if (empty($targettype) || empty($userprompt)) {
                    throw new \Exception(get_string('error_edit_params', 'local_courseagent'));
                }
                $result = $api->edit_item($coursedata, $targettype, $targetindex, $questionindex, $userprompt);
            }

            // Update session with full merged course.
            $SESSION->courseagent_preview = $result->coursedata;

            if (isset($result->delta)) {
                echo json_encode([
                    'success'       => true,
                    'delta'         => $result->delta,
                    'message'       => $result->message,
                    'response_type' => $result->response_type ?? 'delta',
                    'plan_summary'  => $result->plan_summary ?? null,
                    'fallback_log'  => $result->fallback_log ?? [],
                    'used_provider' => $result->used_provider ?? null,
                    'used_model'    => $result->used_model ?? null,
                ]);
            } else {
                echo json_encode([
                    'success'       => true,
                    'data'          => $result->coursedata,
                    'message'       => $result->message,
                    'response_type' => $result->response_type ?? 'delta',
                    'plan_summary'  => $result->plan_summary ?? null,
                    'fallback_log'  => $result->fallback_log ?? [],
                    'used_provider' => $result->used_provider ?? null,
                    'used_model'    => $result->used_model ?? null,
                ]);
            }
            break;

        case 'clear_chat':
            global $SESSION;
            $SESSION->courseagent_chat_history = [];
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception(get_string('error_invalid_action', 'local_courseagent'));
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']',
    ]);
}
