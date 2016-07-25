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
 * Base utility class for Ajax scripts
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . "/../../../config.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once(__DIR__ . "/../edulink_classes/homework.php");
require_once(__DIR__ . "/../edulink_classes/moodle.php");

class ajaxgen_base {

    protected function get_str($id, $params = null) {
        return block_homework_moodle_utils::get_str($id, $params);
    }

    protected function random_pastel_rgb($bias = 50) {
        $r = round(((float) rand() / (float) getrandmax()) * 127) + $bias;
        $g = round(((float) rand() / (float) getrandmax()) * 127) + $bias;
        $b = round(((float) rand() / (float) getrandmax()) * 127) + $bias;
        return array($r, $g, $b);
    }

    protected function rgba($rgb, $opacity) {
        return "rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},{$opacity})";
    }
}