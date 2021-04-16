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
 * CLI install steps for block my_external_backup_restore_courses
 * for moodle course server side
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    block_my_external_backup_restore_courses
 * @author 2021 Celine Perves cperves@unistra.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/blocks/my_external_backup_restore_courses/locallib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('verbose' => false, 'help' => false), array('v' => 'verbose', 'h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
            "install complete webservice (service, role, user and user assignment) for a moodle server for block my_external_backup_restore_courses

Options:
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php /var/www/moodle/block/my_external_backup_restore_courses/cli/install_server.php
";

    echo $help;
    die;
}

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

$result = block_my_external_backup_restore_courses_tools::install_webservice_moodle_server();
$trace->finished();

exit($result);

