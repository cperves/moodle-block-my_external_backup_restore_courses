<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$tasks = array(
		array(
				'classname' => 'block_my_external_backup_restore_courses\task\backup_restore_task',
				'blocking' => 0,
				'minute' => '*',
				'hour' => '18',
				'day' => '*',
				'dayofweek' => '*',
				'month' => '*'
		)
);