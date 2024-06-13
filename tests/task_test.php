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
 * task test
 *
 * @package     block_my_external_backup_restore_courses
 * @category    test
 * @copyright   2024 Céline Pervès <cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_external_backup_restore_courses;

class task_test extends \advanced_testcase {

    protected function setUp() : void {
        parent::setUp();
        global $DB, $CFG;
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
    }

    /**
     * @dataProvider category_provider
     */
    public function test_task_without_default_category($category){
        set_config('defaultcategory', $category, 'block_my_external_backup_restore_courses');
        $sink = $this->redirectMessages();
        ob_start();
        $task = \core\task\manager::get_scheduled_task('\block_my_external_backup_restore_courses\task\backup_restore_task');
        $task->execute();
        ob_end_clean();
        $messages = $sink->get_messages();
        $this->assertCount(2, $messages);
        $this->assertEquals(get_admin()->id, $messages[0]->useridto);
        $this->assertEquals('restorationfailed', $messages[0]->eventtype);
        $sink->close();
    }

    // Provider.
    public function category_provider(): array {
        return [
            [''],
            [0],
        ];
    }
}