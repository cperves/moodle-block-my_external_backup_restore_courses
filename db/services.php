<?php
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

$functions = array(
	'block_my_external_backup_restore_courses_get_courses_zip' => array(
		'classname' => 'block_my_external_backup_restore_courses_external',
		'methodname' => 'get_courses_zip',
		'classpath' => 'blocks/my_external_backup_restore_courses/externallib.php',
		'description' => 'Get a zip of a given course for a given username',
		'type' => 'read',
		'capabilities' => 'block/my_external_backup_restore_courses:can_see_backup_courses',
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