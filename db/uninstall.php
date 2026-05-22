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
 * Pre-uninstallation script for local_courseagent.
 *
 * This runs BEFORE Moodle drops the database tables defined in install.xml.
 * Use it to clean up plugin configuration, cached data, and any artifacts
 * that are not covered by the automatic table removal.
 *
 * Note: Moodle automatically removes all tables defined in db/install.xml
 * after this function returns, so there is no need to drop them here.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Code to be run before the plugin is uninstalled.
 *
 * @return bool True on success
 */
function xmldb_local_courseagent_uninstall()
{
    global $DB;

    // Remove all plugin configuration settings from config_plugins.
    // These are set via settings.php (admin_setting_* calls) and are NOT
    // automatically removed when the plugin is uninstalled.
    $DB->delete_records('config_plugins', ['plugin' => 'local_courseagent']);

    // Note: The following are handled automatically by Moodle:
    // - Database tables defined in db/install.xml are dropped.
    // - Capabilities defined in db/access.php are removed.
    // - Hook callbacks defined in db/hooks.php are unregistered.
    // - Language strings are no longer loaded.

    mtrace('  Course Agent plugin uninstalled successfully.', '');

    return true;
}
