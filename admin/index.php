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

use block_my_external_backup_restore_courses\reportbuilder\local\systemreports\course_restoration_tasks;
use core_reportbuilder\system_report_factory;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('my_external_backup_restore_courses_admin', '', array(), new moodle_url('/blocks/my_external_backup_restore_courses/admin/index.php',array()));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetasks', 'block_my_external_backup_restore_courses'));
$report = system_report_factory::create(course_restoration_tasks::class, context_system::instance());
echo $report->output();
echo $OUTPUT->footer();
