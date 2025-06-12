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
 *
 * @package
 * @subpackage
 * @copyright  2025 Université de Strasbourg  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_my_external_backup_restore_courses\reportbuilder\local\entities;

use block_my_external_backup_restore_courses_tools;
use core\output\html_writer;
use core\output\inplace_editable;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use moodle_url;
use stdClass;

require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
class course_restoration_task extends base {
    protected function get_default_tables(): array {
        return [
            'block_external_backuprestore',
        ];
    }

    protected function get_default_entity_title(): lang_string
    {
        return new lang_string('course_restoration_task', 'block_my_external_backup_restore_courses');
    }

    public function initialise(): base
    {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }
        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }
        return $this;
    }

    protected function get_all_columns(): array
    {
        global $PAGE;
        // Loading amd without explicite functionname not works.
        $jscode =
            "require(['block_my_external_backup_restore_courses/changetaskfields'],
                function(changetaskfields) {
                    changeinternalcategory = function(id) {
                        changetaskfields.changeinternalcategory(id);
                    }
                    changestatus = function(id) {
                        changetaskfields.changestatus(id);
                    }
                }
            );";
        $PAGE->requires->js_amd_inline($jscode);
        $tablealias = $this->get_table_alias('block_external_backuprestore');
        $columns[] = (
        new column(
            'status', new lang_string('status', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.status, {$tablealias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function(?string $value, \stdClass $row): string {
                $options = [
                    block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED =>
                        new lang_string('scheduledstatus', 'block_my_external_backup_restore_courses'),
                    block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS =>
                        new lang_string('inprogressstatus', 'block_my_external_backup_restore_courses'),
                    block_my_external_backup_restore_courses_tools::STATUS_PERFORMED =>
                        new lang_string('performedstatus', 'block_my_external_backup_restore_courses'),
                    block_my_external_backup_restore_courses_tools::STATUS_ERROR =>
                        new lang_string('errorstatus', 'block_my_external_backup_restore_courses'),
                ];
                $selectmenu = \html_writer::select(
                    $options, 'status_select_'.$row->id, $value, null,
                    ['onchange' => 'changestatus('.$row->id.')']
                );
                return $selectmenu;
            });
        $columns[] = (
        new column(
            'source', new lang_string('source', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.source")
            ->set_is_sortable(true);
        $columns[] = (
            new column(
                'id', new lang_string('id', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.id")
            ->set_is_sortable(true);
        $columns[] = (
            new column(
                'courseid', new lang_string('courseid', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.courseid")
            ->set_is_sortable(true);
        $columns[] = (
            new column(
                'externalcoursename', new lang_string('externalcoursename', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields(
                "{$tablealias}.externalcoursename, {$tablealias}.externalmoodleurl, {$tablealias}.externalcourseid"
            )
            ->set_callback(
                static function(?string $value, stdClass $row): string {
                    return ($row->externalcourseid ?
                        html_writer::link(new moodle_url($row->externalmoodleurl.'/course/view.php',
                            ['id' => $row->externalcourseid]),$row->externalcoursename)
                        : '');
                }
            )
            ->set_is_sortable(true);
        $columns[] = (
            new column(
                'externalcourseid', new lang_string('externalcourseid', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.externalcourseid")
            ->set_is_sortable(true);
        $columns[] = (
        new column(
            'userid', new lang_string('userid', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.userid")
            ->set_is_sortable(true);
        $columns[] = (
        new column(
            'externalmoodleurl', new lang_string('externalmoodleurl', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.externalmoodleurl")
            ->set_is_sortable(true);
        $columns[] = (
        new column(
            'internalcategory', new lang_string('internalcategory', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.internalcategory,{$tablealias}.id")
            ->set_callback(
                static function(?int $value, stdClass $row): string {
                    global $OUTPUT;
                    $edittext = html_writer::empty_tag(
                        'input',
                        [
                            'id' => 'internalcategory_'.$row->id,
                            'value' => $row->internalcategory,
                            'onkeydown' => 'if(event.keyCode == 13) changeinternalcategory('.$row->id.')',
                            'disabled' => ''
                        ]
                    );
                    //return $edittext;
                    $editplace = new inplace_editable(
                        'block_my_external_backup_restore_courses',
                        'task_status',
                        $row->id,
                        true,
                        $row->internalcategory,
                        $row->internalcategory
                    );
                    return $OUTPUT->render($editplace);

                }
                )
            ->set_is_sortable(true);
        $columns[] = (
            new column(
                'timecreated', new lang_string('timecreated', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
            ->add_callback(fn($value) => $value ?: get_string('never'));
        $columns[] = (
            new column(
                'timemodified', new lang_string('timemodified', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
            ->add_callback(fn($value) => $value ?: get_string('never'));
        $columns[] = (
            new column(
                'timescheduleprocessed', new lang_string('timescheduleprocessed', 'block_my_external_backup_restore_courses'), $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timescheduleprocessed")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
            ->add_callback(fn($value) => $value ?: get_string('never'));
        return $columns;
    }

    protected function get_all_filters(): array {
        global $DB;

        $tablealias = $this->get_table_alias('block_external_backuprestore');

        $filters[] =
            (new filter(
                number::class,
                'id',
                new lang_string('id', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.id"
            ))
                ->add_joins($this->get_joins());
        $filters[] =
            (new filter(
                number::class,
                'courseid',
                new lang_string('courseid', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.courseid"
            ))
                ->add_joins($this->get_joins());
        $filters[] =
            (new filter(
                select::class,
                'status',
                new lang_string('status', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.status"
            ))
                ->add_joins($this->get_joins())
                ->set_options_callback(
                    static function():  array {
                        $status = [
                            block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED =>
                                new lang_string('scheduledstatus', 'block_my_external_backup_restore_courses'),
                            block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS =>
                                new lang_string('inprogressstatus', 'block_my_external_backup_restore_courses'),
                            block_my_external_backup_restore_courses_tools::STATUS_PERFORMED =>
                                new lang_string('performedstatus', 'block_my_external_backup_restore_courses'),
                            block_my_external_backup_restore_courses_tools::STATUS_ERROR =>
                                new lang_string('errorstatus', 'block_my_external_backup_restore_courses'),

                        ];
                        return $status;
                    }
                );
            $filters[] =
                (new filter(
                    select::class,
                    'source',
                    new lang_string('source', 'block_my_external_backup_restore_courses'),
                    $this->get_entity_name(),
                    "{$tablealias}.source"
                ))
                    ->add_joins($this->get_joins())
                    ->set_options_callback(
                        static function():  array {
                            global $DB;
                            $sources =
                                $DB->get_records_sql(
                                    'select distinct source from {block_external_backuprestore}'
                                );
                            $sourceoptions = [];
                            foreach ($sources as $currentsource) {
                                $sourceoptions[$currentsource->source] =
                                    $currentsource->source;
                            }
                            return $sourceoptions;
                        }
                    );
        $filters[] =
            (new filter(
                text::class,
                'externalcoursename',
                new lang_string('externalcoursename', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.externalcoursename"
            ))
                ->add_joins($this->get_joins());
        $filters[] =
            (new filter(
                number::class,
                'externalcourseid',
                new lang_string('externalcourseid', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.externalcourseid"
            ))
                ->add_joins($this->get_joins());
        $filters[] =
            (new filter(
                number::class,
                'userid',
                new lang_string('userid', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.userid"
            ))
                ->add_joins($this->get_joins());
        $filters[] =
            (new filter(
                select::class,
                'externalmoodleurl',
                new lang_string('externalmoodleurl', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.externalmoodleurl"
            ))
                ->add_joins($this->get_joins())
                ->set_options_callback(
                    static function():  array {
                        global $DB;
                        $externalmoodles =
                            $DB->get_records_sql(
                                'select distinct externalmoodleurl from {block_external_backuprestore}'
                            );
                        $externalmoodlesoptions = [];
                        foreach ($externalmoodles as $currentexternalmoodle) {
                            $externalmoodlesoptions[$currentexternalmoodle->externalmoodleurl] =
                                $currentexternalmoodle->externalmoodleurl;
                        }
                        return $externalmoodlesoptions;
                    }
                );
        $filters[] =
            (new filter(
                number::class,
                'internalcategory',
                new lang_string('internalcategory', 'block_my_external_backup_restore_courses'),
                $this->get_entity_name(),
                "{$tablealias}.internalcategory"
            ))
                ->add_joins($this->get_joins());
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'block_my_external_backup_restore_courses'),
            $this->get_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'block_my_external_backup_restore_courses'),
            $this->get_entity_name(),
            "{$tablealias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timescheduleprocessed',
            new lang_string('timescheduleprocessed', 'block_my_external_backup_restore_courses'),
            $this->get_entity_name(),
            "{$tablealias}.timescheduleprocessed"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
