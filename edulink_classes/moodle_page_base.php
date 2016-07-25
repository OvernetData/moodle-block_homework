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
 * Base class for any Moodle page
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_homework\local\edulink;

defined('MOODLE_INTERNAL') || die();

require_once("controls.php");
require_once("moodle.php");

use Exception;

abstract class block_homework_moodle_page_base {

    protected $content = '';
    protected $blockid = 'blocks/homework';
    protected $languagefile = 'block_homework';
    protected $edulinkpresent = false;
    protected $siteid;
    protected $courseid;
    protected $course = null;
    protected $onfrontpage;
    protected $title;
    protected $userid;

    public function __construct() {
        global $CFG, $PAGE, $OUTPUT, $USER;
        require_login();
        $PAGE->set_context(\context_system::instance());
        $this->userid = $USER->id;

        $edulink = \block_homework_moodle_utils::is_edulink_present();
        if ($edulink) {
            $this->edulinkpresent = true;
            require_once($edulink);
        }

        $PAGE->set_url(qualified_me());

        $this->siteid = get_site()->id;
        $this->courseid = optional_param('course', $this->siteid, PARAM_INT);
        $this->onfrontpage = $this->courseid == $this->siteid;
        if (!$this->onfrontpage) {
            $this->course = get_course($this->courseid);
            $PAGE->set_context(\context_course::instance($this->courseid));
        }

        $this->title = $this->get_title();
        $PAGE->set_title($this->title);

        $heading = $this->get_heading();
        if (is_null($heading)) {
            $heading = $this->title;
        }
        $PAGE->set_heading($heading);

        $PAGE->set_pagelayout('base');
        $PAGE->add_body_class('page_edulink_homework');

        $stringmanager = get_string_manager();
        $strings = $stringmanager->load_component_strings($this->languagefile, 'en');
        $PAGE->requires->strings_for_js(array_keys($strings), $this->languagefile);

        try {
            $this->content = $this->get_content();
        } catch (Exception $ex) {
            $label = new htmlLabel('label-warning', get_string('pageerror', $this->languagefile, $ex->getMessage()));
            $static = new htmlStatic('ond_contactsupport', get_string('contactsupport', $this->languagefile));
            $this->content = $label->get_html() . $static->get_html();
        }

        $PAGE->navbar->ignore_active();
        if (!$this->onfrontpage && !is_null($this->course)) {
            $coursecontext = \context_course::instance($this->courseid);
            $PAGE->navbar->add($this->course->fullname, $CFG->wwwroot . '/course/view.php?id=' . $this->courseid);
        }
        $this->set_navigation();

        print $OUTPUT->header();
        print $this->content;
        print $OUTPUT->footer();
    }

    /**
     * Override in descendant if you want anything extra in navigation breadcrumbs
     * will default to Home | [Course] | This page title where [Course] is only there
     * if course is specified in page url.
     * @global object $PAGE
     */
    protected function set_navigation() {
        global $PAGE;
        $PAGE->navbar->add($this->title);
    }

    /**
     * Just a convenience function so you can use $this->get_str... rather than block_homework_moodle_utils::get_str!
     * @param int $id
     * @param mixed $params
     * @return string
     */
    protected function get_str($id, $params = null) {
        return \block_homework_moodle_utils::get_str($id, $params);
    }

    /**
     * Implement these method in your descendant and return a string or null
     * @return string
     */
    abstract protected function get_title();

    protected function get_heading() {
        return null;
    }

    abstract protected function get_content();

    /**
     * Include a css style sheet on the page
     * @global object $PAGE
     * @param string $cssfilename
     */
    protected function use_stylesheet($cssfilename) {
        global $PAGE;
        $PAGE->requires->css(new \moodle_url($cssfilename));
    }

}
