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
global $CFG;

/**
 *
 * @package
 * @subpackage
 * @copyright  2025 Université de Strasbourg  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\inplace_editable;
use core_external\external_api;
require_once($CFG->dirroot . '/blocks/my_external_backup_restore_courses/locallib.php');
/**
 * Manage inplace editable saves.
 *
 * @param string $itemtype The type of item.
 * @param int $itemid The ID of the item.
 * @param mixed $newvalue The new value
 * @return \core\output\inplace_editable
 */

function block_my_external_backup_restore_courses_inplace_editable($itemtype, $itemid, $newvalue) {
    $context = \context_system::instance();
    external_api::validate_context($context);
    switch($itemtype) {
        case 'task_status':
            require_capability('block/my_external_backup_restore_courses:change_status', $context);
            $newvalue = clean_param($newvalue, PARAM_INT);
            block_my_external_backup_restore_courses_tools::update_status($itemid, $newvalue);
            break;
        default:
            throw new coding_exception(
                'Unexpected block_my_external_backup_restore_courses inplace editable item type'
            );
    }
    return new inplace_editable(
        'block_my_external_backup_restore_courses', $itemtype, $itemid, true, $newvalue
    );

}