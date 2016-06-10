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
 * Return staff report data, used by reports.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once("ajaxbase.php");

class ajaxgen_reports_staff extends ajaxgen_base {

    public static function factory() {
        return new ajaxgen_reports_staff();
    }

    public function __construct() {
        global $USER;
        $from = optional_param('from', date('Y-01-01'), PARAM_ALPHANUMEXT);
        $to = optional_param('to', date('Y-m-d'), PARAM_ALPHANUMEXT);
        $user = optional_param('user', $USER->id, PARAM_INT);
        $course = optional_param('course', get_site()->id, PARAM_INT);

        $from = strtotime($from);
        $to = strtotime($to);
        if (($from == 0) || ($to == 0)) {
            die(json_encode($this->get_str('invaliddatesupplied')));
        }

        $yearfrom = date('Y', $from);
        $yearto = date('Y', $to);
        $monthfrom = date('m', $from);
        $monthto = date('m', $to);

        $bymonth = array();
        $gradedbymonth = array();

        $y = $yearfrom;
        $m = $monthfrom;
        do {
            $dateid = $y . str_pad($m, 2, '0', STR_PAD_LEFT);
            $bymonth[$dateid] = 0;
            $gradedbymonth[$dateid] = 0;
            $m++;
            if ($m == 13) {
                $m = 1;
                $y++;
            }
        } while ($dateid < $yearto . $monthto);

        $stats = block_homework_utils::get_homework_statistics($from, $to, $course, $user);

        $bygroup = array();
        foreach ($stats as $stat) {
            $dateid = date('Ym', $stat["added"]);
            if (!isset($bymonth[$dateid])) {
                $bymonth[$dateid] = 1;
            } else {
                $bymonth[$dateid]++;
            }

            if (!isset($gradedbymonth[$dateid])) {
                $gradedbymonth[$dateid] = 0;
            }
            $gradedbymonth[$dateid] += block_homework_moodle_utils::get_assignment_graded_submission_count(
                $stat["coursemoduleid"], $user);

            $a = json_decode($stat["availability"]);
            // Only detangle availability structure if it's the simple 'any of these groups' variation.
            $show = (isset($a->show) && ($a->show)) || ((isset($a->showc) && ($a->showc))) && (isset($a->op));
            if ($show && ((($a->op == "&") && (count($a->c) == 1)) || ($a->op == "|")) ) {
                foreach ($a->c as $condition) {
                    if ($condition->type == 'group') {
                        if (!isset($bygroup[$condition->id])) {
                            $bygroup[$condition->id] = array('name' => groups_get_group_name($condition->id), 'count' => 0);
                        }
                        $bygroup[$condition->id]['count']++;
                    }
                }
            } else {
                if (!isset($bygroup[0])) {
                    $bygroup[0] = array('name' => $stat["availability"], 'name2' => 'Unspecified', 'count' => 0);
                }
                $bygroup[0]['count']++;
            }
        }

        /* chartjs */
        $monthnames = array();
        $homeworkcounts = array();
        foreach ($bymonth as $dateid => $count) {
            $dateid = DateTime::createFromFormat('!m', substr($dateid, 4, 2))->format('M') . ' ' . substr($dateid, 0, 4);
            $monthnames[] = $dateid;
            $homeworkcounts[] = $count;
        }

        $colour = $this->random_pastel_rgb();
        $chart1 = array(
            'labels' => $monthnames,
            'datasets' => array(
                array(
                    'label' => $this->get_str('assignmentsset'),
                    'strokeColor' => $this->rgba($colour, 0.8),
                    'fillColor' => $this->rgba($colour, 0.6),
                    'data' => $homeworkcounts
                )
            )
        );

        $gradecounts = array();
        foreach ($gradedbymonth as $count) {
            $gradecounts[] = $count;
        }
        $colour = $this->random_pastel_rgb();
        $chart2 = array(
            'labels' => $monthnames,
            'datasets' => array(
                array(
                    'label' => $this->get_str('assignmentsgraded'),
                    'strokeColor' => $this->rgba($colour, 0.8),
                    'fillColor' => $this->rgba($colour, 0.6),
                    'data' => $gradecounts
                )
            )
        );

        $chart3 = array();
        foreach ($bygroup as $group) {
            $colour = $this->random_pastel_rgb();
            $chart3[] = array('value' => $group["count"],
                            'color' => $this->rgba($colour, 0.8),
                            'highlight' => $this->rgba($colour, 0.6),
                            'label' => $group["name"] == '' ? $this->get_str('notspecified') : $group["name"]);
        }

        $chart = array('chart1' => $chart1, 'chart2' => $chart2, 'chart3' => $chart3);

        print json_encode($chart);
    }
}

require_login();
require_sesskey();
ajaxgen_reports_staff::factory();