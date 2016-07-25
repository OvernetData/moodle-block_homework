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
 * View assignment details
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

class block_homework_view_assignment_page extends e\block_homework_form_page_base {

    protected $userid;
    protected $usertype = "";
    protected $children = array();
    protected $cmid;
    protected $assignment, $assignmentstatus;
    protected $canedit = 0;

    public static function factory() {
        return new block_homework_view_assignment_page();
    }

    public function get_title() {
        $this->cmid = optional_param('id', 0, PARAM_INT);
        $this->assignment = block_homework_moodle_utils::get_assignment($this->cmid);
        $title = $this->get_str('viewhomeworkitem');
        $this->usertype = block_homework_moodle_utils::get_user_type($this->userid);
        if ($this->usertype == "employee") {
            $title .= $this->get_str('teacherview');
            $context = context_module::instance($this->cmid);
            if (has_capability('mod/assign:addinstance', $context)) {
                $this->canedit = 1;
            }
        } else if ($this->usertype == "learner") {
            $title .= $this->get_str('studentview');
            $this->assignmentstatus = block_homework_moodle_utils::get_assignment_status($this->cmid, $this->userid);
        } else if ($this->usertype == "parent") {
            $title .= $this->get_str('parentview');
            $this->children = HomeworkAccess::get_children($this->userid);
        } else {
            $title .= $this->get_str('userview');
        }

        return $title;
    }

    public function get_content() {
        global $USER, $DB, $CFG;
        $this->set_stylesheets();
        $context = context_module::instance($this->cmid);
        if ($this->usertype == "parent") {
            $permission = false;
            foreach ($this->children as $childid => $childname) {
                $permission = has_capability('mod/assign:view', $context, $childid);
                if ($permission) {
                    break;
                }
            }
        } else {
            $permission = has_capability('mod/assign:view', $context);
        }
        if ($permission) {
            if (optional_param('action', '', PARAM_RAW) == 'markdone') {
                $userid = optional_param('user', 0, PARAM_INT);
                $course = optional_param('course', 0, PARAM_INT);
                $cmid = optional_param('id', 0, PARAM_INT);
                if (($userid != 0) && ($course != 0) && ($cmid != 0)) {
                    $checkexisting = array('coursemoduleid' => $cmid, 'userid' => $userid);
                    $existing = $DB->get_record('block_homework_item', $checkexisting);
                    if ($existing) {
                        $record = array('id' => $existing->id, 'completed' => 1);
                        $DB->update_record('block_homework_item', (object) $record);
                    } else {
                        $record = array('coursemoduleid' => $cmid, 'userid' => $userid, 'completed' => 1);
                        $DB->insert_record('block_homework_item', (object) $record);
                    }
                    $html = '<div class="ond_centered">';
                    $label = new e\htmlLabel('info', $this->get_str('assignmentmarkedasdone'));
                    $html .= $label->get_html() . '<br>';
                    $link = new e\htmlHyperlink('', $this->get_str('returntocourse'), $CFG->wwwroot . '/course/view.php?id=' .
                            $course, $this->get_str('returntocourse_title'));
                    $link->set_class('ond_material_button_raised');
                    $html .= $link->get_html();
                    $html .= '</div>';
                    return $html;
                }
            } else {
                $form = $this->get_form_settings();
                if (is_array($form)) {
                    $this->set_scripts();
                    $okbutton = false;
                    $cancelbutton = $this->get_str("returntocourse");
                    $coursecontext = context_course::instance($this->courseid);
                    if (!has_capability('moodle/course:view', $coursecontext)) {
                        $cancelbutton = $this->get_str("ok");
                    }
                    if ($this->usertype == "learner") {
                        if ($this->assignmentstatus->nosubmissionrequired && !$this->assignmentstatus->submitted &&
                                !$this->assignmentstatus->graded && !$this->assignmentstatus->completed) {
                            $okbutton = $this->get_str("markdone");
                        } else if ((!$this->assignmentstatus->submitted) && (!$this->assignmentstatus->graded) &&
                                (!$this->assignmentstatus->completed)) {
                            if (has_capability('mod/assign:submit', $context)) {
                                $okbutton = $this->get_str("addsubmission");
                            } else {
                                $okbutton = false;
                            }
                        }
                    } else if ($this->canedit) {
                        $okbutton = $this->get_str("edithomeworkitem");
                    }
                    return $this->get_form($form, $okbutton, $cancelbutton);
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
        global $CFG;

        $introtextdraftitemid = file_get_submitted_draft_itemid('introeditor');
        // $introadditionalfilesdraftitemid = file_get_submitted_draft_itemid('introattachment');
        // Now create a draft area (if drafitemid is 0 - in which case draftitemid is set to a new id)
        // or fetch the existing one (e.g. if in the process of saving the new/edited assignment).
        $itemid = 0;
        $asscontext = context_module::instance($this->cmid);
        // Both intro description editor and file uploader use draft area so set that up.
        $filemanageroptions = $this->get_file_uploader_options($asscontext, 0);
        file_prepare_draft_area($introtextdraftitemid, $asscontext->id, 'mod_assign', 'intro', $itemid, $filemanageroptions);
        // file_prepare_draft_area($introadditionalfilesdraftitemid, $asscontext->id, 'mod_assign', 'introattachment', $itemid,
        // $filemanageroptions);
        $assdesc = '<div class="ond_prompted">' . file_rewrite_pluginfile_urls($this->assignment->description, 'draftfile.php',
                context_user::instance($this->userid)->id, 'user', 'draft', $introtextdraftitemid) . '</div>';

        $assduedate = date('l jS F Y', $this->assignment->duedate);
        $assign = new assign(context_module::instance($this->cmid), null, null);
        $filetreehtml = $assign->render_area_files('mod_assign', 'introattachment', 0);

        $form = array();
        $detailstab = $this->get_str('details');

        $details = $this->assignment->subject;
        if ($details == '') {
            $details = $this->assignment->coursename;
        }
        $details = $this->assignment->name . ' (' . $details . '), ' . $this->get_str('dueonlc') . ' ' . $assduedate;

        $cancelurl = $CFG->wwwroot . '/course/view.php?id=' . $this->courseid;
        $coursecontext = context_course::instance($this->courseid);
        if (!has_capability('moodle/course:view', $coursecontext)) {
            $cancelurl = $CFG->wwwroot;
        }

        $form[$detailstab] = array(
            'action' => array('type' => 'hidden', 'value' => 'markdone'),
            'cancelurl' => array('type' => 'hidden', 'value' => $cancelurl),
            'user' => array('type' => 'hidden', 'value' => $this->userid),
            'course' => array('type' => 'hidden', 'value' => $this->courseid),
            'id' => array('type' => 'hidden', 'value' => $this->cmid),
            'canedit' => array('type' => 'hidden', 'value' => $this->canedit),
            'nosub' => array('type' => 'hidden', 'value' => !empty($this->assignmentstatus) &&
                $this->assignmentstatus->nosubmissionrequired ? 1 : 0),
            'details' => array('type' => 'static', 'prompt' => $this->get_str('details'), 'value' => $details),
            'activitydesc' => array('type' => 'static', 'prompt' => $this->get_str('description'), 'value' => $assdesc)
        );
        if (stripos($filetreehtml, 'pluginfile.php') !== false) {
            $filetreehtml = '<div class="ond_prompted">' . $filetreehtml . '</div>';
            $form[$detailstab]['files'] = array('type' => 'static', 'prompt' => $this->get_str('files'), 'value' => $filetreehtml);
        } else {
            // If we don't output the (empty, and invisible!) files tree html, a bit of Moodle javascript falls over as it expects
            // the tree to be there as we've called the tree rendering code. So, include the dead tree silently after the
            // description. If we don't, the script stops and things like the logout button at top right don't get setup correctly
            // and don't work.
            $form[$detailstab]['activitydesc']['value'] .= $filetreehtml;
        }

        if ($this->usertype == "employee") {
            $form[$detailstab]['participants'] = array('type' => 'static', 'prompt' => $this->get_str('participants'),
                'value' => count(block_homework_moodle_utils::get_assignment_participants($this->cmid)));
            if (!$this->assignment->nosubmissions) {
                $form[$detailstab]['graded'] = array('type' => 'static', 'prompt' => $this->get_str('gradedsubmissions'),
                    'value' => block_homework_moodle_utils::get_assignment_graded_submission_count($this->cmid));
                $form[$detailstab]['ungraded'] = array('type' => 'static', 'prompt' => $this->get_str('ungradedsubmissions'),
                    'value' => block_homework_moodle_utils::get_assignment_ungraded_submission_count($this->cmid));
            } else {
                $form[$detailstab]['markeddone'] = array('type' => 'static', 'prompt' => $this->get_str('markeddone'),
                    'value' => block_homework_moodle_utils::get_assignment_marked_done_count($this->cmid));
            }
        }

        if ($this->usertype == "learner") {
            $status = $this->assignmentstatus->status;
            if ($this->assignment->gradingenabled) {
                if ($this->assignmentstatus->graded) {
                    $status .= ' (' . $this->assignmentstatus->grade . ')';
                }
            }
            if (($this->assignment->feedbackenabled) && ($this->assignmentstatus->feedback != '')) {
                $status .= ', ' . $this->assignmentstatus->feedback;
            }
            $completedclass = '';
            if ($this->assignmentstatus->completed || $this->assignmentstatus->graded || $this->assignmentstatus->submitted) {
                $completedclass = ' ond_homework_item_completed';
            }
            $status = '<div class="ond_prompted' . $completedclass . '">' . $status . '</div>';
            $form[$detailstab]['status'] = array('type' => 'static', 'prompt' => $this->get_str('status'), 'value' => $status);
        }

        if (($this->usertype == "parent") && (!empty($this->children))) {
            $userids = array();
            foreach ($this->children as $childuserid => $childname) {
                $userids[] = $childuserid;
            }
            $assignmentstatuses = block_homework_moodle_utils::get_assignment_statuses($this->cmid, $userids);
            foreach ($this->children as $childuserid => $childname) {
                if (isset($assignmentstatuses[$childuserid])) {
                    $assignmentstatus = $assignmentstatuses[$childuserid];
                    $status = $assignmentstatus->status;
                    if ($assignmentstatus->graded) {
                        if ($this->assignment->gradingenabled) {
                            $status .= ' (' . $assignmentstatus->grade;
                            if (($this->assignment->feedbackenabled) && ($assignmentstatus->feedback != '')) {
                                $status .= ', ' . $assignmentstatus->feedback;
                            }
                            $status .= ')';
                        }
                    }
                    $status = '<div class="ond_prompted">' . $status . '</div>';
                    $form[$detailstab]['child' . $childuserid] = array('type' => 'static', 'prompt' => $this->get_str('child'),
                        'value' => $childname);
                    $form[$detailstab]['status' . $childuserid] = array('type' => 'static', 'prompt' => $this->get_str('status'),
                        'value' => $status);
                }
            }
        }

        return $form;
    }

    protected function get_file_uploader_options($context, $maxbytes) {
        return array('subdirs' => 0, 'maxbytes' => $maxbytes, 'context' => $context);
    }

    protected function set_scripts() {
        global $PAGE;
        parent::set_scripts();
        $PAGE->requires->js_call_amd('block_homework/assignment', 'start');
    }

}

block_homework_view_assignment_page::factory();