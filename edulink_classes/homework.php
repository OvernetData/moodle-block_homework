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
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("moodle.php");
require_once("controls.php");

use block_homework\local\edulink as e;

defined('MOODLE_INTERNAL') || die();

class block_homework_utils {

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
            if (($item->duedate == 0) || ($item->duedate > time() + 14 * 24 * 60 * 60)) {
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

    public static function add_homework_tracking_record($coursemoduleid, $userid, $subject, $duration, $notifyparents,
                                                        $notesforparentssubject, $notesforparents) {
        global $DB;

        $do = array(
            'coursemoduleid' => $coursemoduleid,
            'userid' => $userid,
            'subject' => $subject,
            'duration' => $duration,
            'notifyparents' => $notifyparents,
            'notesforparentssubject' => $notesforparentssubject,
            'notesforparents' => $notesforparents
        );
        return $DB->insert_record('block_homework_assignment', $do);
    }

    public static function update_homework_tracking_record($coursemoduleid, $userid, $subject, $duration, $notifyparents,
                                                           $notesforparentssubject, $notesforparents) {
        global $DB;

        $id = $DB->get_field('block_homework_assignment', 'id', array('coursemoduleid' => $coursemoduleid));
        if ($id) {
            $do = array(
                'id' => $id,
                'userid' => $userid,
                'subject' => $subject,
                'duration' => $duration,
                'notifyparents' => $notifyparents,
                'notesforparentssubject' => $notesforparentssubject,
                'notesforparents' => $notesforparents
            );
            return $DB->update_record('block_homework_assignment', $do);
        } else {
            return false;
        }
    }

    public static function remove_homework_tracking_record($coursemoduleid) {
        global $DB;

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

}
