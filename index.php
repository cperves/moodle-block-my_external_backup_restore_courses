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
 * block my_external_backup_restore_courses index page
 *
 * @package
 * @subpackage
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');

require_login();
$urltokenchecker = $CFG->wwwroot.'/blocks/my_external_backup_restore_courses/ajax/token_checker.php';

$context = context_system::instance();
require_capability('block/my_external_backup_restore_courses:view', $context);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/my_external_backup_restore_courses/index.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('externalmoodlecourselist',
    'block_my_external_backup_restore_courses'));
$PAGE->set_heading(get_string('externalmoodlecourselist', 'block_my_external_backup_restore_courses'));

echo $OUTPUT->header();
echo $OUTPUT->box_start('my_external_backup_restore_course_refresh');
echo html_writer::link('#', html_writer::empty_tag('img',
        array('src' => $OUTPUT->image_url('a/refresh'),
            'alt' => get_string('refresh'),
            'class' => 'iconsmall')
        ),
        array('onclick' => 'window.location=\''.$PAGE->url->out(false).'\';return false;')
    );
echo $OUTPUT->box_end();
echo $OUTPUT->box_start('my_external_backup_restore_course_help');
echo html_writer::tag('span', get_string('externalmoodlehelpsection', 'block_my_external_backup_restore_courses'));
echo $OUTPUT->box_end();
$systemcontext = context_system::instance();
// External cateory parameters.
$config = get_config('block_my_external_backup_restore_courses');
$role = $DB->get_record('role', array('id' => $config->enrollrole));
if (!$role) {
    throw new moodle_exception(get_string('cantenrollocourserolex',
            'block_my_external_backup_restore_courses',
            $config->enrollrole
    ));
}

$restorecourseinoriginalcategory = $config->restorecourseinoriginalcategory;
$categorytable = $config->categorytable;
$categorytableforeignkey = $config->categorytable_foreignkey;
$categorytablecategoryfield = $config->categorytable_categoryfield;
$defaultcategoryid = $config->defaultcategory;
$externalmoodlescfg = $config->external_moodles;
$onlyoneremoteinstance = boolval($config->onlyoneremoteinstance);
// Formatted : domainname1,token1;domainname2;token2;...
$wsparams = array('username' => $USER->username,
        'concernedroles' => implode(",", block_my_external_backup_restore_courses_tools::get_concerned_roles_shortname()));
// Check if plugin is correcly configured.
if (empty($defaultcategoryid) ||
    ($restorecourseinoriginalcategory == 1
        && (empty($categorytable) || empty($categorytableforeignkey) || empty($categorytableforeignkey)
        )
    )
) {
    throw new moodle_exception(get_string('misconfigured_plugin', 'block_my_external_backup_restore_courses'));
}

// Forms paramteters.
$submit = optional_param('submit', null, PARAM_TEXT);
$enrolltocourse = optional_param('enrolltocourse', null, PARAM_TEXT);
$enrolltocourseid = optional_param('enrolltocourseid', null, PARAM_INT);
$selectedcourses = optional_param_array('selectedcourses', null, PARAM_RAW);
$externalmoodleurl = optional_param('externalmoodleurl', null, PARAM_URL);
$allcourses  = optional_param_array('allcourses', null, PARAM_RAW);
// Error message.
$errormsg = '';
if ($submit) {
    if (isset($allcourses)) {
        if (isset($selectedcourses)) {
            foreach ($selectedcourses as $selectedcourse) {
                // Check if already exists in db.
                $dbinfo = $DB->get_record('block_external_backuprestore',
                    array('userid' => $USER->id, 'externalcourseid' => $selectedcourse, 'externalmoodleurl' => $externalmoodleurl));
                if (!$dbinfo) {
                    $datas = new stdClass();
                    $datas->userid = $USER->id;
                    $datas->externalcourseid = $selectedcourse;
                    $datas->externalmoodleurl = $externalmoodleurl;
                    $datas->internalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
                    $datas->withuserdatas = optional_param('withuserdatas_'.$selectedcourse, 0, PARAM_INT);
                    $datas->externalcoursename = optional_param('coursename_'.$selectedcourse, '', PARAM_TEXT);
                    $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
                    $datas->timecreated = time();
                    $datas->id = $DB->insert_record('block_external_backuprestore', $datas);
                } else {
                    // Update.
                    // Only in case of !$onlyoneremoteinstance from performed status to scheduled or if changing category.
                    $datas = $dbinfo;
                    $neworiginalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
                    $newwithuserdatas = optional_param('withuserdatas_'.$selectedcourse, 0, PARAM_INT);
                    $currentoriginalstatus = $datas->status;

                    // Change status only of !onlyremoteinstance && status from performed to scheduled.
                    if ($datas->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                            && !$onlyoneremoteinstance) {
                        $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
                    }
                    // Update only ifcategory changed and status scheduled ...
                    // Or status changed from performed to scheduled and !onlyoneremoteinstance.
                    if ($datas->internalcategory != $neworiginalcategory
                            && $currentoriginalstatus == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                        || (
                            $currentoriginalstatus == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                            && $datas->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                            && !$onlyoneremoteinstance
                        )
                        || ($newwithuserdatas != $datas->withuserdatas
                            && $currentoriginalstatus == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED)
                    ) {
                        $datas->timemodified = time();
                        $datas->internalcategory = $neworiginalcategory;
                        $DB->update_record('block_external_backuprestore', $datas);
                    }
                }

            }
        }
        // Unselect.
        foreach ($allcourses as $currentcourse) {

            if ((isset($selectedcourses) && !in_array($currentcourse, $selectedcourses)) || !isset($selectedcourses)) {
                $dbinfo = $DB->get_record('block_external_backuprestore',
                    array('userid' => $USER->id, 'externalcourseid' => $currentcourse, 'externalmoodleurl' => $externalmoodleurl));
                if ($dbinfo) {
                    if ($dbinfo->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED) {
                        // Remove course entry.
                        $DB->delete_records('block_external_backuprestore',
                            array('userid' => $USER->id, 'externalcourseid' => $currentcourse,
                                'externalmoodleurl' => $externalmoodleurl));
                    }
                }
            }
        }
    }
}
if ($config->enrollbutton && $enrolltocourse) {
    $coursetoenroll = $DB->get_record('course', array('id' => $enrolltocourseid));
    if (!$coursetoenroll) {
        $errormsg .= get_string('coursenotfound', 'block_my_external_backup_restore_courses');
    }
    $enrolinstances = enrol_get_instances($enrolltocourseid, false);
    // Check there's a manual instance.
    $hasmanualenrol = false;
    $manualenrolinstance = null;
    foreach ($enrolinstances as $key => $enrolinstance) {
        if ($enrolinstance->enrol == 'manual') {
            $hasmanualenrol = true;
            $manualenrolinstance = $enrolinstance;
            break;
        }
    }
    if (!$hasmanualenrol) {
        $errormsg .= get_string('nomanualenrol', 'block_my_external_backup_restore_courses');
    }
    if (empty($errormsg)) {
        $enrolmanualplugin = enrol_get_plugin('manual');
        $enrolmanualplugin->enrol_user($enrolinstance, $USER->id, $role->id);
    }

}
if (!empty($errormsg)) {
    echo html_writer::start_div('error');
    echo $errormsg;
    echo html_writer::end_div();
}

if ($externalmoodlescfg && !empty($externalmoodlescfg)) {
    // Scheduled task informations.
    $scheduledtask = core\task\manager::get_scheduled_task('block_my_external_backup_restore_courses\task\backup_restore_task');
    $nextrun = $scheduledtask->get_next_run_time();
    $scheduledtasknextrun = $nextrun == 0 ? get_string('asap', 'tool_task') : userdate($nextrun);
    // Extract key/value.
    $externalmoodles = explode(';', $externalmoodlescfg);
    $nbropenedexternalmoodles = 0;
    foreach ($externalmoodles as $keyvalue) {
        if (!empty($keyvalue)) {
            $keyvalue = explode(',', $keyvalue);
            $domainname = $keyvalue[0];
            $validusername = true;
            $courses = array();
            try {
                $sitename = block_my_external_backup_restore_courses_tools::external_backup_course_sitename($domainname);
                try {
                    $courses = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($domainname,
                        'block_my_external_backup_restore_courses_get_courses',
                        $wsparams);
                } catch (block_my_external_backup_restore_courses_invalid_username_exception $uex) {
                    $validusername = false;
                }
                $nbropenedexternalmoodles += 1;
                echo html_writer::start_tag('div', array('class' => 'mform my_external_backup_course_form'));
                echo html_writer::start_tag('fieldset');
                echo html_writer::tag('legend', $sitename);
                if ($validusername && count($courses) == 0) {
                    echo $OUTPUT->box_start('external_backup_courses_item');
                    echo html_writer::tag('div', get_string('nocourses'), array('class' => 'external_backup_course_name'));
                    echo $OUTPUT->box_end();
                }
                if (!$validusername) {
                    echo $OUTPUT->box_start('external_backup_courses_item');
                    echo html_writer::tag('div',
                        get_string('invalidusername',
                            'block_my_external_backup_restore_courses'),
                        array('class' => 'external_backup_course_name')
                    );
                    echo $OUTPUT->box_end();
                }
                if ($courses) {
                    echo $OUTPUT->box_start('external_backup_restore_courses_item');
                    // Preparing form.
                    $target = new moodle_url('/blocks/my_external_backup_restore_courses/index.php');
                    $attributes = array('method' => 'POST', 'action' => $target);
                    $disabled = empty($options['previewonly']) ? array() : array('disabled' => 'disabled');

                    echo html_writer::start_tag('form', $attributes);
                    echo html_writer::empty_tag('input',
                        array('type' => 'hidden', 'name' => 'externalmoodleurl', 'value' => $domainname));
                    if ($config->enrollbutton) {
                        echo html_writer::empty_tag('input',
                                array('type' => 'hidden', 'id' => 'enrolltocourseid', 'name' => 'enrolltocourseid', 'value' => ''));
                    }
                    $coursetable = new html_table();
                    $coursetable->attributes['class'] = 'admintable generaltable';
                    $coursetable->head = array(
                        get_string('course'),
                        get_string('choose_to_restore',
                            'block_my_external_backup_restore_courses'));
                    $coursetable->head[] = get_string('keepcategory', 'block_my_external_backup_restore_courses');
                    if (has_capability('block/my_external_backup_restore_courses:can_restore_user_datas',$systemcontext)) {
                        $coursetable->head[] = get_string('withuserdatasheadtable', 'block_my_external_backup_restore_courses');
                    }
                    $coursetable->head[] = get_string('status');
                    $coursetable->head[] = get_string('nextruntime', 'block_my_external_backup_restore_courses');
                    if ($onlyoneremoteinstance) {
                        $coursetable->head[] = get_string('executiontimemixed', 'block_my_external_backup_restore_courses');
                    } else {
                        $coursetable->head[] = get_string('executiontimeyourself', 'block_my_external_backup_restore_courses');
                        $coursetable->head[] = get_string('executiontimebyothers', 'block_my_external_backup_restore_courses');
                    }
                    if ($config->enrollbutton) {
                        $coursetable->head[] = get_string('enrollbuttonlabel', 'block_my_external_backup_restore_courses');
                    }
                }
                foreach ($courses as $course) {
                    if (isset($course->fullname)) {
                        // Scheduled info stored in bd.
                        $scheduledinfo = $DB->get_record('block_external_backuprestore',
                            array('userid' => $USER->id, 'externalcourseid' => $course->id, 'externalmoodleurl' => $domainname));

                        $scheduledinfobyotherusersinfos =
                                block_my_external_backup_restore_courses_tools::external_course_restored_or_on_way_by_other_users(
                                        $course->id, $domainname, $USER->id
                                );
                        $firstscheduledinfobyotherusersinfos = false;
                        if ($scheduledinfobyotherusersinfos && count($scheduledinfobyotherusersinfos) > 0 ) {
                            $firstscheduledinfobyotherusersinfos = current($scheduledinfobyotherusersinfos);
                            if ($firstscheduledinfobyotherusersinfos->userid == 0) {
                                $firstscheduledinfobyotherusersinfos->username = 'internal_moodle';
                                $firstscheduledinfobyotherusersinfos->firstname = '';
                                $firstscheduledinfobyotherusersinfos->lastname = 'internal moodle administrator';
                            }
                        }
                        $scheduledinfobyotherusers = !empty($scheduledinfobyotherusersinfos);
                                // Original category informations.
                        $originalcategory = false;
                        if (
                            $restorecourseinoriginalcategory == 1
                            && $course->categoryidentifier != null
                            && !empty($categorytable)
                            && !empty($categorytablecategoryfield)
                            && !empty($categorytableforeignkey)
                        ) {
                            $originalcategory = $DB->get_record_sql(
                                "select cat.* from {".$categorytable."} ct inner join {course_categories} cat on cat.id=ct
                                .$categorytableforeignkey where ct.$categorytablecategoryfield=:categoryfield",
                                array('categoryfield' => $course->categoryidentifier)
                            );

                        }
                        echo html_writer::empty_tag('input',
                            array('type' => 'hidden', 'name' => 'allcourses[]', 'value' => $course->id));
                        echo html_writer::empty_tag('input',
                            array('type' => 'hidden', 'name' => 'coursename_'.$course->id, 'value' => $course->fullname));
                        $externalcourseurl = new moodle_url($domainname.'/course/view.php', array('id' => $course->id));
                        // Preparing html table.
                        $tablerow = new html_table_row();
                        $coursetablecell = new html_table_cell();
                        $coursetablecell->text = '';
                        if (has_capability('block/my_external_backup_restore_courses:can_see_external_course_link',
                            $context)
                        ) {
                            $coursetablecell->text .= html_writer::start_tag('a',
                                    array('href' => $externalcourseurl->out(),
                                            'target' => '_blank')
                            );
                        }
                        $coursetablecell->text .= get_string('courselabel',
                                'block_my_external_backup_restore_courses',
                                $course
                        );
                        if (has_capability('block/my_external_backup_restore_courses:can_see_external_course_link',
                                $context)
                        ) {
                            $coursetablecell->text .= html_writer::end_tag('a');
                        }
                        $selecttablecell = new html_table_cell();
                        $attr = array();
                        $selecttablecell->text = '';
                        $disabledline = false;
                        if (
                            ($onlyoneremoteinstance &&
                                    (
                                        $scheduledinfobyotherusers
                                        ||
                                        (
                                            $scheduledinfo
                                            && $scheduledinfo->status !=
                                                block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                                        )
                                    )
                            )
                            ||
                            (!$onlyoneremoteinstance
                                && (
                                        (
                                            $scheduledinfo &&
                                            ($scheduledinfo->status ==
                                                    block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                                                ||
                                                $scheduledinfo->status ==
                                                block_my_external_backup_restore_courses_tools::STATUS_ERROR
                                            )
                                        )
                                        /*
                                         * if other user why not lauching an other one
                                        ||
                                        (
                                            $scheduledinfobyotherusers &&
                                            block_my_external_backup_restore_courses_tools::array_contains_object_with_properties(
                                                    $scheduledinfobyotherusersinfos,
                                                    'status',
                                                    array(block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED ,
                                                            block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS,
                                                            block_my_external_backup_restore_courses_tools::STATUS_ERROR
                                                    )
                                            )
                                        )
                                        */
                                    )
                            )
                        ) {
                            $disabledline = true;
                            $attr['disabled'] = 'true';
                            // Input same hidden field to post selected value if selected.
                            if ($scheduledinfo
                                && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                                && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED) {
                                $selecttablecell->text .= html_writer::empty_tag('input',
                                    array('type' => 'hidden', 'name' => 'selectedcourses[]', 'value' => $course->id));
                            }
                        }
                        $selecttablecell->text .= html_writer::checkbox('selectedcourses[]', $course->id,
                            $scheduledinfo && $scheduledinfo->status !=
                            block_my_external_backup_restore_courses_tools::STATUS_PERFORMED ? true : false,
                            '', $attr);
                        $categorytablecell = new html_table_cell();
                        $categorytablecell->text = '';
                        $attr = array();
                        // TODO !onlyremoteinstance implementation.
                        $defaultcategorychecked = $config->defaultcategorychecked;
                        $categorychecked = $scheduledinfo ? ($scheduledinfo->internalcategory != 0 ? true : false) :
                                ($scheduledinfobyotherusers ?
                                        (property_exists($firstscheduledinfobyotherusersinfos,'internalcategory')
                                          && $firstscheduledinfobyotherusersinfos->internalcategory != 0 ? true : false)
                                        : ($originalcategory ? $defaultcategorychecked : false)
                                );
                        $withuserdataschecked = ($scheduledinfo ? $scheduledinfo->withuserdatas :
                            ($scheduledinfobyotherusers ?
                                (property_exists($firstscheduledinfobyotherusersinfos,'withuserdatas')
                                && $firstscheduledinfobyotherusersinfos->withuserdatas != 0 ? true : false)
                                : false)
                        );
                        if (!$originalcategory
                            || (
                                $scheduledinfo && $scheduledinfo->status
                                != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                            )
                        ) {
                            if($disabledline) {
                                $attr['disabled'] = 'true';
                            }
                            if ($originalcategory && $categorychecked) {
                                // Add input hidden since checkbox is disabled.
                                $categorytablecell->text .= html_writer::empty_tag('input',
                                        array('type' => 'hidden',
                                                'name' => 'originalcategory_'.$course->id,
                                                'value' => $course->id
                                        )
                                );
                            }
                        }
                        if(!$originalcategory) {
                            $attr['disabled'] = 'true';
                        }
                        $categorytablecell->text .= html_writer::checkbox('originalcategory_'.$course->id,
                            $originalcategory ? $originalcategory->id : 0,
                            $categorychecked,
                            $originalcategory ? $originalcategory->name : '', $attr);
                        if (has_capability('block/my_external_backup_restore_courses:can_restore_user_datas',
                            $systemcontext)) {
                            $withuserdatastablecell = new html_table_cell();
                            $withuserdatastablecell->text .= html_writer::checkbox('withuserdatas_'.$course->id,
                                1,
                                $withuserdataschecked,
                                '');
                        }
                        $statetablecell = new html_table_cell();
                        $statetablecell->attributes['class'] = 'wrap';
                        $nextruntimetablecell = new html_table_cell();
                        $nextruntimetablecell->attributes['class'] = 'wrap';
                        $executiontimetablecell = new html_table_cell();
                        $executiontimetablecell->attributes['class'] = 'wrap';
                        $executiontimebyotherstablecell = new html_table_cell();
                        $executiontimebyotherstablecell->attributes['class'] = 'wrap';
                        $enrollbuttontablecell = new html_table_cell();
                        $enrollbuttontablecell->attributes['class'] = 'wrap';
                        $nextruntime = '';
                        $executiontime = '';
                        $status = '';
                        $enroltocoursearray = array();
                        if ($scheduledinfobyotherusers) {
                            // Other schedule.
                            $index = 0;
                            $executiontimebyothers = '';
                            $executiontimebyotherstablecell->text = '';
                            $hasonestatusperformed = false;
                            foreach ($scheduledinfobyotherusersinfos as $scheduledinfobyotheruserinfo) {
                                $buttonclass = '';
                                switch($scheduledinfobyotheruserinfo->status) {
                                    case block_my_external_backup_restore_courses_tools::STATUS_ERROR:
                                        $buttonclass = 'tag tag-danger';;
                                        break;
                                    case block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS:
                                        $buttonclass = 'tag tag-warning';
                                        break;
                                    case block_my_external_backup_restore_courses_tools::STATUS_PERFORMED:
                                        $buttonclass = 'tag tag-success';
                                        break;
                                    case block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED:
                                        $buttonclass = 'tag tag-info';
                                        break;
                                    default:
                                        $buttonclass = '';
                                        break;
                                }
                                if (!is_null($scheduledinfobyotheruserinfo->courseid) &&
                                        ($scheduledinfobyotheruserinfo->status ==
                                                block_my_external_backup_restore_courses_tools::STATUS_PERFORMED)
                                ) {
                                    $courseurl = new moodle_url('/course/view.php',
                                            array('id' => $scheduledinfobyotheruserinfo->courseid)
                                    );
                                    $status = (empty($status) ? '' : '<br>').
                                            html_writer::start_tag('a',
                                                    array('href' => $courseurl->out(), 'class' => $buttonclass))
                                                        .get_string('status_'.$scheduledinfobyotheruserinfo->status,
                                                    'block_my_external_backup_restore_courses')
                                                        .html_writer::end_tag('a');
                                } else {
                                    $status .= (empty($status) ? '' : '<br>')
                                            .html_writer::start_tag('span', array('class' => $buttonclass))
                                            .get_string('status_' . $scheduledinfobyotheruserinfo->status . '_byuser',
                                                    'block_my_external_backup_restore_courses',
                                                    $scheduledinfobyotheruserinfo)
                                            .html_writer::end_tag('span');
                                }
                                $executiontimeinfo = new stdClass();
                                $executiontimeinfo->firstname = $scheduledinfobyotheruserinfo->firstname;
                                $executiontimeinfo->lastname = $scheduledinfobyotheruserinfo->lastname;
                                if ($scheduledinfobyotheruserinfo->status ==
                                        block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED) {
                                    $executiontimeinfo->executiontime = $scheduledtasknextrun;
                                    $nextruntime .= $scheduledtasknextrun;
                                    $nextruntime = (empty($nextruntime) ? '' : '<br>')
                                            .get_string('executioninformationbyuser',
                                                    'block_my_external_backup_restore_courses',
                                                    $executiontimeinfo);
                                } else if ($scheduledinfobyotheruserinfo->status ==
                                        block_my_external_backup_restore_courses_tools::STATUS_ERROR
                                        || $scheduledinfobyotheruserinfo->status ==
                                            block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                                        || $scheduledinfobyotheruserinfo->status ==
                                            block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                                ) {
                                    $executiontimeinfo->executiontime =
                                            userdate($scheduledinfobyotheruserinfo->timescheduleprocessed);
                                    $executiontime .= (empty($executiontime) ? '' : '<br>')
                                            .get_string('executioninformationbyuser',
                                                    'block_my_external_backup_restore_courses',
                                                    $executiontimeinfo);
                                    if ($scheduledinfobyotheruserinfo->status ==
                                            block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                                            && $config->enrollbutton
                                    ) {
                                        // Exclude without courseid.
                                        if (!is_null($scheduledinfobyotheruserinfo->courseid)) {
                                            $obj = new stdClass();
                                            $obj->courseid = $scheduledinfobyotheruserinfo->courseid;
                                            $obj->firstname = $scheduledinfobyotheruserinfo->firstname;
                                            $obj->lastname = $scheduledinfobyotheruserinfo->lastname;
                                            $enroltocoursearray[$obj->courseid] = $obj;
                                        }
                                    }
                                }
                                $executiontimebyothers .= ($index > 0 ? '<br>' : '') .
                                        get_string('executioninformationbyuser',
                                                'block_my_external_backup_restore_courses',
                                                $executiontimeinfo);
                            }


                            if (!$onlyoneremoteinstance) {
                                $executiontimetablecell->text = (
                                        empty($executiontimetablecell->text) ? '' : '<br>')
                                        .$executiontimebyothers;

                            } else {
                                $executiontimebyotherstablecell->text = $executiontimebyothers;
                            }



                        }

                        if ($config->enrollbutton && count($enroltocoursearray) > 0) {
                            $index = 0;
                            foreach ($enroltocoursearray as $courseid => $enroltocourseelt) {
                                // Check if current user is already enrolled in course with enrollrole role.
                                $alreadyenrolled =
                                        block_my_external_backup_restore_courses_tools::enrol_get_courses_with_role($courseid,
                                                $USER->id,
                                                $role->id);
                                if ($alreadyenrolled) {
                                    $enrollbuttontablecell->text .= ($index == 0 ? '' : '<br>');
                                    $enrollbuttontablecell->text .=
                                            get_string('alreadyenrolledincoursexuserx',
                                                    'block_my_external_backup_restore_courses',
                                                    $enroltocourseelt);

                                } else {
                                    $enrollbuttontablecell->text .= ($index == 0 ? '' : '<br>');
                                    $enrollbuttontablecell->text .= html_writer::tag('input', null,
                                            array('type' => 'submit', 'name' => 'enrolltocourse',
                                                    'class' => 'enrolltocoursebutton btn btn-primary',
                                                    'onclick' => 'document.getElementById("enrolltocourseid").value =' .
                                                            $courseid . '; return true;',
                                                    'value' => (count($enroltocoursearray) > 0 ?
                                                            get_string('enrollbuttonlabelcoursexuserx',
                                                                    'block_my_external_backup_restore_courses',
                                                                    $enroltocourseelt) : get_string('enrollbuttonlabel',
                                                                    'block_my_external_backup_restore_courses'))));
                                }
                                $index++;
                            }
                        }

                        if ($scheduledinfo) {
                            switch($scheduledinfo->status) {
                                case block_my_external_backup_restore_courses_tools::STATUS_ERROR:
                                    $buttonclass = 'tag tag-danger';;
                                    break;
                                case block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS:
                                    $buttonclass = 'tag tag-warning';
                                    break;
                                case block_my_external_backup_restore_courses_tools::STATUS_PERFORMED:
                                    $buttonclass = 'tag tag-success';
                                    break;
                                case block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED:
                                    $buttonclass = 'tag tag-info';
                                    break;
                                default:
                                    $buttonclass = '';
                                    break;
                            }
                            if (!is_null($scheduledinfo->courseid) && $scheduledinfo->status ==
                                    block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                            ) {
                                $courseurl = new moodle_url('/course/view.php', array('id' => $scheduledinfo->courseid));
                                $status = html_writer::start_tag('a',
                                                array('href' => $courseurl->out(),
                                                        'class' => $buttonclass)
                                        ).get_string('status_'.$scheduledinfo->status,
                                                'block_my_external_backup_restore_courses')
                                        .html_writer::end_tag('a');
                            } else {
                                $status = html_writer::start_tag('span', array('class' => $buttonclass))
                                            .get_string('status_'.$scheduledinfo->status,
                                               'block_my_external_backup_restore_courses')
                                            .html_writer::end_tag('span');
                            }
                            $executiontimeinfo = new stdClass();
                            if ($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED) {
                                $executiontimeinfo->executiontime = $scheduledtasknextrun;
                                $nextruntime = get_string('executioninformationyourself',
                                        'block_my_external_backup_restore_courses',
                                        $executiontimeinfo);
                            } else if ($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_ERROR
                                    || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                                    || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS
                            ) {
                                $executiontimeinfo->executiontime = userdate($scheduledinfo->timescheduleprocessed);
                                $executiontime = get_string('executioninformationyourself',
                                        'block_my_external_backup_restore_courses',
                                        $executiontimeinfo);
                            }
                        }

                        $statetablecell->text = $status;
                        $executiontimetablecell->text = $executiontime;
                        $nextruntimetablecell->text = $nextruntime;
                        $tablecells = array(
                                $coursetablecell,
                                $selecttablecell,
                                $categorytablecell);
                        if (has_capability('block/my_external_backup_restore_courses:can_restore_user_datas',
                            $systemcontext)) {
                            array_push($tablecells, $withuserdatastablecell);
                        }
                        array_push($tablecells, $statetablecell, $nextruntimetablecell, $executiontimetablecell);
                        if (!$onlyoneremoteinstance) {
                            $tablecells[] = $executiontimebyotherstablecell;
                        }
                        if ($config->enrollbutton) {
                            $tablecells[] = $enrollbuttontablecell;
                        }
                        $tablerow->cells = $tablecells;
                        $coursetable->data[] = $tablerow;
                    }
                }
                if ($courses) {
                    echo html_writer::table($coursetable);
                    echo html_writer::empty_tag('input',
                        array('type' => 'submit', 'class' => 'btn btn-primary backuprestorelistsubmit',
                                'value' => get_string('submit'),
                                'name' => 'submit')
                    );
                    echo html_writer::end_tag('form');
                    echo $OUTPUT->box_end();
                }
                echo html_writer::end_tag('fieldset');
                echo html_writer::end_tag('div');
            } catch (moodle_exception $ex) {
                echo html_writer::start_div('alert-danger');
                echo $ex->getMessage();
                echo html_writer::end_div();
                continue;
            }
        }
    }
    if ($nbropenedexternalmoodles == 0) {
        echo html_writer::start_tag('div', array('class' => 'notice'));
        echo get_string('noexternalmoodleconnected', 'block_my_external_backup_restore_courses');
        echo html_writer::end_tag('div');
    }
} else {
    echo html_writer::start_tag('div', array('class' => 'notice'));
    echo get_string('noexternalmoodleconnected', 'block_my_external_backup_restore_courses');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
