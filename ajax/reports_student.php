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
 * Return student report data, used by reports.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../edulink_classes/controls.php");
require_once("ajaxbase.php");

use block_homework\local\edulink as e;

class ajaxgen_reports_student extends ajaxgen_base {

    public static function factory() {
        return new ajaxgen_reports_student();
    }

    public function __construct() {
        $course = optional_param('course', get_site()->id, PARAM_INT);
        $student = optional_param('student', 0, PARAM_INT);
        $from = optional_param('from', date('Y-01-01'), PARAM_ALPHANUMEXT);
        $to = optional_param('to', date('Y-m-d'), PARAM_ALPHANUMEXT);

        $from = strtotime($from);
        $to = strtotime($to);
        if (($from == 0) || ($to == 0)) {
            die(json_encode($this->get_str("invaliddatesupplied")));
        }
        if ($student == 0) {
            die(json_encode($this->get_str("invalidstudentsupplied")));
        }

        $html = '';

        /*
         * 1. get list of assignments for specified course(s) and group/date range
         * 2. get grades for each group member for each assignment
         * 3. fill in grid
         */

        $assignments = block_homework_moodle_utils::get_assignments_for_group($course, 0, $from, $to);
        if (!empty($assignments)) {
            $table = new e\htmlTable('studentgrades');
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('issued')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('due')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('course')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('assignment')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('duration')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('status')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('grade')));
            $table->add_header(new e\htmlTableHeader('', '', $this->get_str('feedback')));

            foreach ($assignments as $assignment) {
                if (!block_homework_moodle_utils::user_is_assignment_participant($student, $assignment->id)) {
                    continue;
                }
                $table->add_row();

                $issuedcell = new e\htmlTableCell('', '',  block_homework_utils::format_date($assignment->availabledate));
                $issuedcell->set_property('data-sort', block_homework_utils::date_for_sorting($assignment->availabledate));
                $table->add_cell($issuedcell);

                $duecell = new e\htmlTableCell('', '', block_homework_utils::format_date($assignment->duedate));
                $duecell->set_property('data-sort', block_homework_utils::date_for_sorting($assignment->duedate));
                $table->add_cell($duecell);

                $coursename = $assignment->coursename;
                if ($assignment->subject != '') {
                    $coursename .= ' (' . $assignment->subject . ')';
                }
                $table->add_cell(new e\htmlTableCell('', '', $coursename));

                $table->add_cell(new e\htmlTableCell('', '', $assignment->assignmentname));

                $durationcell = new e\htmlTableCell('', '', block_homework_utils::get_duration_description($assignment->duration));
                $durationcell->set_property('data-sort', empty($assignment->duration) ? 0 : $assignment->duration);
                $table->add_cell($durationcell);

                $status = block_homework_moodle_utils::get_assignment_status($assignment->id, $student);

                $table->add_cell(new e\htmlTableCell('', '', $status->status));

                $table->add_cell(new e\htmlTableCell('', '', $status->grade));

                $table->add_cell(new e\htmlTableCell('', '', $status->feedback));
            }
            $html = '<div id="studentgrades_loading" class="ond_ajax_loading_big">' . $this->get_str('loadingdata') . '</div>';
            $html .= '<div id="studentgrades_loaded" style="display:none;">';
            $html .= $table->get_html();
            $html .= '</div>';
        } else {
            $label = new e\htmlLabel('label-warn', $this->get_str('noassignmentsfound'));
            $html .= $label->get_html();
        }

        $output = array('html' => $html);
        print json_encode($output);
    }
}

require_login();
require_sesskey();
ajaxgen_reports_student::factory();