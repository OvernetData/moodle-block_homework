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
 * @copyright  2017 Overnet Data Ltd. (@link http://www.overnetdata.com)
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
                $logtable = new xmldb_table('edulink_homework');
                $dbman->rename_table($logtable, 'block_homework_assignment');
            }
            if ($dbman->table_exists('edulink_homework_items')) {
                print '<p>Renaming edulink_homework_items table to block_homework_item</p>';
                $logtable = new xmldb_table('edulink_homework_items');
                $dbman->rename_table($logtable, 'block_homework_item');
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result = false;
        }
    }

    if ($oldversion < 2016101300) {
        $transaction = $DB->start_delegated_transaction();
        try {
            print '<h2>1.1.01</h2>';
            $dbman = $DB->get_manager();
            print '<p>Adding learner notification fields</p>';
            $asstable = new xmldb_table('block_homework_assignment');
            $newfields = array();
            $newfields[] = new xmldb_field('notifylearners', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $newfields[] = new xmldb_field('notesforlearnerssubject', XMLDB_TYPE_CHAR, '255');
            $newfields[] = new xmldb_field('notesforlearners', XMLDB_TYPE_TEXT);
            $newfields[] = new xmldb_field('notifyother', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $newfields[] = new xmldb_field('notifyotheremail', XMLDB_TYPE_CHAR, '255');
            foreach ($newfields as $field) {
                if (!$dbman->field_exists($asstable, $field)) {
                    $dbman->add_field($asstable, $field);
                }
            }
            $logtable = new xmldb_table('block_homework_notification');
            if (!$dbman->table_exists($logtable)) {
                $logtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
                $logtable->add_field('coursemoduleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
                $logtable->add_field('recipientuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
                $logtable->add_field('recipientemail', XMLDB_TYPE_CHAR, '255', null, null, null, null);
                $logtable->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
                $logtable->add_field('messageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
                $logtable->add_field('messagereadid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
                $logtable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
                $logtable->add_index('ix_i_coursemoduleid', XMLDB_INDEX_NOTUNIQUE, array('coursemoduleid'));
                $dbman->create_table($logtable);
            }

            print '<p>Writing default configuration settings</p>';
            set_config('max_age_view_all', 40, 'block_homework');
            set_config('max_age_employee', 1, 'block_homework');
            set_config('max_age_other', 8, 'block_homework');
            set_config('submissions', 3, 'block_homework');
            set_config('notify_creator', 0, 'block_homework');
            set_config('notify_other', 0, 'block_homework');
            set_config('new_assign_notification_subject',
                    get_string('newassignmentnotificationsubjectdefault', 'block_homework'), 'block_homework');
            set_config('new_assign_notification_message',
                    get_string('newassignmentnotificationmessagedefault', 'block_homework'), 'block_homework');
            set_config('log_notifications', 0, 'block_homework');
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result = false;
        }
    }

    if ($oldversion < 2016110700) {
        $transaction = $DB->start_delegated_transaction();
        try {
            print '<h2>1.1.03</h2>';
            print '<p>Writing default configuration settings</p>';
            set_config('default_notify_parents', 0, 'block_homework');
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result = false;
        }
    }

    if ($oldversion < 2017051500) {
        $transaction = $DB->start_delegated_transaction();
        try {
            print '<h2>1.1.16</h2>';
            $dbman = $DB->get_manager();
            print '<p>Adding notificationssent field to block_homework_assignment table</p>';
            $asstable = new xmldb_table('block_homework_assignment');
            $nsfield = new xmldb_field('notificationssent', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            if (!$dbman->field_exists($asstable, $nsfield)) {
                $dbman->add_field($asstable, $nsfield);
                $nsindex = new xmldb_index('ix_notificationssent');
                $nsindex->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('notificationssent'));
                $dbman->add_index($asstable, $nsindex);
                // Assume notifications sent for homework assignments that are already live.
                $sql = 'UPDATE {block_homework_assignment} bha SET notificationssent = 1 WHERE EXISTS(' .
                        'SELECT 1 FROM {assign} a JOIN {course_modules} cm ON (a.id = cm.instance) '
                        . 'WHERE cm.id = bha.coursemoduleid AND a.allowsubmissionsfromdate < ?)';
                $DB->execute($sql, array(time()));
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result = false;
        }
    }

    return $result;
}