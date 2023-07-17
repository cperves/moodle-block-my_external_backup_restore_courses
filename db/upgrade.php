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
 * block_my_external_backup_restore_courses db upgrade file
 *
 * @package
 * @subpackage
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
// Can't require_once backup_includes because it trigger a bug with variable block override by a deep require_once
// require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');

function xmldb_block_my_external_backup_restore_courses_upgrade($oldversion=0) {
    global $DB, $CFG;
    if ($oldversion < 2019052302) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_external_backuprestore');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            $dbman->add_field($table, $field);
            $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
            $dbman->add_key($table, $key);
        }
        upgrade_block_savepoint(true, 2019052302, 'my_external_backup_restore_courses');
    }
    if($oldversion < 2021071900){
        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_external_backuprestore');
        $field = new xmldb_field('externalmoodletoken');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021071900, 'my_external_backup_restore_courses');


        
    }
    $newversion = 2023020100;
    if($oldversion < 2023020100){
        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_external_backuprestore');
        $field = new xmldb_field('withuserdatas', XMLDB_TYPE_INTEGER,'1',
            null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, $newversion, 'my_external_backup_restore_courses');
    }

    $newversion = 2023021604;
    if ($oldversion < $newversion) {
        require_once($CFG->dirroot . '/webservice/lib.php');
        require_once($CFG->dirroot . '/blocks/my_external_backup_restore_courses/locallib.php');
        $webservicemanager = new webservice();
        $webservice = $webservicemanager->get_external_service_by_shortname('wsblockmyexternalbakcuprestorecourses',
            MUST_EXIST);
        if (!$webservicemanager->service_function_exists('core_course_get_courses_by_field', $webservice->id)) {
            $webservicemanager->add_external_function_to_service(
                'core_course_get_courses_by_field',
                $webservice->id);
        }
        $wsrole = $DB->get_record('role',
            array('shortname' => block_my_external_backup_restore_courses_tools::BLOCK_MY_EXTERNAL_BACKUP_RESTORE_COURSES_ROLE));
        if ($wsrole) {
            $systemcontext = context_system::instance();
            assign_capability('moodle/course:viewhiddencourses', CAP_ALLOW,
                $wsrole->id, $systemcontext->id, true);
            assign_capability('moodle/category:viewcourselist', CAP_ALLOW,
                $wsrole->id, $systemcontext->id, true);
        }
        upgrade_block_savepoint(true, $newversion, 'my_external_backup_restore_courses');
    }
    $newversion = 2023061600;
    if($oldversion < $newversion) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_external_backuprestore');
        $field = new xmldb_field('enrolmentmode', XMLDB_TYPE_INTEGER, '1',
            null, XMLDB_NOTNULL, null, 2); // can't use backup::ENROL_ALWAYS because of require_once trouble
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2023061600, 'my_external_backup_restore_courses');
    }
        return true;
}
