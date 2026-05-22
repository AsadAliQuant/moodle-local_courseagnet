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

use core\hook\output\before_footer_html_generation;

/**
 * Output hook callbacks for local_courseagent plugin.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Output
{
    /**
     * Injects "Create with AI" AMD module on my/courses.php before JS is finalized.
     *
     * @param before_footer_html_generation $hook
     */
    public static function beforeFooter(before_footer_html_generation $hook): void
    {
        global $PAGE;

        if (!$PAGE->url || strpos($PAGE->url->get_path(), 'my/courses.php') === false) {
            return;
        }

        $context = \context_system::instance();
        if (!has_capability('local/courseagent:createcourse', $context)) {
            return;
        }

        $PAGE->requires->js_call_amd('local_courseagent/mycourses_buttons', 'init', [[
            'url'   => (new \moodle_url('/local/courseagent/index.php'))->out(false),
            'label' => get_string('createwithai', 'local_courseagent'),
        ]]);
    }
}
