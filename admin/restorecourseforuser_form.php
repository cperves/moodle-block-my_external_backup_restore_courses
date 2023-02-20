<?php

/**
 * Folder plugin version information
 *
 * @package
 * @subpackage
 * @copyright  2023 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block\my_external_backup_restore_courses\admin;
require_once("$CFG->libdir/formslib.php");

class restorecourseforuser_form extends \moodleform {
    function definition() {
        $mform = &$this->_form;
        $staticnoplfelement = \html_writer::start_tag('div', array('class' => 'notice'))
            .get_string('noexternalmoodleconnected', 'block_my_external_backup_restore_courses')
            .\html_writer::end_tag('div');

        $externalmoodles = \block_my_external_backup_restore_courses_tools::get_external_moodles_url_token();
        if ($externalmoodles && !empty($externalmoodles)) {
            // Choose external plateforms if more that one
            if (count($externalmoodles)>1) {
                $radioarray = array();
                foreach($externalmoodles as $domain => $externalmoodle) {
                    $radioarray[] = $mform->createElement('radio', 'externalmoodleurl', '', $domain, $domain);
                }
                $mform->addGroup($radioarray, 'externalmoodlesarray',
                    get_string('externalmoodleurl', 'block_my_external_backup_restore_courses'),
                    array(' '), false);
            } else {
                $mform->addElement('hidden', 'externalmoodleurl', array_keys($externalmoodles)[0]);
                $mform->setType('externalmoodleurl', PARAM_RAW);
                $mform->addElement('static', 'moodleurldesc',
                    get_string('externalmoodleurl', 'block_my_external_backup_restore_courses'),
                    array_keys($externalmoodles)[0]);
            }
            $mform->addElement('text', 'externalcourseid',
                get_string('externalcourseid', 'block_my_external_backup_restore_courses'));
            $mform->setType('externalcourseid', PARAM_INT);
            $mform->addRule('externalcourseid', get_string('required'),
                'required', null, 'client');
            $mform->addElement('text', 'userid',
                get_string('userid', 'block_my_external_backup_restore_courses'));
            $mform->setType('userid', PARAM_INT);
            $mform->addElement('checkbox', 'internalcategory',
                get_string('keepcategory', 'block_my_external_backup_restore_courses'));
            $mform->addElement('checkbox', 'withuserdatas',
                get_string('withuserdatas', 'block_my_external_backup_restore_courses'));
            $mform->addElement('submit', 'submit', get_string('planifyrestore',
                'block_my_external_backup_restore_courses'));
        } else {
            $mform->addElement('static', 'noexternalmoodles', $staticnoplfelement);

        }
    }
}