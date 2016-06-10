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
 * Upgrade from one block version to another
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

function xmldb_block_homework_upgrade($oldversion = 0) {
    global $DB;
    $result = true;

    if ($oldversion < 2016061000) {
        $transaction = $DB->start_delegated_transaction();
        try {
            print '<h2>1.0.23</h2>';
            $dbman = $DB->get_manager();
            if ($dbman->table_exists('edulink_homework')) {
                print '<p>Renaming edulink_homework table to block_homework_assignment</p>';
                $table = new xmldb_table('edulink_homework');
                $dbman->rename_table($table, 'block_homework_assignment');
            }
            if ($dbman->table_exists('edulink_homework_items')) {
                print '<p>Renaming edulink_homework_items table to block_homework_item</p>';
                $table = new xmldb_table('edulink_homework_items');
                $dbman->rename_table($table, 'block_homework_item');
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result = false;
        }
    }

    return $result;
}