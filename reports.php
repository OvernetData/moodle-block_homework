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
 * Reports page
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . "/../../config.php");
require_once("edulink_classes/form_page_base.php");
require_once("edulink_classes/controls.php");
require_once("edulink_classes/moodle.php");

use block_homework\local\edulink as e;

class block_homework_view_reports_page extends e\block_homework_form_page_base {

    private $chartwidth = 550;
    private $chartheight = 300;

    public static function factory() {
        return new block_homework_view_reports_page();
    }

    public function get_title() {
        $viewhomework = $this->get_str('viewhomeworkreports');
        if ($this->onfrontpage) {
            $viewhomework .= $this->get_str('onallcourses');
        } else {
            $viewhomework .= $this->get_str('oncourse', $this->course->shortname);
        }
        return $viewhomework;
    }

    public function get_heading() {
        return null;
    }

    public function get_content() {
        $this->set_scripts();
        $this->set_stylesheets();
        $usertype = block_homework_moodle_utils::get_user_type($this->userid);
        if ($usertype == "employee") {
            $form = $this->get_form_settings();
            return $this->get_form($form, false, false);
        } else {
            $label = new e\htmlLabel('label-warning', $this->get_str('nopermission'));
            return $label->get_html();
        }
    }

    protected function get_form_settings() {
        global $USER;

        $form = array();

        $stafftab = '<div class="ond_row"><div class="ond_col"><h2>' . $this->get_str('assignmentssetpermonth') . '</h2>'
                . '<canvas id="mychart1" width="' . $this->chartwidth . '" height="' . $this->chartheight . '"></canvas></div>'
                . '<div class="ond_col"><h2>' . $this->get_str('assignmentsgradedpermonth') . '</h2>'
                . '<canvas id="mychart2" width="' . $this->chartwidth . '" height="' . $this->chartheight . '"></canvas></div>'
                . '<div class="ond_col"><h2>' . $this->get_str('assignmentssetpergroup') . '</h2>'
                . '<canvas id="mychart3" width="' . $this->chartwidth . '" height="' . $this->chartheight . '"></canvas></div>'
                . '</div>';

        $useroptions = block_homework_moodle_utils::get_teacher_users();

        $year = date('Y');
        if (date('m') >= 9) {
            $year++;
        }
        $form[$this->get_str('staffusage')] = array(
            'course' => array('type' => 'hidden', 'value' => $this->courseid),
            'sesskey' => array('type' => 'hidden', 'value' => sesskey()),
            'user' => array('type' => 'select', 'prompt' => $this->get_str('staffmember'), 'options' => $useroptions,
                'value' => $USER->id),
            'from_staff' => array('type' => 'date', 'prompt' => $this->get_str('from'), 'default' => ($year - 1) . '-09-01',
                'required' => true, 'include_tomorrow_button' => false, 'include_next_week_button' => false),
            'to_staff' => array('type' => 'date', 'prompt' => $this->get_str('to'), 'default' => date($year . '-08-31'),
                'required' => true, 'include_tomorrow_button' => false, 'include_next_week_button' => false),
            'tab1' => array('type' => 'static', 'content' => $stafftab)
        );

        $groupoptions = block_homework_moodle_utils::get_groups();

        $grouptab = $this->get_str('groupgrades');
        if (empty($groupoptions)) {
            $form[$grouptab] = array(
                'nogroup' => array('type' => 'label-warning', 'value' => $this->get_str('nogroups'))
            );
        } else {
            $grouptablehtml = '<br /><h2>' . $this->get_str('groupmembergrades') . '</h2>'
                    . '<div id="grouptableholder"><div id="groupgrades_loading" class="ond_ajax_loading_big">' .
                    $this->get_str('loadingdata') . '</div><div id="groupgrades_loaded"></div></div>';
            $form[$grouptab] = array(
                'group' => array('type' => 'select', 'prompt' => $this->get_str('group'), 'options' => $groupoptions),
                'from_group' => array('type' => 'date', 'prompt' => $this->get_str('from'),
                    'default' => ($year - 1) . '-09-01', 'required' => true, 'include_tomorrow_button' => false,
                    'include_next_week_button' => false),
                'to_group' => array('type' => 'date', 'prompt' => $this->get_str('to'),
                    'default' => date($year . '-08-31'), 'required' => true, 'include_tomorrow_button' => false,
                    'include_next_week_button' => false),
                'tab2' => array('type' => 'static', 'content' => $grouptablehtml)
            );
        }

        $studenttab = '<br /><h2>' . $this->get_str('studentgrades') . '</h2>' .
                '<div id="studenttableholder"><h4 id="selectstudentmessage">' . $this->get_str('pleaseselectastudent') . '</h4>' .
                '<div id="studentgrades_loading" class="ond_ajax_loading_big" style="display:none;">' .
                $this->get_str('loadingdata') . '</div><div id="studentgrades_loaded"></div></div>';
        $studentoptions = array();

        $form[$this->get_str('studentgrades')] = array(
            'student' => array('type' => 'select', 'prompt' => $this->get_str('student'), 'options' => $studentoptions),
            'from_student' => array('type' => 'date', 'prompt' => $this->get_str('from'),
                'default' => ($year - 1) . '-09-01', 'required' => true, 'include_tomorrow_button' => false,
                'include_next_week_button' => false),
            'to_student' => array('type' => 'date', 'prompt' => $this->get_str('to'),
                'default' => date($year . '-08-31'), 'required' => true, 'include_tomorrow_button' => false,
                'include_next_week_button' => false),
            'tab3' => array('type' => 'static', 'content' => $studenttab)
        );

        $schooltab = '<div class="ond_row"><div class="ond_col"><h2>' . $this->get_str('assignmentssetpersubject') . '</h2>'
                . '<canvas id="mychart4" width="' . $this->chartwidth . '" height="' . $this->chartheight . '"></canvas>'
                . '</div><div class="ond_col"><h2>' . $this->get_str('assignmentssetperstaffmember') . '</h2>'
                . '<canvas id="mychart5" width="' . $this->chartwidth . '" height="' . $this->chartheight . '"></canvas>'
                . '</div></div>'
                . '<div class="clearfix"></div><h2>' . $this->get_str('staffstatistics') . '</h2>'
                . '<div id="staffstatisticstableholder"><div id="staffstatistics_loading" class="ond_ajax_loading_big">' .
                    $this->get_str('loadingdata') . '</div><div id="staffstatistics_loaded"></div></div>';
        $form[$this->get_str('subjectsandstaff')] = array(
            'from_school' => array('type' => 'date', 'prompt' => $this->get_str('from'),
                'default' => ($year - 1) . '-09-01', 'required' => true, 'include_tomorrow_button' => false,
                'include_next_week_button' => false),
            'to_school' => array('type' => 'date', 'prompt' => $this->get_str('to'), 'default' => date($year . '-08-31'),
                'required' => true, 'include_tomorrow_button' => false, 'include_next_week_button' => false),
            'tab4' => array('type' => 'static', 'content' => $schooltab)
        );

        return $form;
    }

    protected function set_scripts() {
        global $PAGE;
        parent::set_scripts();
        $PAGE->requires->js_call_amd('block_homework/reports', 'start', array($this->edulinkpresent));
    }

    protected function set_stylesheets() {
        global $CFG;
        parent::set_stylesheets();
        $path = $CFG->wwwroot . '/' . $this->blockid . '/style/';
        $this->use_stylesheet($path . 'datatables.css');
    }

}

block_homework_view_reports_page::factory();