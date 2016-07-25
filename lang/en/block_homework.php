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
 * English language strings for Homework block
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Admin.
$string['homework:addinstance'] = 'Add a Homework block';
$string['homework:myaddinstance'] = 'Add a Homework block to my Moodle';
$string['homework:viewreports'] = 'View Homework reports';

// Block list.
$string['pluginname'] = 'Homework';
$string['sethomework'] = 'Set Homework';
$string['viewhomework'] = 'View all Homework';
$string['markhomework'] = 'Mark Homework';
$string['viewreports'] = 'View Reports';
$string['poweredby'] = 'Developed by Overnet Data';
$string['poweredbyurl'] = 'http://www.overnetdata.com/products/edulink-homework-moodle-block/edulink-homework';
$string['dueondate'] = '{$a}';
$string['today'] = 'today';
$string['tomorrow'] = 'tomorrow';
$string['yesterday'] = 'yesterday';
$string['inxdays'] = '{$a} days';
$string['xdaysago'] = '{$a} days ago';
$string['notapplicable'] = 'N/A';
$string['versionx'] = 'Version {$a}';

// Page base.
$string['pageerror'] = 'Sorry, there\'s been a bit of a problem: {$a}';
$string['contactsupport'] = 'Please report bugs to <a href="mailto:support@overnetdata.com">support@overnetdata.com</a>.';
$string['nopermission'] = 'Sorry, you don\'t have permission to use this page';
$string['edulinkfeatureonly'] = 'This feature is only available if you have <a href="http://www.overnetdata.com/products/edulink-moodle/introduction/">EduLink for Moodle</a> installed';

// Set homework screen.
$string['edithomework'] = 'Edit Homework';
$string['assignmentsaved'] = 'Homework assignment saved successfully.';
$string['setanotherassignment'] = 'Set another homework assignment';
$string['setanotherassignment_title'] = 'Add another assignment activity';
$string['tryagain'] = 'Try again';
$string['tryagain_title'] = 'Try adding another homework assignment';
$string['createnewassignmentactivity'] = 'New homework assignment';
$string['clone_'] = 'Clone ';
$string['cloneexistingactivity'] = 'Clone existing activity...';
$string['changecourse'] = 'Change';
$string['communicatormissing'] = 'This feature is only available if you have a licence for <a href="http://www.overnetdata.com/products/edulink-communicator/introduction/">EduLink Communicator</a>. Please contact Overnet Data or your reseller.';

$string['basics'] = 'Basics';
$string['courselabel_help'] = 'This is the course the Homework Assignment Activity will be added to. Only the students enrolled on this course will have access to the activity.';
$string['name'] = 'Name';
$string['name_help'] = 'The name will be visible on the Course, Homework Block and Moodle Gradebook';
$string['activity'] = 'Activity';
$string['activity_help'] = 'You can create a new activity or clone an existing one';
$string['newhomework'] = 'New Homework';
$string['description_help'] = 'A detailed description of the homework assignment';
$string['restricttogroups'] = 'Restrict to groups';
$string['groups_help'] = 'If you have groups on your course the groups selected here will be able to see the activity whereas other enrolled students will not (this can be useful for differentiation)';
$string['groups-none_help'] = 'If you have groups on your course the groups selected here will be able to see the activity whereas other enrolled students will not (this can be useful for differentiation)';
$string['nogroupsoncourse'] = 'No groups on course, homework will be set for for all course participants';
$string['enableavailabilityoff'] = 'Enable restricted access is turned off in Moodle configuration, homework will be set for all course participants';
$string['subject_help'] = 'This is used in the reporting section of this block';

$string['submissions'] = 'Submissions';
$string['submissions_help'] = 'How you expect this homework to be submitted - in class, or online as text or an uploaded file';
$string['noonlinesubs'] = 'In class';
$string['onlinetextsubs'] = 'Online text';
$string['onlinefilesubs'] = 'File upload';
$string['onlinetextorfilesubs'] = 'Online text or file upload';

$string['gradingtype'] = 'Grading scale';
$string['gradingscale_help'] = 'The type of grading to be used for this homework - none, points out of 100 or a specified grading scale';
$string['pointsoutof100'] = 'Points (out of 100)';
$string['gradingscalelink'] = '<a href="{$a}" target="_blank">Grading scales can be created by clicking here</a>';
$string['availablefrom'] = 'Available from';
$string['available_help'] = 'The date on which the homework becomes visible to the participants';
$string['dueon'] = 'Due on';
$string['due_help'] = 'The date on which the homework should be submitted';

$string['additional'] = 'Additional';
$string['duration_notspecified'] = 'Not specified';
$string['duration_10'] = '10 minutes';
$string['duration_20'] = '20 minutes';
$string['duration_30'] = '30 minutes';
$string['duration_60'] = '1 hour';
$string['duration_120'] = '2 hours';
$string['duration_180'] = '3 hours';
$string['duration_240'] = '4 hours';
$string['duration_360'] = '6 hours+';
$string['duration'] = 'Duration';
$string['duration_help'] = 'The expected duration';
$string['notifyparents'] = 'Notify parents';
$string['notifyparents_help'] = 'Email parents to let them know the homework assignment has been set';
$string['notesforparentssubject'] = 'Subject line';
$string['note_to_parents_subject_help'] = 'Subject of the notification email - you can use the following fields within your text: [parent_title], [parent_lastname], [parent_firstname], [child_name], [child_lastname], [child_firstname], [assignment_name], [subject], [assignment_due_date], [assignment_duration], [assignment_link]';
$string['notesforparents'] = 'Message body';
$string['note_to_parents_help'] = 'Body of the notification email - you can use the following fields within your text: [parent_title], [parent_lastname], [parent_firstname], [child_name], [child_lastname], [child_firstname], [assignment_name], [subject], [assignment_due_date], [assignment_duration], [assignment_link]';
$string['addfiles'] = 'Add file(s)';
$string['addfiles_help'] = 'Upload any file(s) relating to the homework assignment';
$string['selectcourse'] = 'Select Course';
$string['selectcourse_help'] = 'This is the course the Homework Assignment Activity will be added to. Only the students enrolled on this course will have access to the activity.';
$string['on'] = 'On';
$string['off'] = 'Off';
$string['sethomeworkforall'] = 'Set homework for all course participants';
$string['copyof_'] = 'Copy of ';
$string['duedateinvalid'] = 'Due date must be after available date';

$string['notifyparentsmessage'] = "Dear [parent_title] [parent_lastname]\n\nThe following piece of [assignment_subject] homework has been set for [child_name]:\n\n[assignment_name]\n\nIt is due on [assignment_due_date] and expected to take approximately [assignment_duration].\n\nThe assignment can be viewed here: [assignment_link]";
$string['notifyparentsmessagesubject'] = '[child_firstname] has new homework';
$string['parentalnotificationerror'] = 'Error trying to notify parents: {$a}';
$string['emailerrors'] = '{$a->count} error(s), example error message: {$a->example}';

// View homework screen.
$string['oncourse'] = ' ({$a} course)';
$string['onallcourses'] = ' (all courses)';
$string['teacherview'] = ', Teacher view';
$string['studentview'] = ', Student view';
$string['parentview'] = ', Parent view';
$string['userview'] = ', User view';
$string['loadingdata'] = 'Loading data...';
$string['dateavailable'] = 'Date available';
$string['course'] = 'Course';
$string['subject'] = 'Subject';
$string['activity'] = 'Activity';
$string['assignment'] = 'Assignment';
$string['setby'] = 'Set by';
$string['restrictions'] = 'Restrictions';
$string['participants'] = 'Participants';
$string['submissionsvsparticipants'] = 'Submissions';
$string['gradedsubmissions'] = 'Graded submissions';
$string['ungradedsubmissions'] = 'Ungraded submissions';
$string['description'] = 'Description';
$string['status'] = 'Status';
$string['grade'] = 'Grade';
$string['duedate'] = 'Due date';
$string['nohomework'] = 'No homework found';
$string['nohomeworkduewithintwoweeks'] = 'None due within two weeks';
$string['nocourses'] = 'You are not on any courses';
$string['nocoursesasteacher'] = 'You are not on any courses as a teacher and therefore cannot set homework assignments';
$string['listview'] = 'List view';
$string['timetableview'] = 'Timetable view';
$string['date'] = 'Date';
$string['notimetable'] = 'No timetable data available';
$string['thereishomeworkset'] = 'There is homework set for this course';
$string['thereishomeworkdue'] = 'There is homework due for this course';
$string['thishomeworkisset'] = 'This homework is set';
$string['thishomeworkisdue'] = 'This homework is due';
$string['thishomeworkisdone'] = 'This homework has been completed';
$string['actions'] = 'Actions';
$string['mark'] = 'Mark';

// View individual assignment screen.
$string['details'] = 'Details';
$string['viewhomeworkitem'] = 'View Homework Assignment';
$string['edithomeworkitem'] = 'Edit Homework';
$string['files'] = 'Files';
$string['dueonlc'] = 'due on';
$string['markeddone'] = 'Marked done';
$string['returntocourse'] = 'Return to Course';
$string['returntocourse_title'] = 'Return to the course this homework assignment was part of';
$string['assignmentmarkedasdone'] = 'The homework assignment has been marked as done';
$string['markdone'] = 'Mark as done';
$string['addsubmission'] = 'Add Submission';
$string['child'] = 'Child';
$string['completed'] = 'Completed';

// Mark homework screen.
$string['invalidcmid'] = 'Invalid course module ID';
$string['invalidcourse'] = 'Invalid course ID';
$string['incompatibleassignment'] = '<div class="ond_centered"><h2>Unable to Mark incompatible assignment</h2><p>This assignment makes use of features not supported by Homework:</p><p>{$a->features}</p><p>This can happen if this assignment was created in, or altered in Moodle after creation in the Homework block.</p><p class="ond_centered">{$a->buttons}</p></div>';
$string['unsupportedblindmarking'] = 'Blind marking';
$string['unsupportedmarkingworkflow'] = 'Marking workflow';
$string['unsupportedmarkingallocation'] = 'Marking allocation';
$string['unsupportedteamsubmission'] = 'Team submissions';
$string['marking'] = 'Marking';
$string['achievementbehaviour'] = 'Achievement &amp; Behaviour';
$string['submission'] = 'Submission';
$string['plusfiles'] = ' (plus file submission)';
$string['assignmentmarked'] = 'Homework assignment marked successfully.';
$string['markanotherassignment'] = 'Mark another assignment';
$string['markanotherassignment_title'] = 'Mark another assignment activity';
$string['backtomarkinglist'] = 'Back to Marking list';

$string['alldone'] = 'All done';
$string['saving'] = 'Saving...';
$string['savinggrades'] = 'Saving grades...';
$string['gradingassignmentfor'] = 'Grading assignment for';
$string['failedtosetmarkfor'] = 'Failed to set mark for';
$string['savingachievement'] = 'Saving achievement...';
$string['addingachievementfor'] = 'Adding achievement for';
$string['failedtoaddachievementfor'] = 'Failed to add achievement for';
$string['savingbehaviour'] = 'Saving behaviour...';
$string['addingbehaviourfor'] = 'Adding behaviour for';
$string['failedtoaddbehaviourfor'] = 'Failed to add behaviour for';

$string['bulkachievementwriteback'] = 'Add achievement record for those<br />who did submit their assignment';
$string['achievementreporter'] = 'Reported by';
$string['achievementtype'] = 'Achievement type';
$string['achievementactivity'] = 'Activity';
$string['achievementcomments'] = 'Comments';
$string['achievementpoints'] = 'Points';

$string['bulkbehaviourwriteback'] = 'Add behaviour record for those<br />who did not submit their assignment';
$string['behaviourreporter'] = 'Reported by';
$string['behaviourtype'] = 'Behaviour type';
$string['behaviouractivity'] = 'Activity';
$string['behaviourstatus'] = 'Status';
$string['behaviourcomments'] = 'Comments';
$string['behaviourpoints'] = 'Points';

$string['nothingdone'] = 'Nothing done';
$string['nothingdonefull'] = 'No grades added or modified; nothing to save!';

// Reports screen.
$string['viewhomeworkreports'] = 'Homework Reports';
$string['year'] = 'Year';
$string['formgroup'] = 'Form group';
$string['community'] = 'Community';
$string['staffusage'] = 'Staff Usage';
$string['assignmentssetpermonth'] = 'Assignments set per month';
$string['assignmentsgradedpermonth'] = 'Assignments graded per month';
$string['assignmentssetpergroup'] = 'Assignments set per group';
$string['from'] = 'From';
$string['to'] = 'To';
$string['nogroups'] = 'There are no groups in your courses';
$string['groupgrades'] = 'Group Grades';
$string['groupmembergrades'] = 'Group member grades';
$string['group'] = 'Group';
$string['studentgrades'] = 'Student Grades';
$string['assignmentssetpersubject'] = 'Assignments set per Subject';
$string['assignmentssetperstaffmember'] = 'Assignments set per Staff member';
$string['subjectsandstaff'] = 'Subjects and Staff';
$string['staffstatistics'] = 'Staff Statistics';
$string['notspecified'] = 'Not specified';
$string['nottracked'] = 'Not tracked';
$string['nottrackedfull'] = 'Not tracked (created outside of Homework block)';

// Common to all reports.
$string['invaliddatesupplied'] = 'Invalid date supplied';
$string['invalidgroupsupplied'] = 'Invalid group supplied';
// Group reports.
$string['noassignmentsfound'] = 'No assignments found';
$string['nogroupmembersfound'] = 'No group members found';
$string['gradenotapplicable'] = 'N/A - non participant';
$string['student'] = 'Student';
// Student reports.
$string['pleaseselectastudent'] = 'Please select a student';
// School reports.
$string['nosubjectspecified'] = 'No subject specified ({$a})';
$string['staffmember'] = 'Staff member';
$string['assignmentsset'] = 'Assignments set';
$string['submissionsgraded'] = 'Submissions graded';
// Staff reports.
$string['assignmentsgraded'] = 'Assignments graded';
// Student reports.
$string['invalidstudentsupplied'] = 'Invalid student supplied';
$string['issued'] = 'Issued';
$string['due'] = 'Due';
$string['feedback'] = 'Feedback';

// Datatables js.
$string['export'] = 'Export';
$string['pleaseselect'] = '[Please Select]';
$string['exportall'] = 'Export All';
$string['exportfiltered'] = 'Export Filtered';
$string['allorfiltered'] = 'Please select All or Filtered prior to clicking Export';

// Form validate js.
$string['valuerange'] = 'Value must be in range';
$string['requiredfield'] = 'Required field';
$string['correcthighlightederrors'] = 'Correct the highlighted error(s) before saving';
$string['oops'] = 'Oops...';
$string['okbutton'] = 'OK';

// Homework utils.
$string['setby'] = 'Set by';
$string['available'] = 'Available';
$string['xminutes'] = '{$a} minutes';
$string['xhours'] = '{$a} hours';
$string['onehour'] = '1 hour';

// Moodle utils.
$string['noonlinesubmissionrequired'] = 'No online submission required';
$string['notsubmitted'] = 'Not submitted';
$string['notgraded'] = 'Not graded';
$string['graded'] = 'Graded';

// Misc.
$string['ok'] = 'OK';
$string['cancel'] = 'Cancel';
$string['save'] = 'Save';
$string['failedtofetchdata'] = 'Failed to fetch data';