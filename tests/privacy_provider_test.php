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
 * Provides the {@link workshopform_accumulative_privacy_provider_testcase} class.
 *
 * @package     block_my_external_backup_restore_courses
 * @category    test
 * @copyright   2019 Céline Pervès <cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use \core_privacy\local\request\approved_userlist;
use \block_my_external_backup_restore_courses\privacy\provider;

/**
 * Unit tests for the privacy API implementation.
 *
 * @copyright 2019 Céline Pervès <cperves@unistra.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Tets get_contexts_for_userid function.
     * Function that get the list of contexts that contain user information for the specified user.
     * @throws coding_exception
     */
    public function test_user_contextlist() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);

        // Test.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);
        $courseperforedcontext = context_course::instance($entryperformed->courseid);
        $this->assertContains($usercontext, $contextlist->get_contexts());
        $this->assertContains($courseperforedcontext, $contextlist->get_contexts());

    }

    /**
     * Test export_context_data_for_user function.
     * Function that Export all data within a context for a component for the specified user.
     * @throws coding_exception
     */
    public function test_export_context_data_for_user() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);
        $this->export_context_data_for_user($user->id, $usercontext, 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($usercontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(4, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals($user->id, $restorecourserecord->userid);
        }
        // Course_context.
        $courseperformedcontext = context_course::instance($entryperformed->courseid);
        $this->export_context_data_for_user($user->id, $courseperformedcontext , 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($courseperformedcontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(1, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals($courseperformedcontext->instanceid, $restorecourserecord->courseid);
        }
    }

    /**
     * Test export_all_data_for_user function.
     * funciton that export all data for a component for the specified user.
     * @throws coding_exception
     */
    public function test_export_all_data_for_user() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);
        $this->export_all_data_for_user($user->id, 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($usercontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(4, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals($user->id, $restorecourserecord->userid);
        }
        // Course_context.
        $courseperformedcontext = context_course::instance($entryperformed->courseid);
        $this->export_context_data_for_user($user->id, $courseperformedcontext,
                'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($courseperformedcontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(1, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals($courseperformedcontext->instanceid, $restorecourserecord->courseid);
        }
    }

    /**
     * Test delete_data_for_all_users_in_context function.
     * Function that delete all data for all users in the specified context
     * @throws coding_exception
     */
    public function test_delete_data_for_all_users_in_context() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);
        $courseperformedcontext = context_course::instance($entryperformed->courseid);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);
        provider::delete_data_for_all_users_in_context($courseperformedcontext);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        provider::delete_data_for_all_users_in_context($usercontext);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);// Inprogress state not deleted.
        $this->export_context_data_for_user($user->id, $usercontext, 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($usercontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(1, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals(null, $restorecourserecord->courseid);
            $this->assertEquals(block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS, $restorecourserecord->status);
        }
    }

    /**
     * Test delete_data_for_users function.
     * Function that Delete multiple users within a single context.
     * @throws coding_exception
     */
    public function test_delete_data_for_all_users() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);
        $courseperformedcontext = context_course::instance($entryperformed->courseid);
        $userapproveduserlist = new approved_userlist($usercontext, 'block_user_session', [$user->id]);
        $courseapproveduserlist = new approved_userlist($courseperformedcontext, 'block_user_session', [$user->id]);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);
        provider::delete_data_for_users($courseapproveduserlist);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        provider::delete_data_for_users($userapproveduserlist);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->export_context_data_for_user($user->id, $usercontext, 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($usercontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(1, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals(null, $restorecourserecord->courseid);
            $this->assertEquals(block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS, $restorecourserecord->status);
        }

    }

    /**
     * Test delete_data_for_user function.
     * Function that delete all user data for the specified user, in the specified contexts.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_user() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        list($entryscheduled, $entryinprogress, $entryperformed, $entryerror) = $this->create_backuprestore_entries($user);
        $courseperformedcontext = context_course::instance($entryperformed->courseid);
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
                \core_user::get_user($user->id),
                'block_external_backup_restore_courses',
                [$usercontext->id, $courseperformedcontext->id]
        );
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);
        provider::delete_data_for_user($approvedcontextlist);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->export_context_data_for_user($user->id, $usercontext, 'block_my_external_backup_restore_courses');
        $writer = \core_privacy\local\request\writer::with_context($usercontext);
        $data = $writer->get_data([get_string('pluginname', 'block_my_external_backup_restore_courses')]);
        $this->assertTrue($writer->has_any_data());
        $this->assertInstanceOf('stdClass', $data);
        $this->assertTrue(property_exists($data, 'restore_course_records'));
        $this->assertCount(1, $data->restore_course_records);
        foreach ($data->restore_course_records as $restorecourserecord) {
            $this->assertEquals(null, $restorecourserecord->courseid);
            $this->assertEquals(block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS, $restorecourserecord->status);
        }

    }

    /**
     * create_backuprestore_entries usefull to test
     * @param stdClass $user
     * @throws coding_exception
     */
    private function create_backuprestore_entries(stdClass $user) {
        $courseperformed = $this->getDataGenerator()->create_course();
        $entryscheduled = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->create_backup_restore_entry($user->id, $courseperformed->id + 1, $courseperformed->category);
        $entryinprogress = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->create_backup_restore_entry($user->id, $courseperformed->id + 2, $courseperformed->category);
        $entryinprogress->status = block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS;
        $entryinprogress = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->update_backup_restore_entry($entryinprogress);
        $entryperformed = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->create_backup_restore_entry($user->id, $courseperformed->id, $courseperformed->category);
        $entryperformed->status = block_my_external_backup_restore_courses_tools::STATUS_PERFORMED;
        $entryperformed->courseid = $courseperformed->id;
        $entryperformed = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->update_backup_restore_entry($entryperformed);
        $entryerror = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->create_backup_restore_entry($user->id, $courseperformed->id + 3, $courseperformed->category);
        $entryerror->status = block_my_external_backup_restore_courses_tools::STATUS_ERROR;
        $entryerror = $this->getDataGenerator()->get_plugin_generator('block_my_external_backup_restore_courses')
            ->update_backup_restore_entry($entryerror);
        return array($entryscheduled, $entryinprogress, $entryperformed, $entryerror);
    }
}
