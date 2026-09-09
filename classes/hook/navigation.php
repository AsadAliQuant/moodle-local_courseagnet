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

namespace local_courseagent\hook;

use core\hook\navigation\primary_extend;

/**
 * Hook callbacks for local_courseagent plugin.
 * Adds navigation link to primary navigation for users with course creation capability.
 *
 * Class name is lowercase to match the file name (navigation.php). Moodle's
 * autoloader resolves `local_courseagent\hook\Navigation` to `classes/hook/Navigation.php`
 * literally, so case-sensitive filesystems (and stricter Moodle 5.x checks) fail
 * unless class and file names match exactly.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MissingNamespace
class navigation
{
    /**
     * Add Course Agent link to the primary top navbar (Moodle 5.x).
     *
     * Method name is snake_case to match the callback registered in db/hooks.php.
     * Moodle's hook manager validates callbacks with strict (case-sensitive) method-name lookup.
     *
     * @param primary_extend $hook
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function extend_primary_navigation(primary_extend $hook): void
    {
        // Check capability - only show for teachers, managers, admins.
        $context = \context_system::instance();
        if (!has_any_capability(['moodle/course:create', 'moodle/site:config', 'local/courseagent:createcourse'], $context)) {
            return;
        }

        $primaryview = $hook->get_primaryview();
        $url = new \moodle_url('/local/courseagent/index.php');

        $primaryview->add(
            get_string('nav_createcourse', 'local_courseagent'),
            $url,
            \navigation_node::TYPE_CUSTOM,
            'local_courseagent',
            'local_courseagent',
            new \pix_icon('wand', '', 'local_courseagent')
        );
    }
}
