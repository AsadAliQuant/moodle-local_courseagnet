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
 * Injects "Create with AI" button alongside Moodle's course action buttons on my/courses.php.
 *
 * Handles both render paths:
 *  - Has-courses: header action dropdown (#newcourseform / #managecoursesform).
 *  - Empty state: block_myoverview zero-state action bar (#action_bar).
 *
 * Uses a MutationObserver because block_myoverview is Reactive and re-renders the
 * centre region on filter/sort/view changes.
 *
 * @module     local_courseagent/mycourses_buttons
 * @package    local_courseagent
 * @copyright  2026 Course Agent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        /**
         * @param {Object} params
         * @param {string} params.url   URL for the AI course creator
         * @param {string} params.label Button label text
         */
        init: function(params) {
            var FLAG = 'data-courseagent-injected';

            var buildForm = function(extraClasses) {
                var form = document.createElement('form');
                form.method = 'get';
                form.action = params.url;
                form.className = extraClasses || '';
                form.setAttribute(FLAG, '1');
                var btn = document.createElement('button');
                btn.type = 'submit';
                btn.className = 'btn btn-outline-info m-1';
                btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2" aria-hidden="true"></i>' + params.label;
                form.appendChild(btn);
                return form;
            };

            var insert = function() {
                if (document.querySelector('[' + FLAG + ']')) {
                    return;
                }

                var newcourseform = document.getElementById('newcourseform');
                if (newcourseform && newcourseform.parentNode) {
                    newcourseform.parentNode.insertBefore(
                        buildForm('m-1'),
                        newcourseform.nextSibling
                    );
                    return;
                }

                var manageform = document.getElementById('managecoursesform');
                if (manageform && manageform.parentNode) {
                    manageform.parentNode.insertBefore(
                        buildForm('m-1'),
                        manageform.nextSibling
                    );
                    return;
                }

                var actionbar = document.getElementById('action_bar');
                if (actionbar) {
                    actionbar.appendChild(buildForm(''));
                    return;
                }
            };

            insert();
            setTimeout(insert, 200);

            var observer = new MutationObserver(function() {
                if (!document.querySelector('[' + FLAG + ']')) {
                    insert();
                }
            });
            observer.observe(document.body, {childList: true, subtree: true});
        }
    };
});
