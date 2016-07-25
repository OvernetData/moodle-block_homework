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
 * Return school report data, used by reports.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../edulink_classes/controls.php");
require_once("ajaxbase.php");

use block_homework\local\edulink as e;

class ajaxgen_reports_school extends ajaxgen_base {

    public static function factory() {
        return new ajaxgen_reports_school();
    }

    public function __construct() {
        global $USER;
        $from = optional_param('from', date('Y-01-01'), PARAM_ALPHANUMEXT);
        $to = optional_param('to', date('Y-m-d'), PARAM_ALPHANUMEXT);
        $course = optional_param('course', get_site()->id, PARAM_INT);

        $from = strtotime($from);
        $to = strtotime($to);
        if (($from == 0) || ($to == 0)) {
            die(json_encode($this->get_str('invaliddatesupplied')));
        }

        $stats = block_homework_utils::get_homework_statistics($from, $to, $course);

        $bysubject = array();
        $byuser = array();

        foreach ($stats as $stat) {
            $subject = ucfirst(strtolower($stat["subject"]));
            if ($subject == '') {
                $subject = $this->get_str('nosubjectspecified', $stat["coursename"]);
            }
            if (!isset($bysubject[$subject])) {
                $bysubject[$subject] = 0;
            }
            $bysubject[$subject]++;

            $userid = $stat["userid"];
            if (!isset($byuser[$userid])) {
                $byuser[$userid] = array('set' => 0, 'graded' => 0, 'participants' => 0,
                    'firstname' => $stat["firstname"], 'lastname' => $stat["lastname"],
                    'fullname' => $stat["fullname"]);
            }
            $byuser[$userid]['set']++;
            $byuser[$userid]['graded'] += block_homework_moodle_utils::get_assignment_graded_submission_count(
                $stat["coursemoduleid"], $userid);
            $byuser[$userid]['participants'] += count(block_homework_moodle_utils::get_assignment_participants(
                $stat["coursemoduleid"]));
        }

        $chart4 = array();
        foreach ($bysubject as $subject => $count) {
            $colour = $this->random_pastel_rgb();
            $chart4[] = array('value' => $count,
                            'color' => $this->rgba($colour, 0.8),
                            'highlight' => $this->rgba($colour, 0.6),
                            'label' => $subject);
        }

        $chart5 = array();
        foreach ($byuser as $user) {
            $colour = $this->random_pastel_rgb();
            $chart5[] = array('value' => $user['set'],
                            'color' => $this->rgba($colour, 0.8),
                            'highlight' => $this->rgba($colour, 0.6),
                            'label' => $user["fullname"] == '' ? $this->get_str('nottracked') : $user["fullname"]);
        }

        $table = new e\htmlTable('staffstatistics');
        $table->add_header(new e\htmlTableHeader('', '', $this->get_str('staffmember')));
        $table->add_header(new e\htmlTableHeader('', '', $this->get_str('assignmentsset')));
        $table->add_header(new e\htmlTableHeader('', '', $this->get_str('submissionsgraded')));
        foreach ($byuser as $user) {
            $table->add_row();
            $namecell = new e\htmlTableCell('', '', $user["fullname"] == '' ? $this->get_str('nottrackedfull') : $user["fullname"]);
            $namecell->set_property('data-sort', $user["lastname"] . " " . $user["firstname"]);
            $table->add_cell($namecell);
            $table->add_cell(new e\htmlTableCell('', '', $user["set"]));
            $gradedcell = new e\htmlTableCell('', '', $user["graded"] . " / " . $user["participants"]);
            $gradedcell->set_property('data-sort', $user["graded"]);
            $table->add_cell($gradedcell);
        }
        $html = '<div id="staffstatistics_loading" class="ond_ajax_loading_big">' . $this->get_str('loadingdata') . '</div>';
        $html .= '<div id="staffstatistics_loaded" style="display:none;">';
        $html .= $table->get_html();
        $html .= '</div>';
        $chart = array('chart4' => $chart4, 'chart5' => $chart5, 'html' => $html, 'stats' => $stats);

        print json_encode($chart);
    }
}

require_login();
require_sesskey();
ajaxgen_reports_school::factory();