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
 * Entry-point script: legitimately mixes declarations with side effects (require,
 * define, header, etc.) — suppress PSR1.Files.SideEffects per Moodle convention.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable PSR1.Files.SideEffects

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/api.php');
require_once(__DIR__ . '/classes/extractor.php');
require_once(__DIR__ . '/classes/license.php');
require_once(__DIR__ . '/classes/saas_http.php');

use local_courseagent\provider;
use local_courseagent\api;

/**
 * Write progress update to a temp file for client polling.
 */
function courseagent_write_progress($step, $percent, $message)
{
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
register_shutdown_function(function () {
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

// Pre-flight license validator for SaaS-mode actions.
// Returns null when free mode (no key) or when validation passes; otherwise an
// array with 'soft_fail' (bool) and 'payload' (JSON-ready array). soft_fail=true
// means the SaaS itself is unreachable — caller may fall back to free mode
// instead of blocking; soft_fail=false means the key/plan is bad — caller blocks.
$preflightlicense = function () {
    $key = get_config('local_courseagent', 'saas_api_key') ?: '';
    if (empty($key)) {
        return null;
    }
    $result = \local_courseagent\license::validate($key);
    if ($result['valid']) {
        return null;
    }
    $soft = in_array($result['error_code'], ['network_error', 'service_error'], true);
    return [
        'soft_fail' => $soft,
        'payload'   => \local_courseagent\license::build_error_payload($result),
    ];
};

// Collect the per-activity count params (sliders + sub-option toggles) from the request
// and resolve them into the tidy struct the API/prompt/publish layers consume.
$collectcounts = function () {
    $raw = [];
    foreach (['quiz', 'assignment', 'h5p'] as $act) {
        $raw[$act . '_per_section_enabled'] = optional_param($act . '_per_section_enabled', 0, PARAM_BOOL);
        $raw[$act . '_total_enabled']       = optional_param($act . '_total_enabled', 0, PARAM_BOOL);
        $raw[$act . '_min_per_section']     = optional_param($act . '_min_per_section', 1, PARAM_INT);
        $raw[$act . '_max_per_section']     = optional_param($act . '_max_per_section', 1, PARAM_INT);
        $raw[$act . '_min_total']           = optional_param($act . '_min_total', 1, PARAM_INT);
        $raw[$act . '_max_total']           = optional_param($act . '_max_total', 1, PARAM_INT);
    }
    return \local_courseagent\api::resolveCounts($raw);
};

try {
    switch ($action) {
        case 'plan':
            // Generate lightweight course plan. The outline itself runs on the local AI provider,
            // so it is free. Only validate the license when this call actually requests a paid
            // feature (H5P activity generation) — otherwise free planning must never be blocked.
            $topic             = optional_param('topic', '', PARAM_TEXT);
            $level             = optional_param('level', 'intermediate', PARAM_TEXT);
            $numsections       = optional_param('numsections', 4, PARAM_INT);
            $includequiz       = optional_param('includequiz', true, PARAM_BOOL);
            $includeassignment = optional_param('includeassignment', false, PARAM_BOOL);
            $includeh5p        = optional_param('includeh5p', false, PARAM_BOOL);
            $h5ptypes          = optional_param('h5p_types', '', PARAM_TEXT);
            $providerid        = optional_param('provider', 0, PARAM_INT);
            $model             = optional_param('model', '', PARAM_TEXT);
            $customtitle       = optional_param('custom_title', '', PARAM_TEXT);

            // Paid-feature gate: block up-front only when H5P is enabled and the license fails.
            if ($includeh5p) {
                $lic = $preflightlicense();
                if ($lic !== null) {
                    echo json_encode($lic['payload']);
                    break;
                }
            }

            if (empty(trim($topic))) {
                throw new Exception(get_string('error_no_topic', 'local_courseagent'));
            }

            $api  = new api();
            $plan = $api->planCourseOutline(
                $topic,
                $level,
                $numsections,
                $includequiz,
                $includeassignment,
                $includeh5p,
                $providerid > 0 ? $providerid : null,
                $model ?: null,
                null,
                $customtitle ?: null,
                $h5ptypes,
                $collectcounts()
            );

            global $SESSION;
            $SESSION->courseagent_plan = $plan;

            echo json_encode(['success' => true, 'plan' => $plan]);
            break;

        case 'generate':
            // Generate course outline using AI (runs on the local AI provider — free).
            $licensewarning = null;
            $topic = optional_param('topic', '', PARAM_TEXT);
            $level = optional_param('level', 'intermediate', PARAM_TEXT);
            $numsections = optional_param('numsections', 4, PARAM_INT);
            $includequiz = optional_param('includequiz', true, PARAM_BOOL);
            $includeassignment = optional_param('includeassignment', false, PARAM_BOOL);
            $useemojis = optional_param('useemojis', false, PARAM_BOOL);
            $usediagrams = optional_param('usediagrams', false, PARAM_BOOL);
            $includeh5p = optional_param('includeh5p', false, PARAM_BOOL);
            $h5ptypes   = optional_param('h5p_types', '', PARAM_TEXT);
            $useplan = optional_param('use_plan', false, PARAM_BOOL);
            $providerid = optional_param('provider', 0, PARAM_INT);
            $model = optional_param('model', '', PARAM_TEXT);
            $customtitle = optional_param('custom_title', '', PARAM_TEXT);

            // Paid-feature gate: block only when H5P is enabled and the license fails.
            // Free generations (H5P off) never touch the SaaS, so they are never gated.
            if ($includeh5p && !empty(get_config('local_courseagent', 'saas_api_key'))) {
                $lic = $preflightlicense();
                if ($lic !== null) {
                    echo json_encode($lic['payload']);
                    break;
                }
            }

            // Require a topic.
            if (empty(trim($topic))) {
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

            // Resolve the activity-count rules once and reuse them for generation + publish.
            $counts = $collectcounts();

            // Generate course using AI.
            $api = new api();
            $coursedata = $api->generateCourseOutline(
                $topic,
                $level,
                $numsections,
                $includequiz,
                $includeassignment,
                $providerid > 0 ? $providerid : null,
                $model ?: null,
                null,
                $customtitle ?: null,
                $useemojis,
                $usediagrams,
                $approvedplan,
                $counts
            );

            // Carry H5P preferences through to publish. When H5P is on, the license was already
            // validated by the gate above, so we trust the form value here.
            $coursedata->_include_h5p  = $includeh5p;
            $coursedata->_h5p_types    = $h5ptypes;
            // Carry the count rules so publish can clamp activity counts to the course-wide caps.
            $coursedata->_counts       = $counts;

            courseagent_write_progress(3, 95, get_string('progress_finalizing', 'local_courseagent'));

            // Store in session for preview page.
            global $SESSION;
            $SESSION->courseagent_preview = $coursedata;

            echo json_encode([
                'success'         => true,
                'data'            => $coursedata,
                'used_provider'   => $coursedata->_used_provider_name ?? null,
                'used_model'      => $coursedata->_used_model ?? null,
                'fallback_log'    => $coursedata->_fallback_log ?? [],
                'license_warning' => $licensewarning,
            ]);
            break;

        case 'publish':
            // Publish course to Moodle.
            $jsondata = file_get_contents('php://input');
            $coursedata = json_decode($jsondata);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(get_string('error_invalid_json', 'local_courseagent'));
            }

            // Paid-feature gate: only validate when this publish actually creates H5P activities.
            // The course is already generated by this point, so on any license failure we degrade
            // (skip H5P, keep the course) rather than block — never throw away the user's work.
            $publishlicensewarning = null;
            if (!empty($coursedata->_include_h5p) && !empty(get_config('local_courseagent', 'saas_api_key'))) {
                $lic = $preflightlicense();
                if ($lic !== null) {
                    $coursedata->_include_h5p = false;
                    $publishlicensewarning = $lic['payload']['error'];
                }
            }

            $api = new api();
            $result = $api->publishCourse($coursedata);
            $courseurl = new moodle_url('/course/view.php', ['id' => $result['courseid']]);

            echo json_encode([
                'success'         => true,
                'course_id'       => $result['courseid'],
                'course_url'      => $courseurl->out(false),
                'h5p_warnings'    => $result['h5p_warnings'],
                'license_warning' => $publishlicensewarning,
            ]);
            break;

        case 'test_provider':
            // Test AI provider connection.
            $providerid = required_param('providerid', PARAM_INT);

            $result = provider::testConnection($providerid);

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

            $result = provider::testConnection_raw($baseurl, $endpoint, $apikey, $model ?: null, $apiformat);

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
                $result = $api->aiAssist($coursedata, $userprompt);
            } else {
                // Legacy targeted edit.
                $targettype    = $input->target_type    ?? '';
                $targetindex   = $input->target_index   ?? 0;
                $questionindex = $input->question_index ?? null;
                if (empty($targettype) || empty($userprompt)) {
                    throw new \Exception(get_string('error_edit_params', 'local_courseagent'));
                }
                $result = $api->editItem($coursedata, $targettype, $targetindex, $questionindex, $userprompt);
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

        case 'get_site_documents':
            // Fetch ready documents from the SaaS document library for this site.
            // Pre-flight license check — this action is paid-only, so any failure blocks.
            $lic = $preflightlicense();
            if ($lic !== null) {
                echo json_encode($lic['payload']);
                break;
            }
            $saaskey = get_config('local_courseagent', 'saas_api_key');
            $saasurl = defined('COURSEAGENT_SAAS_URL') ? rtrim(COURSEAGENT_SAAS_URL, '/') : 'https://api.courseagent.io';

            $ch = curl_init($saasurl . '/api/v1/documents');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => \local_courseagent\saas_http::headers($saaskey),
                CURLOPT_TIMEOUT        => 15,
            ]);
            $responseraw = curl_exec($ch);
            $httpcode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlerr     = curl_error($ch);
            curl_close($ch);

            if ($curlerr || $httpcode >= 400) {
                $errmsg = $curlerr ?: ('HTTP ' . $httpcode . ': ' . ($responseraw ?: 'no response'));
                echo json_encode(['success' => false, 'error' => 'Could not fetch documents: ' . $errmsg]);
                break;
            }

            $docs = json_decode($responseraw, true);
            if (!is_array($docs)) {
                echo json_encode(['success' => false, 'error' => 'Unexpected response from document library.']);
                break;
            }
            // Filter to only ready documents.
            $ready = array_values(array_filter($docs, fn($d) => ($d['status'] ?? '') === 'ready'));
            echo json_encode(['success' => true, 'documents' => $ready]);
            break;

        case 'generate_course_from_docs':
            // Step 1: generate outline. Step 2 (approve + generate) called separately.
            // Pre-flight license check — this action is paid-only, so any failure blocks.
            $lic = $preflightlicense();
            if ($lic !== null) {
                echo json_encode($lic['payload']);
                break;
            }
            $saaskey  = get_config('local_courseagent', 'saas_api_key');
            $saasurl  = defined('COURSEAGENT_SAAS_URL') ? rtrim(COURSEAGENT_SAAS_URL, '/') : 'https://api.courseagent.io';
            $step     = optional_param('step', 'outline', PARAM_ALPHA);
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $rawdocids = optional_param('doc_ids', '', PARAM_RAW);
            $docids   = array_values(array_filter(array_map('trim', explode(',', $rawdocids))));
            $title    = optional_param('title', '', PARAM_TEXT);
            $description = optional_param('description', '', PARAM_TEXT);
            $audience = optional_param('audience', 'learners', PARAM_TEXT);

            if (empty($docids)) {
                echo json_encode(['success' => false, 'error' => 'Select at least one document.']);
                break;
            }
            if (empty($title)) {
                echo json_encode(['success' => false, 'error' => 'Course title is required.']);
                break;
            }

            if ($step === 'outline') {
                $payload = json_encode([
                    'course_id'   => (string) $courseid,
                    'doc_ids'     => $docids,
                    'title'       => $title,
                    'description' => $description,
                    'audience'    => $audience,
                ]);
                $ch = curl_init($saasurl . '/api/v1/courses/generate');
            } else {
                // Step 2: approve outline and generate full course.
                $outlinejson = optional_param('outline', '', PARAM_RAW);
                $outline     = json_decode($outlinejson, true);
                if (empty($outline)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid outline data.']);
                    break;
                }
                $payload = json_encode([
                    'course_id' => (string) $courseid,
                    'doc_ids'   => $docids,
                    'outline'   => $outline,
                ]);
                $ch = curl_init($saasurl . '/api/v1/courses/generate/approve');
            }

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => array_merge(
                    \local_courseagent\saas_http::headers($saaskey),
                    ['Content-Type: application/json']
                ),
                CURLOPT_TIMEOUT => 120,
            ]);
            $responseraw = curl_exec($ch);
            $httpcode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlerr     = curl_error($ch);
            curl_close($ch);

            if ($curlerr) {
                echo json_encode(['success' => false, 'error' => 'Request failed: ' . $curlerr]);
                break;
            }
            if ($httpcode === 402) {
                echo json_encode(['success' => false, 'error' => 'RAG course generation requires a Pro plan.']);
                break;
            }
            if ($httpcode >= 400) {
                $errdata = json_decode($responseraw, true);
                $errmsg  = $errdata['detail'] ?? ('HTTP ' . $httpcode);
                echo json_encode(['success' => false, 'error' => $errmsg]);
                break;
            }

            $result = json_decode($responseraw, true);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Could not parse response from course builder.']);
                break;
            }
            echo json_encode(['success' => true, 'result' => $result]);
            break;

        case 'test_license_key':
            require_capability('moodle/site:config', $context);
            $saasurl = defined('COURSEAGENT_SAAS_URL')
                ? rtrim(COURSEAGENT_SAAS_URL, '/')
                : 'https://api.courseagent.io';
            $apikey = get_config('local_courseagent', 'saas_api_key') ?: '';
            if (empty($apikey)) {
                echo json_encode(['success' => false, 'message' => 'No license key configured.']);
                break;
            }
            $curl = new \curl(['ignoresecurity' => true]);
            $curl->setHeader(\local_courseagent\saas_http::headers($apikey));
            $resp = $curl->get($saasurl . '/api/v1/sites/usage');
            $curlinfo = $curl->get_info();
            $httpcode = (int)($curlinfo['http_code'] ?? 0);
            if ($httpcode === 200) {
                $data = json_decode($resp ?? '', true) ?? [];
                $plan = $data['plan'] ?? 'unknown';
                // Activation: persist the verified plan. Paid plans unlock features; anything
                // else (none/unknown) locks them.
                set_config('saas_plan', in_array($plan, ['starter', 'pro'], true) ? $plan : 'none', 'local_courseagent');
                echo json_encode([
                    'success' => true,
                    'plan'    => $plan,
                    'used'    => $data['used_this_month'] ?? 0,
                    'limit'   => $data['limit'] ?? '&infin;',
                ]);
            } else if ($httpcode === 403) {
                set_config('saas_plan', 'none', 'local_courseagent');
                $body = json_decode((string)$resp, true);
                $detail = is_array($body) ? ($body['detail'] ?? '') : '';
                echo json_encode([
                    'success'  => false,
                    'httpcode' => $httpcode,
                    'detail'   => $detail,
                ]);
            } else {
                // 401/404 are definitive (bad key) — lock features. Network (0) and 5xx are
                // transient — leave any previously activated plan untouched.
                if ($httpcode === 401 || $httpcode === 404) {
                    set_config('saas_plan', 'none', 'local_courseagent');
                }
                echo json_encode(['success' => false, 'httpcode' => $httpcode]);
            }
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
