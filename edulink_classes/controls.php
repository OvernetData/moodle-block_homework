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
 * Classes for various HTML controls that are responsible for producing their
 * own output, assuming use of various Bootstrap scripts
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_homework\local\edulink;

abstract class htmlControl {

    protected $classes = array(), $properties = array(), $required = false;

    public function __construct($id = null, $name = null, $title = null, $class = null) {
        $this->set_id($id);
        $this->set_name($name);
        $this->set_title($title);
        $this->set_class($class);
    }

    public function set_id($id) {
        $this->set_property('id', $id);
    }

    public function set_name($name) {
        $this->set_property('name', $name);
    }

    public function set_title($title) {
        $this->set_property('title', $title);
    }

    public function add_class($class) {
        if (!is_array($class)) {
            $classes = explode(' ', $class);
        } else {
            $classes = $class;
        }
        $this->classes = array_merge($this->classes, $classes);
    }

    public function remove_class($class) {
        unset($this->classes[$class]);
    }

    public function set_class($class) {
        if (!is_array($class)) {
            $classes = explode(' ', $class);
        } else {
            $classes = $class;
        }
        $this->classes = $classes;
    }

    protected function get_class($namesonly = false) {
        $class = implode(' ', $this->classes);
        if (($class != null) && (!$namesonly)) {
            return ' class="' . $class . '"';
        }
        return $class;
    }

    public function set_property($name, $value) {
        $this->properties[$name] = $value;
    }

    public function remove_property($name) {
        unset($this->extra[$name]);
    }

    public function get_properties() {
        $props = null;
        if (!empty($this->properties)) {
            foreach ($this->properties as $name => $value) {
                if ($value != '') {
                    if ($name == 'title') {
                        $value = htmlspecialchars($value);  // Only for title?
                    }
                    $props .= ' ' . $name . '="' . $value . '"';
                }
            }
        }
        if ($this->required) {
            $props .= ' required';
        }
        return $props;
    }

    public function get_property($name) {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        } else {
            return '';
        }
    }

    public function get_id() {
        return $this->get_property("id");
    }

    public function set_required($required) {
        $this->required = $required;
    }

    abstract public function get_html();
}

class htmlLabel extends htmlControl {

    protected $text, $target;

    public function __construct($labeltype, $labeltext, $targetcontrol = null) {
        parent::__construct();
        $this->set_text($labeltext);
        $this->set_target_control($targetcontrol);
        $this->add_class('label');
        $this->add_class($labeltype);
    }

    public function set_target_control($targetcontrol) {
        $this->target = $targetcontrol;
    }

    public function get_html() {
        $html = '<label';
        if (!empty($this->target)) {
            $html .= ' for="' . $this->target . '"';
        }
        $html .= '>';
        $html .= '<span' . $this->get_class() . $this->get_properties() . '>' . $this->text . '</span>';
        $html .= '</label>';
        return $html;
    }

    public function set_text($labeltext) {
        $this->text = $labeltext;
    }

}

class htmlDiv extends htmlControl {

    protected $text = "";

    public function __construct($id = null, $class = null, $text = null) {
        parent::__construct($id, null, null, $class);
        if ($text != null) {
            $this->set_text($text);
        }
    }

    public function get_html() {
        return '<div' . $this->get_class() . $this->get_properties() . '>' . $this->text . '</div>';
    }

    public function set_text($text) {
        $this->text = $text;
    }

}

abstract class htmlBaseInput extends htmlControl {

    protected $type, $value;

    public function __construct($inputtype, $id, $name, $value, $title = null) {
        parent::__construct($id, $name, $title, 'form-control');
        $this->set_property('type', $inputtype);
        $this->value = $value;
    }

    public function get_html() {
        $html = '<input' . $this->get_properties() .
                ' value="' . htmlspecialchars($this->value) . '"' .
                $this->get_class() . '>';
        return $html;
    }

}

abstract class htmlBaseTextInput extends htmlBaseInput {

    public function __construct($type, $id, $name, $value, $title = null) {
        parent::__construct($type, $id, $name, $value, $title);
    }

    public function set_size($size) {
        $this->set_property('size', $size);
    }

    public function set_max_length($maxlength) {
        $this->set_property('maxlength', $maxlength);
    }

}

class htmlTextInput extends htmlBaseTextInput {

    protected $autofilloptions = null;
    
    public function __construct($id, $name, $value, $title = null, $autofilloptions = null) {
        parent::__construct('text', $id, $name, $value, $title);
        $this->autofilloptions = $autofilloptions;
    }

    public function get_html() {
        if ($this->autofilloptions == null) {
            return parent::get_html();
        } else {
            $listid = $this->get_property('name') . '_optionslist';
            $html = '<input' . $this->get_properties() .
                    ' value="' . htmlspecialchars($this->value) . '"' .
                    $this->get_class() . ' list="' . $listid . '" autocomplete="off"><datalist id="' . $listid . '">';
            foreach($this->autofilloptions as $option) {
                $html .= '<option>' . $option . '</option>';
            }
            $html .= '</datalist>';
            return $html;
        }
    }
}

class htmlPasswordInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('password', $id, $name, $value, $title);
    }

}

class htmlSearchInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('search', $id, $name, $value, $title);
    }

}

class htmlURLInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('url', $id, $name, $value, $title);
    }

}

class htmlEmailInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('email', $id, $name, $value, $title);
    }

}

class htmlTelephoneInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('tel', $id, $name, $value, $title);
    }

}

class htmlColourInput extends htmlBaseTextInput {

    public function __construct($id, $name, $value, $title = null) {
        // We tend to store colours in r,g,b rather than hex so conver to hex notation if necessary.
        $value = \block_homework_utils::rgb_to_hex($value);
        parent::__construct('color', $id, $name, $value, $title);
        $this->remove_class('form-control');
    }

}

class htmlDateInput extends htmlBaseInput {

    protected $tomorrowbutton, $nextweekbutton;

    public function __construct($id, $name, $value, $title = null, $includetomorrowbutton = false, $includenextweekbutton = false) {
        $asnum = strval(intval($value));
        if ($value == $asnum) { // If unix epoch set as value, convert to y-m-d.
            $value = date('Y-m-d', $value);
        } else {
            // Make sure valid y-m-d.
            $date = strtotime($value);
            if (!$date) {
                $date = time();
            }
            $value = date('Y-m-d', $date);
        }
        $this->tomorrowbutton = $includetomorrowbutton;
        $this->nextweekbutton = $includenextweekbutton;
        parent::__construct('date', $id, $name, $value, $title);
    }

    public function get_html() {
        $html = parent::get_html();
        $id = $this->get_id();
        if ($this->tomorrowbutton) {
            $html .= ' <button id="' . $id . '_tomorrow" class="ond_material_button_raised_small" onclick="' .
                    $this->get_onclick($id, 1) . '">Tomorrow</button>';
        }
        if ($this->nextweekbutton) {
            $html .= ' <button id="' . $id . '_nextweek" class="ond_material_button_raised_small" onclick="' .
                    $this->get_onclick($id, 7) . '">Next week</button>';
        }
        return $html;
    }

    // TODODMB add trigger of change event.
    public function get_onclick($id, $daystoadd) {
        return 'var day=new Date();day.setDate(day.getDate()+' . $daystoadd . ');' .
                'document.getElementById(\'' . $id . '\').value=day.getFullYear()+\'-\'+String(\'00\'+' .
                '(day.getMonth()+1)).slice(-2)+\'-\'+String(\'00\'+day.getDate()).slice(-2);return false;';
    }

}

class htmlNumberInput extends htmlBaseInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('number', $id, $name, $value, $title);
    }

    public function set_min($min) {
        $this->set_property('min', $min);
    }

    public function set_max($max) {
        $this->set_property('max', $max);
    }

    public function set_step($step) {
        $this->set_property('step', $step);
    }

}

class htmlRangeInput extends htmlBaseInput {

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct('range', $id, $name, $value, $title);
    }

    public function set_min($min) {
        $this->set_property('min', $min);
    }

    public function set_max($max) {
        $this->set_property('max', $max);
    }

}

class htmlCheckbox extends htmlBaseInput {

    protected $checked = false;

    public function __construct($id, $name, $checked, $title = null) {
        parent::__construct('checkbox', $id, $name, '1', $title);
        $this->set_class('');    // Get rid of default form-control class.
        $this->checked = $checked;
    }

    public function get_html() {
        $html = parent::get_html();
        $html = str_replace('>', ' data-toggle="toggle">', $html);
        if ($this->checked) {
            $html = str_replace('>', ' checked>', $html);
        }
        return $html;
    }

}

class htmlSelect extends htmlControl {

    protected $options;
    protected $multiple;
    protected $value;
    protected $values = array();

    public function __construct($id, $name, $value = null, $options = array(), $multiple = false, $title = null) {
        if ($multiple) {
            $name .= '[]';
        }
        parent::__construct($id, $name, $title, 'form-control');
        $this->value = $value;
        $this->options = $options;
        $this->multiple = $multiple;
        if ($multiple) {
            if (is_array($value)) {
                $this->values = $value;
            } else {
                $this->values = explode(',', $value);
            }
        }
    }

    public function get_html() {
        $html = '<select' . $this->get_properties() . $this->get_class();
        if ($this->multiple) {
            $html .= ' multiple';
        }
        $html .= '>';

        foreach ($this->options as $optiongrouporvalue => $optionsorname) {
            if (is_array($optionsorname)) {
                $html .= '<optgroup label="' . $optiongrouporvalue . '">';
                foreach ($optionsorname as $value => $name) {
                    $html .= $this->get_option_html($value, $name);
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->get_option_html($optiongrouporvalue, $optionsorname);
            }
        }

        $html .= "\n</select>";
        return $html;
    }

    protected function get_option_html($value, $name) {
        $html = "\n" . '<option value="' . $value . '"';
        if ($this->multiple) {
            $valuematches = in_array($value, $this->values);
        } else {
            $valuematches = ($value == $this->value);
        }
        if ($valuematches) {
            $html .= ' selected';
        }
        $html .= '>' . $name . '</option>';
        return $html;
    }

}

class htmlTextAreaInput extends htmlControl {

    protected $value;

    public function __construct($id, $name, $value, $title = null) {
        parent::__construct($id, $name, $title, 'form-control');
        $this->value = $value;
    }

    public function set_columns($columns) {
        $this->set_property('cols', $columns);
    }

    public function set_rows($rows) {
        $this->set_property('rows', $rows);
    }

    public function get_html() {
        $html = '<textarea' . $this->get_properties() . $this->get_class() . '>' .
                $this->value . '</textarea>';
        return $html;
    }

}

class htmlButton extends htmlControl {

    protected $text;

    public function __construct($id, $text, $title = null, $onclick = null) {
        parent::__construct($id, null, $title, 'ond_material_button_raised');
        $this->set_text($text);
        $this->set_onclick($onclick);
        $this->set_property('type', 'button');
    }

    public function set_text($text) {
        $this->text = $text;
    }

    public function set_onclick($onclick) {
        $this->set_property('onclick', $onclick);
    }

    public function get_html() {
        $html = '<button' . $this->get_properties() . $this->get_class() . '>' . $this->text . '</button>';
        return $html;
    }

}

class htmlHyperlink extends htmlControl {

    protected $text;

    public function __construct($id, $text, $url, $title = null, $target = null) {
        parent::__construct($id, null, $title, 'ond_material_button');
        $this->set_text($text);
        $this->set_url($url);
        $this->set_target($target);
    }

    public function set_text($text) {
        $this->text = $text;
    }

    public function set_url($url) {
        $this->set_property('href', $url);
    }

    public function set_target($target) {
        $this->set_property('target', $target);
    }

    public function get_html() {
        $html = '<a' . $this->get_properties() . $this->get_class() . '>' . $this->text . '</a>';
        return $html;
    }

}

class htmlStatic extends htmlControl {

    protected $content;

    public function __construct($id, $content) {
        parent::__construct($id);
        $this->content = $content;
    }

    public function get_html() {
        $html = '<div' . $this->get_properties() . $this->get_class() . '>' . $this->content . '</div>';
        return $html;
    }

}

class htmlHiddenInput extends htmlBaseInput {

    public function __construct($id, $name, $value) {
        parent::__construct('hidden', $id, $name, $value);
        $this->set_class(''); // Remove all classes.
    }

}

class htmlSpan extends htmlControl {

    protected $content;

    public function __construct($content = null, $title = null, $class = null) {
        parent::__construct(null, null, $title, $class);
        $this->content = $content;
    }

    public function get_html() {
        $html = '';
        if (!is_null($this->content)) {
            $html .= ' <span' . $this->get_properties() . $this->get_class() . '>' . $this->content . '</span>';
        }
        return $html;
    }

}

class htmlBadge extends htmlSpan {

    public function __construct($content = null, $title = null) {
        parent::__construct($content, $title, "badge");
    }

}

class htmlImage extends htmlControl {

    public function __construct($id, $filename, $width, $height, $alt) {
        parent::__construct($id);
        $this->set_property('src', $filename);
        $this->set_property('width', $width);
        $this->set_property('height', $height);
        $this->set_property('alt', $alt);
        $this->set_property('title', $alt);
    }

    public function get_html() {
        return '<img' . $this->get_properties() . $this->get_class() . '>';
    }

}

class htmlScript extends htmlControl {

    protected $script;

    public function __construct($content) {
        parent::__construct();
        $this->script = $content;
    }

    public function get_html() {
        $html = '';
        if ($this->script != '') {
            $html = "\n<script type=\"text/javascript\">" . $this->script . "</script>\n";
        }
        return $html;
    }

}

class htmlTableCell extends htmlControl {

    protected $text;

    public function __construct($id = null, $class = null, $text = null) {
        $this->set_id($id);
        $this->set_class($class);
        $this->set_text($text);
    }

    public function set_text($text) {
        $this->text = $text;
    }

    public function get_html() {
        $html = '<td' . $this->get_properties() . $this->get_class() . '>' . $this->text . '</td>';
        return $html;
    }

}

class htmlTableHeader extends htmlTableCell {

    public function get_html() {
        $html = '<th' . $this->get_properties() . $this->get_class() . '>' . $this->text . '</th>';
        return $html;
    }

}

class htmlTable extends htmlControl {

    protected $headers = array();
    protected $rows = array();

    public function __construct($id = null, $class = null) {
        $this->set_id($id);
        $this->set_class($class);
        $this->add_class('ond_table');
    }

    public function add_header(htmlTableHeader $header) {
        $this->headers[] = $header;
    }

    public function add_row() {
        $this->rows[] = array();
    }

    public function remove_row() {
        array_pop($this->rows);
    }

    public function add_cell(htmlTableCell $cell) {
        if (empty($this->rows)) {
            $this->add_row();
        }
        $this->rows[count($this->rows) - 1][] = $cell;
    }

    // Column count of header columns.
    public function get_column_count() {
        return count($this->headers);
    }

    // Row count, not including header row.
    public function get_row_count() {
        return count($this->rows);
    }

    public function get_html() {
        $html = '<table' . $this->get_properties() . $this->get_class() . ">\n<thead>\n<tr>";
        foreach ($this->headers as $header) {
            $html .= $header->get_html();
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";
        foreach ($this->rows as $rowcells) {
            $html .= "<tr>";
            foreach ($rowcells as $cell) {
                $html .= $cell->get_html();
            }
            $html .= "</tr>\n";
        }
        $html .= "</tbody>\n</table>";
        return $html;
    }

}

class htmlListItem extends htmlControl {

    protected $text;

    public function __construct($text, $id = null, $class = null) {
        $this->set_text($text);
        $this->set_id($id);
        $this->set_class($class);
    }

    public function set_text($labeltext) {
        $this->text = $labeltext;
    }

    public function get_html() {
        return '<li' . $this->get_properties() . $this->get_class() . '>' . $this->text . '</li>';
    }

}

class htmlList extends htmlControl {

    protected $items = array();
    protected $ordered;

    public function __construct($id = null, $class = null, $ordered = false) {
        $this->set_id($id);
        $this->set_class($class);
        $this->ordered = $ordered;
    }

    public function get_html() {
        if ($this->ordered) {
            $html = '<ol';
        } else {
            $html = '<ul';
        }
        $html .= $this->get_properties() . $this->get_class() . ">\n";
        foreach ($this->items as $item) {
            $html .= $item->get_html() . "\n";
        }
        if ($this->ordered) {
            $html .= '</ol>';
        } else {
            $html .= '</ul>';
        }
        return $html;
    }

    public function add_item(htmlListItem $item) {
        $this->items[] = $item;
    }

}