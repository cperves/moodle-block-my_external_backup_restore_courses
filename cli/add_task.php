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

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/blocks/my_external_backup_restore_courses/locallib.php");
list($options, $unrecognized) = cli_get_params(
    array(
        'verbose' => false,
        'help' => false,
        'externalcourseid' => 0,
        'externalcoursename' => '',
        'externalmoodleurl' => '',
        'internalcategory' => 0,
        'courseid' => 0,
        'withuserdatas' => 0,
        'enrolmentmode' => 2,
        // This just in case of advanced use
        'status' => block_my_external_backup_restore_courses_tools::STATUS_PERFORMED,
        'source' => block_my_external_backup_restore_courses_tools::SOURCE_CLI
    ),
    array(
        'v' => 'verbose',
        'h' => 'help',
        'e' => 'externalcourseid',
        'n'=> 'externalcoursename',
        'u' => 'externalmoodleurl',
        'i' => 'internalcategory',
        's' => 'status',
        'z' => 'source',
        'c' => 'courseid',
        'w' => 'withuserdatas',
        'm' => 'enrolmentmode'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
    "Add a course task to restore to course task table, Usefull for example when you use moosh to restore a course and do not want an other instance

Options:
-v, --verbose            : Print verbose progess information
-h, --help               : Print out this help
-e, --externalcourseid   : Required remote moodle course id
-n, --externalcoursename : Required remote moodle course name
-u, --externalmoodleurl  : Required remote moodle url
-c, --courseid           : Course id in local moodle
-i, --internalcategory   : Required internal category where course is restored
-s, --status             :  Optional Task status int value, 2 by default (performed)
                            0 -> Scheduled
                            1 -> In progress
                            2 -> Performed
                            -1 -> Error  
-z, --source             : source of the task , cli by default
-w, --withuserdatas      : Optional with user datas, 1 by default
-m, --enrolmentmode      : Optional enrolment mode, 2 by default
                           0 -> Restore users as manual enrolments
                           1 -> Yes, but only if users are included
                           2 -> Always     

php /var/www/moodle_path/blocks/my_external_backup_restore_courses/cli/add_task.php --externalcourseid=19 --externalmoodleurl=\"https://dotchnieba.di.unistra.fr/moodle405unistra\" --externalcoursename=\"Very brand new course\" --courseid=2


Example:
\$ sudo -u www-data /usr/bin/php /var/www/moodle/block/my_external_backup_restore_courses/cli/add_task.php --externalcourseid=//externalcourseid --externalmoodleurl=//externalmoodleurl --externalcoursename=//externalcoursename --courseid=//internalcourseid 
";

if ($options['help']
    || empty($options['externalcourseid'])
    || empty($options['externalcoursename'])
    || empty($options['courseid'])
    || empty($options['externalmoodleurl'])
) {
    echo $help;
    exit(0);
}

if ($options['help']) {
    echo $help;
    die;
}

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}
try{
    $admin = get_admin();
    block_my_external_backup_restore_courses_task::create_task(
        $admin->id,
        $admin->id,
        $options['externalcourseid'], $options['externalcoursename'],
        $options['externalmoodleurl'], $options['courseid'],
        $options['status'], $options['source'],
        $options['internalcategory'],
        $options['withuserdatas'], $options['enrolmentmode']
    );
    cli_writeln('task added successfully');
} catch(\core\exception\moodle_exception $e) {
    cli_error($e->getMessage());
}

