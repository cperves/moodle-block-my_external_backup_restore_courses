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
 * @author Thierry Schlecht <thierry.schlecht@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * inspired from webservices/pluiginfile.php
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * AJAX_SCRIPT - exception will be converted into JSON
 */
define('AJAX_SCRIPT', true);

/**
 * NO_MOODLE_COOKIES - we don't want any cookie
 * if cookie the $USER is changed while autheticating with token
 */
define('NO_MOODLE_COOKIES', true);


require_once('../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
// Authenticate the user.
$token = required_param('token', PARAM_ALPHANUM);
$filerecordid = required_param('filerecordid', PARAM_INT);

$webservicelib = new webservice();
$authenticationinfo = $webservicelib->authenticate_user($token);

require_capability('block/my_external_backup_restore_courses:can_retrieve_courses', context_system::instance());
// Check the service allows file download.
$enabledfiledownload = (int) ($authenticationinfo['service']->downloadfiles);
if (empty($enabledfiledownload)) {
    throw new webservice_access_exception('Web service file downloading must be enabled in external service settings');
}

// Finally we can serve the file.
$fs = get_file_storage();
$file = $fs->get_file_by_id($filerecordid);
send_stored_file($file);