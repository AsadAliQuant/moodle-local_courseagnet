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
 * User course dashboard for local_courseagent.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Require login and check capability.
$context = context_system::instance();
require_login();
require_capability('local/courseagent:viewmycourses', $context);

// Setup page.
$pageurl = new moodle_url('/local/courseagent/mycourses.php');
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('my_courses', 'local_courseagent'));
$PAGE->set_heading(get_string('my_courses', 'local_courseagent'));
$PAGE->set_pagelayout('standard');

global $DB, $USER;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_history', 'local_courseagent'), 2);

// Get user's course sessions.
$sessions = $DB->get_records('courseagent_sessions', ['userid' => $USER->id], 'timecreated DESC');

if (empty($sessions)) {
    echo $OUTPUT->notification(get_string('no_courses_yet', 'local_courseagent'), 'info');
    echo html_writer::link(
        new moodle_url('/local/courseagent/index.php'),
        get_string('create_course', 'local_courseagent'),
        ['class' => 'btn btn-primary']
    );
} else {
    // Display courses in a table.
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->head = [
        get_string('date_created', 'local_courseagent'),
        get_string('course_title_col', 'local_courseagent'),
        get_string('status_col', 'local_courseagent'),
        get_string('actions', 'local_courseagent'),
    ];
    $table->data = [];

    foreach ($sessions as $session) {
        $coursedata = json_decode($session->course_json);
        $title = !empty($coursedata->title) ? $coursedata->title : get_string('untitled_course', 'local_courseagent');
        $date = userdate($session->timecreated);
        $status = ucfirst($session->status);

        // Status badge styling.
        $statusclass = $session->status === 'published' ? 'success' :
                       ($session->status === 'failed' ? 'danger' : 'secondary');
        $statusbadge = html_writer::tag(
            'span',
            $status,
            ['class' => "badge badge-{$statusclass}"]
        );

        // Action links.
        $actions = '';
        if ($session->courseid) {
            $actions .= html_writer::link(
                new moodle_url('/course/view.php', ['id' => $session->courseid]),
                get_string('view_course', 'local_courseagent'),
                ['class' => 'btn btn-sm btn-outline-primary mr-2']
            );
        }

        $table->data[] = [$date, $title, $statusbadge, $actions];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
