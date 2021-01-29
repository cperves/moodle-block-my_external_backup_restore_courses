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
 * block_my_external_backup_restore_courses db upgrade file
 *
 * @package
 * @subpackage
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_block_my_external_backup_restore_courses_upgrade($oldversion=0) {
    global $DB;
    $newversion = 2019052302;
    if ($oldversion < $newversion) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('block_external_backuprestore');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            $dbman->add_field($table, $field);
            $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
            $dbman->add_key($table, $key);
        }
        upgrade_block_savepoint(true, $newversion, 'my_external_backup_restore_courses');
    }
    return true;
}
