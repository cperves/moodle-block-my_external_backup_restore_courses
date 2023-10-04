<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2023 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;


require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/my_external_backup_restore_courses/admin/coursetoimportadmintable.php');
require_once($CFG->dirroot . '/blocks/my_external_backup_restore_courses/admin/adminlist_filter_form.php');
admin_externalpage_setup('my_external_backup_restore_courses_managment', '', array(), new moodle_url('/blocks/my_external_backup_restore_courses/admin//managment.php',array()));
$PAGE->navbar->add(get_string('adminpage', 'block_my_external_backup_restore_courses'));
$PAGE->requires->jquery();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('scheduledtasklist','block_my_external_backup_restore_courses'));

// Filters.
$courseoruserfilter = optional_param('courseoruserfilter', '', PARAM_TEXT);
$courseidfilter = optional_param('courseidfilter', '', PARAM_INT);
$useridfilter = optional_param('useridfilter', '', PARAM_INT);

$defaultcategoryid = get_config('block_my_external_backup_restore_courses','defaultcategory');
if(!isset($defaultcategoryid) || empty($defaultcategoryid) || !(is_numeric($defaultcategoryid))){
     throw new moodle_exception('malformed default category parameter for block my_external_backup_courses, please contact adlministrator');
}
$defaultcategory = $DB->get_record('course_categories', array('id'=>$defaultcategoryid));
if(!$defaultcategory){
     throw new moodle_exception('malformed default category parameter for block my_external_backup_courses, unexisting category, please contact adlministrator');
}
echo $OUTPUT->box_start('my_external_backup_restore_courses_admin_help');
echo html_writer::tag('span', get_string('externalmoodleadminhelpsection','block_my_external_backup_restore_courses'));
echo html_writer::empty_tag('br');
//writing default category informations
echo html_writer::tag('span', get_string('defaultcategoryx','block_my_external_backup_restore_courses', $defaultcategory));
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('my_external_backup_restore_course_refresh');
echo html_writer::link('#', html_writer::empty_tag('img',
    array('src' => $OUTPUT->image_url('a/refresh'),
        'alt' => get_string('refresh'),
        'title' => get_string('refresh'),
        'class' => 'iconsmall')
),
    array('onclick' => 'window.location=\''.$PAGE->url->out(false).'\';return false;')
);
echo $OUTPUT->box_end();

// Per Page treatment TODO.
$perpage = optional_param('perpage', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
if (!$perpage) {
     $perpage = get_user_preferences('block_my_external_backup_restore_courses_admin_perpage', 10);
} else {
     set_user_preference('block_my_external_backup_restore_courses_admin_perpage', $perpage);
}

// Filter form.
$adminlistfilter= new block\my_external_backup_restore_courses\admin\adminlist_filter_form();
$adminlistfilter->display();

// Action.
$submit = optional_param('submit', null, PARAM_TEXT);
$trigger = optional_param('trigger', 0, PARAM_INT);
if($submit && $trigger!=0){
     $internalcategory = required_param('internalcategory_'.$trigger, PARAM_INT); 
     $status = required_param('status_'.$trigger, PARAM_INT);;
     $scheduledtask = $DB->get_record('block_external_backuprestore', array('id'=>$trigger));
     if($scheduledtask != false && ($scheduledtask->internalcategory != $internalcategory || $scheduledtask->status != $status)){
          $scheduledtask->internalcategory = $internalcategory;
          $scheduledtask->status = $status;
          $scheduledtask->timemodified = time();
          $DB->update_record('block_external_backuprestore', $scheduledtask);
     }
}
$parameters = array(
    'courseoruserfilter' => $courseoruserfilter,
    'useridfilter' => $useridfilter,
    'courseidfilter' => $courseidfilter
);
$table = new block_my_external_backup_restore_course_admin_table($parameters,$perpage,$page);
$table->is_persistent(true);
echo $table->out($table->get_rows_per_page(), true);
echo $OUTPUT->footer();