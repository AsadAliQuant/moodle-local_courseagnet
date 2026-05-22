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
 * Post-installation script for local_courseagent.
 *
 * This runs AFTER install.xml has created the database tables.
 * Use it for initial data seeding, logging, or any setup that
 * cannot be expressed in XMLDB.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Code to be run after the plugin is installed.
 *
 * @return bool True on success
 */
function xmldb_local_courseagent_install()
{
    global $DB;

    // Database tables are already created by install.xml at this point.
    // Use this space only for post-install data seeding or setup that
    // cannot be expressed in XMLDB (e.g. inserting default records,
    // creating file areas, registering hooks, etc.).

    mtrace('  Course Agent plugin installed successfully.', '');

    return true;
}
