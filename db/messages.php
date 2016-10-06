<?php
/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$messageproviders = array(
		// Notify that an external course is successfully restored.
		'restorationsuccess' => array(
				'defaults' => array(
					'popup'=> MESSAGE_DISALLOWED,
					'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF
				)
				
		),
		// Notify that an external course as failed to restore.
		'restorationfailed' => array(
				
				'defaults' => array(
					'popup'=> MESSAGE_DISALLOWED,
					'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF
				)
		),
);