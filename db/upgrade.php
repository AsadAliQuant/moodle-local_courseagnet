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
 * Course Agent plugin upgrade steps.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_courseagent_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026041602) {
        // Create the courseagent_providers table for custom AI provider support.

        $table = new xmldb_table('courseagent_providers');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('apikey', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('baseurl', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endpoint', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('models', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add primary key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes.
        $table->add_index('isdefault', XMLDB_INDEX_NOTUNIQUE, ['isdefault']);
        $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

        // Only create if it doesn't already exist (safe to re-run).
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2026041602, 'local', 'courseagent');
    }

    if ($oldversion < 2026041603) {
        // Safety net: ensure courseagent_providers table exists regardless of how plugin was installed.
        // Covers the case where install.xml ran before this table was added, or upgrade was skipped.
        $table = new xmldb_table('courseagent_providers');

        if (!$dbman->table_exists($table)) {
            // Re-define all fields (table doesn't exist yet).
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('apikey', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('baseurl', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, null);
            $table->add_field('endpoint', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('models', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('isdefault', XMLDB_INDEX_NOTUNIQUE, ['isdefault']);
            $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026041603, 'local', 'courseagent');
    }

    if ($oldversion < 2026041604) {
        // Ensure the courseagent_providers table exists (safety net for upgrades).
        $table = new xmldb_table('courseagent_providers');

        if (!$dbman->table_exists($table)) {
            // Define all fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('apikey', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('baseurl', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, null);
            $table->add_field('endpoint', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('models', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('isdefault', XMLDB_INDEX_NOTUNIQUE, ['isdefault']);
            $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026041604, 'local', 'courseagent');
    }

    if ($oldversion < 2026041605) {
        // Add api_format field to courseagent_providers.
        // Values: 'openai' (default, OpenAI-compatible) or 'gemini' (Google Gemini native).
        $table = new xmldb_table('courseagent_providers');
        $field = new xmldb_field('api_format', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'openai', 'endpoint');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026041605, 'local', 'courseagent');
    }

    return true;
}
