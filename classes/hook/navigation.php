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
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Navigation
{
    /**
     * Add Course Agent link to the primary top navbar (Moodle 5.x).
     *
     * @param primary_extend $hook
     */
    public static function extendPrimaryNavigation(primary_extend $hook): void
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
