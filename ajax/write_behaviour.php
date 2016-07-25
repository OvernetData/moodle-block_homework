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
 * Write achievement or behaviour record back to SIMS, used by mark.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../edulink_classes/moodle.php');
require_once(block_homework_moodle_utils::is_edulink_present());

global $DB, $PAGE, $OUTPUT;

require_login();
require_sesskey();
$PAGE->set_context(context_system::instance());

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$action = optional_param('action', '', PARAM_RAW);
$cmid = optional_param('cmid', 0, PARAM_INT);
$learnerid = optional_param('learnerid', 0, PARAM_INT);
$learnername = optional_param('name', '', PARAM_RAW);
$staffid = optional_param($action . 'reporter', 0, PARAM_INT);
$typeid = optional_param($action . 'type', 0, PARAM_INT);
$activityid = optional_param($action . 'activity', 0, PARAM_INT);
$statusid = optional_param($action . 'status', 0, PARAM_INT);
$comments = optional_param($action . 'comments', '', PARAM_RAW);
$points = optional_param($action . 'points', 0, PARAM_INT);

if ((($action != "achievement") && ($action != "behaviour")) || ($cmid == 0) || ($learnerid == 0) || ($learnername == '') ||
        ($staffid == 0) || ($typeid == 0) || ($activityid == 0) || (($action == "behaviour") && ($statusid == 0)) ||
        ($comments == '')) {
    $result = array('success' => false, 'error' => 'Required parameter(s) not supplied', 'name' => $learnername);
} else {
    try {
        $result = null;
        $checkexisting = array('coursemoduleid' => $cmid,
            'userid' => $learnerid);
        $existing = $DB->get_records('block_homework_item', $checkexisting);
        if ($existing) {
            $existing = reset($existing);
        }
        if ($action == "achievement") {
            if (($existing) && ($existing->achievementcomments != '')) {
                $result = array('success' => false, 'error' => 'Achievement record already created for this assignment',
                    'name' => $learnername);
            } else {
                $newid = HomeworkAccess::write_achievement($staffid, $learnerid, $typeid, $activityid, $comments, $points);
            }
        } else {
            if (($existing) && ($existing->behaviourcomments != '')) {
                $result = array('success' => false, 'error' => 'Behaviour record already created for this assignment',
                    'name' => $learnername);
            } else {
                $newid = HomeworkAccess::write_behaviour($staffid, $learnerid, $typeid, $activityid, $statusid, $comments, $points);
            }
        }
        if ($result == null) {
            $record = array();
            if ($action == "achievement") {
                $record["achievementid"] = $newid;
                $record["achievementcomments"] = $comments;
            } else {
                $record["behaviourid"] = $newid;
                $record["behaviourcomments"] = $comments;
            }
            if ($existing) {
                $record["id"] = $existing->id;
                $DB->update_record('block_homework_item', (object) $record);
            } else {
                $record["coursemoduleid"] = $cmid;
                $record["userid"] = $learnerid;
                $DB->insert_record('block_homework_item', (object) $record);
            }
            $result = array('success' => true, 'error' => '', 'name' => $learnername);
        }
    } catch (Exception $e) {
        $result = array('success' => false, 'error' => $e->getMessage(), 'name' => $learnername);
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
