<?php

/**
 * This file contains the definition for course table which subclassses easy_table
 *
 * @package   tool_my_external_bakcup_restore_courses
 * @copyright  2023 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_table\dynamic as dynamic_table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');

/**
 * Extends table_sql to provide a table of course to import from external platform
 *
 * @package   block_my_external_backup_restore_courses
 */
class block_my_external_backup_restore_course_admin_table extends table_sql {
    /** @var int $perpage */
    private $perpage = 10;
    /** @var int $rownum (global index of current row in table) */
    private $rownum = -1;
    /** @var renderer_base for getting output */
    private $output = null;
    /** @var boolean $any - True if there is one or more entries*/
    public $anyentry = false;

    /**
     * This table loads the list of all course programmed to be restored from external p)lf to current plf
     *
     * @param int $perpage How many per page
     * @param int $rowoffset The starting row for pagination
     */
    function __construct($parameters, $perpage=null, $page=null, $rowoffset=0) {
        global $PAGE, $CFG;
        parent::__construct('block_my_external_backup_restore_course_admin_entries');
        if(isset($perpage)){
             $this->perpage = $perpage;
        }
        if(isset($page)){
             $this->currpage = $page;
        }

        $this->define_baseurl(new moodle_url('/blocks/my_external_backup_restore_courses/admin/managment.php'));

        $this->anyentries = block_my_external_backup_restore_courses_tools::admin_any_entries();

        // do some business - then set the sql
        if ($rowoffset) {
            $this->rownum = $rowoffset - 1;
        }

        
       $params = array('component'=>'course','filearea'=>'legacy','coursecontext'=>CONTEXT_COURSE);
       $fields='beb.*';
       $from =
           '{block_external_backuprestore} beb left join {user} u on u.id=beb.userid left join {course} c on c.id=beb.courseid';
       $where = '';

        $courseoruserfilter = $parameters['courseoruserfilter'];
        $useridfilter = $parameters['useridfilter'];
        $courseidfilter = $parameters['courseidfilter'];
        if (!empty($courseoruserfilter)) {
            if (!empty($where)) {
                $where .= ' and ';
            }
            $where .= " (u.username ilike '%$courseoruserfilter%' or u.firstname ilike '%$courseoruserfilter%' or u.lastname ilike '%$courseoruserfilter%' or c.shortname ilike '%$courseoruserfilter%' or c.fullname ilike '%$courseoruserfilter%')";
        }
        if (!empty($useridfilter)) {
            if (!empty($where)) {
                $where .= ' and ';
            }
            $where .= " u.id = $useridfilter";
        }
        if (!empty($courseidfilter)) {
            if (!empty($where)) {
                $where .= ' and ';
            }
            $where .= " beb.courseid = $courseidfilter";
        }

        if (empty($where)) {
            $where = 'true';
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql('select count(*) from {block_external_backuprestore}');

        $columns = array();
        $headers = array();

        $columns[] = 'action';
        $headers[] = '';
        $columns[] = $headers[] = 'status';
        $columns[] = $headers[]= 'id';
        $columns[] = $headers[]= 'courseid';
        $columns[] = $headers[]= 'externalcoursename';
        $columns[] = $headers[]= 'externalcourseid';
        $columns[] = $headers[]= 'userid';      
        $columns[] = $headers[] = 'externalmoodleurl';
        $columns[] = $headers[] = 'internalcategory';
        $columns[] = $headers[] = 'timecreated';
        $columns[] = $headers[] = 'timemodified';
        $columns[] = $headers[] = 'timescheduleprocessed';
     
        // set the columns
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true,'coursename');
        $this->no_sorting('action');
        $this->use_pages =true;
        $this->collapsible(false);
    }

    /**
     * Return the number of rows to display on a single page
     *
     * @return int The number of rows per page
     */
    function get_rows_per_page() {
        return $this->perpage;
    }

    /**
     * Format a link to the assignment instance
     *
     * @param stdClass $row
     * @return string
     */
    function col_externalcoursename(stdClass $row) {
        return html_writer::link(new moodle_url($row->externalmoodleurl.'/course/view.php',
                array('id' => $row->externalcourseid)), $row->externalcoursename);
    }


    function col_courseid(stdClass $row) {
        global $DB;
        if($row->courseid) {
            $restoredcourse = $DB->get_record('course', array('id' => $row->courseid));
            if ($restoredcourse) {
                return html_writer::link(new moodle_url('/course/view.php',
                    array('id' => $row->courseid)), $row->courseid);
            } else {
                return get_string('courseidXbutdeleted', 'block_my_external_backup_restore_courses', $row->courseid);
            }

        }else{
            return '';
        }
    }


    /**
     * internal category input field
     *
     * @param stdClass $row
     * @return string
     */
    function col_internalcategory(stdClass $row) {
         return html_writer::empty_tag('input', array('name'=>'internalcategory_'.$row->id,'type'=>'text','value'=>$row->internalcategory, 'size' => "4"));
       
    }
    
    /**
     * status selection
     * @param stdClass $row
     * @return string
     */
    function col_status(stdClass $row) {
         return html_writer::select(array(block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED => get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED,'block_my_external_backup_restore_courses'),
         block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS => get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS,'block_my_external_backup_restore_courses'),
         block_my_external_backup_restore_courses_tools::STATUS_PERFORMED => get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_PERFORMED,'block_my_external_backup_restore_courses'),
         block_my_external_backup_restore_courses_tools::STATUS_ERROR => get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_ERROR,'block_my_external_backup_restore_courses')
         ),
         'status_'.$row->id,$row->status);
    }
    function col_action(stdClass $row){
         $out=html_writer::empty_tag('input',array('type'=>'hidden','value'=>$this->currpage, 'name'=>'page'));
         $out.=html_writer::empty_tag('input',array('type'=>'submit','value'=>get_string('edit'), 'name'=>'submit','onclick'=>'$(\'#trigger\').val('.$row->id.')'));
         return $out;
    }
    function col_timecreated(stdClass $row){
         return $row->timecreated == 0? get_string('never') : userdate($row->timecreated,'%D %X');
    }
    function col_timemodified(stdClass $row){
         return $row->timemodified == 0? get_string('never') : userdate($row->timemodified,'%D %X');
    }
    function col_timescheduleprocessed(stdClass $row){
         return $row->timescheduleprocessed == 0? get_string('never') : userdate($row->timescheduleprocessed,'%D %X');
    }
    
    //override fonctions to include form
    function start_html(){

         parent::start_html();
         echo html_writer::start_tag('form', array('action'=>$this->baseurl->out()));
         echo html_writer::empty_tag('input', array('type'=>'hidden','name'=>'trigger', 'id'=>'trigger'));


    }
    
    function finish_html(){

         echo html_writer::end_tag('form');
         parent::finish_html();


    }
}
