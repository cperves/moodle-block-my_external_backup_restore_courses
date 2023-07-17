<?php

/**
 * This file contains the definition for course table which subclassses easy_table
 *
 * @package   tool_my_external_bakcup_restore_courses
 * @copyright  2023 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/admin/restorecourseforuser_form.php');

admin_externalpage_setup('my_external_backup_restore_courses_restorecourseforuser', '', array(),
    new moodle_url('/blocks/my_external_backup_restore_courses/admin/restorecourseforuser.php',array()));


$restorecourseforuserform= new block\my_external_backup_restore_courses\admin\restorecourseforuser_form();
$PAGE->requires->js(new moodle_url('/blocks/my_external_backup_restore_courses/module.js'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('adminrestorecourseforuser','block_my_external_backup_restore_courses'));
if ($data = $restorecourseforuserform->get_data()) {
    $externalmoodles = block_my_external_backup_restore_courses_tools::get_external_moodles_url_token();
    // Check course exists and retrieve course name.
    $data->externalcoursename =
        block_my_external_backup_restore_courses_tools::external_backup_course_name(
            $data->externalmoodleurl,
            $data->externalcourseid);
    if (!empty($data->userid)) {
        $user = $DB->get_record('user', array('id' => $data->userid));
        if (!$user) {
            throw new moodle_exception("user $data->userid does not exists");
        }
    }
    $data->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
    $data->timecreated = time();
    $DB->insert_record('block_external_backuprestore', $data);
    echo $OUTPUT->box_start('my_external_backup_restore_courses_restorecourseforuser_success');
    echo html_writer::tag('span', get_string('my_external_backup_restore_courses_restorecourseforuser_success',
        'block_my_external_backup_restore_courses'), array('class' => 'table-success'));
    echo $OUTPUT->box_end();
}
$restorecourseforuserform->display();

echo $OUTPUT->footer();
