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
 * License key validator for CourseAgent SaaS.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable PSR1.Files.SideEffects -- Moodle requires bootstrap.
namespace local_courseagent;

defined('MOODLE_INTERNAL') || die();

/**
 * Pre-flight validator for the SaaS license key.
 *
 * Class name is lowercase to match the file name (license.php) — Moodle's
 * autoloader resolves `local_courseagent\License` literally on case-sensitive
 * filesystems, so PascalCase in a lowercase file would fail on Linux.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class license {

    /** @var int Cache TTL (seconds) for definitive verdicts: ok / invalid_key / no_plan. */
    const CACHE_TTL_DEFINITIVE = 300;

    /** @var int Cache TTL (seconds) for transient errors: network_error / service_error. */
    const CACHE_TTL_TRANSIENT = 30;

    /** @var int HTTP timeout for the validation call. */
    const HTTP_TIMEOUT = 8;

    /**
     * Validate the given API key against the SaaS /sites/usage endpoint.
     *
     * Result is cached in $SESSION keyed by sha1($apikey) so repeated calls
     * within the same course-creation flow (plan → generate → publish) hit
     * the network at most once per ~5 minutes per session.
     *
     * @param string $apikey      The license key to validate.
     * @param bool   $forcefresh  When true, bypass the session cache and re-hit the SaaS.
     * @return array              See class docblock for the full shape.
     */
    public static function validate(string $apikey, bool $forcefresh = false): array {
        global $SESSION;

        if (empty(trim($apikey))) {
            return self::result_invalid_key(0);
        }

        $hash = sha1($apikey);

        // Cache hit?
        if (!$forcefresh && !empty($SESSION->courseagent_license_cache)) {
            $cache = $SESSION->courseagent_license_cache;
            $ttl = isset($cache['ttl']) ? (int)$cache['ttl'] : self::CACHE_TTL_DEFINITIVE;
            if (!empty($cache['hash']) && $cache['hash'] === $hash
                && !empty($cache['time']) && (time() - (int)$cache['time']) < $ttl
                && !empty($cache['result'])) {
                return $cache['result'];
            }
        }

        // Cache miss — call the SaaS.
        $result = self::call_usage_endpoint($apikey);

        // Cache the verdict with the appropriate TTL.
        $ttl = in_array($result['error_code'], ['network_error', 'service_error'], true)
            ? self::CACHE_TTL_TRANSIENT
            : self::CACHE_TTL_DEFINITIVE;
        $SESSION->courseagent_license_cache = [
            'hash'   => $hash,
            'time'   => time(),
            'ttl'    => $ttl,
            'result' => $result,
        ];

        return $result;
    }

    /**
     * Build the JSON payload that AJAX handlers echo on hard-fail.
     *
     * @param array $result A result array from validate().
     * @return array
     */
    public static function build_error_payload(array $result): array {
        $stringid    = $result['error_string_id'] ?? 'license_invalid_msg';
        $settingsurl = (new \moodle_url('/admin/settings.php', ['section' => 'local_courseagent']))->out(false);
        $istransient = in_array($result['error_code'] ?? '', ['network_error', 'service_error'], true);
        return [
            'success'      => false,
            'error_code'   => 'license_' . ($result['error_code'] ?? 'invalid_key'),
            'error'        => get_string($stringid, 'local_courseagent'),
            'settings_url' => $settingsurl,
            'is_transient' => $istransient,
        ];
    }

    /**
     * Hit GET /api/v1/sites/usage with the given key and classify the response.
     *
     * @param string $apikey
     * @return array
     */
    private static function call_usage_endpoint(string $apikey): array {
        $saasurl = defined('COURSEAGENT_SAAS_URL')
            ? rtrim(COURSEAGENT_SAAS_URL, '/')
            : 'https://api.courseagent.io';

        require_once(__DIR__ . '/saas_http.php');
        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setHeader(\local_courseagent\saas_http::headers($apikey));
        $resp = $curl->get($saasurl . '/api/v1/sites/usage', null, [
            'CURLOPT_TIMEOUT'        => self::HTTP_TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => self::HTTP_TIMEOUT,
        ]);
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        if ($httpcode === 200) {
            $data = json_decode((string)$resp, true);
            $plan = is_array($data) ? ($data['plan'] ?? null) : null;
            if ($plan === 'starter' || $plan === 'pro') {
                return [
                    'valid'            => true,
                    'plan'             => $plan,
                    'used_this_month'  => is_array($data) ? ($data['used_this_month'] ?? null) : null,
                    'limit'            => is_array($data) ? ($data['limit'] ?? null) : null,
                    'plan_expires_at'  => is_array($data) ? ($data['plan_expires_at'] ?? null) : null,
                    'error_code'       => 'ok',
                    'error_string_id'  => null,
                    'http_code'        => 200,
                ];
            }
            // Plan is "none", null, or unrecognised — treat as no_plan.
            return [
                'valid'            => false,
                'plan'             => $plan,
                'used_this_month'  => null,
                'limit'            => null,
                'plan_expires_at'  => is_array($data) ? ($data['plan_expires_at'] ?? null) : null,
                'error_code'       => 'no_plan',
                'error_string_id'  => 'license_no_plan_msg',
                'http_code'        => 200,
            ];
        }

        if ($httpcode === 401 || $httpcode === 404) {
            return self::result_invalid_key($httpcode);
        }

        if ($httpcode === 403) {
            $body = json_decode((string)$resp, true);
            if (is_array($body) && ($body['detail'] ?? '') === 'key_origin_mismatch') {
                return [
                    'valid'            => false,
                    'plan'             => null,
                    'used_this_month'  => null,
                    'limit'            => null,
                    'plan_expires_at'  => null,
                    'error_code'       => 'origin_mismatch',
                    'error_string_id'  => 'license_origin_mismatch_msg',
                    'http_code'        => 403,
                ];
            }
        }

        if ($httpcode === 0) {
            return [
                'valid'            => false,
                'plan'             => null,
                'used_this_month'  => null,
                'limit'            => null,
                'plan_expires_at'  => null,
                'error_code'       => 'network_error',
                'error_string_id'  => 'license_network_msg',
                'http_code'        => 0,
            ];
        }

        // Any 5xx or unexpected 4xx — treat as service_error so we soft-fail.
        return [
            'valid'            => false,
            'plan'             => null,
            'used_this_month'  => null,
            'limit'            => null,
            'plan_expires_at'  => null,
            'error_code'       => 'service_error',
            'error_string_id'  => 'license_service_msg',
            'http_code'        => $httpcode,
        ];
    }

    /**
     * Shorthand for the invalid_key result.
     *
     * @param int $httpcode
     * @return array
     */
    private static function result_invalid_key(int $httpcode): array {
        return [
            'valid'            => false,
            'plan'             => null,
            'used_this_month'  => null,
            'limit'            => null,
            'plan_expires_at'  => null,
            'error_code'       => 'invalid_key',
            'error_string_id'  => 'license_invalid_msg',
            'http_code'        => $httpcode,
        ];
    }
}
