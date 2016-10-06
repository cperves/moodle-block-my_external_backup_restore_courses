<?php
/**
 * capabilities for my_external_backup_restore_courses block
 *
 * @package
 * @subpackage
 * @copyright  2015 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$capabilities = array(
	'block/my_external_backup_restore_courses:view' => array(
			'captype' => 'read',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
	'block/my_external_backup_restore_courses:myaddinstance' => array(
			'captype' => 'write',
			'contextlevel' => CONTEXT_SYSTEM,
			'archetypes' => array(
					'coursecreator' => CAP_ALLOW,
					'manager' => CAP_ALLOW
			),
	),
	'block/my_external_backup_restore_courses:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
	'block/my_external_backup_restore_courses:can_retrieve_courses' => array(
		'riskbitmask' => RISK_PERSONAL,
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
				'manager' => CAP_INHERIT
		),
	),
	'block/my_external_backup_restore_courses:can_see_backup_courses' => array(
		'riskbitmask' => RISK_PERSONAL,
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => array(
				'manager' => CAP_INHERIT
		),
	),
);
?>