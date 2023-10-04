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
 * @package     block_my_external_backup_restore_courses
 * @category    test
 * @copyright   2021 Céline Pervès <cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_external_backup_restore_courses;

global $CFG;

use backup;
use block_my_external_backup_restore_courses_external;
use block_my_external_backup_restore_courses_task;
use block_my_external_backup_restore_courses_task_helper;
use block_my_external_backup_restore_courses_tools;
use context_course;
use context_system;
use core_competency\course_competency;
use core_competency\user_evidence_competency;
use external_api;
use externallib_advanced_testcase;
use stdClass;
use core_competency\api;

require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
//require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot.'/webservice/lib.php');

class externallib_test extends externallib_advanced_testcase {
    private $datagenerator;
    private $course1;
    private $defaultcategory;
    private $coursecategory;
    private $editingteacheruser;
    private $studentuser1;
    private $studentuser2;
    private $wsuser;
    private $wsrole;
    private $forum;
    protected const EDITING_TEACHER_USERNAME = 'editingteacher1';


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

    /**
     * @dataProvider username_provider
     */
    public function test_get_courses_zip($username){
        $this->setUser($this->wsuser);
        $coursezip = block_my_external_backup_restore_courses_external::get_courses_zip($username,
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

    /**
     * @dataProvider username_provider
     */

    public function test_get_courses_zip_withuserdatas($username){
        $this->setUser($this->wsuser);
        $coursezip = block_my_external_backup_restore_courses_external::get_courses_zip($username,
            $this->course1->id, true);

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


    public function test_restore_course_in_categories() {
        global $PAGE, $DB;
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(1, $coursesincoursecategory);
        $this->assertCount(0, $coursesindefaultcategory);
        $this->restore_course(0, false, backup::ENROL_NEVER);
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(1, $coursesincoursecategory);
        $this->assertCount(1, $coursesindefaultcategory);
        $this->restore_course($this->coursecategory->id, false, backup::ENROL_NEVER);
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(2, $coursesincoursecategory);
        $this->assertCount(1, $coursesindefaultcategory);
    }

    /**
     * @dataProvider enrolmode_withoutuserdatas_competencies_provider
     */
    public function test_restore_course_withoutuserdatas($enrolmentmode, $iscompetencyenabled){
        global $DB;
        $this->set_comptencies($iscompetencyenabled);
        $restoredcourseid = $this->restore_course(0, false, $enrolmentmode);
        // Check that user datas are here.
        // Pass as admin to check course datas.
        $this->setAdminUser();
        $coursecontext = context_course::instance($restoredcourseid);
        $enrollees = get_enrolled_users($coursecontext);
        if($enrolmentmode == backup::ENROL_ALWAYS) {
            // User userstudent1 enrolled by cohort
            // editingteacher  enrolled as requester
            $this->assertCount(2, $enrollees);
        } else {
            $this->assertCount(1, $enrollees); // Requester is manually enrolled
        }
        $this->assertNotFalse(array_search($this->editingteacheruser, $enrollees));
        $enrolleeswithmethod =
            $DB->get_records_sql(
                'select ue.userid,e.enrol from {user_enrolments} ue inner join {enrol} e on e.id=ue.enrolid where courseid=:courseid',
                array('courseid' => $restoredcourseid)
            );
        if($enrolmentmode == backup::ENROL_ALWAYS) {
            $this->assertCount(2, $enrolleeswithmethod);
            $this->assertNotFalse(array_search($this->editingteacheruser->id, array_keys($enrolleeswithmethod)));
            $this->assertNotFalse(array_search($this->studentuser1->id, array_keys($enrolleeswithmethod)));
            $this->assertFalse(array_search($this->studentuser2->id, array_keys($enrolleeswithmethod)));
        } else {
            $this->assertCount(1, $enrolleeswithmethod);
            $this->assertNotFalse(array_search($this->editingteacheruser->id, array_keys($enrolleeswithmethod)));
        }

        $enrolinstances = enrol_get_instances($restoredcourseid, true);
        if($enrolmentmode == backup::ENROL_ALWAYS) {
            $this->assertCount(2, $enrolinstances);
        } else {
            $this->assertCount(1, $enrolinstances);
        }
        $forum = forum_get_course_forum($restoredcourseid, 'news');
        $this->assertNotFalse($forum);
        $forumcm = get_course_and_cm_from_instance($forum->id, 'forum');
        $forumcm = $forumcm[1];
        $discussions = forum_get_discussions($forumcm);
        $this->assertEmpty($discussions);
        if ($iscompetencyenabled) {
            $this->check_competencies($restoredcourseid, false);
        }
    }

    /**
     * @dataProvider enrolmode_withuserdatas_competencies_provider
     */

    public function test_restore_course_withuserdatas($enrolmentmode, $iscompetencyenabled){
        global $DB;
        set_config('backup_general_users', 1, 'backup');
        $this->set_comptencies($iscompetencyenabled);
        $coursesincoursecategory = get_courses($this->coursecategory->id);
        $coursesindefaultcategory = get_courses($this->defaultcategory->id);
        $this->assertCount(1, $coursesincoursecategory);
        $this->assertCount(0, $coursesindefaultcategory);
        $this->setAdminUser();
        $restoredcourseid = $this->restore_course(0, true, $enrolmentmode);
        // Check that user datas are here.
        // Pass as admin to check course datas.
        $this->setAdminUser();
        $coursecontext = context_course::instance($restoredcourseid);
        $enrollees = get_enrolled_users($coursecontext);
        $this->assertCount(3, $enrollees);
        $this->assertNotFalse(array_search($this->editingteacheruser, $enrollees));
        $this->assertNotFalse(array_search($this->studentuser1, $enrollees));
        $enrolinstances = enrol_get_instances($restoredcourseid, true);
        if ($enrolmentmode == backup::ENROL_NEVER) {
            $this->assertCount(1, $enrolinstances);
        } else {
            $this->assertCount(2, $enrolinstances);
        }
        $enrolleeswithmethod =
            $DB->get_records_sql(
                'select ue.userid,e.enrol from {user_enrolments} ue inner join {enrol} e on e.id=ue.enrolid where courseid=:courseid',
                array('courseid' => $restoredcourseid)
            );
        $this->assertCount(3, $enrolleeswithmethod);
        $this->assertNotFalse(array_search($this->editingteacheruser->id, array_keys($enrolleeswithmethod)));
        $this->assertNotFalse(array_search($this->studentuser1->id, array_keys($enrolleeswithmethod)));
        $this->assertEquals('manual',$enrolleeswithmethod[$this->editingteacheruser->id]->enrol);
        $this->assertEquals('manual',$enrolleeswithmethod[$this->studentuser2->id]->enrol);
        if ($enrolmentmode == backup::ENROL_NEVER) {
            $this->assertEquals('manual', $enrolleeswithmethod[$this->studentuser1->id]->enrol);
        } else {
            $this->assertEquals('cohort', $enrolleeswithmethod[$this->studentuser1->id]->enrol);
        }
        $forum = forum_get_course_forum($restoredcourseid, 'news');
        $this->assertNotFalse($forum);
        $forumcm = get_course_and_cm_from_instance($forum->id, 'forum');
        $forumcm = $forumcm[1];
        $discussions = forum_get_discussions($forumcm);
        $this->assertNotFalse($discussions);
        $this->assertCount(1, $discussions);
        $this->assertNotFalse(forum_get_user_posts($forum->id, $this->studentuser1->id));
        if ($iscompetencyenabled) {
            $this->check_competencies($restoredcourseid, true);
        }
    }

    private function set_comptencies($iscompetencyenabled) {
        global $DB;
        if($iscompetencyenabled){
            set_config('enabled', true, 'core_competency');
            $dg = $this->getDataGenerator();
            $lpg = $dg->get_plugin_generator('core_competency');
            // Framework.
            $framework = $lpg->create_framework();
            $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
            // Add competency to course;
            $this->setAdminUser();
            api::add_competency_to_course($this->course1->id, $comp1->get('id'));
            api::grade_competency_in_course($this->course1->id, $this->studentuser1->id, $comp1->get('id'), 1, true);
            api::grade_competency($this->studentuser1->id, $comp1->get('id'), 1, true);
            api::grade_competency_in_course($this->course1->id, $this->studentuser1->id,$comp1->get('id'), 2 , true);
            $usercoursecompetency = api::get_user_competency_in_course($this->course1->id,$this->studentuser1->id, $comp1->get('id'));
            $this->assertNotEmpty($usercoursecompetency);
        } else {
            set_config('enabled', false, 'core_competency');
        }

    }

    private function check_competencies($restoredcourseid, $withuserdatas) {
        $coursecompetencies = course_competency::list_competencies($restoredcourseid);
        $this->assertCount(1, $coursecompetencies);
        $competency = array_pop($coursecompetencies);
        $usercoursecompetency = api::get_user_competency_in_course($restoredcourseid,$this->studentuser1->id, $competency->get('id'));
        if ($withuserdatas) {
            $this->assertNotNull($usercoursecompetency->get('grade'));
        } else {
            $this->assertNull($usercoursecompetency->get('grade'));
        }
    }

    protected function setUp() : void {
        parent::setUp();
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $CFG->enrol_plugins_enabled = 'cohort,manual';
        $cohortplugin = enrol_get_plugin('cohort');
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
        assign_capability('block/my_external_backup_restore_courses:can_see_backup_courses', CAP_ALLOW,
            $this->wsrole->id, $systemcontext->id, true);
        assign_capability('block/my_external_backup_restore_courses:can_retrieve_courses', CAP_ALLOW,
            $this->wsrole->id, $systemcontext->id, true);
        assign_capability('moodle/course:viewhiddencourses', CAP_ALLOW,
            $this->wsrole->id, $systemcontext->id, true);
        assign_capability('moodle/category:viewcourselist', CAP_ALLOW,
            $this->wsrole->id, $systemcontext->id, true);
        role_assign($this->wsrole->id, $this->wsuser->id, $systemcontext->id);
        // Add necessary capabilities for restore user
        assign_capability('moodle/restore:restorecourse', CAP_ALLOW, $coursecreatorrole->id, $systemcontext, true);
        accesslib_clear_all_caches_for_unit_testing();
        // Courses datas.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $editingteacherrecord = new  stdClass();
        $editingteacherrecord->username='editingteacher1';
        $this->editingteacheruser = $this->datagenerator->create_user($editingteacherrecord);
        $this->studentuser1 = $this->datagenerator->create_user();
        $this->studentuser2 = $this->datagenerator->create_user();
        $this->course1 = $this->datagenerator->create_course(array('category' => $this->coursecategory->id));
        $cohortrecord = new \stdClass();
        $cohortrecord->contextid = \context_system::instance()->id;
        $cohortrecord->name = 'The loneliest';
        $cohortrecord->idnumber = 'loneliestcohort';
        $cohortrecord->description = 'You\'ll be the sadiest part of me...';
        $cohortrecord->descriptionformat = FORMAT_HTML;
        $cohort = $this->getDataGenerator()->create_cohort($cohortrecord);
        cohort_add_member($cohort->id, $this->studentuser1->id);
        $cohortplugin->add_instance($this->course1, array('customint1' => $cohort->id,'roleid' => $studentrole->id));
        $this->datagenerator->create_module('forum', array(
            'course' => $this->course1->id));
        $this->forum = forum_get_course_forum($this->course1->id, 'news');
        $this->datagenerator->role_assign($coursecreatorrole->id, $this->editingteacheruser->id);
        $this->datagenerator->enrol_user($this->editingteacheruser->id, $this->course1->id, 'editingteacher', 'manual');
        $this->datagenerator->enrol_user($this->studentuser2->id, $this->course1->id, 'student', 'manual');
        // Disable all loggers.
        $CFG->backup_error_log_logger_level = backup::LOG_NONE;
        $CFG->backup_output_indented_logger_level = backup::LOG_NONE;
        $CFG->backup_file_logger_level = backup::LOG_NONE;
        $CFG->backup_database_logger_level = backup::LOG_NONE;
        $CFG->backup_file_logger_level_extra = backup::LOG_NONE;
        $this->emulate_user_activites();
    }

    /**
     * @param object $CFG
     * @param $categoryid
     * @param $DB
     * @return mixed
     * @throws invalid_response_exception
     */
    private function restore_course($internalcategory, $withuserdatas,$enrolmentmode) {
        global $DB, $CFG;
        // Add course to courses to restore
        $datas = new  stdClass();
        $datas->userid = $this->editingteacheruser->id;
        $datas->externalcourseid = $this->course1->id;
        $datas->externalcoursename = $this->course1->shortname;
        $datas->externalmoodleurl = $CFG->wwwroot;
        $datas->externalmoodletoken = 'atoken';
        $datas->internalcategory = $internalcategory;
        $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
        $datas->enrolmentmode = $enrolmentmode;
        $datas->timecreated = time();
        $datas->withuserdatas = $withuserdatas;
        $taskid = $DB->insert_record('block_external_backuprestore', $datas);
        // retrieve created task
        $tasks = block_my_external_backup_restore_courses_task_helper::retrieve_tasks(empty($internalcategory) ? $this->defaultcategory->id : $internalcategory);
        $task = array_pop($tasks);
        $taskobject = new block_my_external_backup_restore_courses_task($task);
        $this->setUser($this->wsuser);
        $coursezip = block_my_external_backup_restore_courses_external::get_courses_zip($this->editingteacheruser->username,
            $this->course1->id, $withuserdatas);
        $file = external_api::clean_returnvalue(
            block_my_external_backup_restore_courses_external::get_courses_zip_returns(), $coursezip);
        $fs = get_file_storage();
        $storefile = $fs->get_file_by_id($file['filerecordid']);
        $storefile->copy_content_to($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR
            . block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME);
        $restoredcourseid = $taskobject->restore_course_from_backup_file($this->defaultcategory->id, $withuserdatas, $enrolmentmode);
        $DB->delete_records('block_external_backuprestore', array('id' => $taskid));
        return $restoredcourseid;
    }

    /**
     * @return void
     */
    private function emulate_user_activites(): void {
        // Emulate activites
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $forumgenerator->create_discussion(
            array(
                'course' => $this->course1->id,
                'forum' => $this->forum->id,
                'userid' => $this->editingteacheruser->id,
                'attachment' => 1
            )
        );
        $record = new stdClass();
        $record->discussion = $discussion->id;
        $record->parent = $discussion->firstpost;
        $record->userid = $this->studentuser1->id;
        $record->created = $record->modified = time();
        $forumgenerator->create_post($record);

    }

    // Provider.
    public function enrolmode_withuserdatas_competencies_provider(): array {
        return [
            [backup::ENROL_ALWAYS, true],
            [backup::ENROL_ALWAYS, false],
            [backup::ENROL_NEVER, true],
            [backup::ENROL_NEVER, false],
            [backup::ENROL_WITHUSERS, true],
            [backup::ENROL_WITHUSERS, false],
        ];
    }

    public function enrolmode_withoutuserdatas_competencies_provider(): array {
        return [
            [backup::ENROL_ALWAYS, true],
            [backup::ENROL_ALWAYS, false],
            [backup::ENROL_NEVER, true],
            [backup::ENROL_NEVER, false],
        ];
    }

    // Provider.
    public function username_provider(): array {
        return [
            [self::EDITING_TEACHER_USERNAME],
            [''],
        ];
    }

}