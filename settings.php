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

    $settings->add(new admin_setting_configpasswordunmask(
        'local_courseagent/saas_api_key',
        get_string('saas_api_key', 'local_courseagent'),
        get_string('saas_api_key_desc', 'local_courseagent'),
        ''
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
