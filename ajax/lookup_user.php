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
 * User lookup, used in Reports | Student Grades | Student selector if EduLink integration not installed
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../../../config.php");

global $DB, $PAGE, $OUTPUT;

require_login();
require_sesskey();
$PAGE->set_context(context_system::instance());

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$response = array('data' => array('total_count' => 0, 'items' => array()));

$namestr = strtolower(trim(optional_param('q', '', PARAM_RAW)));
if (strlen($namestr) < 2) {
    $namestr = '';
}
$select = '';
if (strpos($namestr, ' ')) {
    $tmparr = explode(' ', $namestr);
    $firstname = reset($tmparr);
    $lastname = end($tmparr);
    $firstname = '%' . $firstname . '%';
    $lastname = '%' . $lastname . '%';
} else {
    $firstname = '%' . $namestr . '%';
    $lastname = $firstname;
}
$emptyname = '%%';
if (($firstname != $emptyname) && ($lastname != $emptyname)) {
    $select = "LOWER(firstname) LIKE ? ";
    $select .= (strpos($namestr, ' ')) ? "AND" : "OR";
    $select .= " LOWER(lastname) LIKE ?";
    $select = " WHERE (" . $select . ")";

    $select = "SELECT id, firstname, firstnamephonetic, middlename, lastname, lastnamephonetic, alternatename, email, "
            . "picture, imagealt "
            . "FROM {user} " . $select . " ORDER BY lastname, firstname";

    $rs = $DB->get_records_sql($select, array($firstname, $lastname));

    $response['data']['total_count'] = count($rs);

    foreach ($rs as $row) {
        $url = '';
        if ($row->picture) {
            $url = $OUTPUT->user_picture($row, array('link' => false, 'alttext' => false, 'class' => '', 'size' => 32));
            $url = substr($url, strpos($url, '"') + 1);
            $url = substr($url, 0, strpos($url, '"'));
        }
        $response['data']['items'][] = array(
            'id' => $row->id,
            'surname' => $row->lastname,
            'forename' => $row->firstname,
            'formgroup' => "",
            'yeargroup' => "",
            'community' => "",
            'photourl' => $url
        );
    }
}
echo json_encode($response, JSON_PRETTY_PRINT);