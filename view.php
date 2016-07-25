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
 * View homework assignment listing page
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . "/../../config.php");
require_once("edulink_classes/form_page_base.php");
require_once("edulink_classes/moodle.php");
require_once("edulink_classes/homework.php");
require_once("edulink_classes/controls.php");
require_once($CFG->dirroot . "/mod/assign/lib.php");
require_once($CFG->dirroot . "/mod/assign/externallib.php");
require_once($CFG->dirroot . "/lib/modinfolib.php");

use block_homework\local\edulink as e;

class block_homework_view_page extends e\block_homework_form_page_base {

    protected $userid;
    protected $usertype = "";
    protected $children = array();

    public static function factory() {
        return new block_homework_view_page();
    }

    public function get_title() {
        global $USER;

        if (optional_param('mark', 0, PARAM_INT) == 1) {
            $title = $this->get_str('markhomework');
        } else {
            $title = $this->get_str('viewhomework');
        }

        $this->userid = $USER->id;
        $this->usertype = block_homework_moodle_utils::get_user_type($this->userid);
        if ($this->usertype == "employee") {
            $title .= $this->get_str('teacherview');
        } else if ($this->usertype == "learner") {
            $title .= $this->get_str('studentview');
        } else if ($this->usertype == "parent") {
            $this->children = HomeworkAccess::get_children($this->userid);
            $title .= $this->get_str('parentview');
        } else {
            $title .= $this->get_str('userview');
        }
        return $title;
    }

    public function get_content() {
        global $USER;
        $this->set_stylesheets();
        $context = context_course::instance($this->courseid);
        if ($this->onfrontpage) {
            $courses = block_homework_moodle_utils::get_users_courses($USER->id);
            if (!empty($courses)) {
                $context = context_course::instance(reset($courses)->id);
            }
        }
        $permission = ($this->usertype == "parent") || ($this->usertype == "employee") || ($this->usertype == "learner");
        if ($permission) {
            $form = $this->get_form_settings();
            if (is_array($form)) {
                $this->set_scripts();
                return $this->get_form($form, false, false);
            } else {
                return $form;
            }
        } else {
            $label = new e\htmlLabel('label-warning', $this->get_str('nopermission'));
            return $label->get_html();
        }
    }

    protected function get_form_settings() {
        $htmllist = '<div id="ond_homework_list_loading" class="ond_ajax_loading_big">' . $this->get_str('loadingdata') . '</div>';
        $htmllist .= '<div id="ond_homework_list_loaded" style="display:none;"></div>';

        $form = array();
        $listtab = $this->get_str('listview');
        $myorchilduserid = $this->userid;
        if (count($this->children) > 0) {
            reset($this->children);
            $myorchilduserid = key($this->children);
        }
        $date = date('Y-m-d');
        if ($this->edulinkpresent) {
            $date = HomeworkAccess::get_effective_date();
        }
        $date = strtotime("last monday", strtotime("next monday", strtotime($date)));
        $form[$listtab] = array(
            'user' => array('type' => 'hidden', 'value' => $this->userid),
            'displayuser' => array('type' => 'hidden', 'value' => $myorchilduserid),
            'usertype' => array('type' => 'hidden', 'value' => $this->usertype),
            'course' => array('type' => 'hidden', 'value' => $this->courseid),
            'sesskey' => array('type' => 'hidden', 'value' => sesskey()),
            'marking' => array('type' => 'hidden', 'value' => optional_param('mark', 0, PARAM_INT)),
            'date' => array('type' => 'hidden', 'value' => $date));
        if ($this->usertype == "parent") {
            $form[$listtab]['user1'] = array('type' => 'select', 'prompt' => 'Child', 'options' => $this->children);
        }
        $form[$listtab]['listview'] = array('type' => 'static', 'value' => $htmllist);

        $timetabletab = $this->get_str('timetableview');
        $form[$timetabletab] = array();
        if ($this->edulinkpresent) {
            unset($form[$listtab]["date"]);
            $dateoptions = array();
            for ($i = 0; $i < 5; $i++) {
                $dateoptions[date('Y-m-d', $date)] = 'Week commencing ' . date('D jS M Y', $date);
                $date = strtotime("next monday", $date);
            }
            $form[$timetabletab]['date'] = array('type' => 'select', 'prompt' => $this->get_str('date'), 'options' => $dateoptions);
            if ($this->usertype == "parent") {
                $form[$timetabletab]['user2'] = array('type' => 'select', 'prompt' => 'Child', 'options' => $this->children);
            }
            $htmltimetable = '<div id="ond_homework_timetable_loading" class="ond_ajax_loading_big">' .
                    $this->get_str('loadingdata') . '</div>';
            $htmltimetable .= '<div id="ond_homework_timetable_loaded" style="display:none;"></div>';
        } else {
            $label = new e\htmlLabel('label-info', $this->get_str('edulinkfeatureonly'));
            $htmltimetable = $label->get_html();
        }
        $form[$timetabletab]['timetableview'] = array('type' => 'static', 'value' => $htmltimetable);

        return $form;
    }

    protected function set_scripts() {
        global $PAGE;
        parent::set_scripts();
        $PAGE->requires->js_call_amd('block_homework/view', 'start');
    }

    protected function set_stylesheets() {
        global $CFG;
        parent::set_stylesheets();
        $path = $CFG->wwwroot . '/' . $this->blockid . '/style/';
        $this->use_stylesheet($path . 'datatables.css');
    }

}

block_homework_view_page::factory();