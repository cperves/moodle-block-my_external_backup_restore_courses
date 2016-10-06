<?php

/**
 * Folder plugin version information
 *
 * @package  
 * @subpackage 
 * @copyright  2013 unistra  {@link http://unistra.fr}
 * @author Thierry Schlecht <thierry.schlecht@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


abstract class backup_external_courses_helper {

    /** automated backups are active and ready to run */
    const STATE_OK = 1;
    /** automated backups are disabled and will not be run */
    const STATE_DISABLED = 0;
    /** Course automated backup completed successfully */
    const BACKUP_STATUS_OK = 1;
    /** Course automated backup errored */
    const BACKUP_STATUS_ERROR = 0;
    /** Course automated backup never finished */
    const BACKUP_STATUS_UNFINISHED = 2;
    /** Course automated backup was skipped */
    const BACKUP_STATUS_SKIPPED = 3;
    /** Course automated backup had warnings */
    const BACKUP_STATUS_WARNING = 4;

    public static $courseid = 0;
    public static $userid = 0;
    public static $filename = '';
    public static $file_record_id = 0;
    
    /**
     * Runs the automated backups if required
     *
     * @global moodle_database $DB
     */
    public static function run_external_backup($courseid, $userid) {
        global $CFG, $DB,$SITE;
        $status = true;
        $emailpending = false;
        $now = time();
        self::$courseid = $courseid;
        self::$userid = $userid;
        require_once($CFG->libdir.'/filelib.php');

        //TODO remove when tested
        $status = true;
        $result = array(
        		self::BACKUP_STATUS_ERROR => 0,
        		self::BACKUP_STATUS_OK => 0,
        		self::BACKUP_STATUS_UNFINISHED => 0,
        		self::BACKUP_STATUS_SKIPPED => 0,
        		self::BACKUP_STATUS_WARNING => 0
        );
        
        if ($status) {      
            // This could take a while!
            @set_time_limit(0);
            raise_memory_limit(MEMORY_EXTRA);
            $course = $DB->get_record('course', array('id'=>self::$courseid));
			//mtrace('Backing up '.$course->fullname.'...');
			$course_status = backup_external_courses_helper::launch_automated_backup_delete($course);
			
			$result[$course_status] += 1;
			//mtrace("backup completed for course ");
        }
        return array(
        	'filename'=>self::$filename,
        	'file_record_id'=>self::$file_record_id
       	);
    }

    /**
     * @param stdClass $course
     * @param int $starttime
     * @return bool
     */
    public static function launch_automated_backup_delete($course) {
    	global $CFG;
		require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
        $outcome = self::BACKUP_STATUS_OK;
        
        $config = get_config('my_external_backup_courses');
        
        $customsettings = array(
        	"backup_auto_storage" =>"2" ,
        	"backup_auto_keep"=>"1",
        	"backup_auto_blocks"=>"1", 
        	"backup_auto_users"=>"0",
        	"backup_auto_role_assignments"=>"0",
        	"backup_auto_activities"=>"1" ,
        	"backup_auto_filters"=>"1",
        	"backup_auto_comments"=>"1", 
        	"backup_auto_logs"=>"0",
        	"backup_auto_histories"=>"0"
       	);
        $customsettings = (object)$customsettings;
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_HUB, self::$userid);

        try {

            $settings = array(
                //'users' => 'backup_auto_users',
                'role_assignments' => 'backup_auto_role_assignments',
                'activities' => 'backup_auto_activities',
                'blocks' => 'backup_auto_blocks',
                'filters' => 'backup_auto_filters',
                'comments' => 'backup_auto_comments',
                'completion_information' => 'backup_auto_userscompletion',
                'logs' => 'backup_auto_logs',
                'histories' => 'backup_auto_histories',
            );
            foreach ($settings as $setting => $configsetting) {
                if ($bc->get_plan()->setting_exists($setting)) {
                    $bc->get_plan()->get_setting($setting)->set_value($customsettings->{$configsetting});
                }
            }

            // Set the default filename
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = true;
            
            $bc->get_plan()->get_setting('anonymize')->set_value($anonymised);
			//awaiting status
            $bc->set_status(backup::STATUS_AWAITING);
            $bc->execute_plan();
            
            $results = $bc->get_results();
            $outcome = self::outcome_from_results($results);
            $file = $results['backup_destination']; // may be empty if file already moved to target location
            self::$file_record_id = $file->get_id();

        } catch (moodle_exception $e) {
            $bc->log('backup_auto_failed_on_course', backup::LOG_ERROR, $course->shortname); // Log error header.
            $bc->log('Exception: ' . $e->errorcode, backup::LOG_ERROR, $e->a, 1); // Log original exception problem.
            $bc->log('Debug: ' . $e->debuginfo, backup::LOG_DEBUG, null, 1); // Log original debug information.
            $outcome = self::BACKUP_STATUS_ERROR;
        }
        $bc->destroy();
        unset($bc);

        return $outcome;
    }

    /**
     * Returns the backup outcome by analysing its results.
     *
     * @param array $results returned by a backup
     * @return int {@link self::BACKUP_STATUS_OK} and other constants
     */
    public static function outcome_from_results($results) {
        $outcome = self::BACKUP_STATUS_OK;
        foreach ($results as $code => $value) {
            // Each possible error and warning code has to be specified in this switch
            // which basically analyses the results to return the correct backup status.
            switch ($code) {
                case 'missing_files_in_pool':
                    $outcome = self::BACKUP_STATUS_WARNING;
                    break;
            }
            // If we found the highest error level, we exit the loop.
            if ($outcome == self::BACKUP_STATUS_ERROR) {
                break;
            }
        }
        return $outcome;
    }


    /**
     * Gets the state of the automated backup system.
     *
     * @global moodle_database $DB
     * @return int One of self::STATE_*
     */
    public static function get_automated_backup_state() {
        global $DB;

        //$config = get_config('local_autoclean_categories');
        $active = (int)$config->backup_auto_active;
        // In case of automated backup also check that it is scheduled for at least one weekday.
        if ($active === 0) {
            return self::STATE_DISABLED;
        }
        return self::STATE_OK;
    }
}