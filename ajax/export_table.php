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
 * Export table (passed as JSON) to CSV, used by filterable_exportable_table.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../../../config.php");
require_once($CFG->libdir . '/moodlelib.php');

class export_table {

    protected $exportfilename;
    protected $exporttype;
    protected $exportfilter;
    protected $tabledata;

    public static function factory() {
        return new export_table();
    }

    public function __construct() {
        $this->exportfilename = optional_param('filename', 'exported', PARAM_RAW);
        $this->exporttype = optional_param('type', 'csv', PARAM_RAW);
        if (empty($this->exporttype)) {
            die("No export type selected");
        }
        if ($this->exporttype != 'xls') {
            $this->exporttype = 'csv';
        }
        $this->tabledata = optional_param('tabledata', '', PARAM_RAW);
        if (!empty($this->tabledata)) {
            $this->tabledata = json_decode(urldecode($this->tabledata));
            header("Content-Disposition: attachment; filename=\"" . $this->exportfilename . "." . $this->exporttype . "\"");
            if ($this->exporttype == 'csv') {
                header("Content-Type: text/csv; name=\"" . $this->exportfilename . ".csv\"");
            } else {
                header("Content-Type: application/vnd.ms-excel");
            }
            header("\n");
            if (empty($this->tabledata)) {
                print "JSON error: " . json_last_error_msg();
            } else {
                if ($this->exporttype == 'csv') {
                    $this->export_csv();
                } else {
                    $this->export_xls();
                }
            }
        } else {
            die('Invalid table data supplied');
        }
    }

    protected function export_csv() {
        foreach ($this->tabledata as $row) {
            array_walk($row, array($this, '_clean_data_for_csv'));
            print implode(",", $row) . "\r\n";
        }
    }

    protected function _clean_data_for_csv(&$str) {
        $str = trim(strip_tags($str));
        if (strstr($str, '"')) {
            $str = str_replace('"', '""', $str);
        }
        $str = '"' . $str . '"';
    }

    // Tab separated really, not a proper excel file.
    protected function export_xls() {
        foreach ($this->tabledata as $row) {
            array_walk($row, array($this, '_clean_data_for_excel'));
            print implode("\t", $row) . "\r\n";
        }
    }

    protected function _clean_data_for_excel(&$str) {
        $str = trim(strip_tags($str));
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if (strstr($str, '"')) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }
    }
}

export_table::factory();