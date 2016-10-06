<?php
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
//authenticate the user
$token = required_param('token', PARAM_ALPHANUM);
$filerecordid = required_param('filerecordid', PARAM_INT);

$webservicelib = new webservice();
$authenticationinfo = $webservicelib->authenticate_user($token);

require_capability('block/my_external_backup_restore_courses:can_retrieve_courses', context_system::instance());
//check the service allows file download
$enabledfiledownload = (int) ($authenticationinfo['service']->downloadfiles);
if (empty($enabledfiledownload)) {
	error_log('Web service file downloading must be enabled in external service settings');
    throw new webservice_access_exception('Web service file downloading must be enabled in external service settings');
}

//finally we can serve the file :)
$fs = get_file_storage();
$file = $fs->get_file_by_id($filerecordid);
send_stored_file($file);