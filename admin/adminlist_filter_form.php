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

class adminlist_filter_form extends \moodleform {
    function definition() {
        $mform = &$this->_form;
        $mform->addElement('header', "filterheader",
            get_string('filters', 'block_my_external_backup_restore_courses'));
        $mform->addElement('text', 'courseoruserfilter',
            get_string('courseoruserfilter', 'block_my_external_backup_restore_courses'));
        $mform->setType('courseoruserfilter', PARAM_TEXT);
        $mform->addElement('text', 'courseidfilter',
            get_string('courseidfilter', 'block_my_external_backup_restore_courses'));
        $mform->setType('courseidfilter', PARAM_INT);
        $mform->addElement('text', 'useridfilter',
            get_string('useridfilter', 'block_my_external_backup_restore_courses'));
        $mform->setType('useridfilter', PARAM_INT);
        $mform->addElement('submit', 'submit',
            get_string('filter', 'block_my_external_backup_restore_courses'));
    }
}