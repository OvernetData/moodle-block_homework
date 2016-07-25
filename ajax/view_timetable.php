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
 * Return homework list or timetable data, used by view.js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . "/../edulink_classes/controls.php");
require_once("ajaxbase.php");

use block_homework\local\edulink as e;

class ajaxgen_view_timetable extends ajaxgen_base {

    protected $edulinkpresent = false;

    public static function factory() {
        return new ajaxgen_view_timetable();
    }

    public function __construct() {
        global $CFG, $USER, $PAGE;
        require_login();
        $PAGE->set_context(\context_system::instance());

        $homeworkaccessfile = block_homework_moodle_utils::is_edulink_present();
        if ($homeworkaccessfile !== false) {
            $this->edulinkpresent = true;
            require_once($homeworkaccessfile);
        }
        $siteid = get_site()->id;
        $courseid = optional_param('course', $siteid, PARAM_INT);
        $displayuserid = optional_param('displayuser', $USER->id, PARAM_INT);
        $userid = optional_param('user', $USER->id, PARAM_INT);
        $usertype = optional_param('usertype', "learner", PARAM_ALPHANUMEXT);
        $date = optional_param('date', date('Y-m-d'), PARAM_ALPHANUMEXT);
        $marking = optional_param('marking', 0, PARAM_INT) == 1;

        if ($courseid == $siteid) {
            $courses = block_homework_moodle_utils::get_users_courses($displayuserid);
        } else {
            $courses = array(get_course($courseid));
        }

        // Homework listing.
        $htmllist = "";
        $table = new e\htmlTable('ond_homework_list');
        if ($usertype == "employee") {
            if ($marking) {
                $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('actions')));
            } else {
                $table->add_header(new e\htmlTableHeader(null, "ond_cell_date", $this->get_str('dateavailable')));
            }
        }
        if ($courseid == $siteid) { // Show coursename if all courses view.
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('course')));
        }
        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('subject')));
        $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('activity')));
        if (($usertype == "learner") || ($usertype == "parent")) {
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('setby')));
        } else if ($usertype == "employee") {
            if ($CFG->enableavailability != 0) {
                $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('restrictions')));
            }
            $table->add_header(new e\htmlTableHeader(null, "ond_cell_number", $this->get_str('submissionsvsparticipants')));
            $table->add_header(new e\htmlTableHeader(null, "ond_cell_number", $this->get_str('gradedsubmissions')));
            $table->add_header(new e\htmlTableHeader(null, "ond_cell_number", $this->get_str('ungradedsubmissions')));
        }
        if (($usertype == "learner") || ($usertype == "parent")) {
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('status')));
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('grade')));
            $table->add_header(new e\htmlTableHeader(null, null, $this->get_str('feedback')));
        }
        $table->add_header(new e\htmlTableHeader(null, "ond_cell_date", $this->get_str('duedate')));
        $totalhomework = 0;

        foreach ($courses as $course) {
            // Get ALL assignment activities on the course regardless of whether the user is a participant or creator.
            // TODO - replace false with !$this->isteacher?
            $homeworkactivities = block_homework_utils::get_homework_for_course($course->id, $displayuserid, false, 366);
            foreach ($homeworkactivities as $item) {
                $context = context_module::instance($item->id);
                // Skip this one if the user is not an activity creator (teacher) or participant in the specific
                // assignment (student).
                if (($usertype != "employee") &&
                    (!block_homework_moodle_utils::user_is_assignment_participant($displayuserid, $item->id))) {
                    continue;
                }
                $table->add_row();
                $removethisrow = false;
                if ($usertype == "employee") {
                    if ($marking) {
                        $markurl = $CFG->wwwroot . '/blocks/homework/mark.php?course=' . $item->courseid . '&id=' .
                            $item->id;
                        $marklink = new e\htmlHyperLink(null, $this->get_str('mark'), $markurl, $this->get_str('markhomework'));
                        $marklink->set_class('ond_mark_button');
                        $markcell = new e\htmlTableCell(null, null, $marklink->get_html());
                        $table->add_cell($markcell);
                    } else {
                        $datecell = new e\htmlTableCell(null, "ond_cell_date", block_homework_utils::format_date($item->availabledate));
                        $datecell->set_property('data-order', $item->availabledate);
                        $table->add_cell($datecell);
                    }
                }
                if ($courseid == $siteid) { // Show coursename if all courses view.
                    $table->add_cell(new e\htmlTableCell(null, null, $course->shortname));
                }
                $table->add_cell(new e\htmlTableCell(null, null, $item->subject));

                // Assignment name comes back as a bit of html so it's linked and has a little graphic
                // we replace the graphic with a status graphic but only in student view.
                $status = false;
                if (($usertype == "learner") || ($usertype == "parent")) {
                    $status = block_homework_moodle_utils::get_assignment_status($item->id, $displayuserid);
                }
                $name = block_homework_moodle_utils::get_assignment_name($item->id, $status);
                $assignmentcell = new e\htmlTableCell(null, null, $name);
                $assignmentcell->set_property("data-for-export", $item->name);
                $table->add_cell($assignmentcell);

                if (($usertype == "learner") || ($usertype == "parent")) {
                    $table->add_cell(new e\htmlTableCell(null, null, $item->setbyname));
                    $table->add_cell(new e\htmlTableCell(null, null, $status->status));
                    $table->add_cell(new e\htmlTableCell(null, null, $status->grade));
                    $table->add_cell(new e\htmlTableCell(null, null, $status->feedback));
                } else if ($usertype == "employee") {
                    if ($CFG->enableavailability != 0) {
                        $avail = block_homework_moodle_utils::get_assignment_availability_text($item->id);
                        if (!empty($avail)) {
                            $avail = str_ireplace(array('<div class="availabilityinfo ">', '</div>'), '', $avail);
                        }
                        $table->add_cell(new e\htmlTableCell(null, null, $avail));
                    }
                    $participants = block_homework_moodle_utils::get_assignment_participants($item->id);
                    $removethisrow = (count($participants) == 0) && ($marking);
                    $na = $this->get_str('notapplicable');
                    $marked = 0;
                    $unmarked = 0;
                    if (!$item->nosubmissions) {
                        $marked = block_homework_moodle_utils::get_assignment_graded_submission_count($item->id);
                        $unmarked = block_homework_moodle_utils::get_assignment_ungraded_submission_count($item->id);
                    }
                    $submissions = ($marked + $unmarked) . ' / ' . count($participants);
                    if ($item->nosubmissions || ($item->grade == 0)) {
                        $marked = $na;
                        $unmarked = $na;
                    }
                    $table->add_cell(new e\htmlTableCell(null, "ond_cell_number", $submissions));
                    $table->add_cell(new e\htmlTableCell(null, "ond_cell_number", $marked));
                    $table->add_cell(new e\htmlTableCell(null, "ond_cell_number", $unmarked));
                }
                $dueclass = '';
                // Only highlight submission date if teacher view or student view and not yet submitted.
                if ((!$status) || (!$status->submitted && !$status->graded)) {
                    if (date('Ymd', $item->duedate) == date('Ymd')) {
                        $dueclass = ' ond_due';
                    } else if ($item->duedate < time()) {
                        $dueclass = ' ond_overdue';
                    }
                }
                $duedatecell = new e\htmlTableCell(null, "ond_cell_date" . $dueclass, block_homework_utils::format_date($item->duedate));
                $duedatecell->set_property('data-order', $item->duedate);
                $table->add_cell($duedatecell);

                if ($removethisrow) {
                    $table->remove_row();
                    continue;
                }
                $totalhomework++;
            }
        }
        if ($totalhomework == 0) {
            $label = new e\htmlLabel('label-info', $this->get_str('nohomework'));
            $htmllist = $label->get_html();
        } else {
            $htmllist .= $table->get_html();
            $htmllist .= '</div>';
        }

        // Timetable.
        if ($this->edulinkpresent) {
            $timetable = HomeworkAccess::get_timetable($displayuserid, $date);
        } else {
            $timetable = array();
        }
        if (empty($timetable)) {
            $label = new e\htmlLabel('label-info', $this->get_str("notimetable"));
            $htmltimetable = $label->get_html();
        } else {
            $table = new e\htmlTable('ond_homework_timetable');
            $table->add_header(new e\htmlTableHeader(null, null, "Day"));
            $periods = reset($timetable)["periods"];
            $periodheaders = array();
            foreach ($periods as $position => $period) {
                $periodheaders[$position] = "Period " . $position;
            }
            $homeworkcache = array();

            $adjuster = 0;
            // Non teaching days don't get returned by timetable, but as dates aren't in the data, we can only
            // tell if days are missing at the start of the week (e.g. bank holiday monday) by comparing day names
            // which is a bit rubbish but seems to be all we can do.
            switch (key($timetable)) {
                case 'Tue' : $adjuster = 1;
                    break;
                case 'Wed' : $adjuster = 2;
                    break;
                case 'Thu' : $adjuster = 3;
                    break;
                case 'Fri' : $adjuster = 4;
                    break;
            }
            $adjuster = $adjuster * 24 * 60 * 60;
            foreach ($timetable as $dayname => $day) {
                $today = $day["date"] + $adjuster;
                $periods = $day["periods"];
                $table->add_row();
                $table->add_cell(new e\htmlTableHeader(null, null, /* dayname . '<br />' . */ date('D jS M Y', $today)));
                foreach ($periods as $position => $period) {
                    $celltitle = "";
                    if ($period) {
                        $periodheaders[$position] = $period->start . ' - ' . $period->end;
                        $subject = $period->subject;
                        $hashomework = 0;
                        $hasduehomework = 0;
                        if ($period->courseid) {
                            $subject = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $period->courseid . '">' .
                                    $subject . '</a>';
                            if (!isset($homeworkcache[$period->courseid])) {
                                $homeworkcache[$period->courseid] = block_homework_utils::get_homework_for_course($period->courseid,
                                        $displayuserid, false);
                            }
                            $homework = $homeworkcache[$period->courseid];
                            foreach ($homework as $activity) {
                                if ($activity->availabledate <= $today) {
                                    if ($activity->duedate == $today) {
                                        $hasduehomework++;
                                    } else if ($activity->duedate >= $today) {
                                        $hashomework++;
                                    }
                                }
                            }
                        }
                        $teacher = $period->teacher;
                        if ($period->teacheruserid) {
                            $teacher = '<a href="' . $CFG->wwwroot . '/user/profile.php?id=' . $period->teacheruserid . '">' .
                                    $teacher . '</a>';
                        }
                        $text = '<div class="ond_homework_timetable"><span class="ond_homework_subject">' . $subject .
                                '</span><br />' .
                                '<span class="ond_homework_group">' . $period->group . '</span><br />' .
                                '<span class="ond_homework_room">' . $period->room . '</span><br />' .
                                '<span class="ond_homework_teacher">' . $teacher . '</span></div>';
                        if ($period->courseid) {
                            $text .= '<div class="ond_homework_timetable_actions">';
                            if ($usertype == "employee") {
                                if (($period->groupid) && ($CFG->enableavailability != 0)) {
                                    $groupspecifier = '&group=' . $period->groupid;
                                } else {
                                    $groupspecifier = '';
                                }
                                $text .= '<a href="' . $CFG->wwwroot . '/blocks/homework/set.php?course=' .
                                        $period->courseid . '&avail=' . $today . $groupspecifier . '">' .
                                        block_homework_utils::get_icon_html('sethomework') . '</a><br />';
                            }
                            $viewicon = block_homework_utils::get_icon_html('viewhomework', !($hashomework || $hasduehomework));
                            if ($hashomework || $hasduehomework) {
                                $text .= '<a href="' . $CFG->wwwroot . '/blocks/homework/view.php?course=' .
                                        $period->courseid . '">' . $viewicon . '</a><br />';
                            } else {
                                $text .= $viewicon . '<br />';
                            }
                            if ($usertype == "employee") {
                                $markicon = block_homework_utils::get_icon_html('markhomework', !$hasduehomework);
                                if ($hasduehomework) {
                                    $text .= '<a href="' . $CFG->wwwroot . '/blocks/homework/view.php?course=' .
                                            $period->courseid . '&mark=1">' . $markicon . '</a>';
                                } else {
                                    $text .= $markicon;
                                }
                            }
                            $text .= '</div>';
                            $text .= '<div class="ond_homework_timetable_indicators">';
                            if ($hashomework) {
                                $ctrl = new e\htmlSpan($hashomework,
                                        $this->get_str("thereishomeworkset"), 'ond_homework_timetable_set');
                                $text .= $ctrl->get_html() . '<br />';
                            }
                            if ($hasduehomework) {
                                $ctrl = new e\htmlSpan($hasduehomework,
                                        $this->get_str("thereishomeworkdue"), 'ond_homework_timetable_due');
                                $text .= $ctrl->get_html();
                            }
                            $text .= '</div>';
                        }
                    } else {
                        $text = '';
                    }
                    $cell = new e\htmlTableCell(null, "ond_homework_timetable_cell", $text);
                    if (!empty($celltitle)) {
                        $cell->set_property('title', $celltitle);
                    }
                    $table->add_cell($cell);
                }
            }
            foreach ($periodheaders as $ph) {
                $table->add_header(new e\htmlTableHeader(null, null, $ph));
            }
            $htmltimetable = $table->get_html();
        }

        $output = array('htmllist' => $htmllist, 'htmltimetable' => $htmltimetable);
        print json_encode($output);
    }

}

require_login();
require_sesskey();
ajaxgen_view_timetable::factory();