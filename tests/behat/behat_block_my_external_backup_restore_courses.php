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
 * Behat custom steps and configuration for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @category  test
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat custom steps and configuration for block_my_external_backup_restore_courses.
 *
 * @package   block_my_external_backup_restore_courses
 * @author 2023 Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg {@link https://unistra.fr/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_my_external_backup_restore_courses extends behat_base {

    public const BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE = 'block_my_external_backup_restore_courses_ws';
    public const BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER = 'block_my_external_backup_restore_courses_user';

    /**
     * configure local server as external moodle to perform behat tests.
     *
     * @Given /^a myexternalbackuprestorecourses mock server is configured$/
     */
    public function mock_is_configured(): void {
        global $CFG;
        // Create role,user and token for webservice
        $token = self::install_webservice_moodle_server();
        set_config('external_moodles', $CFG->behat_wwwroot.",".$token, 'block_my_external_backup_restore_courses');
    }

    /**
     * add local server as external moodle to perform behat tests.
     *
     * @When /^a myexternalbackuprestorecourses fake mock server is added$/
     */
    public function mock_server_add(): void {
        global $CFG, $DB;
        // Create role,user and token for webservice
        $webservicemanager = new webservice();
        $webservice = $webservicemanager->get_external_service_by_shortname('wsblockmyexternalbakcuprestorecourses',
            MUST_EXIST);
        $wsuser = $DB->get_record('user', array('username' => self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER));
        $systemcontext = context_system::instance();
        $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $webservice->id, $wsuser->id, $systemcontext->id);
        $externalmoodles = get_config('block_my_external_backup_restore_courses', 'external_moodles');
        $externalmoodles = (empty($externalmoodles) ? '' : $externalmoodles.';').$CFG->wwwroot."/fake,".$token;
        set_config('external_moodles', $externalmoodles, 'block_my_external_backup_restore_courses');
    }

    public static function install_webservice_moodle_server() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/webservice/lib.php');
        $systemcontext = context_system::instance();
        $rolerecord = $DB->get_record('role', array('shortname' => self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE));
        $wsroleid = 0;
        if ($rolerecord) {
            $wsroleid = $rolerecord->id;
            cli_writeln('role '.self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE.' already exists, we\'ll use it');
        } else {
            $wsroleid = create_role(self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE,
                self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE,
                self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE);
        }
        assign_capability('block/my_external_backup_restore_courses:can_see_backup_courses', CAP_ALLOW,
            $wsroleid, $systemcontext->id, true);
        assign_capability('block/my_external_backup_restore_courses:can_retrieve_courses', CAP_ALLOW,
            $wsroleid, $systemcontext->id, true);
        assign_capability('webservice/rest:use', CAP_ALLOW,
            $wsroleid, $systemcontext->id, true);
        assign_capability('moodle/course:viewhiddencourses', CAP_ALLOW,
            $wsroleid, $systemcontext->id, true);
        assign_capability('moodle/category:viewcourselist', CAP_ALLOW,
            $wsroleid, $systemcontext->id, true);
        // Allow role assignmrnt on system.
        set_role_contextlevels($wsroleid, array(10 => 10));
        $wsuser = $DB->get_record('user', array('username' => self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER));
        if (!$wsuser) {
            $wsuser = create_user_record(self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER, generate_password(20));
            $wsuser->firstname = 'wsuser';
            $wsuser->lastname = self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER;
            $wsuser->email = 'ws_dtas'.$CFG->noreplyaddress;
            $wsuser->confirmed = 1;
            $DB->update_record('user', $wsuser);
        } else {
            cli_writeln('user '.self::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_DEFAULT_USER.'already exists, we\'ll use it');
        }
        role_assign($wsroleid, $wsuser->id, $systemcontext->id);
        $service = $DB->get_record('external_services', array('shortname' => 'wsblockmyexternalbakcuprestorecourses'));
        // Assign user to webservice.
        $webservicemanager = new webservice();
        $serviceuser = new stdClass();
        $serviceuser->externalserviceid = $service->id;
        $serviceuser->userid = $wsuser->id;
        $webservicemanager->add_ws_authorised_user($serviceuser);

        $params = array(
            'objectid' => $serviceuser->externalserviceid,
            'relateduserid' => $serviceuser->userid
        );
        $event = \core\event\webservice_service_user_added::create($params);
        $event->trigger();
        $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service->id, $wsuser->id, $systemcontext->id);
        return $token;
    }

    /**
     * configure local server as external moodle to perform behat tests.
     *
     * @Given /^I set the field "(?P<field_string>(?:[^"]|\\")*)" to last created course id$/
     * @param string $field
     * @return void
     */
    public function i_set_field_to_last_created_course_id($field) {
        global $DB;
        $courses = $DB->get_records('course', null, 'id DESC', 'id');
        $course = array_shift($courses);
        $this->execute('behat_forms::i_set_the_field_to', array($field,$course->id));


    }
}
