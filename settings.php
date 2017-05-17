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
 * Settings for the Homework block
 *
 * @copyright 2017 Overnet Data Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_homework
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    global $CFG;

    $yesno = array(0 => get_string('no'), 1 => get_string('yes'));
    $onoff = array(0 => get_string('off', 'block_homework'), 1 => get_string('on', 'block_homework'));
    $days = array();
    for ($i = 1; $i <= 14; $i++) {
        $days[$i] = $i;
    }
    $daysfuture = array();
    $daysyear = array();
    for ($i = 7; $i <= 366; $i++) {
        $daysfuture[$i] = $i;
        if ($i >= 40) {
            $daysyear[$i] = $i;
        }
    }

    $settings->add(new admin_setting_configselect('block_homework/max_age_future', get_string('maxagefuture', 'block_homework'),
                       get_string('maxagefuture_help', 'block_homework'), 14, $daysfuture));
    $settings->add(new admin_setting_configselect('block_homework/max_age_view_all', get_string('maxageviewall', 'block_homework'),
                       get_string('maxageviewall_help', 'block_homework'), 40, $daysyear));
    $settings->add(new admin_setting_configselect('block_homework/max_age_employee', get_string('maxageemployee', 'block_homework'),
                       get_string('maxageemployee_help', 'block_homework'), 1, $days));
    $settings->add(new admin_setting_configselect('block_homework/max_age_other', get_string('maxageother', 'block_homework'),
                       get_string('maxageother_help', 'block_homework'), 8, $days));

    $settings->add(new admin_setting_configselect('block_homework/allow_showdescription',
                       get_string('allowshowdescription', 'block_homework'),
                       get_string('allowshowdescription_help', 'block_homework'), 1, $yesno));

    $settings->add(new admin_setting_configselect('block_homework/default_showdescription',
                       get_string('defaultshowdescription', 'block_homework'),
                       get_string('defaultshowdescription_help', 'block_homework'), 0, $onoff));

    $submissionoptions = array(0 => get_string("noonlinesubs", 'block_homework'),
        1 => get_string("onlinetextsubs", 'block_homework'),
        2 => get_string("onlinefilesubs", 'block_homework'),
        3 => get_string("onlinetextorfilesubs", 'block_homework'));
    $settings->add(new admin_setting_configselect('block_homework/submissions', get_string('submissionsdefault', 'block_homework'),
                       get_string('submissionsdefault_help', 'block_homework'), 3, $submissionoptions));

    $settings->add(new admin_setting_configselect('block_homework/require_restriction',
                       get_string('requirerestriction', 'block_homework'),
                       get_string('requirerestriction_help', 'block_homework'), 0, $yesno));

    $settings->add(new admin_setting_configselect('block_homework/default_notify_parents',
                       get_string('defaultnotifyparents', 'block_homework'),
                       get_string('defaultnotifyparents_help', 'block_homework'), 0, $onoff));

    /* settings specific to Homework block extended by proprietary availability plugin */
    $extrasettings = $CFG->dirroot . '/availability/condition/user/homework/settings.php';
    if (file_exists($extrasettings)) {
        require_once($extrasettings);
    }
}


