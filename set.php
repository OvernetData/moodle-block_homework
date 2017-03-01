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
 * Set homework assignment page
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . "/../../config.php");
require_once("edulink_classes/form_page_base.php");
require_once("edulink_classes/moodle.php");
require_once("edulink_classes/homework.php");
require_once($CFG->dirroot . "/mod/assign/lib.php");
require_once($CFG->dirroot . "/mod/assign/externallib.php");
require_once($CFG->dirroot . "/lib/modinfolib.php");
require_once($CFG->dirroot . "/lib/formslib.php");
$extended = $CFG->dirroot . "/availability/condition/user/homework/utils.php";
if (file_exists($extended)) {
    require_once($extended);
}

use block_homework\local\edulink as e;

class block_homework_file_uploader_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $filemanageropts = $this->_customdata['filemanageropts'];
        $mform->addElement('filemanager', 'introattachment', '', null, $filemanageropts);
    }

}

class block_homework_text_editor_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $mform->addElement('editor', $this->_customdata['name'], '', array('rows' => 10),
            array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->_customdata['context'],
                'subdirs' => true)
            );
        $mform->setType($this->_customdata['name'], PARAM_RAW);
    }
}

class block_homework_set_page extends e\block_homework_form_page_base {

    protected $editingcmid;
    protected $assignment = null;

    public static function factory() {
        return new block_homework_set_page();
    }

    public function get_title() {
        $this->editingcmid = optional_param('edit', 0, PARAM_INT);
        if ($this->editingcmid == 0) {
            return $this->get_str('sethomework');
        } else {
            $this->assignment = block_homework_moodle_utils::get_assignment($this->editingcmid);
            $this->courseid = $this->assignment->courseid;
            $this->course = get_course($this->courseid);
            $this->onfrontpage = false;
            return $this->get_str('edithomework');
        }
    }

    public function get_content() {
        global $USER;
        $this->set_stylesheets();
        $context = context_course::instance($this->courseid);
        if ($this->onfrontpage) {
            $permission = false;
            $courses = block_homework_moodle_utils::get_users_courses($USER->id, 'mod/assign:addinstance');
            if (empty($courses)) {
                $label = new e\htmlLabel('label-warning', $this->get_str('nocoursesasteacher'));
                return $label->get_html();
            } else {
                $permission = true;
            }
        } else {
            $permission = has_capability('mod/assign:addinstance', $context);
        }
        if ($permission) {
            if (optional_param('action', '', PARAM_RAW) == 'save') {
                if (optional_param('selectcourse', '', PARAM_RAW) != '') {
                    $this->courseid = optional_param('selectcourse', '', PARAM_INT);
                    $this->course = get_course($this->courseid);
                    $this->onfrontpage = false;
                    return $this->set_homework_form();
                } else {
                    return $this->save_homework();
                }
            } else {
                return $this->set_homework_form();
            }
        } else {
            $label = new e\htmlLabel('label-warning', $this->get_str('nopermission'));
            return $label->get_html();
        }
    }

    protected function save_homework() {
        global $USER, $CFG, $DB;
        require_sesskey();
        $html = '<div class="ond_centered">';

        $form = $this->get_form_settings();
        if (!is_array($form)) {
            $label = new e\htmlLabel('label-warning', $form);
            $html .= $label->get_html() . '<br>';
        } else {
            $values = $this->get_submitted_values($form);
            $act = null;
            $activity = $values["activity"];
            if (($activity == "0") || (substr($activity, 0, 5) == 'clone')) {
                // Cloning existing or creating new one from scratch.
                $act = block_homework_moodle_utils::add_course_activity("assign",
                        $values["course"], $values["section"], $values["name"],
                        block_homework_moodle_utils::rewrite_urls_to_pluginfile($values["introeditor"]["text"]),
                        $values["submissions"] == 1 || $values["submissions"] == 3, $values["submissions"] >= 2,
                        $values["gradingscale"], strtotime($values["available"]), strtotime($values["due"]), $values["groups"],
                        $values["users"], $values["showdescription"]);
                if ($act) {
                    $subject = isset($values["subject"]) ? $values["subject"] : '';
                    $notetoparentssubject = isset($values["note_to_parents_subject"]) ? $values["note_to_parents_subject"] : '';
                    $notetoparents = '';
                    if (isset($values["note_to_parents"]) && isset($values["note_to_parents"]["text"])) {
                        $notetoparents = $values["note_to_parents"]["text"];
                    }
                    $notetolearnerssubject = isset($values["note_to_learners_subject"]) ? $values["note_to_learners_subject"] : '';
                    $notetolearners = '';
                    if (isset($values["note_to_learners"]) && isset($values["note_to_learners"]["text"])) {
                        $notetolearners = $values["note_to_learners"]["text"];
                    }
                    $tracker = block_homework_utils::add_homework_tracking_record(
                                    $act["coursemodule"], $USER->id, $subject, $values["duration"],
                                    $values["notifyother"], $values["notifyotheremail"],
                                    $values["notifyparents"], $notetoparentssubject, $notetoparents,
                                    $values["notifylearners"], $notetolearnerssubject, $notetolearners);
                    if (($values["notifyparents"] == 1) && ($notetoparents != '')) {
                        $html .= $this->send_parental_notifications($this->courseid, $act["coursemodule"], $values["subject"],
                                $values["name"], $values["due"], $values["duration"], $notetoparentssubject, $notetoparents);
                    }
                    if (($values["notifylearners"] == 1) && ($notetolearners != '')) {
                        $html .= $this->send_learner_notifications($this->courseid, $act["coursemodule"], $values["subject"],
                                $values["name"], $values["due"], $values["duration"], $notetolearnerssubject, $notetolearners);
                    }
                    $html .= $this->send_admin_notifications($this->courseid, $act["coursemodule"], $values["subject"],
                            $values["name"], $values["notifyother"] == 1 ? $values["notifyotheremail"] : '');
                }
            } else {
                // Editing existing activity.
                $act = block_homework_moodle_utils::update_course_activity(substr($activity, 4), // Skip the use. bit at the start.
                        $values["section"], $values["name"],
                        block_homework_moodle_utils::rewrite_urls_to_pluginfile($values["introeditor"]["text"]),
                        $values["submissions"] == 1 || $values["submissions"] == 3, $values["submissions"] >= 2,
                        $values["gradingscale"], strtotime($values["available"]), strtotime($values["due"]), $values["groups"],
                        $values["users"], $values["showdescription"]);
                if ($act) {
                    $subject = isset($values["subject"]) ? $values["subject"] : '';
                    $notetoparentssubject = isset($values["note_to_parents_subject"]) ? $values["note_to_parents_subject"] : '';
                    $notetoparents = '';
                    if (isset($values["note_to_parents"]) && isset($values["note_to_parents"]["text"])) {
                        $notetoparents = $values["note_to_parents"]["text"];
                    }
                    $notetolearnerssubject = isset($values["note_to_learners_subject"]) ? $values["note_to_learners_subject"] : '';
                    $notetolearners = '';
                    if (isset($values["note_to_learners"]) && isset($values["note_to_learners"]["text"])) {
                        $notetolearners = $values["note_to_learners"]["text"];
                    }
                    $tracker = block_homework_utils::update_homework_tracking_record(
                                    $act["coursemodule"], $USER->id, $subject, $values["duration"],
                                    $values["notifyother"], $values["notifyotheremail"],
                                    $values["notifyparents"], $notetoparentssubject, $notetoparents,
                                    $values["notifylearners"], $notetolearnerssubject, $notetolearners);
                    if (($values["notifyparents"] == 1) && ($notetoparents != '')) {
                        $html .= $this->send_parental_notifications($this->courseid, $act["coursemodule"], $values["subject"],
                                $values["name"], $values["due"], $values["duration"], $notetoparentssubject, $notetoparents);
                    }
                    if (($values["notifylearners"] == 1) && ($notetolearners != '')) {
                        $html .= $this->send_learner_notifications($this->courseid, $act["coursemodule"], $values["subject"],
                                $values["name"], $values["due"], $values["duration"], $notetolearnerssubject, $notetolearners);
                    }
                }
                // Don't send admin notifications on edit.
            }
            if ($act) {
                // Intro text uses 'intro' draft file area but 'introeditor' control has the draft item id in its itemid key.
                $introdraftitemid = file_get_submitted_draft_itemid('introeditor');
                if ($introdraftitemid != 0) {
                    $itemid = 0;
                    $context = context_module::instance($act["coursemodule"]);
                    $filemanageropts = $this->get_file_uploader_options($context, $this->course->maxbytes);
                    file_save_draft_area_files($introdraftitemid, $context->id, 'mod_assign', 'intro', $itemid, $filemanageropts);
                    $DB->delete_records('editor_atto_autosave', array('userid' => $USER->id, 'elementid' => 'id_introeditor',
                        'contextid' => $context->id));
                }

                // Additional files uses 'introattachment' draft file area.
                $introattachmentdraftitemid = file_get_submitted_draft_itemid('introattachment');
                if ($introattachmentdraftitemid != 0) {
                    $itemid = 0;
                    $context = context_module::instance($act["coursemodule"]);
                    $filemanageropts = $this->get_file_uploader_options($context, $this->course->maxbytes);
                    file_save_draft_area_files($introattachmentdraftitemid, $context->id, 'mod_assign', 'introattachment',
                            $itemid, $filemanageropts);
                }
            }
            $label = new e\htmlLabel('info', $this->get_str('assignmentsaved'));
            $html .= $label->get_html() . '<br>';
            $linkok = new e\htmlHyperlink('', 'OK', $CFG->wwwroot . "/course/view.php?id=" . $this->courseid);
            $linkok->set_class('ond_material_button_raised');
            $html .= $linkok->get_html();
            $linkset = new e\htmlHyperlink('', $this->get_str('setanotherassignment'), $CFG->wwwroot .
                    '/blocks/homework/set.php?course=' . $this->courseid, $this->get_str('setanotherassignment_title'));
            $linkset->set_class('ond_material_button_raised');
            $html .= $linkset->get_html();
        }
        $html .= '</div>';
        return $html;
    }

    protected function send_parental_notifications($courseid, $coursemoduleid, $assignmentsubject, $assignmentname,
            $assignmentdue, $assignmentduration, $subject, $body) {
        $notifyerror = HomeworkAccess::notify_parents($courseid, $coursemoduleid, $assignmentsubject,
                $assignmentname, $assignmentdue, $assignmentduration, $subject, $body);
        if ($notifyerror != '') {
            $label = new e\htmlLabel('label-warning', $this->get_str('parentalnotificationerror', $notifyerror));
            return $label->get_html() . '<br>';
        }
        return '';
    }

    public static function send_learner_notifications($courseid, $coursemoduleid, $assignmentsubject, $assignmentname,
            $assignmentdue, $assignmentduration, $subject, $body) {
        global $CFG, $DB;

        if ($assignmentduration == '') {
            $assignmentduration = get_string('durationnotspecified', 'block_homework');
        } else {
            $assignmentduration = block_homework_utils::get_duration_description($assignmentduration);
        }
        $errors = array();
        $formattedduedate = block_homework_utils::format_date(strtotime($assignmentdue));
        $variables = array(
            'assignment_subject' => $assignmentsubject,
            'subject' => $assignmentsubject,
            'assignment_name' => $assignmentname,
            'assignment_due_date' => $formattedduedate,
            'assignment_duration' => $assignmentduration,
            'assignment_link' => $CFG->wwwroot . '/blocks/homework/assignment.php?course=' . $courseid . '&id=' .
            $coursemoduleid,
            'learner_name ' => '',
            'learner_lastname' => '',
            'learner_firstname' => '');
        $learners = block_homework_moodle_utils::get_assignment_participants($coursemoduleid);
        $lognotifications = get_config('block_homework', 'log_notifications');
        foreach ($learners as $learnerentry) {
            $learner = $DB->get_record('user', array('id' => $learnerentry->id), 'id,firstname,lastname,email');
            $variables["learner_name"] = $learner->firstname . " " . $learner->lastname;
            $variables["learner_lastname"] = $learner->lastname;
            $variables["learner_firstname"] = $learner->firstname;
            $notificationbody = $body;
            $notificationsubject = $subject;
            foreach ($variables as $name => $value) {
                $notificationbody = str_ireplace('[' . $name . ']', $value, $notificationbody);
                $notificationsubject = str_ireplace('[' . $name . ']', $value, $notificationsubject);
            }
            // Moodle editor helpfully inserts full site URL into any link it thinks needs it so this gets rid of any resulting
            // duplicates if you use a link that is a template e.g. <a href="[assignment_link]">blah</a>.
            $notificationbody = str_replace($CFG->wwwroot . '/' . $CFG->wwwroot, $CFG->wwwroot, $notificationbody);
            $messageid = block_homework_moodle_utils::send_message($learner->id, $notificationsubject, $notificationbody,
                    $variables["assignment_link"], $variables["assignment_name"]);
            if (!$messageid) {
                $errors[] = $variables["learner_name"] . ": " . get_string('messagesendfailed', 'block_homework');
            } else {
                if (($lognotifications) && (class_exists('block_homework_utils_extended'))) {
                    block_homework_utils_extended::log_notification($coursemoduleid, $learner->id, $learner->email, $messageid);
                }
            }
        }
        if (count($errors) > 0) {
            return get_string('emailerrors', 'block_homework', array('count' => count($errors), 'example' => $errors[0]));
        }
        return '';
    }

    public static function send_admin_notifications($courseid, $coursemoduleid, $assignmentsubject, $assignmentname,
                                                    $notifyotheremail) {
        global $USER, $CFG;
        $notifycreator = get_config('block_homework', 'notify_creator');
        $lognotifications = get_config('block_homework', 'log_notifications');
        $course = get_course($courseid);
        $variables = array(
            'assignment_subject' => $assignmentsubject,
            'subject' => $assignmentsubject,
            'assignment_name' => $assignmentname,
            'assignment_link' => $CFG->wwwroot . '/blocks/homework/assignment.php?course=' . $courseid . '&id=' .
            $coursemoduleid,
            'course_name' => $course->fullname);
        $notificationbody = get_config('block_homework', 'new_assign_notification_message');
        $notificationsubject = get_config('block_homework', 'new_assign_notification_subject');
        foreach ($variables as $name => $value) {
            $notificationbody = str_ireplace('[' . $name . ']', $value, $notificationbody);
            $notificationsubject = str_ireplace('[' . $name . ']', $value, $notificationsubject);
        }
        // Moodle editor helpfully inserts full site URL into any link it thinks needs it so this gets rid of any resulting
        // duplicates if you use a link that is a template e.g. <a href="[assignment_link]">blah</a>.
        $notificationbody = str_replace($CFG->wwwroot . '/' . $CFG->wwwroot, $CFG->wwwroot, $notificationbody);
        $errors = array();
        if ($notifycreator) {
            $messageid = block_homework_moodle_utils::send_message($USER->id, $notificationsubject, $notificationbody,
                    $variables["assignment_link"], $variables["assignment_name"]);
            if (!$messageid) {
                $errors[] = fullname($USER) . ": " . get_string('messagesendfailed', 'block_homework');
            } else {
                if (($lognotifications) && (class_exists('block_homework_utils_extended'))) {
                    block_homework_utils_extended::log_notification($coursemoduleid, $USER->id, $USER->email, $messageid);
                }
            }
        }
        if ($notifyotheremail != '') {
            if (class_exists('block_homework_utils_extended')) {
                $error = block_homework_utils_extended::send_email($USER, $notifyotheremail, '', $notificationsubject,
                    $notificationbody);
            } else {
                $error = 'Incomplete installation';
            }
            if ($error != '') {
                $errors[] = $error;
            } else {
                if (($lognotifications) && (class_exists('block_homework_utils_extended'))) {
                    block_homework_utils_extended::log_notification($coursemoduleid, null, $notifyotheremail, null);
                }
            }
        }
        if (count($errors) > 0) {
            return get_string('emailerrors', 'block_homework', array('count' => count($errors), 'example' => $errors[0]));
        }
        return '';
    }

    protected function set_homework_form() {
        global $CFG;

        $form = $this->get_form_settings();
        if (is_array($form)) {
            $this->set_scripts();
            return $this->get_form($form, $this->get_str('save'), $this->get_str('cancel'), false);   // Don't use tabs.
        } else {
            $html = '<div class="ond_centered">';
            $label = new e\htmlLabel('label-warning', $form);
            $html .= $label->get_html() . '<br>';
            $oklink = new e\htmlHyperlink('', $this->get_str('ok'), $CFG->wwwroot);
            $oklink->set_class('ond_material_button_raised');
            $html .= $oklink->get_html();
            $setlink = new e\htmlHyperlink('', $this->get_str('tryagain'), $CFG->wwwroot .
                    '/blocks/homework/set.php?course=' . $this->courseid, $this->get_str('tryagain_title'));
            $setlink->set_class('ond_material_button_raised');
            $html .= $setlink->get_html();
            $html .= '</div>';
            return $html;
        }
    }

    protected function get_form_settings() {
        global $USER, $DB, $CFG;
        $form = array();
        /* detect presence of proprietary availability plugin for additional functionality */
        $extrafunc = file_exists($CFG->dirroot . '/availability/condition/user/homework/settings.php');

        if ($this->onfrontpage) {
            $courseoptions = $this->get_course_options();
            if (empty($courseoptions)) {
                return $this->get_str('nocourses');
            } else {
                if (count($courseoptions) == 1) {
                    $this->courseid = key($courseoptions);
                    $this->course = get_course($this->courseid);
                } else {
                    $form[$this->get_str('selectcourse')] = array(
                        'selectcourse' => array('type' => 'select', 'prompt' => $this->get_str('course'),
                            'options' => $courseoptions));
                    return $form;
                }
            }
        } else {
            $courseoptions = array($this->courseid => $this->course->fullname);
        }

        $basicstab = $this->get_str('basics');
        $courselabel = new e\htmlLabel('label-info', $this->course->fullname);
        $courselabelhtml = $courselabel->get_html();
        if ($this->editingcmid == 0) {
            $coursebutton = new e\htmlHyperlink('', $this->get_str('changecourse'), $CFG->wwwroot .
                    '/blocks/homework/set.php');
            $coursebutton->set_class('ond_material_button_raised_small');
            $i = strpos($courselabelhtml, '</label>');
            $courselabelhtml = substr($courselabelhtml, 0, $i) . $coursebutton->get_html() . '</label>';
        }
        $form[$basicstab] = array(
            'reqrestrict' => array('type' => 'hidden', 'value' => get_config('block_homework', 'require_restriction')),
            'course' => array('type' => 'hidden', 'value' => $this->courseid),
            'sesskey' => array('type' => 'hidden', 'value' => $USER->sesskey),
            'courselabel' => array('type' => 'static', 'prompt' => $this->get_str('course'),
                'value' => $courselabelhtml));

        $sectionoptions = block_homework_moodle_utils::get_course_sections($this->courseid);
        $section = 0;
        if ($this->editingcmid != 0) {
            $section = $this->assignment->section;
        }
        if (!empty($sectionoptions)) {
            $form[$basicstab]['section'] = array('type' => 'select', 'prompt' => $this->get_str('section'),
                'options' => $sectionoptions, 'value' => $section);
        } else {
            $form[$basicstab]['section'] = array('type' => 'hidden', 'value' => '0');
        }

        $activityoptions = array(0 => $this->get_str('createnewassignmentactivity'));
        $activities = block_homework_moodle_utils::get_assignments_on_course($this->courseid);
        if (!empty($activities)) {
            $cloneactivities = array();
            foreach ($activities as $value => $activity) {
                $cloneactivities['clone.' . $value] = $this->get_str('clone_') . $activity->name;
            }
            $activityoptions = array_merge($activityoptions, array($this->get_str('cloneexistingactivity') => $cloneactivities));
        }
        if ($this->editingcmid == 0) {
            $form[$basicstab]['activity'] = array('type' => 'select', 'prompt' => $this->get_str('activity'),
                'options' => $activityoptions);
        } else {
            $form[$basicstab]['activity'] = array('type' => 'hidden', 'value' => 'use.' . $this->editingcmid);
        }
        $activityvars = 'var ondEduLinkActivities = ' . json_encode($activities) . ';';
        $defaultgroup = optional_param('group', 0, PARAM_INT);
        if ($defaultgroup > 0) {
            $activityvars .= ' var ondEduLinkDefaultGroup = ' . $defaultgroup . ';';
        }
        $form[$basicstab]['activity_variables'] = array('type' => 'script',
            'value' => $activityvars);

        $assname = ($this->editingcmid == 0) ? '' : $this->assignment->name;
        $form[$basicstab]['name'] = array('prompt' => $this->get_str('name'), 'type' => 'text', 'size' => 50,
            'required' => true, 'value' => $assname);
        $assdesc = ($this->editingcmid == 0) ? '' : $this->assignment->description;

        // If editing an activity pass the appropriate cm context not user context.
        $asscontext = ($this->editingcmid == 0) ? context_user::instance($USER->id) : context_module::instance($this->editingcmid);
        // Both intro description editor and file uploader use draft area so set that up.
        $filemanageroptions = $this->get_file_uploader_options($asscontext, $this->course->maxbytes);
        $itemid = 0;
        // This fetches an item id to be used to group any files uploaded into the draft area
        // if there is already a parameter called 'introeditor["itemid"]' it uses that, otherwise it returns 0.
        $introtextdraftitemid = file_get_submitted_draft_itemid('introeditor');
        $introadditionalfilesdraftitemid = file_get_submitted_draft_itemid('introattachment');
        // Now create a draft area (if drafitemid is 0 - in which case draftitemid is set to a new id)
        // or fetch the existing one (e.g. if in the process of saving the new/edited assignment).
        file_prepare_draft_area($introtextdraftitemid, $asscontext->id, 'mod_assign', 'intro', $itemid, $filemanageroptions);
        file_prepare_draft_area($introadditionalfilesdraftitemid, $asscontext->id, 'mod_assign', 'introattachment', $itemid,
                $filemanageroptions);
        // Now one problem we have is the Atto editor autosaves and will restore a draft in preference to the value you supply!
        // and as it's partially keyed on a hash of the page URL we don't restore the draft from any use of Moodle's own
        // assignment editing page, which could be very confusing. Solution - discard any drafts in the db entirely.
        $DB->delete_records('editor_atto_autosave', array('userid' => $USER->id, 'elementid' => 'id_introeditor',
            'contextid' => $asscontext->id));

        // Any files within the description will have @@PLUGINFILE@@ instead of a URL, so replace those with real urls...
        $assdesc = file_rewrite_pluginfile_urls($assdesc, 'draftfile.php', context_user::instance($USER->id)->id, 'user', 'draft',
                $introtextdraftitemid);
        $desccontrol = $this->get_text_editor($asscontext, $introtextdraftitemid, 'introeditor', $assdesc);
        $form[$basicstab]['descriptioneditor'] = array('prompt' => $this->get_str('description'), 'type' => 'static',
            'value' => $desccontrol);
        $defaultshowdescription = get_config('block_homework', 'default_showdescription');
        $assshowdescription = ($this->editingcmid == 0) ? $defaultshowdescription : $this->assignment->showdescription;
        $form[$basicstab]['showdescription'] = array('type' => 'switch', 'prompt' => $this->get_str('showdescription'),
                'default' => $defaultshowdescription, 'value' => $assshowdescription);

        $fileuploader = $this->get_file_uploader($introadditionalfilesdraftitemid, $filemanageroptions);
        $form[$basicstab]['addfiles'] = array('type' => 'static', 'prompt' => $this->get_str('addfiles'), 'value' => $fileuploader);

        $subject = ($this->editingcmid == 0) ? '' : $this->assignment->subject;
        $subjectoptions = array();
        if (($this->edulinkpresent) && ($subject == '')) {
            $subject = HomeworkAccess::get_subject_for_course($this->courseid);
            if (method_exists('HomeworkAccess', 'get_subjects')) {
                $subjectoptions = HomeworkAccess::get_subjects();
            }
        }
        $popularsubjects = $DB->get_records_sql('SELECT DISTINCT subject FROM {block_homework_assignment} ORDER BY subject');
        foreach ($popularsubjects as $sub) {
            if (!in_array($sub->subject, $subjectoptions)) {
                $subjectoptions[] = $sub->subject;
            }
        }
        asort($subjectoptions);
        $form[$basicstab]['subject'] = array('prompt' => $this->get_str('subject'), 'type' => 'text',
            'autofilloptions' => $subjectoptions, 'size' => 50, 'value' => $subject, 'required' => true);

        if ($CFG->enableavailability == 0) {
            $form[$basicstab]['groups'] = array('type' => "hidden", 'value' => '');
            $form[$basicstab]['groups-none'] = array('prompt' => $this->get_str('restricttogroups'), 'type' => "label-warning",
                'value' => $this->get_str('enableavailabilityoff'));
            $form[$basicstab]['users'] = array('type' => "hidden", 'value' => '');
        } else {
            $selectedgroups = array();
            $selectedusers = array();
            // Groups on the specified course.
            $coursegroups = array();
            if (!$this->onfrontpage) {
                $coursecontext = context_course::instance($this->courseid);
                // Debatable as to whether moodle/course:managegroups should allow selection of any group too?
                $seeallgroups = $this->course->groupmode != 1 ||
                                has_capability('moodle/site:accessallgroups', $coursecontext);
                if ($seeallgroups) {
                    $groups = groups_get_all_groups($this->courseid);
                } else {
                    $groups = groups_get_all_groups($this->courseid, $USER->id);
                }
                if (!empty($groups)) {
                    foreach ($groups as $group) {
                        $coursegroups[$group->id] = $group->name;
                        if ($this->editingcmid != 0) {
                            if (block_homework_moodle_utils::is_group_or_user_in_availability_json($this->assignment->availability,
                                    $group->id, null, false)) {
                                $selectedgroups[] = $group->id;
                            }
                        }
                    }
                }
            }
            if (empty($coursegroups)) {
                $form[$basicstab]['groups'] = array('type' => "hidden", 'value' => '');
                $form[$basicstab]['groups-none'] = array('prompt' => $this->get_str('restricttogroups'), 'type' => "label-warning",
                    'value' => $this->get_str('nogroupsoncourse'));
            } else {
                $form[$basicstab]['groups'] = array('prompt' => $this->get_str('restricttogroups'), 'type' => "multiselect",
                    'options' => $coursegroups, 'value' => $selectedgroups, 'validate' => true);
            }
            if (block_homework_moodle_utils::is_availability_condition_user_present()) {
                $context = \context_course::instance($this->courseid);
                $seeallusers = $this->course->groupmode != 1 ||
                                has_capability('moodle/site:accessallgroups', $coursecontext);
                $courseuserobjects = get_role_users(5, $context);
                $courseusers = array();
                $myusers = array();
                if (!$seeallusers) {
                    $usersinmygroups = $DB->get_records_sql('SELECT DISTINCT userid FROM {groups_members} gm2 ' .
                            'WHERE groupid IN (SELECT DISTINCT gm.groupid FROM {groups_members} gm WHERE gm.userid = ? ' .
                            'AND gm.groupid IN (SELECT g.id FROM {groups} g WHERE g.courseid = ?))',
                            array($USER->id, $this->courseid));
                    if ($usersinmygroups) {
                        foreach($usersinmygroups as $myuser) {
                            $myusers[] = $myuser->userid;
                        }
                    }
                }
                foreach ($courseuserobjects as $user) {
                    if ((!$seeallusers) && (!in_array($user->id, $myusers))) {
                        continue;
                    }
                    $courseusers[$user->id] = $user->lastname . ', ' . $user->firstname;
                    if ($this->editingcmid != 0) {
                        if (block_homework_moodle_utils::is_group_or_user_in_availability_json($this->assignment->availability,
                                null, $user->id, false)) {
                            $selectedusers[] = $user->id;
                        }
                    }
                }
                if (empty($courseusers)) {
                    $form[$basicstab]['users'] = array('type' => "hidden", 'value' => '');
                    $form[$basicstab]['users-none'] = array('prompt' => $this->get_str('restricttousers'),
                        'type' => "label-warning", 'value' => $this->get_str('nousersoncourse'));
                } else {
                    $form[$basicstab]['users'] = array('prompt' => $this->get_str('restricttousers'), 'type' => "multiselect",
                        'options' => $courseusers, 'value' => $selectedusers, 'validate' => true);
                }
            } else {
                $form[$basicstab]['users'] = array('type' => "hidden", 'value' => '');
            }
        }

        $submissionoptions = array(0 => $this->get_str("noonlinesubs"),
            1 => $this->get_str("onlinetextsubs"),
            2 => $this->get_str("onlinefilesubs"),
            3 => $this->get_str("onlinetextorfilesubs"));
        $submissionsvalue = get_config('block_homework', 'submissions');
        if (($submissionsvalue == '') || ($submissionsvalue < 0) || ($submissionsvalue > 3)) {
            $submissionsvalue = 3;
        }
        if ($this->editingcmid != 0) {
            $submissionsvalue = 0;
            if ($this->assignment->textsubmissionenabled) {
                $submissionsvalue = 1;
            }
            if ($this->assignment->filesubmissionenabled) {
                $submissionsvalue += 2;
            }
        }

        $form[$basicstab]['submissions'] = array('prompt' => $this->get_str('submissions'), 'type' => 'select',
            'options' => $submissionoptions, 'value' => $submissionsvalue);
        $scalequery = 'SELECT id, name FROM {scale} WHERE courseid = 0 OR courseid = ' . (int) $this->courseid . ' ORDER BY name';
        $scales = $DB->get_records_sql($scalequery);
        $scaleoptions = array(0 => 'None', 100 => $this->get_str('pointsoutof100'));
        if ($scales) {
            foreach ($scales as $scale) {
                $scaleoptions[-$scale->id] = $scale->name;
            }
        }
        $assscale = ($this->editingcmid == 0) ? 100 : $this->assignment->grade;
        $todayepoch = optional_param('avail', time(), PARAM_INT);
        $today = date('Y-m-d', $todayepoch);
        $assavaildate = ($this->editingcmid == 0) ? $today : $this->assignment->availabledate;
        $tomorrow = date('Y-m-d', $todayepoch + 24 * 60 * 60);
        $assduedate = ($this->editingcmid == 0) ? $tomorrow : $this->assignment->duedate;

        $form[$basicstab]['gradingscale'] = array('type' => 'select', 'prompt' => $this->get_str('gradingtype'),
            'options' => $scaleoptions, 'value' => $assscale);
        $context = context_course::instance($this->courseid);
        if (has_capability('moodle/course:managescales', $context)) {
            $form[$basicstab]['gradingscale-info'] = array('type' => 'label-info', 'value' => $this->get_str('gradingscalelink',
                    $CFG->wwwroot . '/grade/edit/scale/index.php?id=' . $this->courseid));
        }
        $durationoptions = array('' => $this->get_str('duration_notspecified'),
            10 => $this->get_str('duration_10'),
            20 => $this->get_str('duration_20'),
            30 => $this->get_str('duration_30'),
            60 => $this->get_str('duration_60'),
            120 => $this->get_str('duration_120'),
            180 => $this->get_str('duration_180'),
            240 => $this->get_str('duration_240'),
            360 => $this->get_str('duration_360'));
        $assduration = ($this->editingcmid == 0) ? '' : $this->assignment->duration;
        $form[$basicstab]['duration'] = array(
            'type' => 'select', 'prompt' => $this->get_str('duration'), 'options' => $durationoptions, 'value' => $assduration);

        $form[$basicstab]['available'] = array('type' => 'date', 'prompt' => $this->get_str('availablefrom'),
            'value' => $assavaildate,
            'required' => true, 'include_tomorrow_button' => true, 'include_next_week_button' => true);

        $form[$basicstab]['due'] = array('type' => 'date', 'prompt' => $this->get_str('dueon'), 'value' => $assduedate,
            'required' => true, 'include_tomorrow_button' => true, 'include_next_week_button' => true);

        $assnotifylearners = true;
        $asslearnernotes = $this->get_str('notifylearnersmessage');
        $asslearnernotessubject = $this->get_str('notifylearnersmessagesubject');
        $assnotifyother = false;
        $assnotifyotheremail = '';
        if ($this->editingcmid == 0) {
            $sql = 'SELECT id, notesforlearners, notesforlearnerssubject '
                    . 'FROM {block_homework_assignment} '
                    . 'WHERE notifylearners = 1 AND userid = ? ORDER BY id DESC LIMIT 1';
            $row = $DB->get_record_sql($sql, array($USER->id));
            if ($row) {
                $assnotifylearners = true;
                if (!empty($row->notesforlearners)) {
                    $asslearnernotes = $row->notesforlearners;
                }
                if (!empty($row->notesforlearnerssubject)) {
                    $asslearnernotessubject = $row->notesforlearnerssubject;
                }
            }
            $sql = 'SELECT id, notifyotheremail '
                    . 'FROM {block_homework_assignment} '
                    . 'WHERE notifyother = 1 AND userid = ? ORDER BY id DESC LIMIT 1';
            $row = $DB->get_record_sql($sql, array($USER->id));
            if ($row) {
                $assnotifyother = true;
                if (!empty($row->notifyotheremail)) {
                    $assnotifyotheremail = $row->notifyotheremail;
                }
            }
        } else {
            $assnotifylearners = $this->assignment->notifylearners;
            $asslearnernotes = $this->assignment->notesforlearners;
            $asslearnernotessubject = $this->assignment->notesforlearnerssubject;
            $assnotifyother = $this->assignment->notifyother;
            $assnotifyotheremail = $this->assignment->notifyotheremail;
        }

        if ($extrafunc) {
            $form[$basicstab]['notifyother'] = array('type' => 'switch', 'prompt' => $this->get_str('notifyother'),
                'default' => true, 'value' => $assnotifyother,
                'subgroup_if_on' => array(
                    'notifyotheremail' => array('prompt' => $this->get_str('notifyotheremail'), 'type' => 'email',
                        'size' => 80, 'required' => true, 'value' => $assnotifyotheremail)
                ));
        } else {
            $form[$basicstab]['notifyother'] = array('type' => 'hidden', 'value' => 0);
            $form[$basicstab]['notifyotheremail'] = array('type' => 'hidden', 'value' => '');
        }
        if ($extrafunc) {
            $form[$basicstab]['notifyparents'] = array('type' => 'hidden', 'value' => 0);
            $form[$basicstab]['note_to_parents'] = array('type' => 'hidden', 'value' => '');
        } else if ($this->edulinkpresent) {
            if (HomeworkAccess::communicator_enabled()) {
                $assnotifyparents = ($this->editingcmid == 0) ?
                        get_config('block_homework', 'default_notify_parents') : $this->assignment->notifyparents;
                if ($this->editingcmid == 0) {
                    $assparentnotes = $this->get_str('notifyparentsmessage');
                    $assparentnotessubject = $this->get_str('notifyparentsmessagesubject');
                    $sql = 'SELECT id, notesforparents, notesforparentssubject '
                            . 'FROM {block_homework_assignment} '
                            . 'WHERE notifyparents = 1 AND userid = ? ORDER BY id DESC LIMIT 1';
                    $row = $DB->get_record_sql($sql, array($USER->id));
                    if ($row) {
                        if (!empty($row->notesforparents)) {
                            $assparentnotes = $row->notesforparents;
                        }
                        if (!empty($row->notesforparentssubject)) {
                            $assparentnotessubject = $row->notesforparentssubject;
                        }
                    }
                } else {
                    $assparentnotes = $this->assignment->notesforparents;
                    $assparentnotessubject = $this->assignment->notesforparentssubject;
                }
                $notetoparentscontrol = $this->get_text_editor($asscontext, 0, 'note_to_parents', $assparentnotes);
                $form[$basicstab]['notifyparents'] = array('type' => 'switch', 'prompt' => $this->get_str('notifyparents'),
                    'default' => true, 'value' => $assnotifyparents,
                    'subgroup_if_on' => array(
                        'note_to_parents_subject' => array('prompt' => $this->get_str('notesforparentssubject'), 'type' => 'text',
                            'size' => 80, 'required' => true, 'value' => $assparentnotessubject),
                        'note_to_parents_body' => array('prompt' => $this->get_str('notesforparents'), 'type' => 'static',
                            'value' => $notetoparentscontrol)
                    ));
            } else {
                $form[$basicstab]['notifyparents'] = array('type' => 'switch', 'prompt' => $this->get_str('notifyparents'),
                    'default' => false,
                    'subgroup_if_on' => array(
                        'note_to_parents' => array('type' => 'label-warning', 'value' => $this->get_str('communicatormissing'))
                    ));
            }
        } else {
            $form[$basicstab]['notifyparents'] = array('type' => 'switch', 'prompt' => $this->get_str('notifyparents'),
                'default' => false,
                'subgroup_if_on' => array(
                    'note_to_parents' => array('type' => 'label-warning', 'value' => $this->get_str('edulinkfeatureonly'))
                ));
        }
        if ($extrafunc) {
            $notetolearnerscontrol = $this->get_text_editor($asscontext, 0, 'note_to_learners', $asslearnernotes);
            $form[$basicstab]['notifylearners'] = array('type' => 'switch', 'prompt' => $this->get_str('notifylearners'),
                'default' => true, 'value' => $assnotifylearners,
                'subgroup_if_on' => array(
                    'note_to_learners_subject' => array('prompt' => $this->get_str('notesforlearnerssubject'), 'type' => 'text',
                        'size' => 80, 'required' => true, 'value' => $asslearnernotessubject),
                    'note_to_learners_body' => array('prompt' => $this->get_str('notesforlearners'), 'type' => 'static',
                        'value' => $notetolearnerscontrol)
                ));
        } else {
            $form[$basicstab]['notifylearners'] = array('type' => 'hidden', 'value' => 0);
            $form[$basicstab]['note_to_learners_subject'] = array('type' => 'hidden', 'value' => '');
            $form[$basicstab]['note_to_learners_body'] = array('type' => 'hidden', 'value' => '');
        }

        return $form;
    }

    /* Set clicked from front page, i.e. no course id or id of site so pick up
     * list of user's courses to pick one before continuing...
     */
    protected function get_course_options() {
        global $USER;
        $courseoptions = array();
        $courses = block_homework_moodle_utils::get_users_courses($USER->id, 'mod/assign:addinstance');
        foreach ($courses as $course) {
            $courseoptions[$course->id] = $course->fullname;
        }
        return $courseoptions;
    }

    protected function get_file_uploader_options($context, $maxbytes) {
        return array('subdirs' => 0, 'maxbytes' => $maxbytes, 'context' => $context);
    }

    // While we don't want to use Moodle Forms we do want to use the drag and
    // drop file manager, so this is a bit of a hack to use this particular
    // Moodle Forms element within our page.
    protected function get_file_uploader($draftitemid, $filemanageroptions) {
        $customdata = array('filemanageropts' => $filemanageroptions);
        $mform = new block_homework_file_uploader_form(null, $customdata);
        $entry = new stdClass();
        $entry->introattachment = $draftitemid;
        $mform->set_data($entry);
        $html = $mform->render();
        // As Moodle's own file uploading code often says... 'this is a nasty hack' - we only want the form element for
        // the file uploader so strip out all before the <fieldset> tag and all from the closing </form> tag onwards.
        $i = strpos($html, '<fieldset');
        $html = substr($html, $i);
        $html = str_replace('</form>', '', $html);
        return $html;
    }

    // Similar to above only for the text editor control.
    protected function get_text_editor($context, $draftitemid, $name, $value) {
        $customdata = array('context' => $context, 'name' => $name);
        $mform = new block_homework_text_editor_form(null, $customdata);
        $entry = new stdClass();
        $entry->$name = array('text' => $value, 'itemid' => $draftitemid);
        $mform->set_data($entry);
        $html = $mform->render();
        $i = strpos($html, '<fieldset');
        $html = substr($html, $i);
        $html = str_replace('</form>', '', $html);
        return $html;
    }

    protected function set_scripts() {
        global $PAGE;
        parent::set_scripts();
        $PAGE->requires->js_call_amd('block_homework/set', 'start');
    }

}

block_homework_set_page::factory();