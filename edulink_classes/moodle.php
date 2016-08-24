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
 * Various functions relying on Moodle, e.g. accessing assignment details
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$moodlepath = dirname(__FILE__) . "/../../../";
require_once($moodlepath . "config.php");
require_once($moodlepath . "mod/assign/lib.php");
require_once($moodlepath . "mod/assign/locallib.php");
require_once($moodlepath . "mod/assign/externallib.php");
require_once($moodlepath . "lib/modinfolib.php");
require_once($moodlepath . "lib/datalib.php");
require_once($moodlepath . "course/lib.php");

class block_homework_moodle_utils {

    public static function get_str($id, $params = null) {
        if ($params == null) {
            return get_string($id, 'block_homework');
        } else {
            return get_string($id, 'block_homework', $params);
        }
    }

    /**
     * return an array of id/name pairs listing the assign activities
     * on the specified course
     * @global type $DB
     * @param int $courseid
     * @return array
     */
    public static function get_assignments_on_course($courseid, $maximumdaysage = 28) {
        global $DB;
        $activities = array();
        // Get instances of each assign activity type. Change last two joins to OUTER JOIN to include non-homework block items.
        $sql = "SELECT cm.id, cm.instance, cm.availability, cm.completion, eh.subject, eh.userid, u.firstname, u.lastname,
                a.name, a.intro, a.allowsubmissionsfromdate, a.duedate, a.grade, a.nosubmissions, gi.scaleid,
                c.id AS courseid, c.shortname AS courseshortname
                FROM {course_modules} cm
                JOIN {course} c ON (c.id = cm.course)
                JOIN {modules} m ON (m.id = cm.module)
                JOIN {assign} a ON (a.id = cm.instance)
                JOIN {grade_items} gi ON (gi.courseid = cm.course AND gi.itemtype = 'mod' AND gi.itemmodule = m.name AND
                    gi.iteminstance = a.id)
                JOIN {block_homework_assignment} eh ON (eh.coursemoduleid = cm.id)
                JOIN {user} u ON (u.id = eh.userid)
                WHERE cm.course = ? AND a.duedate > ? AND cm.instance <> 0 AND m.visible = 1 AND m.name = 'assign'
                ORDER BY a.duedate DESC";
        $agelimit = time() - ($maximumdaysage * 24 * 60 * 60);
        $assignments = $DB->get_records_sql($sql, array($courseid, $agelimit));
        if ($assignments) {
            foreach ($assignments as $assignment) {
                $setbyuserid = $assignment->userid;
                $setbyname = trim($assignment->firstname . ' ' . $assignment->lastname);
                /* if ($setbyuserid == '') {
                    // No userid from our cm query, i.e. an assignment activity not
                    // created via our homework block - find out creator name the slow way.
                    list($setbyuserid, $setbyname) = self::get_course_module_creator($assignment->id);
                } */
                $activities[$assignment->id] = (object) array(
                            'courseid' => $assignment->courseid,
                            'coursename' => $assignment->courseshortname,
                            'id' => $assignment->id,
                            'type' => $assignment->name,
                            'instanceid' => $assignment->instance,
                            'assignmentid' => $assignment->instance,
                            'name' => $assignment->name,
                            'description' => $assignment->intro,
                            'availabledate' => $assignment->allowsubmissionsfromdate,
                            'duedate' => $assignment->duedate,
                            'completion' => $assignment->completion,
                            'grade' => intval($assignment->grade),
                            'availability' => json_decode($assignment->availability),
                            'subject' => $assignment->subject,
                            'userid' => $setbyuserid,
                            'setbyname' => $setbyname,
                            'nosubmissions' => $assignment->nosubmissions != 0);
            }
        }
        return $activities;
    }

    /**
     * return details of an assignment activity (for editing purposes)
     * @global type $DB
     * @param int $coursemoduleid
     * @return stdobject
     */
    public static function get_assignment($coursemoduleid) {
        global $DB;
        // Change last two joins to OUTER JOIN if you want to include assignments set outside of the block.
        $sql = "SELECT cm.id, c.id AS courseid, c.fullname AS coursefullname,
                a.id AS assignmentid, a.name, a.intro, a.grade, a.nosubmissions,
                a.blindmarking, a.markingworkflow, a.markingallocation, a.teamsubmission,
                eh.subject, cm.availability, a.grade, gi.scaleid,
                a.allowsubmissionsfromdate, a.duedate, eh.duration,
                eh.notifyparents, eh.notesforparentssubject, eh.notesforparents,
                eh.userid, u.firstname, u.lastname
                FROM {course_modules} cm
                JOIN {course} c ON (c.id = cm.course)
                JOIN {assign} a ON (a.id = cm.instance)
                JOIN {grade_items} gi ON (gi.courseid = cm.course AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign'
                AND gi.iteminstance = a.id)
                JOIN {block_homework_assignment} eh ON (eh.coursemoduleid = cm.id)
                JOIN {user} u ON (u.id = eh.userid)
                WHERE cm.id = ?";
        $ass = $DB->get_record_sql($sql, array($coursemoduleid));
        $activity = null;
        if ($ass) {
            $unsupported = array();
            if ($ass->blindmarking != 0) {
                $unsupported[] = get_string('unsupportedblindmarking', 'block_homework');
            }
            if ($ass->markingworkflow != 0) {
                $unsupported[] = get_string('unsupportedmarkingworkflow', 'block_homework');
            }
            if ($ass->markingallocation != 0) {
                $unsupported[] = get_string('unsupportedmarkingallocation', 'block_homework');
            }
            if ($ass->teamsubmission != 0) {
                $unsupported[] = get_string('unsupportedteamsubmission', 'block_homework');
            }
            $setbyuserid = $ass->userid;
            $setbyname = trim($ass->firstname . ' ' . $ass->lastname);
            /* if ($setbyuserid == '') {
                // No userid from our cm query, i.e. an assignment activity not
                // created via our homework block - find out creator name the slow way.
                list($setbyuserid, $setbyname) = self::get_course_module_creator($coursemoduleid);
            } */
            $params = array('assignment' => $ass->assignmentid,
                'plugin' => 'comments',
                'subtype' => 'assignfeedback',
                'name' => 'enabled');
            $fb = $DB->get_record('assign_plugin_config', $params, 'value');
            $params["subtype"] = "assignsubmission";
            $params["plugin"] = "file";
            $filesub = $DB->get_record('assign_plugin_config', $params, 'value');
            $params["plugin"] = "onlinetext";
            $textsub = $DB->get_record('assign_plugin_config', $params, 'value');
            $activity = (object) array(
                        'id' => $coursemoduleid,
                        'assignmentid' => $ass->assignmentid,
                        'courseid' => $ass->courseid,
                        'coursename' => $ass->coursefullname,
                        'name' => $ass->name,
                        'description' => $ass->intro,
                        'subject' => $ass->subject,
                        'availabledate' => $ass->allowsubmissionsfromdate,
                        'duedate' => $ass->duedate,
                        'availability' => json_decode($ass->availability),
                        'grade' => intval($ass->grade),
                        'duration' => $ass->duration,
                        'notifyparents' => $ass->notifyparents,
                        'notesforparentssubject' => $ass->notesforparentssubject,
                        'notesforparents' => $ass->notesforparents,
                        'userid' => $setbyuserid,
                        'setbyname' => $setbyname,
                        'gradingenabled' => $ass->grade != 0,
                        'nosubmissions' => $ass->nosubmissions != 0,
                        'feedbackenabled' => $fb->value == 1,
                        'textsubmissionenabled' => $textsub->value == 1,
                        'filesubmissionenabled' => $filesub->value == 1,
                        'unsupported' => $unsupported);
        }
        return $activity;
    }

    /**
     * Return an array of assignment ids that are available to the specified group
     * @global $DB
     * @param int $courseid - course id or site id
     * @param int $groupid - limit to assignments available to this group
     * @return array - of assignment instance ids
     */
    public static function get_assignments_for_group($courseid, $groupid = 0, $fromdate = 0, $todate = 0) {
        global $DB;
        $assignments = array();
        $params = array();
        // Get instances of each assign activity type.
        // Change last join to OUTER JOIN if you want to include assignments set outside of the block.
        $sql = "SELECT cm.id, cm.instance, cm.availability, ass.name, ass.allowsubmissionsfromdate, ass.duedate,
                       eh.duration, eh.subject, c.fullname AS coursename
                FROM {course} c
                JOIN {course_modules} cm ON (cm.course = c.id)
                JOIN {modules} m ON (m.id = cm.module)
                JOIN {assign} ass ON (ass.id = cm.instance)
                JOIN {block_homework_assignment} eh ON (eh.coursemoduleid = cm.id)
                WHERE ";
        if ($courseid != get_site()->id) {
            $sql .= "c.id = ? AND ";
            $params[] = intval($courseid);
        }
        $sql .= "cm.instance <> 0 AND m.visible = 1 AND m.name = 'assign' ";
        if ($fromdate != 0) {
            $sql .= "AND ass.duedate >= ? ";
            $params[] = intval($fromdate);
        }
        if ($todate != 0) {
            $sql .= "AND ass.duedate <= ? ";
            $params[] = intval($todate);
        }
        $sql .= "ORDER BY ass.duedate, ass.name";
        $rows = $DB->get_records_sql($sql, $params);
        if ($rows) {
            foreach ($rows as $row) {
                if (($groupid == 0) || (self::is_group_in_availability_json($row->availability, $groupid))) {
                    $assignments[] = (object) array(
                                'id' => $row->id,
                                'assignmentid' => $row->instance,
                                'assignmentname' => $row->name,
                                'availabledate' => $row->allowsubmissionsfromdate,
                                'duedate' => $row->duedate,
                                'duration' => $row->duration,
                                'coursename' => $row->coursename,
                                'subject' => $row->subject);
                }
            }
        }
        return $assignments;
    }

    /**
     * Given a number of group ids, return an appropriate course_modules availability string
     * @param array $groupids
     * @return string
     */
    public static function groupids_to_availability_json($groupids) {
        if (empty($groupids) || !is_array($groupids)) {
            $availability = null;
        } else {
            $availability = array("op" => "|",
                "c" => array(),
                "show" => true);
            foreach ($groupids as $groupid) {
                if ($groupid > 0) {
                    $availability["c"][] = (object) array("type" => "group", "id" => intval($groupid));
                }
            }
            if (!empty($availability["c"])) {
                $availability = json_encode((object) $availability);
            } else {
                $availability = null;
            }
        }
        return $availability;
    }

    /**
     * Given a course_module availability string, return true if the specified
     * group is included (or there are no restrictions)
     * @param string $availability
     * @param int $groupid
     * @return boolean
     */
    public static function is_group_in_availability_json($availability, $groupid) {
        $a = json_decode($availability);
        // Only detangle availability structure if it's the simple 'any of these groups' variation.
        $show = (isset($a->show) && ($a->show)) || ((isset($a->showc) && ($a->showc))) && (isset($a->op));
        if ($show && ((($a->op == "&") && (count($a->c) == 1)) || ($a->op == "|"))) {
            foreach ($a->c as $condition) {
                if (($condition->type == 'group') && ($condition->id == $groupid)) {
                    return true;
                }
            }
            return false;
        } else {
            // A more complex structure so assume the group is included.
            return true;
        }
    }

    /**
     * add a "course module" - i.e. an instance of a module (e.g. mod_assign) on a course
     * @param int $courseid - id of the course (courses table)
     * @param int $moduleid - id of the module (modules table)
     * @param int $instanceid - instance id of the module - usually 0 as you don't know it yet!
     * @param int $sectionid - id of the course section  (course_sections table)
     * @return array or false on failure
     */
    public static function add_course_module($courseid, $moduleid, $instanceid, $sectionid, $groupids) {
        $availability = self::groupids_to_availability_json($groupids);
        $coursemodule = array(
            'course' => $courseid,
            'module' => $moduleid,
            'instance' => $instanceid,
            'section' => $sectionid,
            'idnumber' => null,
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => null,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => $availability
        );
        $id = add_course_module((object) $coursemodule);
        if (!$id) {
            return false;
        }
        $coursemodule["id"] = $id;
        return $coursemodule;
    }

    public static function update_course_module_group_availability($coursemoduleid, $groupids) {
        global $DB;
        $availability = self::groupids_to_availability_json($groupids);
        return $DB->set_field("course_modules", "availability", $availability, array("id" => $coursemoduleid));
    }

    /**
     * return id of a module (e.g. mod_assign)
     * @global type $DB
     * @param string $modulename - name of module e.g. "assign"
     * @return int or boolean false if not found
     */
    public static function get_module_id($modulename) {
        global $DB;
        $mod = $DB->get_record('modules', array('name' => $modulename), 'id');
        if ($mod) {
            return $mod->id;
        } else {
            return false;
        }
    }

    /**
     * get id of course section (course_sections table)
     * @global type $DB
     * @param int $courseid - id of the course (courses table)
     * @param int $sectionnumber - number of the section (0 to 10 normally, 0 is general section at top)
     * @return int or boolean false if section not found
     */
    public static function get_course_section_id($courseid, $sectionnumber = 0) {
        global $DB;
        $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnumber), 'id');
        if ($section) {
            return $section->id;
        } else {
            return false;
        }
    }

    /**
     * add an activity to the general (top) section of a course
     * @global object $DB
     * @param string $module - name of module (modules table, only "assign" supported at the moment)
     * @param int $courseid - id of course (courses table)
     * @param string $name - name of activity
     * @param string $description - description of activity
     * @param int $grade - negative if grading id, positive if maximum grade
     * @param int $availabledate - date as linux epoch
     * @param int $duedate - date as linux epoch
     * @return array or boolean false if failed
     */
    public static function add_course_activity($module, $courseid, $name, $description, $textsubmissions, $filesubmissions, $grade,
                                               $availabledate, $duedate, $groupids) {
        global $DB;
        // First we need the id of the module.
        $moduleid = self::get_module_id($module);
        if (!$moduleid) {
            return false;
        }
        // ...then the id of the general section on the course.
        $sectionid = self::get_course_section_id($courseid, 0);
        if (!$sectionid) {
            return false;
        }
        // Chicken and egg situation here as assign_add_instance wants a course
        // module, but course module wants an instance... so create course
        // module with 0 instance to start with, then come back and update it
        // with the instance id once that's been added.
        $coursemodule = self::add_course_module($courseid, $moduleid, 0, $sectionid, $groupids);
        if (!$coursemodule) {
            return false;
        }

        // Now we have the course linked to mod_whatever, we need an actual
        // instance of an activity to put into that link.
        $id = false;
        switch ($module) {
            case "assign" :
                $activity = array(
                    'name' => $name,
                    'course' => $courseid,
                    'coursemodule' => $coursemodule["id"],
                    'intro' => $description,
                    'introformat' => 1,
                    // next few bits use defaults set in 
                    // Site administration / Plugins / Activity modules / Assignment / Assignment settings
                    'alwaysshowdescription' => get_config('assign','alwaysshowdescription'),
                    'submissiondrafts' => get_config('assign','submissiondrafts'),
                    'requiresubmissionstatement' => get_config('assign','requiresubmissionstatement'),
                    'sendnotifications' => get_config('assign','sendnotifications'),
                    'sendlatenotifications' => get_config('assign','sendlatenotifications'),
                    'sendstudentnotifications' => get_config('assign','sendstudentnotifications'),
                    'duedate' => $duedate,
                    'cutoffdate' => 0,
                    'allowsubmissionsfromdate' => $availabledate,
                    'grade' => $grade,
                    'completionsubmit' => 0,
                    'teamsubmission' => 0,
                    'requireallteammemberssubmit' => 0,
                    'blindmarking' => 0,
                    'markingworkflow' => 0,
                    'markingallocation' => 0,
                    'attemptreopenmethod' => 'none',
                    'maxattempts' => -1,
                    'assignsubmission_onlinetext_enabled' => $textsubmissions ? 1 : 0,
                    'assignsubmission_file_enabled' => $filesubmissions ? 1 : 0,
                    'assignsubmission_file_maxfiles' => $filesubmissions ? 1 : 0,
                    'assignsubmission_file_maxsizebytes' => 0,
                    'assignfeedback_comments_enabled' => 1
                );

                $id = assign_add_instance((object) $activity);
                break;
            default :
                throw new Exception("Only assign activities supported at the moment");
        }

        if (!$id) {
            return false;
        }
        $activity["id"] = $id;

        // Now go back to the course module link and fill in the instance id.
        $coursemodule["instance"] = $id;
        $DB->set_field("course_modules", "instance", $id, array("id" => $coursemodule["id"]));

        // There's a csv field in each course_sections record that defines
        // which course modules actually show up on the course view so add our new
        // course module link id to the list... also rebuilds the course cache.
        course_add_cm_to_section($courseid, $coursemodule["id"], 0);

        return $activity;
    }

    public static function rebuild_course_cache($courseid) {
        global $DB;
        $DB->set_field("course", "cacherev", time(), array("id" => $courseid));   // Necessary?
        rebuild_course_cache($courseid, true);
    }

    public static function update_course_activity($coursemoduleid, $name, $description, $textsubmissions, $filesubmissions, $grade,
                                                  $availabledate, $duedate, $groupids) {
        global $DB;
        $result = false;
        if (self::update_course_module_group_availability($coursemoduleid, $groupids)) {
            $coursemodule = get_coursemodule_from_id(false, $coursemoduleid);
            $activity = $DB->get_record($coursemodule->modname, array("id" => $coursemodule->instance));
            switch ($coursemodule->modname) {
                case "assign":
                    $activity->name = $name;
                    $activity->intro = $description;
                    $activity->grade = $grade;
                    $activity->allowsubmissionsfromdate = $availabledate;
                    $activity->duedate = $duedate;
                    $activity->cutoffdate = 0;

                    $activity->assignsubmission_onlinetext_enabled = $textsubmissions ? 1 : 0;

                    $activity->assignsubmission_file_enabled = $filesubmissions ? 1 : 0;
                    $activity->assignsubmission_file_maxfiles = $filesubmissions ? 1 : 0;
                    $activity->assignsubmission_file_maxsizebytes = 0;

                    $activity->assignfeedback_comments_enabled = 1;

                    $activity->instance = $activity->id;
                    $activity->coursemodule = $coursemoduleid;
                    $result = assign_update_instance($activity, null);
                    break;
                default :
                    throw new Exception("Only assign activities supported at the moment");
            }
            if ($result) {
                self::rebuild_course_cache($coursemodule->course);
            }
        }
        if ($result) {
            // Convert object to array.
            return json_decode(json_encode($activity), true);
        } else {
            return $result;
        }
    }

    /**
     * Returns true if the user is a participant in the specified assignment
     * @param int $userid
     * @param int $coursemoduleid
     * @return boolean
     */
    public static function user_is_assignment_participant($userid, $coursemoduleid) {
        $users = self::get_assignment_participants($coursemoduleid);
        return isset($users[$userid]);
    }

    public static function get_assignment_status_icon($status = false, $duedate = false) {
        global $CFG;
        $statusicon = "set";
        if ($status) {
            if ($status->graded || $status->submitted || $status->completed) {
                $statusicon = "done";
            } else if ($status->due) {
                $statusicon = "due";
            }
        } else {
            if ($duedate && $duedate < time()) {
                $statusicon = "due";
            }
        }
        $imgtitle = get_string('thishomeworkis' . $statusicon, 'block_homework');
        return '<img src="' . $CFG->wwwroot . '/blocks/homework/pix/homework-' . $statusicon .
                '-small.png" width="24" height="24" title="' . $imgtitle . '" alt="' . $statusicon . '" role="presentation">';
    }

    /**
     * Returns assignment name (formatted as a link with icon), optionally uses status info to set icon appropriately
     * @global object $CFG
     * @param int $coursemoduleid
     * @param object $status
     * @param int $duedate
     * @return string
     */
    public static function get_assignment_name($coursemoduleid, $status = false, $duedate = false) {
        global $CFG;
        $ass = self::get_assignment($coursemoduleid);
        $name = $ass->name;
        $icon = '';
        if ($status !== false) {
            $icon = self::get_assignment_status_icon($status, $duedate) . ' ';
        }

        return '<a href="' . $CFG->wwwroot . '/blocks/homework/assignment.php?course=' . $ass->courseid .
                '&id=' . $coursemoduleid . '">' . $icon . $name . '</a>';
    }

    public static function get_assignment_availability_text($coursemoduleid) {
        global $PAGE, $CFG;
        if ($CFG->enableavailability != 0) {
            $renderer = $PAGE->get_renderer('core', 'course');
            list($course, $cminfo) = get_course_and_cm_from_cmid($coursemoduleid, 'assign');
            return $renderer->course_section_cm_availability($cminfo);
        } else {
            return '';
        }
    }

    /**
     * Get assignment creator - only used for assignments created outside our homework block, as we track the user in our tracking
     * table for our assignments... this looks in the event log so is rather slower. Now shouldn't be used any more.
     * @global object $DB
     * @param int $coursemoduleid
     * @return string
     */
    /* public static function get_course_module_creator($coursemoduleid) {
        global $DB;
        $sql = "SELECT u.id, u.firstname, u.lastname
    FROM {logstore_standard_log} l JOIN {user} u ON (u.id = l.userid)
    WHERE objecttable = 'course_modules' AND component = 'core' AND action = 'created' AND crud = 'c' AND objectid = ? LIMIT 1";
        $user = $DB->get_record_sql($sql, array($coursemoduleid));
        $userid = '';
        $fullname = '';
        $firstname = '';
        $lastname = '';
        if ($user) {
            $userid = $user->id;
            $firstname = $user->firstname;
            $lastname = $user->lastname;
            $fullname = trim($firstname . ' ' . $lastname);
        }
        return array($userid, $fullname, $firstname, $lastname);
    } */

    public static function get_assignment_status($coursemoduleid, $userid, $getsubmissions = true) {
        $statuses = self::get_assignment_statuses($coursemoduleid, array($userid), $getsubmissions);
        return reset($statuses);
    }

    public static function get_assignment_statuses($coursemoduleid, $userids, $getsubmissions = true) {
        global $DB;
        $sql = "SELECT a.id, a.nosubmissions, a.duedate FROM {course_modules} cm JOIN {assign} a ON (a.id = cm.instance) "
                . "WHERE cm.id = ?";
        $ass = $DB->get_record_sql($sql, array($coursemoduleid));
        $nosubmissionrequired = $ass->nosubmissions == 1;
        // TODODMB need to detect variations of activity that we won't be supporting
        // at the marking stage e.g. blind marking and redirect user to normal
        // Moodle grading page etc. instead.
        $due = $ass->duedate < time();
        $assign = new assign(context_module::instance($coursemoduleid), null, null);

        $useridlist = implode(',', $userids);
        // TODODMB change useridlist to param?
        $sql = "SELECT u.id AS userid, u.firstname, u.lastname, asub.id AS submissionid, asub.status,
asubtext.onlinetext, asubfile.numfiles, ag.grade, afc.commenttext AS feedback,
ehi.completed, ehi.achievementid, ehi.achievementcomments, ehi.behaviourid, ehi.behaviourcomments
FROM {user} u
LEFT OUTER JOIN {assign_submission} asub ON (asub.userid = u.id AND asub.assignment = ? AND asub.latest = 1)
LEFT OUTER JOIN {assignsubmission_onlinetext} asubtext ON (asubtext.submission = asub.id AND asubtext.assignment = asub.assignment)
LEFT OUTER JOIN {assignsubmission_file} asubfile ON (asubfile.submission = asub.id AND asubfile.assignment = asub.assignment)
LEFT OUTER JOIN {assign_grades} ag ON (ag.assignment = asub.assignment AND ag.userid = u.id)
LEFT OUTER JOIN {grade_items} gi ON (gi.iteminstance = asub.assignment AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign')
LEFT OUTER JOIN {grade_grades} gg ON (gg.userid = asub.userid AND gg.itemid = gi.id)
LEFT OUTER JOIN {assignfeedback_comments} afc ON (afc.assignment = asub.assignment AND afc.grade = ag.id)
LEFT OUTER JOIN {block_homework_item} ehi ON (ehi.userid = u.id AND ehi.coursemoduleid = ?)
WHERE u.id IN ({$useridlist}) ORDER BY u.lastname, u.firstname";
        $records = $DB->get_records_sql($sql, array($ass->id, $coursemoduleid));
        $statuses = array();
        foreach ($records as $record) {
            $status = '';
            $grade = '';
            $graded = false;
            $feedback = '';
            $status = $record->status;
            $grade = $record->grade;
            $feedback = $record->feedback;
            if (($status == '') || ($status == 'new')) {
                if ($nosubmissionrequired) {
                    $status = get_string('noonlinesubmissionrequired', 'block_homework');
                } else {
                    $status = get_string('notsubmitted', 'block_homework');
                }
            }
            if ($record->completed == 1) {
                $status = get_string('completed', 'block_homework');
            }
            if ($grade == '') {
                $grade = get_string('notgraded', 'block_homework');
            } else {
                $graded = true;
                $status = get_string('graded', 'block_homework');
                $grade = $assign->get_user_grade($record->userid, false);
                $grade = $assign->display_grade($grade->grade, false, $record->userid);
            }
            $submitted = ($record->status == 'submitted');
            $submissionfiles = '';
            $gradeeditor = '';
            if ($getsubmissions) {
                if ($record->numfiles > 0) {
                    $submissionfiles = $assign->render_area_files('assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA,
                            $record->submissionid);
                }
                $gradeeditor = $assign->display_grade($record->grade, true, $record->userid);
            }
            $statuses[$record->userid] = (object) array('userid' => $record->userid,
                        'firstname' => $record->firstname,
                        'lastname' => $record->lastname,
                        'submitted' => $submitted,
                        'status' => ucfirst($status),
                        'submissionid' => $record->submissionid,
                        'graded' => $graded,
                        'grade' => $grade,
                        'rawgrade' => $record->grade,
                        'gradeeditor' => $gradeeditor,
                        'nosubmissionrequired' => $nosubmissionrequired,
                        'submissiontext' => $record->onlinetext,
                        'submissionnumfiles' => $record->numfiles,
                        'submissionfiles' => $submissionfiles,
                        'feedback' => $feedback,
                        'due' => $due,
                        'achievementid' => $record->achievementid,
                        'achievementcomments' => $record->achievementcomments,
                        'behaviourid' => $record->behaviourid,
                        'behaviourcomments' => $record->behaviourcomments,
                        'completed' => $record->completed == 1);
        }
        return $statuses;
    }

    public static function get_assignment_submission_count($coursemoduleid) {
        $ass = new assign(context_module::instance($coursemoduleid), null, null);
        return $ass->count_submissions(false); // False = don't count 'new' submissions i.e. unsubmitted.
    }

    public static function get_assignment_ungraded_submission_count($coursemoduleid) {
        $ass = new assign(context_module::instance($coursemoduleid), null, null);
        return $ass->count_submissions_need_grading();
    }

    public static function get_assignment_participants($coursemoduleid) {
        $ass = new assign(context_module::instance($coursemoduleid), null, null);
        return $ass->list_participants(0, true);
    }

    public static function get_course_id_from_cmid($coursemoduleid) {
        global $DB;
        $row = $DB->get_record('course_modules', array('id' => $coursemoduleid), 'course');
        if ($row) {
            return $row->course;
        } else {
            return null;
        }
    }

    public static function get_assignment_participants_and_statuses($coursemoduleid) {
        $participants = self::get_assignment_participants($coursemoduleid);
        $userids = array();
        foreach ($participants as $user) {
            $userids[] = $user->id;
        }
        return self::get_assignment_statuses($coursemoduleid, $userids);
    }

    public static function does_user_have_role($userid, $courseid, $rolename) {
        $roles = get_user_roles(context_course::instance($courseid), $userid, false);
        foreach ($roles as $role) {
            if (strtolower($role->shortname) == strtolower($rolename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return list of user's courses, or list of courses user has a specific capability on
     * will also include courses that the user has set homework in regardless of whether
     * user is on/managing the course as non-mis staff Moodle admin would not be able to
     * see their own homework assignments otherwise.
     * @param int $userid
     * @param string $withcapability
     * @return array
     */
    public static function get_users_courses($userid, $withcapability = '') {
        global $DB;
        $courses = array();
        if ($withcapability == '') {
            $usercourses = enrol_get_all_users_courses($userid);
            foreach ($usercourses as $course) {
                $courses[$course->id] = $course;
            }
            $sql = "SELECT DISTINCT c.id FROM {course} c JOIN {course_modules} cm ON (c.id = cm.course) "
                    . "JOIN {block_homework_assignment} eh ON (cm.id = eh.coursemoduleid) WHERE eh.userid = ?";
            $setcourses = $DB->get_records_sql($sql, array($userid));
            if ($setcourses) {
                foreach ($setcourses as $setcourse) {
                    if (!isset($courses[$setcourse->id])) {
                        $course = get_course($setcourse->id);
                        if ($course) {
                            $courses[$setcourse->id] = $course;
                        }
                    }
                }
            }
        } else {
            $allcourses = get_courses();
            $siteid = get_site()->id;
            foreach ($allcourses as $course) {
                if ($course->id != $siteid) {
                    $coursecontext = context_course::instance($course->id);
                    if (has_capability($withcapability, $coursecontext)) {
                        $courses[$course->id] = $course;
                    }
                }
            }
        }
        return $courses;
    }

    public static function get_teacher_users() {
        global $DB;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {role_assignments} ra ON (ra.userid = u.id)
            JOIN {role} r ON (r.id = ra.roleid)
            WHERE r.shortname IN ('manager','coursecreator','editingteacher','teacher')
            UNION SELECT DISTINCT u.id, u.firstname, u.lastname FROM {user} u
                  JOIN {block_homework_assignment} eh ON (u.id = eh.userid)
            ORDER BY lastname, firstname";

        $rows = $DB->get_records_sql($sql);

        $teachers = array();
        if ($rows) {
            foreach ($rows as $row) {
                $teachers[$row->id] = $row->firstname . ' ' . $row->lastname;
            }
        }
        return $teachers;
    }

    public static function get_assignment_marked_done_count($coursemoduleid, $userid = 0) {
        global $DB;
        $sql = "SELECT COUNT(*) FROM {block_homework_item} WHERE coursemoduleid = ? AND completed = 1";
        $params = array($coursemoduleid);
        if ($userid > 0) {
            $sql .= " AND userid = ?";
            $params[] = $userid;
        }
        return $DB->get_field_sql($sql, array($coursemoduleid, $userid));
    }

    public static function get_assignment_graded_submission_count($coursemoduleid, $userid = 0) {
        global $DB;
        $sql = "SELECT COUNT(*) FROM {assign_grades} ag JOIN {course_modules} cm ON (ag.assignment = cm.instance) "
                . "JOIN {modules} m ON (cm.module = m.id) WHERE m.name = 'assign' AND cm.id = ?";
        $params = array($coursemoduleid);
        if ($userid > 0) {
            $sql .= " AND grader = ?";
            $params[] = $userid;
        }
        return $DB->get_field_sql($sql, $params);
        // $ass = new assign(context_module::instance($coursemoduleid),null,null);
        // return $ass->count_grades();
    }

    public static function get_groups() {
        global $DB;
        $rows = $DB->get_records_sql("SELECT DISTINCT g.id, g.name FROM {groups} g "
                . "JOIN {groups_members} gm ON (gm.groupid = g.id) ORDER BY g.name");
        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $result[$row->id] = $row->name;
            }
        }
        return $result;
    }

    public static function get_group_members($groupid) {
        $rows = groups_get_members($groupid, 'u.id, u.firstname, u.lastname');
        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $result[] = (object) array('id' => $row->id,
                            'firstname' => $row->firstname,
                            'lastname' => $row->lastname,
                            'fullname' => $row->firstname . ' ' . $row->lastname,
                            'reversename' => $row->lastname . ' ' . $row->firstname);
            }
        }
        return $result;
    }

    /**
     * As embedded files in intro have urls to draft file areas, this changes them
     * to @@PLUGINFILE@@ - reason not using Moodle's own file_rewrite_urls_to_pluginfile
     * is that expects a draft item id and that will change with each page reload so
     * using browser refresh on submit page to redo a save during testing was not working.
     * @global object $CFG
     * @global object $USER
     * @param string $content
     * @return string
     */
    public static function rewrite_urls_to_pluginfile($content) {
        global $CFG, $USER;
        $usercontext = context_user::instance($USER->id);
        $urlstart = $CFG->wwwroot . '/draftfile.php/' . $usercontext->id . '/user/draft/';
        do {
            $start = strpos($content, $urlstart);
            if ($start !== false) {
                $end = strpos($content, "/", $start + strlen($urlstart));
                if ($end !== false) {
                    $url = substr($content, $start, $end - $start);
                    $content = str_replace($url, '@@PLUGINFILE@@', $content);
                }
            }
        } while ($start !== false);
        return $content;
    }

    public static function is_edulink_present() {
        global $CFG, $DB;
        $homeworkaccessfile = $CFG->dirroot . '/blocks/mis_integrator/classes/edulink/homework_api.php';
        if ((file_exists($homeworkaccessfile)) && ($DB->get_manager()->table_exists('mis_tr_msheet_entry'))) {
            return $homeworkaccessfile;
        }
        return false;
    }

    public static function get_user_type($userid) {
        $usertype = '';
        $edulink = self::is_edulink_present();
        if ($edulink) {
            require_once($edulink);
            $usertype = HomeworkAccess::get_user_type($userid);
        }
        if ($usertype == '') {
            if (is_siteadmin()) {
                $usertype = "employee";
            } else {
                // Figure out user type from their capabilities and roles.
                $courses = self::get_users_courses($userid);
                foreach ($courses as $course) {
                    $coursecontext = context_course::instance($course->id);
                    if (has_capability('mod/assign:addinstance', $coursecontext)) {
                        $usertype = "employee";
                        break;
                    }
                    if (self::does_user_have_role($userid, $course->id, 'student')) {
                        $usertype = "learner";
                    }
                }
            }
        }
        return $usertype;
    }

}
