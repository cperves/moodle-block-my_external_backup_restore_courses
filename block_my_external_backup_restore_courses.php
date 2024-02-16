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
 * Folder plugin version information
 *
 * @package
 * @subpackage
 * @subpackage
 * @copyright  2013 unistra  {@link http://unistra.fr}
 * @author Thierry Schlecht <thierry.schlecht@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_my_external_backup_restore_courses extends block_list {
    protected function init() {
        $this->title = get_string('pluginname', 'block_my_external_backup_restore_courses');
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $USER, $CFG, $PAGE;
        if (!has_capability('block/my_external_backup_restore_courses:view', $PAGE->context)) {
            return;
        }

        require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->items[] =
            (isguestuser($USER->id) or empty($USER->id) or $USER->id == 0) ?
                '' : block_my_external_backup_restore_courses_tools::print_content();
        $this->content->icons[] = '';
        $this->content->footer = '';
        return $this->content;
    }

    public function applicable_formats() {
        return array('all ' => true, 'course-view' => true,
            'mod' => false, 'site' => true , 'my' => true, 'tag' => false);
    }

}
