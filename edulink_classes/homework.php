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
 * Various utility functions
 * @package    block_homework
 * @copyright  2017 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("moodle.php");
require_once("controls.php");

use block_homework\local\edulink as e;

defined('MOODLE_INTERNAL') || die();

$extended = $CFG->dirroot . "/availability/condition/user/homework/utils.php";
if (file_exists($extended)) {
    require_once($extended);
}

class block_homework_utils {

    public static function format_datetime($epoch) {
        if ($epoch <= 0) {
            return block_homework_moodle_utils::get_str('notapplicable');
        }
        $format = 'strftimedatetime';
        return userdate($epoch, get_string($format));
    }

    public static function format_date($epoch, $short = false) {
        if ($epoch <= 0) {
            return block_homework_moodle_utils::get_str('notapplicable');
        }
        $format = $short ? 'strftimedateshort' : 'strftimedate';
        return userdate($epoch, get_string($format));
    }

    public static function friendly_date($epoch) {
        if ($epoch <= 0) {
            return block_homework_moodle_utils::get_str('notapplicable');
        }
        $today = strtotime(date('Y-m-d'));
        $date = strtotime(date('Y-m-d', $epoch));
        $difference = round(($date - $today) / (24 * 60 * 60));
        if ($difference == 0) {
            return block_homework_moodle_utils::get_str('today');
        } else if ($difference == 1) {
            return block_homework_moodle_utils::get_str('tomorrow');
        } else if ($difference == -1) {
            return block_homework_moodle_utils::get_str('yesterday');
        } else if ($difference < -1) {
            return block_homework_moodle_utils::get_str('xdaysago', abs($difference));
        } else {
            return block_homework_moodle_utils::get_str('inxdays', $difference);
        }
    }

    public static function date_for_sorting($epoch) {
        return date('Ymd', $epoch);
    }

    public static function get_homework_for_course($courseid, $userid, $onlyifavailable, $maxdaysage = 28) {
        $homework = array();
        $activities = block_homework_moodle_utils::get_assignments_on_course($courseid, $maxdaysage);
        foreach ($activities as $activity) {
            $availabledate = (int) $activity->availabledate;
            $duedate = (int) $activity->duedate;
            if (!$onlyifavailable || ($availabledate <= time())) {
                $homework[] = $activity;
            }
        }
        return $homework;
    }

    public static function homework_items_for_block_list($homework, $userid, $showcoursename = false, $usertype = '') {
        global $CFG;
        $html = '';
        $orderedhomework = array();
        foreach ($homework as $item) {
            // Filter out any where due date is not set, or too far in future.
            $maxdaysfuture = get_config('block_homework', 'max_age_future');
            if ($maxdaysfuture < 7) {
                $maxdaysfuture = 7;
            } else if ($maxdaysfuture > 366) {
                $maxdaysfuture = 366;
            }
            if (($item->duedate == 0) || ($item->duedate > time() + $maxdaysfuture * 24 * 60 * 60)) {
                continue;
            }
            $orderedhomework[date('Ymd', $item->duedate) . '-' . $item->id] = $item;
        }
        ksort($orderedhomework);
        foreach ($orderedhomework as $item) {
            if (($usertype == "employee") && ($item->userid != $userid) && (!is_siteadmin())) {
                continue;
            }
            $context = context_module::instance($item->id);
            $userisparticipant = block_homework_moodle_utils::user_is_assignment_participant($userid, $item->id);
            if ((!has_capability('moodle/course:manageactivities', $context)) && (!$userisparticipant)) {
                continue;
            }
            $status = false;
            if ($userisparticipant) {
                $status = block_homework_moodle_utils::get_assignment_status($item->id, $userid);
            }

            // If a student or parent and the due date has passed and the homework was completed, don't show it.
            if (($usertype != "employee") && (date('Ymd', $item->duedate) < date('Ymd')) && ($status) &&
                (($status->submitted) || ($status->completed) || ($status->graded))) {
                continue;
            }

            $setbyname = '';
            if ($item->setbyname != '') {
                $setbyname = get_string('setby', 'block_homework') . ': ' . $item->setbyname;
            }
            $popup = $setbyname;
            if (($usertype == "employee") && ($item->availabledate > 0)) {
                if ($popup != '') {
                    $popup .= ', ';
                }
                $popup .= get_string('available', 'block_homework') . ': ' . self::friendly_date($item->availabledate);
            }
            if ($item->duedate > 0) {
                if ($popup != '') {
                    $popup .= ', ';
                }
                $popup .= get_string('due', 'block_homework') . ': ' . self::format_date($item->duedate, true);
            }
            if (($usertype == "employee") && ($CFG->enableavailability != 0)) {
                $avail = block_homework_moodle_utils::get_assignment_availability_text($item->id);
                if (!empty($avail)) {
                    $avail = str_ireplace(array('<div class="availabilityinfo ">', '</div>'), '', $avail);
                    if ($popup != '') {
                        $popup .= ', ';
                    }
                    $popup .= strip_tags($avail);
                }
            }

            $html .= '<div id="ond_homework_item_' . $item->id . '" title="' . $popup . '"><table class="ond_homework_item"><tr>' .
                    '<td class="ond_homework_item_status">' . block_homework_moodle_utils::get_assignment_status_icon($status,
                            $item->duedate) .
                    '</td>' .
                    '<td class="ond_homework_item_name">' . block_homework_moodle_utils::get_assignment_name($item->id);
            $coursename = '';
            if ($showcoursename) {
                if ($item->subject != '') {
                    $coursename = $item->subject;
                } else if (isset($item->coursename)) {
                    $coursename = $item->coursename;
                }
                if ($coursename != '') {
                    $html .= ' (' . $coursename . ')';
                }
            }
            $html .= '</td><td class="ond_homework_item_duedate">';
            $duebadge = new e\htmlBadge(get_string('dueondate', 'block_homework', self::friendly_date($item->duedate)));
            if ($item->duedate < time()) {
                $duebadge->set_class('badge ond_duetoday');
            }
            if ($status && ($status->submitted || $status->completed || $status->graded)) {
                $duebadge->set_class('badge ond_completed');
            }
            $html .= $duebadge->get_html();
            $html .= '</td></tr></table>';
            $html .= '</div>';
        }
        return $html;
    }

    public static function get_unmarked_homework_count_for_course($courseid, $userid) {
        if ($courseid == get_site()->id) {
            $courses = block_homework_moodle_utils::get_users_courses($userid);
        } else {
            $courses = array((object) array('id' => $courseid));
        }
        $unmarked = 0;
        foreach ($courses as $course) {
            $activities = block_homework_moodle_utils::get_assignments_on_course($course->id, 28); // 28 days max age.
            foreach ($activities as $activity) {
                if (($activity->grade != 0) && (!$activity->nosubmissions)) {
                    $unmarked += block_homework_moodle_utils::get_assignment_ungraded_submission_count($activity->id);
                }
            }
        }
        return $unmarked;
    }

    public static function add_homework_tracking_record($coursemoduleid, $userid, $subject, $duration,
                                                        $notifyother, $notifyotheremail,
                                                        $notifyparents, $notesforparentssubject, $notesforparents,
                                                        $notifylearners, $notesforlearnerssubject, $notesforlearners) {
        global $DB;

        $do = array(
            'coursemoduleid' => $coursemoduleid,
            'userid' => $userid,
            'subject' => $subject,
            'duration' => $duration,
            'notifyother' => $notifyother,
            'notifyotheremail' => $notifyotheremail,
            'notifyparents' => $notifyparents,
            'notesforparentssubject' => $notesforparentssubject,
            'notesforparents' => $notesforparents,
            'notifylearners' => $notifylearners,
            'notesforlearnerssubject' => $notesforlearnerssubject,
            'notesforlearners' => $notesforlearners
        );
        return $DB->insert_record('block_homework_assignment', $do);
    }

    public static function update_homework_tracking_record($coursemoduleid, $userid, $subject, $duration,
                                                           $notifyother, $notifyotheremail,
                                                           $notifyparents, $notesforparentssubject, $notesforparents,
                                                           $notifylearners, $notesforlearnerssubject, $notesforlearners) {
        global $DB;

        $id = $DB->get_field('block_homework_assignment', 'id', array('coursemoduleid' => $coursemoduleid));
        if ($id) {
            $do = array(
                'id' => $id,
                'userid' => $userid,
                'subject' => $subject,
                'duration' => $duration,
                'notifyother' => $notifyother,
                'notifyotheremail' => $notifyotheremail,
                'notifyparents' => $notifyparents,
                'notesforparentssubject' => $notesforparentssubject,
                'notesforparents' => $notesforparents,
                'notifylearners' => $notifylearners,
                'notesforlearnerssubject' => $notesforlearnerssubject,
                'notesforlearners' => $notesforlearners
            );
            return $DB->update_record('block_homework_assignment', $do);
        } else {
            return false;
        }
    }

    public static function remove_homework_tracking_record($coursemoduleid) {
        global $DB;

        $DB->delete_records('block_homework_notification', array('coursemoduleid' => $coursemoduleid));
        $DB->delete_records('block_homework_assignment', array('coursemoduleid' => $coursemoduleid));
        $DB->delete_records('block_homework_item', array('coursemoduleid' => $coursemoduleid));
    }

    public static function get_homework_statistics($fromdate = 0, $todate = 0, $courseid = 0, $setbyuserid = 0) {
        global $DB;

        // Change last two joins to OUTER JOIN if you want to include assignments set outside of the block.
        $sql = "SELECT cm.id, cm.added, a.name, cm.availability, eh.userid, eh.subject,
            u.firstname, u.lastname, c.id AS courseid, c.shortname AS coursename
            FROM {modules} m
            JOIN {course_modules} cm ON (cm.module = m.id)
            JOIN {course} c ON (c.id = cm.course)
            JOIN {assign} a ON (a.id = cm.instance)
            JOIN {block_homework_assignment} eh ON (eh.coursemoduleid = cm.id)
            JOIN {user} u ON (u.id = eh.userid)
            WHERE m.name = 'assign' AND a.duedate <> 0";
        $params = array();
        if ($fromdate > 0) {
            $sql .= " AND cm.added >= ?";
            $params[] = (int) $fromdate;
        }
        if ($todate > 0) {
            $sql .= " AND cm.added <= ?";
            $params[] = (int) $todate;
        }
        if (($courseid) && ($courseid != get_site()->id)) {
            $sql .= " AND c.id = ?";
            $params[] = (int) $courseid;
        }
        $rows = $DB->get_records_sql($sql, $params);
        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $userid = $row->userid;
                $firstname = $row->firstname;
                $lastname = $row->lastname;
                $fullname = trim($firstname . ' ' . $lastname);
                /* if ($userid == '') {
                    list($userid, $fullname, $firstname, $lastname) = block_homework_moodle_utils::get_course_module_creator(
                        $row->id);
                } */
                if (($setbyuserid == 0) || ($userid == $setbyuserid)) {
                    $result[] = array(
                        'coursemoduleid' => $row->id,
                        'added' => $row->added,
                        'name' => $row->name,
                        'availability' => $row->availability,
                        'userid' => $userid,
                        'fullname' => $fullname,
                        'firstname' => $row->firstname,
                        'lastname' => $row->lastname,
                        'courseid' => $row->courseid,
                        'coursename' => $row->coursename,
                        'subject' => $row->subject);
                }
            }
        }
        return $result;
    }

    public static function get_duration_description($duration) {
        if (empty($duration)) {
            return '';
        }
        if ($duration < 60) {
            return get_string('xminutes', 'block_homework', $duration);
        }
        $hours = $duration / 60;
        if ($hours == 1) {
            return get_string('onehour', 'block_homework');
        } else {
            return get_string('xhours', 'block_homework', $hours);
        }
    }

    public static function get_icon_html($iconid, $disabled = false) {
        global $CFG;
        $path = $CFG->wwwroot . "/blocks/homework/pix";
        $img = new e\htmlImage($iconid, "{$path}/{$iconid}.svg", 16, 16, get_string($iconid, 'block_homework'));
        $img->set_class('smallicon navicon');
        if ($disabled) {
            $img->set_class('ond_disabled');
        }
        return $img->get_html();
    }

    public static function int_to_hex($value, $digits = 2) {
        $hex = dechex($value);
        return str_pad($hex, $digits, '0', STR_PAD_LEFT);
    }

    public static function rgb_to_hex($value) {
        if (substr($value, 0, 1) == '#') {
            return $value;
        }
        $rgb = explode(',', $value);
        return '#' . self::int_to_hex($rgb[0]) . self::int_to_hex($rgb[1]) . self::int_to_hex($rgb[2]);
    }

    public static function send_new_assignment_notifications() {
        global $DB;
        // Get list of homework assignments where notifications haven't been sent and allowsubmissionsfromdate is past.
        $sql = 'SELECT bha.*, cm.course, a.name, a.duedate FROM {block_homework_assignment} bha ' .
                'JOIN {course_modules} cm ON (cm.id = bha.coursemoduleid) ' .
                'JOIN {assign} a ON (a.id = cm.instance) ' .
                'WHERE bha.notificationssent = 0 AND a.allowsubmissionsfromdate < ? AND a.duedate > ?';
        $now = time();
        $params = array($now, $now);
        $rows = $DB->get_records_sql($sql, $params);
        foreach ($rows as $row) {
            $assignmentduedate = self::format_date($row->duedate);
            if (empty($row->duration)) {
                $assignmentduration = get_string('durationnotspecified', 'block_homework');
            } else {
                $assignmentduration = self::get_duration_description($row->duration);
            }
            $assignmentowner = $DB->get_record('user', array('id' => $row->userid));

            if (($row->notifyparents == 1) && ($row->notesforparents != '')) {
                self::notify_parents($row->course, $row->coursemoduleid, $row->subject, $row->name, $assignmentowner,
                    $assignmentduedate, $assignmentduration, $row->notesforparentssubject, $row->notesforparents);
            }
            if (($row->notifylearners == 1) && ($row->notesforlearners != '')) {
                self::notify_learners($row->course, $row->coursemoduleid, $row->subject, $row->name, $assignmentowner,
                    $assignmentduedate, $assignmentduration, $row->notesforlearnerssubject, $row->notesforlearners);
            }
            self::notify_admin($row->course, $row->coursemoduleid, $row->subject, $row->name, $assignmentowner,
                ($row->notifyother == 1 ? $row->notifyotheremail : ''));

            $DB->update_record('block_homework_assignment', (object) array('id' => $row->id, 'notificationssent' => 1));
        }
    }

    public static function notify_parents($courseid, $coursemoduleid, $assignmentsubject, $assignmentname, $assignmentowner,
                                          $assignmentduedate, $assignmentduration, $messagesubject, $messagebody) {
        global $CFG;

        $edulink = \block_homework_moodle_utils::is_edulink_present();
        if (!$edulink) {
            return false;
        }
        require_once($edulink);

        $errors = array();
        $variables = array(
            'assignment_subject' => $assignmentsubject,
            'subject' => $assignmentsubject,
            'assignment_name' => $assignmentname,
            'assignment_due_date' => $assignmentduedate,
            'assignment_duration' => $assignmentduration,
            'assignment_link' => $CFG->wwwroot . '/blocks/homework/assignment.php?course=' . $courseid . '&id=' .
            $coursemoduleid,
            'child_name ' => '',
            'child_lastname' => '',
            'child_firstname' => '',
            'parent_title' => '',
            'parent_name' => '',
            'parent_lastname' => '',
            'parent_firstname' => '');
        $learners = block_homework_moodle_utils::get_assignment_participants($coursemoduleid);
        $learnerids = array();
        foreach ($learners as $id => $learner) {
            $learnerids[] = $id;
        }
        $learnerswithparents = HomeworkAccess::get_parents($learnerids);
        if (!is_array($learnerswithparents)) {
            return $learnerswithparents;
        }

        foreach ($learnerswithparents as $learner) {
            $variables["child_name"] = $learner["firstname"] . " " . $learner["lastname"];
            $variables["child_lastname"] = $learner["lastname"];
            $variables["child_firstname"] = $learner["firstname"];
            foreach ($learner["parents"] as $parent) {
                $variables["parent_title"] = $parent["title"];
                $variables["parent_name"] = $parent["firstname"] . " " . $parent["lastname"];
                $variables["parent_lastname"] = $parent["lastname"];
                $variables["parent_firstname"] = $parent["firstname"];
                $notificationbody = $messagebody;
                $notificationsubject = $messagesubject;
                foreach ($variables as $name => $value) {
                    $notificationbody = str_ireplace('[' . $name . ']', $value, $notificationbody);
                    $notificationsubject = str_ireplace('[' . $name . ']', $value, $notificationsubject);
                }
                // Moodle editor helpfully inserts full site URL into any link it thinks needs it so this gets rid of any resulting
                // duplicates if you use a link that is a template e.g. <a href="[assignment_link]">blah</a>.
                $notificationbody = str_replace($CFG->wwwroot . '/' . $CFG->wwwroot, $CFG->wwwroot, $notificationbody);
                $error = HomeworkAccess::email_parent($parent["id"], $notificationsubject, $notificationbody, $parent["email"],
                        $learner["localid"], $assignmentowner);
                if ($error != '') {
                    $errors[] = $parent["firstname"] . " " . $parent["lastname"] . ": " . $error;
                }
            }
        }
        if (count($errors) > 0) {
            return get_string('emailerrors', 'block_homework', array('count' => count($errors), 'example' => $errors[0]));
        }
        return '';
    }

    public static function notify_learners($courseid, $coursemoduleid, $assignmentsubject, $assignmentname, $assignmentowner,
                                           $assignmentduedate, $assignmentduration, $messagesubject, $messagebody) {
        global $CFG, $DB;

        $errors = array();
        $variables = array(
            'assignment_subject' => $assignmentsubject,
            'subject' => $assignmentsubject,
            'assignment_name' => $assignmentname,
            'assignment_due_date' => $assignmentduedate,
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
            $notificationbody = $messagebody;
            $notificationsubject = $messagesubject;
            foreach ($variables as $name => $value) {
                $notificationbody = str_ireplace('[' . $name . ']', $value, $notificationbody);
                $notificationsubject = str_ireplace('[' . $name . ']', $value, $notificationsubject);
            }
            // Moodle editor helpfully inserts full site URL into any link it thinks needs it so this gets rid of any resulting
            // duplicates if you use a link that is a template e.g. <a href="[assignment_link]">blah</a>.
            $notificationbody = str_replace($CFG->wwwroot . '/' . $CFG->wwwroot, $CFG->wwwroot, $notificationbody);
            $messageid = block_homework_moodle_utils::send_message($assignmentowner, $learner->id, $notificationsubject,
                    $notificationbody, $variables["assignment_link"], $variables["assignment_name"], $courseid);
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

    public static function notify_admin($courseid, $coursemoduleid, $assignmentsubject, $assignmentname, $assignmentowner,
            $notifyotheremail) {
        global $CFG;

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
            $messageid = block_homework_moodle_utils::send_message($assignmentowner, $assignmentowner, $notificationsubject,
                    $notificationbody, $variables["assignment_link"], $variables["assignment_name"], $courseid);
            if (!$messageid) {
                $errors[] = fullname($assignmentowner) . ": " . get_string('messagesendfailed', 'block_homework');
            } else {
                if (($lognotifications) && (class_exists('block_homework_utils_extended'))) {
                    block_homework_utils_extended::log_notification($coursemoduleid, $assignmentowner->id, $assignmentowner->email,
                        $messageid);
                }
            }
        }
        if ($notifyotheremail != '') {
            if (class_exists('block_homework_utils_extended')) {
                $error = block_homework_utils_extended::send_email($assignmentowner, $notifyotheremail, '', $notificationsubject,
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
}
