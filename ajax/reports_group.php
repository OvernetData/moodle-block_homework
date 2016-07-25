<?php
// This file is part of Moodle - http://moodle.org/
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
 * Return group report data, used by reports.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../edulink_classes/controls.php");
require_once("ajaxbase.php");

use block_homework\local\edulink as e;

class ajaxgen_reports_group extends ajaxgen_base {

    public static function factory() {
        return new ajaxgen_reports_group();
    }

    public function __construct() {
        $course = optional_param('course', get_site()->id, PARAM_INT);
        $group = optional_param('group', 0, PARAM_INT);
        $from = optional_param('from', date('Y-01-01'), PARAM_ALPHANUMEXT);
        $to = optional_param('to', date('Y-m-d'), PARAM_ALPHANUMEXT);

        $from = strtotime($from);
        $to = strtotime($to);
        if (($from == 0) || ($to == 0)) {
            die(json_encode($this->get_str('invaliddatesupplied')));
        }
        if ($group == 0) {
            die(json_encode($this->get_str('invalidgroupsupplied')));
        }

        $html = '';
        $groupmembers = block_homework_moodle_utils::get_group_members($group);
        if (!empty($groupmembers)) {

            /*
             * 1. get list of assignments for specified course(s) and group/date range
             * 2. get grades for each group member for each assignment
             * 3. fill in grid
             */

            $assignments = block_homework_moodle_utils::get_assignments_for_group($course, $group, $from, $to);
            if (!empty($assignments)) {

                // First get rid of any assignments that aren't for any of our group members.
                for ($i = count($assignments) - 1; $i >= 0; $i--) {
                    $assignment = $assignments[$i];
                    $noparticipants = true;
                    foreach ($groupmembers as $student) {
                        if (block_homework_moodle_utils::user_is_assignment_participant($student->id, $assignment->id)) {
                            $noparticipants = false;
                            break;
                        }
                    }
                    if ($noparticipants) {
                        unset($assignments[$i]);
                    }
                }

                $table = new e\htmlTable('groupgrades');
                $table->add_header(new e\htmlTableHeader('', '', $this->get_str("student")));
                foreach ($assignments as $assignment) {
                    $assheader = new e\htmlTableHeader('', '', block_homework_utils::format_date($assignment->duedate));
                    $title = '';
                    if ($course == get_site()->id) {
                        $title = $assignment->coursename . ', ';
                    }
                    $title .= $assignment->assignmentname;
                    $assheader->set_property('title', $title);
                    $table->add_header($assheader);
                }
                foreach ($groupmembers as $student) {
                    $table->add_row();
                    $namecell = new e\htmlTableCell('', '', $student->fullname);
                    $namecell->set_property('data-sort', $student->reversename);
                    $table->add_cell($namecell);
                    foreach ($assignments as $assignment) {
                        if (block_homework_moodle_utils::user_is_assignment_participant($student->id, $assignment->id)) {
                            $status = block_homework_moodle_utils::get_assignment_status($assignment->id, $student->id, false);
                            $grade = '';
                            if ($status->graded) {
                                $grade = str_replace('&nbsp;', ' ', $status->grade);
                            } else {
                                if ($assignment->duedate < time()) {
                                    $grade = $status->status;
                                }
                            }
                        } else {
                            $grade = $this->get_str('gradenotapplicable');
                        }
                        $table->add_cell(new e\htmlTableCell('', '', $grade));
                    }
                }
                $html = '<div id="groupgrades_loading" class="ond_ajax_loading_big">' . $this->get_str('loadingdata') . '</div>';
                $html .= '<div id="groupgrades_loaded" style="display:none;">';
                $html .= $table->get_html();
                $html .= '</div>';
            } else {
                $label = new e\htmlLabel('label-warn', $this->get_str('noassignmentsfound'));
                $html .= $label->get_html();
            }
        } else {
            $label = new e\htmlLabel('label-warn', $this->get_str('nogroupmembersfound'));
            $html .= $label->get_html();
        }

        $output = array('html' => $html);
        print json_encode($output);
    }
}

require_login();
require_sesskey();
ajaxgen_reports_group::factory();