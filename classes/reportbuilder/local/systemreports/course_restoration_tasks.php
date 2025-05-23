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

namespace block_my_external_backup_restore_courses\reportbuilder\local\systemreports;

use block_my_external_backup_restore_courses\reportbuilder\local\entities\course_restoration_task;
use context_system;
use core_reportbuilder\system_report;

class course_restoration_tasks extends system_report {
    private $courserestorationtaskentity;
    protected function initialise(): void
    {
        $this->courserestorationtaskentity = new course_restoration_task();
        $entitymainalias = $this->courserestorationtaskentity->get_table_alias('block_external_backuprestore');
        $this->set_main_table('block_external_backuprestore', $entitymainalias);
        $this->add_entity($this->courserestorationtaskentity);
        $this->add_base_fields("{$entitymainalias}.id");
        $this->add_columns();
        $this->add_filters();
    }

    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }
    public function add_columns(): void
    {
        $entitityname = 'course_restoration_task';

        $this->add_columns_from_entities([
            $entitityname.':status',
            $entitityname.':id',
            $entitityname.':courseid',
            $entitityname.':externalcoursename',
            $entitityname.':externalcourseid',
            $entitityname.':userid',
            $entitityname.':externalmoodleurl',
            $entitityname.':internalcategory',
            $entitityname.':source',
            $entitityname.':timecreated',
            $entitityname.':timemodified',
            $entitityname.':timescheduleprocessed',
        ]);
    }

    protected function add_filters(): void {
        $entitityname = 'course_restoration_task';
        $filters = [
            $entitityname.':status',
            $entitityname.':id',
            $entitityname.':courseid',
            $entitityname.':externalcoursename',
            $entitityname.':externalcourseid',
            $entitityname.':userid',
            $entitityname.':externalmoodleurl',
            $entitityname.':internalcategory',
            $entitityname.':source',
            $entitityname.':timecreated',
            $entitityname.':timemodified',
            $entitityname.':timescheduleprocessed',
        ];

        $this->add_filters_from_entities($filters);
    }
}