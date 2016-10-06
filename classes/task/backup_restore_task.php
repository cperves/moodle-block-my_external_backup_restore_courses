<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2012 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
 */
namespace block_my_external_backup_restore_courses\task;


class backup_restore_task extends \core\task\scheduled_task {
	public function get_name() {
		// Shown in admin screens
		return get_string('my_external_backup_restore_courses_task', 'block_my_external_backup_restore_courses');
	}

	public function execute() {
		global $CFG;
		require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
		
		$errors = \block_my_external_backup_restore_courses_task_helper::run_automated_backup_restore();
	}
}
?>