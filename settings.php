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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin settings for local_courseagent.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom admin setting for the CourseAgent license key.
 * Validates the key against the SaaS API before saving.
 *
 * Guarded with class_exists() because Moodle 5.x includes plugin settings.php
 * more than once per admin request while building the admin tree; without the
 * guard the second include fatals with "Cannot declare class ... already in use".
 */
if (!class_exists('local_courseagent_licensekey_setting')) {
    require_once(__DIR__ . '/classes/saas_http.php');
    class local_courseagent_licensekey_setting extends admin_setting_configpasswordunmask {
        public function write_setting($data) {
            // Removing the key clears any activated plan — paid features lock immediately.
            if (empty(trim($data))) {
                set_config('saas_plan', 'none', 'local_courseagent');
                return parent::write_setting($data);
            }
            $saasurl = \local_courseagent\saas_http::base_url();
            $curl = new \curl(['ignoresecurity' => true]);
            $curl->setHeader(\local_courseagent\saas_http::headers($data));
            $resp     = $curl->get($saasurl . '/api/v1/sites/usage');
            $info     = $curl->get_info();
            $httpcode = (int)($info['http_code'] ?? 0);
            if ($httpcode === 401 || $httpcode === 404) {
                set_config('saas_plan', 'none', 'local_courseagent');
                return get_string('saas_key_invalid', 'local_courseagent');
            }
            if ($httpcode === 403) {
                $body = json_decode((string)$resp, true);
                if (is_array($body) && ($body['detail'] ?? '') === 'key_origin_mismatch') {
                    set_config('saas_plan', 'none', 'local_courseagent');
                    return get_string('license_origin_mismatch_msg', 'local_courseagent');
                }
            }
            if ($httpcode === 0) {
                // Could not verify — save the key but keep features locked until activation.
                set_config('saas_plan', 'none', 'local_courseagent');
                parent::write_setting($data);
                return get_string('saas_key_network_error', 'local_courseagent');
            }
            // 200 (and any other non-fatal status): store the verified plan, else lock.
            $body = json_decode((string)$resp, true);
            $plan = (is_array($body) && in_array($body['plan'] ?? '', ['starter', 'pro'], true))
                ? $body['plan']
                : 'none';
            set_config('saas_plan', $plan, 'local_courseagent');
            return parent::write_setting($data);
        }
    }
}

if ($hassiteconfig) {
    // Create settings category for our plugin.
    $ADMIN->add(
        'localplugins',
        new admin_category('local_courseagent_folder', get_string('pluginname', 'local_courseagent'))
    );

    // Settings page.
    $settings = new admin_settingpage('local_courseagent', get_string('settings', 'local_courseagent'));
    $ADMIN->add('local_courseagent_folder', $settings);

    // Link to provider management.
    $settings->add(new admin_setting_heading(
        'local_courseagent/providers_heading',
        get_string('provider_management', 'local_courseagent'),
        html_writer::link(
            new moodle_url('/local/courseagent/providers.php'),
            get_string('provider_manage_link', 'local_courseagent'),
            ['class' => 'btn btn-primary']
        )
    ));

    // Default provider selection (populated dynamically).
    $settings->add(new admin_setting_configselect(
        'local_courseagent/default_provider',
        get_string('default_provider', 'local_courseagent'),
        get_string('default_provider_desc', 'local_courseagent'),
        0,
        function () {
            global $DB;
            $providers = $DB->get_records_menu('courseagent_providers', ['enabled' => 1], 'name', 'id,name');
            return [0 => get_string('provider_autoselect', 'local_courseagent')] + $providers;
        }
    ));

    // Course generation settings.
    $settings->add(new admin_setting_heading(
        'local_courseagent/generation_settings',
        get_string('generation_settings', 'local_courseagent'),
        ''
    ));

    // Max sections.
    $settings->add(new admin_setting_configtext(
        'local_courseagent/max_sections',
        get_string('max_sections', 'local_courseagent'),
        get_string('max_sections_desc', 'local_courseagent'),
        8,
        PARAM_INT
    ));

    // Max quiz questions.
    $settings->add(new admin_setting_configtext(
        'local_courseagent/max_quiz_questions',
        get_string('max_quiz_questions', 'local_courseagent'),
        get_string('max_quiz_questions_desc', 'local_courseagent'),
        7,
        PARAM_INT
    ));

    // Enable assignments.
    $settings->add(new admin_setting_configcheckbox(
        'local_courseagent/enable_assignments',
        get_string('enable_assignments', 'local_courseagent'),
        get_string('enable_assignments_desc', 'local_courseagent'),
        1
    ));

    // SaaS settings.
    $settings->add(new admin_setting_heading(
        'local_courseagent/saas_heading',
        get_string('saas_heading', 'local_courseagent'),
        get_string('saas_heading_desc', 'local_courseagent')
    ));

    $settings->add(new local_courseagent_licensekey_setting(
        'local_courseagent/saas_api_key',
        get_string('saas_api_key', 'local_courseagent'),
        get_string('saas_api_key_desc', 'local_courseagent'),
        ''
    ));

    // Where the SaaS lives. Previously hardcoded at five call sites, which made
    // moving the backend an edit in five files; now read through
    // \local_courseagent\saas_http::base_url(). The COURSEAGENT_SAAS_URL
    // constant in config.php still overrides this when set.
    $settings->add(new admin_setting_configtext(
        'local_courseagent/saas_base_url',
        get_string('saas_base_url', 'local_courseagent'),
        get_string('saas_base_url_desc', 'local_courseagent'),
        \local_courseagent\saas_http::DEFAULT_BASE_URL,
        PARAM_URL
    ));


    // Activate License button — validates the key against the SaaS and stores the plan.
    $activatedtpl = addslashes(get_string('saas_activated', 'local_courseagent', '__PLAN__'));
    $activatebtnhtml  = html_writer::tag('button',
        get_string('saas_activate_btn', 'local_courseagent'),
        ['type' => 'button', 'id' => 'ca-test-license-btn', 'class' => 'btn btn-primary btn-sm']
    );
    $activatebtnhtml .= ' ' . html_writer::span('', 'small ml-2', ['id' => 'ca-test-license-result']);
    $activatebtnhtml .= '<script>
document.addEventListener("DOMContentLoaded", function() {
    var btn = document.getElementById("ca-test-license-btn");
    var result = document.getElementById("ca-test-license-result");
    if (!btn) { return; }
    btn.addEventListener("click", function() {
        btn.disabled = true;
        result.innerHTML = "' . addslashes(get_string('saas_activate_loading', 'local_courseagent')) . '";
        var fd = new FormData();
        fd.append("action", "test_license_key");
        fd.append("sesskey", M.cfg.sesskey);
        fetch(M.cfg.wwwroot + "/local/courseagent/ajax.php", {method: "POST", body: fd})
            .then(function(r) { return r.json(); })
            .then(function(d) {
                btn.disabled = false;
                if (d.success && (d.plan === "starter" || d.plan === "pro")) {
                    var planLabel = d.plan.charAt(0).toUpperCase() + d.plan.slice(1);
                    var msg = "' . $activatedtpl . '".replace("__PLAN__", planLabel);
                    result.innerHTML = "<span class=\"text-success\"><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i> " + msg + "</span>";
                } else if (d.success) {
                    result.innerHTML = "<span class=\"text-warning\"><i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i> ' . addslashes(get_string('saas_activate_no_plan', 'local_courseagent')) . '</span>";
                } else {
                    result.innerHTML = "<span class=\"text-danger\"><i class=\"fa fa-times-circle\" aria-hidden=\"true\"></i> ' . addslashes(get_string('saas_activate_failed', 'local_courseagent')) . '</span>";
                }
            })
            .catch(function() {
                btn.disabled = false;
                result.innerHTML = "<span class=\"text-danger\"><i class=\"fa fa-times-circle\" aria-hidden=\"true\"></i> ' . addslashes(get_string('saas_activate_failed', 'local_courseagent')) . '</span>";
            });
    });
});
</script>';
    $settings->add(new admin_setting_heading(
        'local_courseagent/saas_test_connection',
        '',
        $activatebtnhtml
    ));

    // Add external page for provider management.
    $ADMIN->add(
        'local_courseagent_folder',
        new admin_externalpage(
            'local_courseagent_providers',
            get_string('provider_management', 'local_courseagent'),
            new moodle_url('/local/courseagent/providers.php'),
            'moodle/site:config'
        )
    );
}
