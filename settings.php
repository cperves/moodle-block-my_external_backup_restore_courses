<?php
/**
 * settings
 *
 * @package  
 * @subpackage 
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
global $DB;

if ($hassiteconfig) {
	$roles = $DB->get_records('role', array('archetype'=>'editingteacher'));
	$arrayofroles = array();
	$defaultarrayofroles = array();
	
	foreach($roles as $role) {
		$arrayofroles[$role->id] = $role->shortname;
		if ($role->shortname=='editingteacher') {
			$defaultarrayofroles[$role->id] = $role->shortname;
		}
	}
	
	$settings->add(new admin_setting_configtext("block_my_external_backup_restore_courses/search_roles", 
		get_string("roles_included_in_external_courses_search", "block_my_external_backup_restore_courses"), 
		get_string("roles_included_in_external_courses_search_Desc", "block_my_external_backup_restore_courses"),'editingteacher' 
		));
	$settings->add(new admin_setting_configtextarea("block_my_external_backup_restore_courses/external_moodles",
		get_string('external_moodle', 'block_my_external_backup_restore_courses'), 
		get_string('external_moodleDesc', 'block_my_external_backup_restore_courses'), '' 
	));
	$settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/defaultcategory',
			get_string('defaultcategory', 'block_my_external_backup_restore_courses'),
			get_string('defaultcategory_desc', 'block_my_external_backup_restore_courses'),
			''
	));
	$settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/restorecourseinoriginalcategory',
			get_string('restorecourseinoriginalcategory', 'block_my_external_backup_restore_courses'),
			get_string('restorecourseinoriginalcategory_desc', 'block_my_external_backup_restore_courses'),
			0
	));
	$settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable',
			get_string('categorytable', 'block_my_external_backup_restore_courses'),
			get_string('categorytable_desc', 'block_my_external_backup_restore_courses'),
			''
	));
	$settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable_foreignkey',
			get_string('categorytable_foreignkey', 'block_my_external_backup_restore_courses'),
			get_string('categorytable_foreignkey_desc', 'block_my_external_backup_restore_courses'),
			''
	));
	$settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable_categoryfield',
			get_string('categorytable_categoryfield', 'block_my_external_backup_restore_courses'),
			get_string('categorytable_categoryfield_desc', 'block_my_external_backup_restore_courses'),
			''
	));
	$settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/includeexternalurlinmail', get_string('includeexternalurlinmail','block_my_external_backup_restore_courses'), get_string('includeexternalurlinmail_desc','block_my_external_backup_restore_courses'), 0));
	$settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/warningstoowner', get_string('warningstoowner','block_my_external_backup_restore_courses'), get_string('warningstoowner_desc','block_my_external_backup_restore_courses'), 1));
	$settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/timelimitedmod', get_string('timelimitedmod','block_my_external_backup_restore_courses'), get_string('timelimitedmod_desc','block_my_external_backup_restore_courses'), 1));
	$settings->add(new admin_setting_configtime('block_my_external_backup_restore_courses/limitstart_hour', 'limitstart_minute', new lang_string('limitstart','block_my_external_backup_restore_courses'),
			new lang_string('limitstart_desc','block_my_external_backup_restore_courses'), array('h' => 20, 'm' => 0)));
	$settings->add(new admin_setting_configtime('block_my_external_backup_restore_courses/limitend_hour', 'limitend_minute', new lang_string('limitend','block_my_external_backup_restore_courses'),
			new lang_string('limitend_desc','block_my_external_backup_restore_courses'), array('h' => 6, 'm' => 0)));
	
}
