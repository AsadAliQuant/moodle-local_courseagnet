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
 * Shared header builder for outgoing SaaS calls.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable PSR1.Files.SideEffects -- Moodle requires bootstrap.
namespace local_courseagent;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the standard header set every SaaS call sends.
 *
 * Class name is lowercase to match the file name (saas_http.php) so the
 * Moodle autoloader resolves it identically on case-sensitive filesystems.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class saas_http {

    /**
     * Return the flat header array every plugin → SaaS request sends.
     *
     * Includes X-Site-URL with this Moodle's wwwroot so the backend can bind
     * the API key to its registered site origin. $CFG->wwwroot is server-side
     * config (set in config.php), not request-derived — so it's trustworthy as
     * a self-identifier.
     *
     * The returned shape works for both the Moodle \curl class
     * (`$curl->setHeader([...])`) and the raw cURL extension
     * (`curl_setopt($ch, CURLOPT_HTTPHEADER, [...])`).
     *
     * @param string $apikey The SaaS license key.
     * @return string[]
     */
    public static function headers(string $apikey): array {
        global $CFG;
        return [
            'X-API-Key: ' . $apikey,
            'X-Site-URL: ' . rtrim($CFG->wwwroot, '/'),
            'Accept: application/json',
        ];
    }

    /**
     * Default SaaS base URL, used when nothing else is configured.
     */
    const DEFAULT_BASE_URL = 'https://api.courseagent.io';

    /**
     * Return the SaaS base URL, without a trailing slash.
     *
     * Resolution order, most specific first:
     *   1. The COURSEAGENT_SAAS_URL constant in config.php — lets a site pin the
     *      endpoint outside the database, and lets developers point a local
     *      Moodle at `wrangler dev` without touching admin settings.
     *   2. The local_courseagent/saas_base_url admin setting.
     *   3. DEFAULT_BASE_URL.
     *
     * This exists because the URL used to be duplicated at five call sites
     * (settings.php, ajax.php x3, api.php, license.php), which meant moving the
     * backend required editing five files in lockstep.
     *
     * @return string
     */
    public static function base_url(): string {
        if (defined('COURSEAGENT_SAAS_URL') && !empty(COURSEAGENT_SAAS_URL)) {
            return rtrim(COURSEAGENT_SAAS_URL, '/');
        }

        $configured = get_config('local_courseagent', 'saas_base_url');
        if (!empty($configured)) {
            return rtrim(trim($configured), '/');
        }

        return self::DEFAULT_BASE_URL;
    }
}
