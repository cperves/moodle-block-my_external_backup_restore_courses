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
 * webservices declaration
 *
 * @package
 * @subpackage
 * @copyright  2014 unistra  {@link http://unistra.fr}
 * @author Thierry Schlecht <thierry.schlecht@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_my_external_backup_restore_courses_get_courses_zip' => array(
        'classname' => 'block_my_external_backup_restore_courses_external',
        'methodname' => 'get_courses_zip',
        'classpath' => 'blocks/my_external_backup_restore_courses/externallib.php',
        'description' => 'Get a zip of a given course for a given username',
        'type' => 'read',
        'capabilities' => 'block/my_external_backup_restore_courses:can_retrieve_courses',
    ),
    'block_my_external_backup_restore_courses_get_courses' => array(
        'classname' => 'block_my_external_backup_restore_courses_external',
        'methodname' => 'get_courses',
        'classpath' => 'blocks/my_external_backup_restore_courses/externallib.php',
        'description' => 'Get the list of courses for a given username',
        'type' => 'read',
        'capabilities' => 'block/my_external_backup_restore_courses:can_see_backup_courses',
    ),
);

$services = array(
    'Block my external backup restore courses web services' => array(
        'functions' => array (  'block_my_external_backup_restore_courses_get_courses_zip',
            'block_my_external_backup_restore_courses_get_courses',
            'core_webservice_get_site_info',
            'core_course_get_courses_by_field'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'wsblockmyexternalbakcuprestorecourses',
        'downloadfiles' => 1
    )
);