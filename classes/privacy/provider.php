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
 * Privacy Subsystem implementation for repository_flickr.
 *
 * @package    repository_flickr
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_external_backup_restore_courses\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use gradereport_singleview\local\ui\empty_element;
global $CFG;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');

/**
 * Privacy Subsystem for block_my_external_backup_restore_courses implementing metadata, plugin, and user_preference providers.
 *
 * @copyright  2019 University of Strasbourg
 * @author Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider
{
    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_external_location_link(
                'remote.moodle',
                [
                        'externalcourseid' =>
                                'privacy:metadata:blocks_my_external_backup_restore_courses:remote_moodle:externalcourseid'
                ],
                'privacy:metadata:blocks_my_external_backup_restore_courses:remote_moodle'
        );
        // No files stored since archive files are deleted in current moodle.
        $collection->add_subsystem_link('core_enrol', [], 'privacy:metadata:core_enrol');
        $collection->add_database_table('block_external_backuprestore',
            [
                'userid' => 'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:userid',
                'externalcourseid' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalcourseid',
                'externalcoursename' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalcoursename',
                'externalmoodleurl' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalmoodleurl',
                'internalcategory' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:internalcategory',
                'status' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:status',
                'courseid' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:courseid',
                'timecreated' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timecreated',
                'timemodified' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timemodified',
                'timescheduleprocessed' =>
                     'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timescheduleprocessed'
            ],
            'privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Store datas in system and course context.
        // Since enrolment is a subsystem link does not return user enrolments in course.
        $contextlist = new contextlist();
        $contextlist->add_user_context($userid);
        // Add linked course context.
        $sql = 'select ctx.id from {block_external_backuprestore} beb
                    inner join {context} ctx on ctx.instanceid=beb.courseid and ctx.contextlevel=:coursecontext
                    where userid=:userid and beb.courseid is not null';
        $params = [
            'coursecontext' => CONTEXT_COURSE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_user or $context instanceof \context_course) {
            if ($context instanceof \context_user) {
                $sql = 'select beb.userid as userid from {block_external_backuprestore} beb where beb.userid=:userid';
                $params = [
                        'userid' => $context->instanceid
                ];
                $userlist->add_from_sql('userid', $sql, $params);
            }
            if ($context instanceof \context_course) {
                $sql = 'select beb.userid as userid from {block_external_backuprestore} beb where beb.courseid=:courseid';
            }
            $params = [
                    'courseid' => $context->instanceid
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        // Sanitize contexts.
        $aprovedcontextlist = self::validate_contextlist_contexts($contextlist, [CONTEXT_USER, CONTEXT_COURSE]);
        if (empty($aprovedcontextlist)) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $entries = array();
        // Return database entries.
        foreach ($aprovedcontextlist as $approvedcontext) {
            if ($approvedcontext instanceof \context_user) {
                $entries = $DB->get_records('block_external_backuprestore', array('userid' => $approvedcontext->instanceid));
            }
            if ($approvedcontext instanceof \context_course) {
                $params = array(
                        'courseid' => $approvedcontext->instanceid,
                        'userid' => $userid
                );
                $entries = $DB->get_records('block_external_backuprestore', $params);
            }
            if (!empty($entries)) {
                writer::with_context($approvedcontext)->export_data(
                        [
                                get_string('pluginname', 'block_my_external_backup_restore_courses')
                        ],
                        (object)['restore_course_records' => $entries]
                );
            }
        }

        // Can't be sure that enrolment was triggered by this plugin + can retrieve course enrolment through other report.
        // Coursecontext : need to return course enrolment database entries if restored course context.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        // Can't remove enrolment since can\'t be sure it comes from.
        if ($context instanceof \context_user) {
            $DB->delete_records_select('block_external_backuprestore', 'userid=:userid and status <> :status',
                    array('userid' => $context->instanceid,
                            'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                    )
            );
        }
        if ($context instanceof \context_course) {
            $DB->delete_records_select('block_external_backuprestore', 'courseid=:courseid and status <> :status',
                    array('courseid' => $context->instanceid,
                            'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                    )
            );
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user) {
                $DB->delete_records_select('block_external_backuprestore', 'userid=:userid and status <> :status',
                        array('userid' => $context->instanceid,
                                'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                        )
                );
            }
            if ($context instanceof \context_course) {
                $DB->delete_records_select('block_external_backuprestore', 'courseid=:courseid and status <> :status',
                        array('courseid' => $context->instanceid,
                                'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                        )
                );
            }
        }

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        foreach ($userlist as $user) {
            if ($context instanceof \context_user && $user->id == $context->instanceid) {

                $DB->delete_records_select('block_external_backuprestore', 'userid=:userid and status <> :status',
                        array('userid' => $context->instanceid,
                                'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS));
            }
            if ($context instanceof \context_course) {
                $DB->delete_records_select('block_external_backuprestore',
                        'courseid=:courseid and status <> :status and userid=:userid',
                        array('courseid' => $context->instanceid, 'userid' => $user->id,
                                'status' => \block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS));
            }
        }
    }

    /**
     * sanitize contextlist course and system context
     * @param approved_contextlist $contextlist
     * @return mixed
     */
    protected static function validate_contextlist_contexts(approved_contextlist $contextlist, $contextlevellist) {
        return array_reduce($contextlist->get_contexts(), function($carry, $context) use($contextlevellist) {
            if (in_array($context->contextlevel, $contextlevellist)) {
                $carry[$context->id] = $context;
            }
            return $carry;
        }, []);
    }

}
