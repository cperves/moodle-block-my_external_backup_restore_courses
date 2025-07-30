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
 *
 * @package
 * @subpackage
 * @copyright  2025 Université de Strasbourg  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_external_backup_restore_courses;

use block_my_external_backup_restore_courses_task_helper;
use block_my_external_backup_restore_courses_tools;
use calendartype_test_example\structure;
use stdClass;

class locallib_test extends \advanced_testcase {
    public function test_cancelled_status(){
        global $CFG, $DB;
        $this->datagenerator = $this->getDataGenerator();
        $defaultcategory = $this->datagenerator->create_category(array('idnumber' => 'defaultcat'));
        set_config('restorecourseinoriginalcategory', 0, 'block_my_external_backup_restore_courses');
        set_config('defaultcategory', $defaultcategory->id, 'block_my_external_backup_restore_courses');
        set_config('search_roles', 'editingteacher', 'block_my_external_backup_restore_courses');
        $token = block_my_external_backup_restore_courses_tools::install_webservice_moodle_server();
        set_config('external_moodle',$CFG->wwwroot,'block_my_external_backup_restore_courses');
        $taskrecord = new stdClass();
        $taskrecord->externalmoodleurl = $CFG->wwwroot;
        $taskrecord->userid = 0;
        $taskrecord->restoredby = get_admin()->id;
        $taskrecord->externalcourseid = 42;
        $taskrecord->internalcategory = 0;
        $taskrecord->internalcourseid = $defaultcategory->id;
        $taskrecord->withuserdatas = 0;
        $taskrecord->status = block_my_external_backup_restore_courses_tools::STATUS_CANCELLED;
        $DB->insert_record('block_external_backuprestore', $taskrecord);
        ob_start();
        block_my_external_backup_restore_courses_task_helper::run_automated_backup_restore();
        ob_get_contents();
        ob_end_clean();
        $tasks = block_my_external_backup_restore_courses_task_helper::retrieve_tasks($defaultcategory->id);
        $this->assertCount(0, $tasks);
    }


    protected function setUp() : void {
        parent::setUp();
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
    }
}