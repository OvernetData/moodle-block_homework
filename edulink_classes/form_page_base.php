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
 * Base class for any page that uses our forms framework
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_homework\local\edulink;

require_once("moodle_page_base.php");

defined('MOODLE_INTERNAL') || die();

abstract class block_homework_form_page_base extends block_homework_moodle_page_base {

    protected $groups;

    protected function set_scripts() {
    }

    protected function set_stylesheets() {
        global $CFG;
        $path = $CFG->wwwroot . '/' . $this->blockid . '/style/';
        $this->use_stylesheet('https://fonts.googleapis.com/css?family=Lato');
        $this->use_stylesheet($path . 'styles.css');
        $this->use_stylesheet($path . 'bootstrap-switch.css');
        $this->use_stylesheet($path . 'sumoselect.css');
        $this->use_stylesheet($path . 'select2.css');
        $this->use_stylesheet($path . 'zebra_tooltips.css');
    }

    protected function get_form($groups, $savebutton = 'Save', $cancelbutton = 'Cancel', $usetabs = true) {
        $this->groups = $groups;

        $html = '<div id="ond_form_progress"><div id="ond_form_progress_bar"></div></div>';
        $html .= '<form id="ond_form" action="' . strip_querystring(qualified_me()) . '" method="POST">';
        $html .= "\n" . '<input type="hidden" name="action" value="save">';
        if ($usetabs) {
            $html .= "\n" . '<ul id="tablist" class="nav nav-tabs" role="tablist">';
        }
        $isactive = ' class="active"';
        foreach ($groups as $groupname => $group) {
            $groupid = str_replace(array('&amp;', ' '), '_', $groupname);
            if ($usetabs) {
                $html .= "\n" . '  <li' . $isactive . '>' .
                        '<a role="tab" data-toggle="tab" id="' . $groupid . '-tab" href="#' . $groupid .
                        '">' . $groupname . '</a></li>';
            }
            $isactive = '';
        }
        if ($usetabs) {
            $html .= "\n" . '</ul>';
            $html .= "\n" . '<div class="tab-content">';
        }
        $isactive = ' active';
        foreach ($groups as $groupname => $group) {
            $groupid = str_replace(array('&amp;', ' '), '_', $groupname);
            if ($usetabs) {
                $html .= '<div id="' . $groupid . '" class="tab-pane fade in' . $isactive . '">';
            }
            $isactive = '';
            $html .= "<div class=\"ond_controlgroup\" id=\"inner-{$groupid}\">\n";
            $html .= $this->setting_controls($group);
            $html .= "\n</div>";
            if ($usetabs) {
                $html .= "\n</div>";
            }
        }

        if ($usetabs) {
            $html .= "\n</div>";
        }
        if ($savebutton || $cancelbutton) {
            $html .= '<div class="ond_centered" id="ond_form_buttons">';
            if ($savebutton) {
                $submit = new \block_homework\local\edulink\htmlButton('btnsubmit', $savebutton);
                $html .= $submit->get_html();
            }
            if ($cancelbutton) {
                $cancel = new \block_homework\local\edulink\htmlButton('btncancel', $cancelbutton);
                $html .= $cancel->get_html();
            }
            $html .= '</div>';
        }
        $html .= '<div class="ond_centered" id="ond_validationfailed"><span class="label label-danger">' .
                $this->get_str('correcthighlightederrors') . '</span></div>';
        $html .= '</form>';
        return $html;
    }

    protected function get_submitted_values($form) {
        $values = array();
        foreach ($form as $groupname => $group) {
            $values = array_merge($values, $this->get_group_values($group));
        }
        foreach ($_POST as $name => $value) {
            if (!isset($values[$name])) {
                if (is_array($value)) {
                    $values[$name] = optional_param_array($name, array(), PARAM_RAW);
                } else {
                    $values[$name] = optional_param($name, null, PARAM_RAW);
                }
            }
        }
        return $values;
    }

    protected function get_group_values($settingsgroup) {
        $values = array();
        foreach ($settingsgroup as $settingname => $settingdetails) {
            $type = $settingdetails["type"];
            if ((substr($type, 0, 5) == "label") || ($type == "static") || ($type == "button") || ($type == "script")) {
                continue;
            }
            if ($settingdetails["type"] == "multiselect") {
                $values[$settingname] = optional_param_array($settingname, array(), PARAM_RAW);
            } else if ($settingdetails["type"] == "switch") {
                // Special case - if checkboxes aren't checked, they don't show up in params
                // so use 0 as default if not present.
                $values[$settingname] = optional_param($settingname, 0, PARAM_RAW);
            } else {
                $values[$settingname] = optional_param($settingname, '', PARAM_RAW);
            }
            if (isset($settingdetails["subgroup_if_on"])) {
                // On = switch is 1/true/etc. or select is at first option in list
                // only look at the subgroup if it's parent is 'on'.
                $on = (($type == 'switch') && ($values[$settingname])) ||
                        (($type == 'select') && ($values[$settingname] == key($settingdetails["options"])));
                if ($on) {
                    $values = array_merge($values, $this->get_group_values($settingdetails["subgroup_if_on"]));
                }
            }
            if (isset($settingdetails["subgroup_if_off"])) {
                // On = switch is 1/true/etc. or select is at first option in list
                // only save the subgroup if it's parent is 'off'.
                $on = (($type == 'switch') && ($values[$settingname])) ||
                        (($type == 'select') && ($values[$settingname] == key($settingdetails["options"])));
                if (!$on) {
                    $values = array_merge($values, $this->get_group_values($settingdetails["subgroup_if_off"]));
                }
            }
        }
        return $values;
    }

    protected function setting_controls($group) {
        $html = '';
        foreach ($group as $settingname => $settingdetails) {
            $html .= $this->setting_control($settingname, $settingdetails);
        }
        return $html;
    }

    protected function setting_control($settingname, $settingdetails) {
        $html = $this->get_setting_html($settingname, $settingdetails);
        $type = isset($settingdetails["type"]) ? $settingdetails["type"] : '';
        $isswitch = ($type == 'switch') || ($type == 'select');
        if ($isswitch && (isset($settingdetails["subgroup_if_on"]) || isset($settingdetails["subgroup_if_off"]))) {
            if (isset($settingdetails["subgroup_if_on"])) {
                $html .= '<div class="ond_controlsubgroup" id="' . $settingname . '_subgroup_on">' .
                        $this->setting_controls($settingdetails["subgroup_if_on"]) . '</div>';
            }
            if (isset($settingdetails["subgroup_if_off"])) {
                $html .= '<div class="ond_controlsubgroup" id="' . $settingname . '_subgroup_off">' .
                        $this->setting_controls($settingdetails["subgroup_if_off"]) . '</div>';
            }
        }
        return $html;
    }

    public function get_setting_html($settingname, $settingdetails) {
        $prompt = isset($settingdetails["prompt"]) ? $settingdetails["prompt"] : null;
        $isrequired = isset($settingdetails["required"]) ? $settingdetails["required"] : false;
        $type = isset($settingdetails["type"]) ? $settingdetails["type"] : '';
        $default = isset($settingdetails["default"]) ? $settingdetails["default"] : null;
        $help = isset($settingdetails["help"]) ? $settingdetails["help"] : '';
        if ($help == '') {
            if (get_string_manager()->string_exists($settingname . '_help', $this->languagefile)) {
                $help = get_string($settingname . '_help', $this->languagefile);
            }
        }
        $title = isset($settingdetails["title"]) ? $settingdetails["title"] : '';
        $extracontrolproperties = null;
        if (isset($settingdetails["extracontrolproperties"])) {
            $extracontrolproperties = $settingdetails["extracontrolproperties"];
        }

        if (isset($settingdetails["value"])) {
            $value = $settingdetails["value"];
        } else {
            $value = $default;
        }

        $html = '';
        // Prompt for control.
        if (($type != 'hidden') && ($type != 'script')) {

            if (($type != "static") || (($type == "static") && ($prompt != ''))) {
                $html .= "\n" . '<div class="ond_controlname">';
                if (!empty($prompt)) {
                    $label = new htmlLabel('label-default', $prompt, $settingname);
                    if ($help != '') {
                        $label->add_class('tooltips');
                        $label->set_title($help);
                    }
                    $html .= $label->get_html();
                } else {
                    $html .= '&nbsp;';  // Something to keep this div collapsing if no prompt set.
                }
                $html .= '</div>';
            }

            // Control itself.
            $html .= "\n" . '<div class="ond_control">';
        }

        if (substr($type, 0, 5) == 'label') {
            $control = new htmlLabel($type, $value);
        } else {
            $control = null;
            switch ($type) {
                case 'script' :
                    $control = new htmlScript($value);
                    break;
                case 'switch' :
                    $value = $this->str_to_boolean($value);
                    $control = new htmlCheckbox($settingname, $settingname, $value, $title);
                    if ((isset($settingdetails["subgroup_if_on"])) || (isset($settingdetails["subgroup_if_off"]))) {
                        $control->add_class("ond_subgroupcontroller");
                    }
                    break;

                case 'int' :
                case 'float' :
                    $control = new htmlNumberInput($settingname, $settingname, $value, $title);
                    if (isset($settingdetails["min"])) {
                        $control->set_min($settingdetails["min"]);
                    }
                    if (isset($settingdetails["max"])) {
                        $control->set_max($settingdetails["max"]);
                    }
                    if (isset($settingdetails["step"])) {
                        $control->set_step($settingdetails["step"]);
                    }
                    break;

                case 'url' :
                    $control = new htmlURLInput($settingname, $settingname, $value, $title);
                    if (isset($settingdetails["size"])) {
                        $control->set_size($settingdetails["size"]);
                    }

                    break;

                case 'text' :
                    $autofilloptions = isset($settingdetails["autofilloptions"]) ? $settingdetails["autofilloptions"] : null;
                    $control = new htmlTextInput($settingname, $settingname, $value, $title, $autofilloptions);
                    if (isset($settingdetails["size"])) {
                        $control->set_size($settingdetails["size"]);
                    }
                    if (isset($settingdetails["maxlength"])) {
                        $control->set_max_length($settingdetails["maxlength"]);
                    }
                    break;

                case 'password' :
                    $control = new htmlPasswordInput($settingname, $settingname, $value, $title);
                    if (isset($settingdetails["size"])) {
                        $control->set_size($settingdetails["size"]);
                    }
                    if (isset($settingdetails["maxlength"])) {
                        $control->set_max_length($settingdetails["maxlength"]);
                    }
                    $control->set_property('autocomplete', 'new-password'); // Stops Chrome autofilling user/password fields.
                    break;

                case 'colour' :
                    $control = new htmlColourInput($settingname, $settingname, $value, $title);
                    break;

                case 'date' :
                    $tomorrowbutton = isset($settingdetails["include_tomorrow_button"]) &&
                        $settingdetails["include_tomorrow_button"];
                    $nextweekbutton = isset($settingdetails["include_next_week_button"]) &&
                            $settingdetails["include_next_week_button"];
                    $control = new htmlDateInput($settingname, $settingname, $value, $title, $tomorrowbutton, $nextweekbutton);
                    break;

                case 'select' :
                case 'multiselect' :
                    $options = isset($settingdetails["options"]) ? $settingdetails["options"] : array();
                    $control = new htmlSelect($settingname, $settingname, $value, $options, $type == "multiselect", $title);
                    if ((isset($settingdetails["subgroup_if_on"])) || (isset($settingdetails["subgroup_if_off"]))) {
                        $control->add_class("ond_subgroupcontroller");
                    }
                    break;

                case 'memo' :
                    $control = new htmlTextAreaInput($settingname, $settingname, $value, $title);
                    $columns = isset($settingdetails["columns"]) ? $settingdetails["columns"] : 80;
                    $control->set_columns($columns);
                    $rows = isset($settingdetails["rows"]) ? $settingdetails["rows"] : 5;
                    $control->set_rows($rows);
                    break;

                case 'button' :
                    $anchor = isset($settingdetails["anchor"]) ? $settingdetails["anchor"] : '';
                    $control = new htmlButton($settingname, $settingname, $value, $anchor, $title);
                    $control->remove_class('ond_material_button_raised');
                    $control->add_class('ond_material_button_raised_small');
                    break;

                case 'static' :
                    if (isset($settingdetails["content"])) {
                        $content = $settingdetails["content"];
                    } else {
                        $content = $value;
                    }
                    $control = new htmlStatic($settingname, $content);
                    break;

                case 'hidden' :
                    $control = new htmlHiddenInput($settingname, $settingname, $value);
                    break;
            }
            if (is_null($control)) {
                if ($type == '') {
                    $control = new htmlLabel('label-danger', "[$settingname] does not have its type set!");
                } else {
                    $control = new htmlLabel('label-danger', "[$settingname] has unsupported type [$type]!");
                }
            }
            if ($isrequired) {
                $control->set_required(true);
                $control->add_class('ond_required');
            }
            if (!empty($extracontrolproperties)) {
                foreach ($extracontrolproperties as $key => $val) {
                    $control->set_property($key, $val);
                }
            }
        }
        $html .= $control->get_html();
        if (($type != 'hidden') && ($type != 'script')) {
            $html .= '</div>';
            $html .= '<div class="ond_clearer"></div>';
        }
        return $html;
    }

    protected function str_to_boolean($string) {
        $string = strtolower($string);
        return ($string == 'true') || ($string == 't') || ($string == 'yes') || ($string == 'y') || ($string == 'on') ||
            ($string == 'enabled') || ($string == '1');
    }

}
