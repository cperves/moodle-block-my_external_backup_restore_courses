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
 * Unit tests for the backup_restore webservices
 * @package     blocks_my_external_backup_restore_courses
 * @category    test
 * @copyright   2021 Céline Pervès <cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
//require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot.'/webservice/lib.php');

class block_my_external_backup_restore_courses_externallib_testcase extends externallib_advanced_testcase {
    private $datagenerator;
    private $course1;
    private $course2;
    private $defaultcategory;
    private $coursecategory;
    private $editingteacheruser;
    private $studentuser;
    private $wsuser;
    private $wsrole;


    public function test_get_courses(){
        $this->setUser($this->wsuser);
        $courses = block_my_external_backup_restore_courses_external::get_courses($this->editingteacheruser->username,
            'editingteacher');
        $courses = external_api::clean_returnvalue(
            block_my_external_backup_restore_courses_external::get_courses_returns(), $courses);
        $this->assertCount(1, $courses);
        $course = array_pop($courses);
        $this->assertEquals($this->course1->id, $course['id']);
        $this->assertEquals($this->coursecategory->idnumber, $course['categoryidentifier']);
        $this->assertEquals($this->coursecategory->id, $course['category']);
    }

    public function test_get_courses_zip(){
        $this->setUser($this->wsuser);
        $coursezip = block_my_external_backup_restore_courses_external::get_courses_zip($this->editingteacheruser->username,
            $this->course1->id);

        $file = external_api::clean_returnvalue(
            block_my_external_backup_restore_courses_external::get_courses_zip_returns(), $coursezip);
        $this->assertCount(2, $file);
        $this->assertTrue(array_key_exists('filename', $file));
        $this->assertTrue(array_key_exists('filerecordid', $file));
        $fs = get_file_storage();
        $storefile = $fs->get_file_by_id($file['filerecordid']);
        $this->assertNotEmpty($storefile);
        $this->assertEquals('application/vnd.moodle.backup', $storefile->get_mimetype());
        $this->assertEquals('block_my_external_backup_restore_courses', $storefile->get_source());
    }

    public function test_restore_course(){
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(2, $coursesincoursecategory);
        $this->assertCount(0, $coursesindefaultcategory);
        $this->restore_course(0);
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(2, $coursesincoursecategory);
        $this->assertCount(1, $coursesindefaultcategory);
        $this->restore_course($this->coursecategory->id);
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(3, $coursesincoursecategory);
        $this->assertCount(1, $coursesindefaultcategory);
    }

    protected function setUp() : void {
        parent::setUp();
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->datagenerator = $this->getDataGenerator();
        $coursecreatorrole = $DB->get_record('role', array('shortname' => 'coursecreator'));
        $this->defaultcategory = $this->datagenerator->create_category(array('idnumber' => 'defaultcat'));
        $this->coursecategory = $this->datagenerator->create_category(array('idnumber' => 'coursecat'));
        set_config('restorecourseinoriginalcategory', 1, 'block_my_external_backup_restore_courses');
        set_config('defaultcategory', $this->defaultcategory->id, 'block_my_external_backup_restore_courses');
        // Webservice settings.
        $systemcontext = context_system::instance();
        $this->wsuser = $this->datagenerator->create_user();
        $roleid = $this->datagenerator->create_role();
        $this->wsrole = $DB->get_record('role', array('id' => $roleid));
        assign_capability('block/my_external_backup_restore_courses:can_see_backup_courses', CAP_ALLOW, $this->wsrole->id, $systemcontext->id, true);
        assign_capability('block/my_external_backup_restore_courses:can_retrieve_courses', CAP_ALLOW, $this->wsrole->id, $systemcontext->id, true);
        role_assign($this->wsrole->id, $this->wsuser->id, $systemcontext->id);
        // Add necessary capabilities for restore user
        assign_capability('moodle/restore:restorecourse', CAP_ALLOW, $coursecreatorrole->id, $systemcontext, true);
        accesslib_clear_all_caches_for_unit_testing();
        // Courses datas.
        $this->editingteacheruser = $this->datagenerator->create_user();
        $this->studentuser = $this->datagenerator->create_user();
        $this->course1 = $this->datagenerator->create_course(array('category' => $this->coursecategory->id));
        $this->datagenerator->create_module('forum', array(
            'course' => $this->course1->id));
        $this->course2 = $this->datagenerator->create_course(array('category' => $this->coursecategory->id));
        $this->datagenerator->create_module('forum', array(
            'course' => $this->course2->id));
        $this->datagenerator->role_assign($coursecreatorrole->id, $this->editingteacheruser->id);
        $this->datagenerator->enrol_user($this->editingteacheruser->id, $this->course1->id, 'editingteacher');
        $this->datagenerator->enrol_user($this->studentuser->id, $this->course1->id, 'student');
        $this->datagenerator->enrol_user($this->editingteacheruser->id, $this->course2->id, 'teacher');
        // Disable all loggers.
        $CFG->backup_error_log_logger_level = backup::LOG_NONE;
        $CFG->backup_output_indented_logger_level = backup::LOG_NONE;
        $CFG->backup_file_logger_level = backup::LOG_NONE;
        $CFG->backup_database_logger_level = backup::LOG_NONE;
        $CFG->backup_file_logger_level_extra = backup::LOG_NONE;
    }

    /**
     * @param object $CFG
     * @param $categoryid
     * @param $DB
     * @return mixed
     * @throws invalid_response_exception
     */
    private function restore_course($internalcategory) {
        global $DB, $CFG;
        // Add course to courses to restore
        $datas = new stdClass();
        $datas->userid = $this->editingteacheruser->id;
        $datas->externalcourseid = $this->course1->id;
        $datas->externalcoursename = $this->course1->shortname;
        $datas->externalmoodleurl = $CFG->wwwroot;
        $datas->externalmoodletoken = 'atoken';
        $datas->internalcategory = $internalcategory;
        $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
        $datas->timecreated = time();
        $taskid = $DB->insert_record('block_external_backuprestore', $datas);
        // retrieve created task
        $tasks = block_my_external_backup_restore_courses_task_helper::retrieve_tasks(empty($internalcategory) ? $this->defaultcategory->id : $internalcategory);
        $task = array_pop($tasks);
        $taskobject = new block_my_external_backup_restore_courses_task($task);
        $this->setUser($this->wsuser);
        $coursezip = block_my_external_backup_restore_courses_external::get_courses_zip($this->editingteacheruser->username,
            $this->course1->id);
        $file = external_api::clean_returnvalue(
            block_my_external_backup_restore_courses_external::get_courses_zip_returns(), $coursezip);
        $fs = get_file_storage();
        $storefile = $fs->get_file_by_id($file['filerecordid']);
        $path = $CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR
            .block_my_external_backup_restore_courses_task_helper::BACKUP_TEMPDIRNAME;
        $storefile->copy_content_to($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR
            . block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME);
        $taskobject->restore_course_from_backup_file($this->defaultcategory->id);
        $DB->delete_records('block_external_backuprestore', array('id' => $taskid));
    }

}