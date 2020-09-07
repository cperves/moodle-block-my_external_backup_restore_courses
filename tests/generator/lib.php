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
 * block_user_session data generator
 *
 * @package    block_user_session
 * @category   test
 * @copyright  2020 University of Strasbourg  {@link http://unistra.fr}
 * @author Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class block_my_external_backup_restore_courses_generator extends testing_block_generator {

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
    }

    public function create_backup_restore_entry($userid, $courseid,$categoryid){
        global $DB, $CFG;
        require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
        $datas = new stdClass();
        $datas->userid = $userid;
        $datas->externalcourseid = $courseid;
        $datas->externalmoodleurl = 'mock_external_url';
        $datas->externalmoodletoken = 'mock_external_token';
        $datas->internalcategory = $categoryid;
        $datas->externalcoursename = 'mock_externalcoursename';
        $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
        $datas->timecreated = time();
        $id = $DB->insert_record('block_external_backuprestore', $datas);;
        $datas->id = $id;
        return $datas;

    }

    public function update_backup_restore_entry($record){
        global $DB;
        $DB->update_record('block_external_backuprestore', $record);
        return $record;
    }
}
