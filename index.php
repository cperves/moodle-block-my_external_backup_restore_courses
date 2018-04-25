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
 * Folder plugin version information
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
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('externalmoodlecourselist',
    'block_my_external_backup_restore_courses'),
    $PAGE->url);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('externalmoodlecourselist', 'block_my_external_backup_restore_courses'));
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
$externalmoodlescfg = get_config('block_my_external_backup_restore_courses', 'external_moodles');
// Formatted : domainname1,token1;domainname2;token2;...
$wsparams = array('username' => $USER->username);
// External cateory parameters.
$config = get_config('block_my_external_backup_restore_courses');
$restorecourseinoriginalcategory = $config->restorecourseinoriginalcategory;
$categorytable = $config->categorytable;
$categorytableforeignkey = $config->categorytable_foreignkey;
$categorytablecategoryfield = $config->categorytable_categoryfield;
$defaultcategoryid = $config->defaultcategory;

// Check if plugin is correcly configured.
if (empty($defaultcategoryid) ||
    ($restorecourseinoriginalcategory == 1
        && (empty($categorytable) || empty($categorytableforeignkey) || empty($categorytableforeignkey)
        )
    )
) {
    print_error(get_string('misconfigured_plugin', 'block_my_external_backup_restore_courses'));
}

// Forms paramteters.
$submit = optional_param('submit', null, PARAM_TEXT);
$selectedcourses = optional_param_array('selectedcourses', null, PARAM_RAW);
$externalmoodleurl = optional_param('externalmoodleurl', null, PARAM_URL);
$externalmoodletoken  = optional_param('externalmoodletoken', null, PARAM_TEXT);
$allcourses  = optional_param_array('allcourses', null, PARAM_RAW);
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
                    $datas->externalmoodletoken = $externalmoodletoken;
                    $datas->internalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
                    $datas->externalcoursename = optional_param('coursename_'.$selectedcourse, '', PARAM_TEXT);
                    $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
                    $datas->timecreated = time();
                    $DB->insert_record('block_external_backuprestore', $datas);
                } else {
                    // Update.
                    $datas = $dbinfo;
                    $currentoriginalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
                    $datas->internalcategory = optional_param('originalcategory_'.$selectedcourse, 0, PARAM_INT);
                    $currentoriginalstatus = $datas->status;

                    if ($datas->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED) {
                        $datas->status = block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED;
                    }
                    if ($datas->internalcategory != $currentoriginalcategory
                        || (
                            $currentoriginalstatus == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                            && $datas->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                        )
                    ) {
                        $datas->timemodified = time();
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
            $token = $keyvalue[1];
            $serveroptions = array();
            $serveroptions['token'] = $token;
            $serveroptions['domainname'] = $domainname;
            $validusername = true;
            $courses = array();
            try {
                $sitename = block_my_external_backup_restore_courses_tools::external_backup_course_sitename($domainname, $token);
                try {
                    $courses = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($domainname,
                        $token,
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
                    echo html_writer::empty_tag('input',
                        array('type' => 'hidden', 'name' => 'externalmoodletoken', 'value' => $token));
                    $coursetable = new html_table();
                    $coursetable->attributes['class'] = 'admintable generaltable';
                    $coursetable->head = array(
                        get_string('course'),
                        get_string('choose_to_restore',
                            'block_my_external_backup_restore_courses'));
                    if ($restorecourseinoriginalcategory == 1) {
                        $coursetable->head[] = get_string('keepcategory', 'block_my_external_backup_restore_courses');
                    }
                    $coursetable->head[] = get_string('status');
                    $coursetable->head[] = get_string('executiontime', 'block_my_external_backup_restore_courses');
                    $coursetable->head[] = get_string('nextruntime', 'block_my_external_backup_restore_courses');
                }
                foreach ($courses as $course) {
                    if (isset($course->fullname)) {
                        // Scheduled info stored in bd.
                        $scheduledinfo = $DB->get_record('block_external_backuprestore',
                            array('userid' => $USER->id, 'externalcourseid' => $course->id, 'externalmoodleurl' => $domainname));
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
                                .$categorytableforeignkey where $categorytablecategoryfield=:categoryfield",
                                array('categoryfield' => $course->categoryidentifier)
                            );

                        }
                        echo html_writer::empty_tag('input',
                            array('type' => 'hidden', 'name' => 'allcourses[]', 'value' => $course->id));
                        echo html_writer::empty_tag('input',
                            array('type' => 'hidden', 'name' => 'coursename_'.$course->id, 'value' => $course->fullname));
                        // Preparing html table.
                        $tablerow = new html_table_row();
                        $coursetablecell = new html_table_cell();
                        $coursetablecell->text = $course->fullname;
                        $selecttablecell = new html_table_cell();
                        $attr = array();
                        if ($scheduledinfo
                            && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                            && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                        ) {
                            $attr['disabled'] = 'true';
                            // Input same hidden field to post selected value if selected.
                            if ($scheduledinfo
                                && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                                && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED) {
                                echo html_writer::empty_tag('input',
                                    array('type' => 'hidden', 'name' => 'selectedcourses[]', 'value' => $course->id));
                            }
                        }
                        $selecttablecell->text = html_writer::checkbox('selectedcourses[]', $course->id,
                            $scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED ?
                                true : false,
                            '', $attr);
                        $categorytablecell = new html_table_cell();
                        $attr = array();
                        if (!$originalcategory
                            || (
                                $scheduledinfo && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                                && $scheduledinfo->status != block_my_external_backup_restore_courses_tools::STATUS_PERFORMED
                            )
                        ) {
                            $attr['disabled'] = 'true';
                        }
                        $categorytablecell->text = html_writer::checkbox('originalcategory_'.$course->id,
                            $originalcategory ? $originalcategory->id : 0,
                            $scheduledinfo ? ($scheduledinfo->internalcategory != 0 ? true : false) : ($originalcategory ? true : false),
                            $originalcategory ? $originalcategory->name : '', $attr);
                        // TODO prechecked and freeze.

                        $statetablecell = new html_table_cell();
                        $statetablecell->attributes['class'] = 'nowrap';
                        $nextruntimetablecell = new html_table_cell();
                        $nextruntimetablecell->attributes['class'] = 'nowrap';
                        $executiontimetablecell = new html_table_cell();
                        $executiontimetablecell->attributes['class'] = 'nowrap';
                        if ($scheduledinfo) {
                            $statetablecell->text = get_string('status_'.$scheduledinfo->status, 'block_my_external_backup_restore_courses');
                            if ($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED
                                || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS) {
                                $nextruntimetablecell->text = $scheduledtasknextrun;
                            }
                            if ($scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_ERROR
                                || $scheduledinfo->status == block_my_external_backup_restore_courses_tools::STATUS_PERFORMED) {
                                $executiontimetablecell->text = userdate($scheduledinfo->timescheduleprocessed);
                            }
                        }

                        $tablerow->cells = array(
                            $coursetablecell,
                            $selecttablecell,
                            $categorytablecell,
                            $statetablecell,
                            $nextruntimetablecell,
                            $executiontimetablecell);
                        $coursetable->data[] = $tablerow;
                    }
                }
                if ($courses) {
                    echo html_writer::table($coursetable);
                    echo html_writer::empty_tag('input',
                        array('type' => 'submit', 'value' => get_string('submit'), 'name' => 'submit'));
                    echo html_writer::end_tag('form');
                    echo $OUTPUT->box_end();
                }
                echo html_writer::end_tag('fieldset');
                echo html_writer::end_tag('div');
            } catch (Exception $ex) {
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