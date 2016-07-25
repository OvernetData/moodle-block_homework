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
 * Mark an individual assignment, used by mark.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../mod/assign/externallib.php');
require_once(__DIR__ . '/../edulink_classes/moodle.php');

global $DB, $PAGE, $OUTPUT;

require_login();
require_sesskey();
$PAGE->set_context(context_system::instance());

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$cmid = optional_param('cmid', 0, PARAM_INT);
$learnerid = optional_param('learnerid', 0, PARAM_INT);
$learnername = optional_param('name', '', PARAM_RAW);
$grade = optional_param('grade', '', PARAM_RAW);
$feedback = optional_param('feedback', '', PARAM_RAW);

if (($cmid == 0) || ($learnerid == 0) || ($learnername == '')) {
    $result = array('success' => false, 'error' => 'Required parameter(s) not supplied', 'name' => $learnername);
} else {
    $gradedata = (object) array('assignfeedbackcomments_editor' => array('text' => $feedback, 'format' => 1),
            'files_filemanager' => null,
            'addattempt' => 0,
            'attemptnumber' => -1,
            'workflowstate' => '',
            'applytoall' => 0,
            'grade' => $grade);
    try {
        $cm = get_coursemodule_from_id(false, $cmid);
        $context = context_module::instance($cm->id);
        $assignment = new assign($context, $cm, null);
        // Can't use mod_assign_external::save_grade as it barfs on feedback without a grade!
        $assignment->save_grade($learnerid, $gradedata);
        $result = array('success' => true, 'error' => '', 'name' => $learnername);
    } catch (Exception $e) {
        $result = array('success' => false, 'error' => $e->getMessage(), 'name' => $learnername);
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);