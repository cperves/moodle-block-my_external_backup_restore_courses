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

// Redefine admin menu
$plugin = core_plugin_manager::instance()->get_plugin_info('block_my_external_backup_restore_courses');
$myexternalfolder = new admin_category('blockmyexternalbackuprestorecoursesfolder',
    new lang_string('pluginname', 'block_my_external_backup_restore_courses'),
    $plugin->is_enabled() === false);
$ADMIN->add('blocksettings', $myexternalfolder);
$settings->visiblename = new lang_string('settings', 'block_my_external_backup_restore_courses');
$ADMIN->add('blockmyexternalbackuprestorecoursesfolder', $settings);
if ($hassiteconfig) {
    // Admin page declaration.
    $ADMIN->add('blockmyexternalbackuprestorecoursesfolder',
        new admin_externalpage(
            'my_external_backup_restore_courses_managment',
            get_string('adminpage', 'block_my_external_backup_restore_courses'),
            "$CFG->wwwroot/blocks/my_external_backup_restore_courses/admin/managment.php",
            'moodle/site:config'));
    $ADMIN->add('blockmyexternalbackuprestorecoursesfolder',
        new admin_externalpage(
            'my_external_backup_restore_courses_restorecourseforuser',
            get_string('adminrestorecourseforuser', 'block_my_external_backup_restore_courses'),
            "$CFG->wwwroot/blocks/my_external_backup_restore_courses/admin/restorecourseforuser.php",
            'moodle/site:config'));

    // Plugin settings.
    $roles = $DB->get_records('role', array('archetype' => 'editingteacher'));
    $arrayofroles = array();
    $defaultrole = null;
    foreach ($roles as $role) {
        $arrayofroles[$role->id] = $role->shortname;
        if ($role->shortname == 'editingteacher') {
            $defaultrole = $role->id;
        }
    }

    $settings->add(new admin_setting_configtext("block_my_external_backup_restore_courses/search_roles",
        get_string("roles_included_in_external_courses_search", "block_my_external_backup_restore_courses"),
        get_string("roles_included_in_external_courses_search_Desc", "block_my_external_backup_restore_courses"),
        'editingteacher'
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
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/defaultcategorychecked',
        get_string('defaultcategorychecked', 'block_my_external_backup_restore_courses'),
        get_string('defaultcategorychecked_desc', 'block_my_external_backup_restore_courses'),
        1
    ));
    $settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable',
        get_string('categorytable', 'block_my_external_backup_restore_courses'),
        get_string('categorytable_desc', 'block_my_external_backup_restore_courses'),
        'course_categories'
    ));
    $settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable_foreignkey',
        get_string('categorytable_foreignkey', 'block_my_external_backup_restore_courses'),
        get_string('categorytable_foreignkey_desc', 'block_my_external_backup_restore_courses'),
        'id'
    ));
    $settings->add(new admin_setting_configtext('block_my_external_backup_restore_courses/categorytable_categoryfield',
        get_string('categorytable_categoryfield', 'block_my_external_backup_restore_courses'),
        get_string('categorytable_categoryfield_desc', 'block_my_external_backup_restore_courses'),
        'idnumber'
    ));
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/onlyoneremoteinstance',
        get_string('onlyoneremoteinstance', 'block_my_external_backup_restore_courses'),
        get_string('onlyoneremoteinstance_desc', 'block_my_external_backup_restore_courses'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/enrollbutton',
        get_string('enrollbutton', 'block_my_external_backup_restore_courses'),
        get_string('enrollbutton_desc', 'block_my_external_backup_restore_courses'),
        1
    ));
    $settings->add(new admin_setting_configselect('block_my_external_backup_restore_courses/enrollrole',
        get_string('enrollrole', 'block_my_external_backup_restore_courses'),
        get_string('enrollrole_desc', 'block_my_external_backup_restore_courses'),
        $defaultrole, $arrayofroles
    ));
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/includeexternalurlinmail',
        get_string('includeexternalurlinmail', 'block_my_external_backup_restore_courses'),
        get_string('includeexternalurlinmail_desc', 'block_my_external_backup_restore_courses'), 0));
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/warningstoowner',
        get_string('warningstoowner', 'block_my_external_backup_restore_courses'),
        get_string('warningstoowner_desc', 'block_my_external_backup_restore_courses'), 1));
    $settings->add(new admin_setting_configcheckbox('block_my_external_backup_restore_courses/checkrequestercapascoursecreate',
        get_string('checkrequestercapascoursecreate', 'block_my_external_backup_restore_courses'),
        get_string('checkrequestercapascoursecreate_desc', 'block_my_external_backup_restore_courses'), 1));
}

// Prevent Moodle from adding settings block in standard location.
$settings = null;