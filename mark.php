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
 * Mark assignment page
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

class block_homework_mark_page extends e\block_homework_form_page_base {

    protected $cmid = 0;
    protected $assignment;
    protected $behaviourpoints, $achievementpoints;

    public static function factory() {
        return new block_homework_mark_page();
    }

    public function get_title() {
        $title = $this->get_str('markhomework');
        $this->cmid = optional_param('id', 0, PARAM_INT);
        if ($this->cmid != 0) {
            $this->assignment = block_homework_moodle_utils::get_assignment($this->cmid);
            $title = $this->get_str('mark') . ' ' . $this->assignment->name;
        }
        return $title;
    }

    protected function set_navigation() {
        global $CFG, $PAGE;
        $PAGE->navbar->add($this->get_str('markhomework'), $CFG->wwwroot . '/blocks/homework/view.php?course=' .
                $this->courseid . '&mark=1');
        parent::set_navigation();
    }

    public function get_content() {
        global $CFG;
        $this->set_stylesheets();
        $context = context_course::instance($this->courseid);
        if ($this->onfrontpage) {
            $label = new e\htmlLabel('label-warning', $this->get_str('invalidcourse'));
            return $label->get_html();
        }
        if (has_capability('mod/assign:grade', $context)) {
            if ($this->cmid == 0) {
                $label = new e\htmlLabel('label-warning', $this->get_str('invalidcmid'));
                return $label->get_html();
            } else if (!empty($this->assignment->unsupported)) {
                $button = new e\htmlHyperlink('', $this->get_str("backtomarkinglist"), $CFG->wwwroot .
                        '/blocks/homework/view.php?course=' . $this->course->id . '&mark=1');
                $button->set_class('ond_material_button_raised');
                $params = array('features' => implode(', ', $this->assignment->unsupported), 'buttons' => $button->get_html());
                $div = new e\htmlDiv(null, null, $this->get_str('incompatibleassignment', (object) $params));
                return $div->get_html();
            } else {
                $form = $this->get_form_settings();
                if (is_array($form)) {
                    $this->set_scripts();
                    return $this->get_form($form);
                } else {
                    return $form;
                }
            }
        } else {
            $label = new e\htmlLabel('label-warning', $this->get_str('nopermission'));
            return $label->get_html();
        }
    }

    protected function get_form_settings() {
        global $CFG, $USER;
        $form = array();
        $markingtab = $this->get_str('marking');
        $form[$markingtab]['id'] = array('type' => 'hidden', 'value' => $this->cmid);
        $form[$markingtab]['course'] = array('type' => 'hidden', 'value' => $this->assignment->courseid);
        $form[$markingtab]['sesskey'] = array('type' => 'hidden', 'value' => sesskey());

        $table = new e\htmlTable('ond_assign_marking');
        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('name')));
        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('group')));
        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('status')));
        if (($this->assignment->textsubmissionenabled) || ($this->assignment->filesubmissionenabled)) {
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('submission')));
        }

        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('grade')));
        if ($this->assignment->feedbackenabled) {
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('feedback')));
        }

        $notsubmitted = $this->get_str('notsubmitted');
        $users = block_homework_moodle_utils::get_assignment_participants_and_statuses($this->cmid);
        $learners = array();
        
        // one query instead of repeated use of groups_get_group_name
        $allgroupnames = block_homework_moodle_utils::get_groups();
        
        foreach ($users as $user) {
            $learners[] = $user->userid;
            $table->add_row();
            $userlink = new e\htmlHyperlink(null, $user->firstname . ' ' . $user->lastname, $CFG->wwwroot . '/user/view.php?id=' .
                    $user->userid . '&course=' . $this->courseid);
            $userlink->set_class("");
            $namecell = new e\htmlTableCell(null, null, $userlink->get_html());
            $namecell->set_property('data-sort', $user->lastname . ' ' . $user->firstname);
            $table->add_cell($namecell);
            $groups = groups_get_user_groups($this->assignment->courseid, $user->userid);
            $allgroups = $groups['0'];
            $groupnames = '';
            foreach($allgroups as $groupid) {
                $groupnames .= ($groupnames == '') ? '' : ', ';
                if (isset($allgroupnames[$groupid])) {
                    $groupnames .= $allgroupnames[$groupid];
                } else {
                    $groupname = groups_get_group_name($groupid);
                    $allgroupnames[$groupid] = $groupname;
                    $groupnames .= $groupname;
                }
            }
            $groupscell = new e\htmlTableCell(null, null, $groupnames);
            $table->add_cell($groupscell);
            $table->add_cell(new e\htmlTableCell(null, null, $user->status));

            if (($this->assignment->textsubmissionenabled) || ($this->assignment->filesubmissionenabled)) {
                $submissionstuff = $user->submissiontext;
                if ($user->submissionnumfiles > 0) {
                    $submissionstuff .= $user->submissionfiles;
                }
                $subcell = new e\htmlTableCell(null, null, $submissionstuff);
                if ($user->submissionnumfiles > 0) {
                    $subcell->set_property('data-for-export', $user->submissiontext . $this->get_str('plusfiles'));
                } else {
                    $subcell->set_property('data-for-export', $user->submissiontext);
                }
                $table->add_cell($subcell);
            }

            $gradeeditor = str_replace("class=\"quickgrade", "class=\"quickgrade ond_validate", $user->gradeeditor);
            $modified = new e\htmlHiddenInput('modified_' . $user->userid, 'modified_' . $user->userid, 0);
            $lname = new e\htmlHiddenInput('name_' . $user->userid, 'name_' . $user->userid, $user->firstname . ' ' .
                    $user->lastname);
            $extra = $modified->get_html() . $lname->get_html();
            if ($user->status == $notsubmitted) {
                $unsubmitted = new e\htmlHiddenInput('unsubmitted_' . $user->userid, 'unsubmitted_' . $user->userid, 1);
                $extra .= $unsubmitted->get_html();
            }
            $gradecell = new e\htmlTableCell(null, null, $gradeeditor . $extra);
            $gradecell->set_property('data-for-export', $user->grade);

            $table->add_cell($gradecell);

            if ($this->assignment->feedbackenabled) {
                $feedback = new e\htmlTextAreaInput('feedback_' . $user->userid, 'feedback_' . $user->userid, $user->feedback, '');
                $feedback->set_class('quickgrade ond_validate');
                $feedback->set_columns(50);
                $feedback->set_rows(2);
                $table->add_cell(new e\htmlTableCell(null, null, $feedback->get_html()));
            }
        }

        $htmltable = '<div id="ond_assign_marking_loading" class="ond_ajax_loading_big">' . $this->get_str('loadingdata') .
                '</div>';
        $htmltable .= '<div id="ond_assign_marking_loaded" style="display:none;">' . $table->get_html() . '</div>';

        $form[$markingtab]['learners'] = array('type' => 'hidden', 'value' => implode(',', $learners));
        $form[$markingtab]['marking-table'] = array('type' => 'static', 'value' => $htmltable);

        $behavetab = $this->get_str('achievementbehaviour');
        $form[$behavetab] = array();
        if ($this->edulinkpresent) {
            $staff = HomeworkAccess::get_staff_list();

            $this->achievementpoints = array();
            $achievementtypeoptions = array();
            $atypes = HomeworkAccess::achievement_types();
            $defaultachievementtype = '';
            foreach ($atypes as $type) {
                if ($defaultachievementtype == '') {
                    $defaultachievementtype = $type->id;
                }
                $achievementtypeoptions[$type->id] = $type->description;
                $this->achievementpoints[$type->id] = $type->points;
                if (stripos($type->description, 'work') !== false) {
                    $defaultachievementtype = $type->id;
                }
            }
            $achievementactivityoptions = array();
            $aatypes = HomeworkAccess::achievement_activity_types();
            $defaultachievementactivitytype = '';
            foreach ($aatypes as $type) {
                if ($defaultachievementactivitytype == '') {
                    $defaultachievementactivitytype = $type->id;
                }
                $achievementactivityoptions[$type->id] = $type->description;
                if (stripos($type->description, $this->assignment->subject) !== false) {
                    $defaultachievementactivitytype = $type->id;
                }
            }
            // Are we a member of staff? if not, present a dropdown to select one to report as.
            if (isset($staff[$USER->id])) {
                $achievementreporter = array('type' => 'hidden', 'value' => $USER->id);
            } else {
                $achievementreporter = array('prompt' => $this->get_str('achievementreporter'), 'type' => 'select',
                    'options' => $staff);
            }
            $form[$behavetab]['bulkachievement'] = array('type' => 'switch', 'prompt' => $this->get_str('bulkachievementwriteback'),
                'default' => false,
                'subgroup_if_on' => array(
                    'achievementtype' => array('prompt' => $this->get_str('behaviourtype'), 'type' => 'select',
                        'options' => $achievementtypeoptions, 'default' => $defaultachievementtype),
                    'achievementactivity' => array('prompt' => $this->get_str('achievementactivity'), 'type' => 'select',
                        'options' => $achievementactivityoptions, 'default' => $defaultachievementactivitytype),
                    'achievementcomments' => array('prompt' => $this->get_str('achievementcomments'), 'type' => 'memo',
                        'columns' => 80, 'rows' => 5, 'required' => true),
                    'achievementpoints' => array('prompt' => $this->get_str('achievementpoints'), 'type' => 'int', 'value' => 0,
                        'required' => true),
                    'achievementreporter' => $achievementreporter
                )
            );

            $this->behaviourpoints = array();
            $behaviourtypeoptions = array();
            $btypes = HomeworkAccess::behaviour_types();
            $defaultbehaviourtype = '';
            foreach ($btypes as $type) {
                if ($defaultbehaviourtype == '') {
                    $defaultbehaviourtype = $type->id;
                }
                $behaviourtypeoptions[$type->id] = $type->description;
                $this->behaviourpoints[$type->id] = $type->points;
                if (stripos($type->description, 'homework') !== false) {
                    $defaultbehaviourtype = $type->id;
                }
            }
            $behaviouractivityoptions = array();
            $batypes = HomeworkAccess::behaviour_activity_types();
            $defaultactivitytype = '';
            foreach ($batypes as $type) {
                if ($defaultactivitytype == '') {
                    $defaultactivitytype = $type->id;
                }
                $behaviouractivityoptions[$type->id] = $type->description;
                if (stripos($type->description, $this->assignment->subject) !== false) {
                    $defaultactivitytype = $type->id;
                }
            }
            $behaviourstatusoptions = array();
            $stypes = HomeworkAccess::behaviour_statuses();
            foreach ($stypes as $type) {
                $behaviourstatusoptions[$type->id] = $type->description;
            }
            // Are we a member of staff? if not, present a dropdown to select one to report as.
            if (isset($staff[$USER->id])) {
                $behaviourreporter = array('type' => 'hidden', 'value' => $USER->id);
            } else {
                $behaviourreporter = array('prompt' => $this->get_str('behaviourreporter'), 'type' => 'select',
                    'options' => $staff);
            }
            $form[$behavetab]['bulkbehaviour'] = array('type' => 'switch', 'prompt' => $this->get_str('bulkbehaviourwriteback'),
                'default' => false,
                'subgroup_if_on' => array(
                    'behaviourtype' => array('prompt' => $this->get_str('behaviourtype'), 'type' => 'select',
                        'options' => $behaviourtypeoptions, 'default' => $defaultbehaviourtype),
                    'behaviouractivity' => array('prompt' => $this->get_str('behaviouractivity'), 'type' => 'select',
                        'options' => $behaviouractivityoptions, 'default' => $defaultactivitytype),
                    'behaviourstatus' => array('prompt' => $this->get_str('behaviourstatus'), 'type' => 'select',
                        'options' => $behaviourstatusoptions),
                    'behaviourcomments' => array('prompt' => $this->get_str('behaviourcomments'), 'type' => 'memo',
                        'columns' => 80, 'rows' => 5, 'required' => true),
                    'behaviourpoints' => array('prompt' => $this->get_str('behaviourpoints'), 'type' => 'int', 'value' => 0,
                        'required' => true),
                    'behaviourreporter' => $behaviourreporter
                )
            );
        } else {
            $label = new e\htmlLabel('label-info', $this->get_str('edulinkfeatureonly'));
            $form[$behavetab]['notavail'] = array('type' => 'static', 'value' => $label->get_html());
        }

        return $form;
    }

    protected function set_scripts() {
        global $PAGE;
        parent::set_scripts();
        $PAGE->requires->js_call_amd('block_homework/mark', 'start', array(array("achievement" => $this->achievementpoints,
            "behaviour" => $this->behaviourpoints)));
    }

    protected function set_stylesheets() {
        global $CFG;
        parent::set_stylesheets();
        $path = $CFG->wwwroot . '/' . $this->blockid . '/style/';
        $this->use_stylesheet($path . 'datatables.css');
    }

}

block_homework_mark_page::factory();