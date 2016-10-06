<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2013 unistra  {@link http://unistra.fr}
 * @author Thierry Schlecht <thierry.schlecht@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_my_external_backup_restore_courses extends block_list {
	function init() {
		$this->title = get_string('pluginname', 'block_my_external_backup_restore_courses');
	}
	
	function has_config() {
		return true;
	}
	
	function get_content() {
		global $USER, $CFG, $PAGE;
		if (!has_capability('block/my_external_backup_restore_courses:view', $PAGE->context)) {
			return;
		}
		
		require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
		
		if ($this->content !== NULL) {
			return $this->content;
		}
		$this->content =  new stdClass;
		$this->content->items[] = (isguestuser($USER->id) or empty($USER->id) or $USER->id ==0) ? '' : block_my_external_backup_restore_courses_tools::print_content();
		$this->content->icons[] = '';
		$this->content->footer = '';
		return $this->content;
	}
	
	function applicable_formats() {
		return array('all'=>true,'course-view'=>false,'mod'=>false,'site'=>true ,'my' => true,'tag'=>false);
	}
	
}