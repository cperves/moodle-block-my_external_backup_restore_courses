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

class block_my_external_backup_restore_courses_tools{
	const STATUS_SCHEDULED = 0;
	const STATUS_INPROGRESS = 1;
	const STATUS_PERFORMED = 2;
	const STATUS_ERROR = -1;
	
	public static function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? block_my_external_backup_restore_courses_tools::delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}
	public static function external_backup_course_sitename($domainname, $token) {
		$site_info = NULL;
		try {
			$site_info = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($domainname, $token, 'core_webservice_get_site_info');
		} catch (Exception $e) {
			throw new Exception('site name can \'t be retrieved : '.$e->getMessage());
		}
		$sitename = $site_info->sitename;
		if (!isset($sitename)) {
			throw new Exception('site name can \'t be retrieved');
		}
		return $sitename;
	}
	public static function get_all_users_courses($username, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
		global $DB;
	
		$config = get_config('block_my_external_backup_restore_courses');
		$restorecourseinoriginalcategory= $config->restorecourseinoriginalcategory;
		$categorytable=$config->categorytable;
		$categorytable_foreignkey = $config->categorytable_foreignkey;
		$categorytable_categoryfield = $config->categorytable_categoryfield;
		
		$category_select ='';
		$category_join='';
		$category_where='';
		
		if($restorecourseinoriginalcategory==1 && !empty($categorytable) && !empty($categorytable_foreignkey) && !empty($categorytable_categoryfield)){
			$category_select =", ct.$categorytable_categoryfield as categoryidentifier ";
			$category_join=" left join {".$categorytable."} ct on ct.$categorytable_foreignkey=c.category ";
			$category_where=" and ct.$categorytable_foreignkey=c.category ";
		}
	    
	    // Guest account does not have any courses
	    $user_record = $DB->get_record('user', array('username' => $username));
		if (!$user_record) { 
			throw new block_my_external_backup_restore_courses_invalid_username_exception('user with username not found');
		}
	
	    $userid = $user_record->id;
		
	    if (isguestuser($userid) or empty($userid)) {
	        return(array());
	    }
	
	    $basefields = array('id', 'category', 'sortorder',
	            'shortname', 'fullname', 'idnumber',
	            'startdate', 'visible',
	            'groupmode', 'groupmodeforce');
	
	    if (empty($fields)) {
	        $fields = $basefields;
	    } else if (is_string($fields)) {
	        // turn the fields from a string to an array
	        $fields = explode(',', $fields);
	        $fields = array_map('trim', $fields);
	        $fields = array_unique(array_merge($basefields, $fields));
	    } else if (is_array($fields)) {
	        $fields = array_unique(array_merge($basefields, $fields));
	    } else {
	        throw new coding_exception('Invalid $fileds parameter in enrol_get_my_courses()');
	    }
	    if (in_array('*', $fields)) {
	        $fields = array('*');
	    }
	
	    $orderby = "";
	    $sort    = trim($sort);
	    if (!empty($sort)) {
	        $orderby = "ORDER BY $sort";
	    }
	
	    $params = array();
	
	    if ($onlyactive) {
	        $subwhere = "WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)";
	        $params['now1']    = round(time(), -2); // improves db caching
	        $params['now2']    = $params['now1'];
	        $params['active']  = ENROL_USER_ACTIVE;
	        $params['enabled'] = ENROL_INSTANCE_ENABLED;
	    } else {
	        $subwhere = "";
	    }
	
	    $coursefields = 'c.' .join(',c.', $fields);
	        
	    $select = ", " . context_helper::get_preload_record_columns_sql('ctx');
	    $join = "LEFT JOIN mdl_context ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")";
	    list($ccselect, $ccjoin)= array($select, $join);   
	    
	    
		$roles = $config->search_roles;
		if(empty($roles)){
			return false;
		}	
		$roles = explode(',', $roles);
		$new_formatted_roles = array();
		foreach($roles as $key=>$role){
			$new_formatted_roles[] = '\''.$role.'\'';
		}
		if(count($new_formatted_roles)==0){
			return false;
		}
		
	    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
	    $sql = "SELECT $coursefields $ccselect $category_select
	              	FROM {course} c
	              	INNER JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")
	              	INNER JOIN (
	              		SELECT ra.contextid AS contextid, usr.firstname AS firstname, usr.lastname AS lastname FROM {role_assignments} ra 
		    			INNER JOIN {role} r ON (r.id = ra.roleid and r.shortname IN (".implode(',',$new_formatted_roles)."))
		    			INNER JOIN {user} usr ON (ra.userid = usr.id AND usr.id = $userid)
		    			) AS u ON (u.contextid = ctx.id)
		    		$category_join
		    		WHERE c.id <> ".SITEID 
	    		 ." UNION 
	    		SELECT $coursefields $ccselect $category_select
	    			FROM {course} c
	    			INNER JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")
	    			INNER JOIN {enrol} e ON e.enrol = 'category' AND e.courseid = c.id
	    			INNER JOIN (SELECT cctx.path, ra.userid, MIN(ra.timemodified) AS estart, ra.roleid as roleid
				    			FROM {course_categories} cc
				    			JOIN {context} cctx ON (cctx.instanceid = cc.id AND cctx.contextlevel = ".CONTEXT_COURSECAT.")
				    			JOIN {role_assignments} ra ON (ra.contextid = cctx.id)
				    			JOIN {role} ro ON (ra.roleid = ro.id and ro.shortname in (".implode(',',$new_formatted_roles)."))
				    			GROUP BY cctx.path, ra.userid, ra.roleid
	              	) cat ON (ctx.path LIKE cat.path || '/%')
				    INNER JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cat.userid)
				    INNER JOIN {user} u ON u.id = cat.userid AND u.id = ue.userid
				    INNER JOIN {role} r ON r.id = cat.roleid AND r.shortname IN (".implode(',',$new_formatted_roles).") 
				    $category_join 
					WHERE u.id = $userid AND c.id <> ".SITEID 
	          		." $orderby";
	
	    $courses = $DB->get_records_sql($sql, $params);
	    return $courses;
	}
	
	public static function print_content() {
		global $OUTPUT;
		$output = '';
		$external_moodles = get_config('block_my_external_backup_restore_courses', 'external_moodles');
		if ($external_moodles && !empty($external_moodles)) {
			$external_moodles = explode(';', $external_moodles);
			if (count($external_moodles)>0) {
				$backup_courses_url = new moodle_url('/blocks/my_external_backup_restore_courses/index.php');
				$output = html_writer::link($backup_courses_url, get_string('restorecourses', 'block_my_external_backup_restore_courses'));
			}
		}
		return $output;
		
	}
	
	public static function rest_call_external_courses_client($domainname, $token, $functionname, $params=array(), $restformat='json', $method='get') {
		global $CFG;
		require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
		require_once($CFG->dirroot.'/lib/filelib.php');
		require_once($CFG->dirroot.'/webservice/lib.php');
		$serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;
		$curl = new curl;
		//if rest format == 'xml', then we do not add the param for backward compatibility with Moodle < 2.2
		$restformat = ($restformat == 'json') ? '&moodlewsrestformat=' . $restformat : '';
		if ($method == 'get') {
			$resp = $curl->get($serverurl . $restformat, $params);
		} else if ($method == 'post') {
			$resp = $curl->post($serverurl . $restformat, $params);
		}
		$resp = json_decode($resp);
		//check if errors encountered
		if (!isset($resp)) {
			throw new Exception($resp);
		}
		if (isset($resp->errorcode)) {
			if($resp->exception == 'block_my_external_backup_restore_courses_invalid_username_exception'){
				throw new block_my_external_backup_restore_courses_invalid_username_exception($resp->errorcode);		
			}
			throw new moodle_exception($resp->errorcode);
		}
		return $resp;
	}
}

class block_my_external_backup_restore_courses_invalid_username_exception extends moodle_exception {
	/**
	 * Constructor
	 * @param string $debuginfo some detailed information
	 */
	function __construct($debuginfo=null) {
		parent::__construct('invalidusername', 'debug', '', null, $debuginfo);
	}
}

abstract class block_my_external_backup_restore_courses_task_helper{
	const BACKUP_FILENAME='currentbackupedcourse.mbz';
	const BACKUP_TEMPDIRNAME='currentbackupedcourse';
	public static function run_automated_backup_restore() {
		global $CFG,$SITE, $DB;
		
		$config = get_config('block_my_external_backup_restore_courses');
		$timelimitedmod = $config->timelimitedmod == 1? true : false;
		
		$errors=new block_my_external_backup_restore_courses_task_error_list();
		// This could take a while!
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);
        $defaultcategoryid = get_config('block_my_external_backup_restore_courses','defaultcategory');
        if(empty($defaultcategoryid)){
        	$errors->add_error(new block_my_external_backup_restore_courses_task_error(null,'defaultcategoryid not defined for my_external_backup_restore_courses plugin, please correct this.'));
        	$errors->notify_errors();
        	return false;
        }
        //check that category exists
        $cat = $DB->get_record('course_categories', array('id'=>$defaultcategoryid));
        if(!$cat){
        	$errors->add_error(new block_my_external_backup_restore_courses_task_error(null,'defaultcategoryid not defined for my_external_backup_restore_courses plugin, please correct this.'));
        	$errors->notify_errors();
        	return false;
        }
		$tasks = block_my_external_backup_restore_courses_task_helper::retrieve_tasks($defaultcategoryid);
		$defaultcategorycontext = context_coursecat::instance($defaultcategoryid);
		$externalmoodlesitenames = array();
		foreach($tasks as $task){
			if($timelimitedmod){
				$currenttime = new DateTime();
				$limitstarttime =clone $currenttime;
				$limitstarttime->setTimezone(core_date::get_server_timezone_object());
				$limitstarttime->setTime($config->limitstart_hour, $config->limitstart_minute);
				$limitendtime =clone $currenttime;
				$limitendtime->setTimezone(core_date::get_server_timezone_object());
				$limitendtime->setTime($config->limitend_hour, $config->limitend_minute);
				if($limitstarttime>$limitendtime){
					//changing day during interval
					//pas starttime in day before
					$limitstarttime->sub(new DateInterval('P1D'));
				}
				
				if($limitstarttime==$limitendtime ||  ($currenttime>$limitstarttime && $currenttime>$limitendtime))				
				{
					$errors->add_error(new block_my_external_backup_restore_courses_task_error($task,'execution time outdated'));
					return true;
				}
			}
			
			$errors=new block_my_external_backup_restore_courses_task_error_list();
			$task_object = new block_my_external_backup_restore_courses_task($task);
			//search externalmoodlesitename
			if(!array_key_exists($task->externalmoodleurl,$externalmoodlesitenames)){
				$sitename = $task_object->retrieve_external_moodle_name();
				$externalmoodlesitenames[$task->externalmoodleurl]=$sitename;
				$task->externalmoodlesitename =$sitename;
			}else{
				$task->externalmoodlesitename =$externalmoodlesitenames[$task->externalmoodleurl];
			}
			$username = $task_object->get_username();
			$user = $DB->get_record('user', array('username'=>$username)); 
			if(!$username){
				$errors->add_error(new block_my_external_backup_restore_courses_task_error($task,'user not found for task : '.$task->id));
				$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
				$errors->add_errors($task->get_errors());
				$errors->notify_errors();
				continue;
			}
			//check user rights to restore his course in cateogry
			$taskcategorycontext = null;
			if($task->internalcategory == 0){
				$taskcategorycontext = $defaultcategorycontext;
			}else {
				$internalcategoryrecord=$DB->get_record('course_categories', array('id'=>$task->internalcategory));
				if(!$internalcategoryrecord){
					$task_object->add_error(get_string('notexistinginternalcategory','block_my_external_backup_restore_courses',$task_object->get_lang_object()));
				}else{
					$taskcategorycontext = context_coursecat::instance($task->internalcategory);
				}	
				
			}
			if($task->internalcategory !=0 && ($taskcategorycontext==null || !has_capability('moodle/course:create', $taskcategorycontext,$task->userid))){
				//trying to check if ok in defaultcategory context
				$task_object->add_error(get_string('cantrestorecourseincategorycontext','block_my_external_backup_restore_courses',$task_object->get_lang_object()));
				//changing category
				$task->internalcategory = $defaultcategoryid;
				if(!has_capability('moodle/course:create', $defaultcategorycontext,$task->userid)){
					$task_object->add_error(get_string('cantrestorecourseindefaultcategorycontext','block_my_external_backup_restore_courses',$task_object->get_lang_object()));
					$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
					$errors->add_errors($task_object->get_errors());
					$errors->notify_errors();
					continue;
				}
			}
			else if($task->internalcategory==0 && !has_capability('moodle/course:create', $defaultcategorycontext,$task->userid)){
						$task_object->add_error(get_string('cantrestorecourseindefaultcategorycontext','block_my_external_backup_restore_courses',$task_object->get_lang_object()));
						$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
						$errors->add_errors($task_object->get_errors());
						$errors->notify_errors();
						continue;
			}
			$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS);
			$result = $task_object->download_external_backup_courses($username);
			if($result){
				$result = $task_object->restore_course_from_backup_file($defaultcategoryid);
				if($result){
					$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_PERFORMED);
					$task_object->notify_success();
				}
				
			}
			if(!$result){
				$task_object->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
				$errors->add_errors($task_object->get_errors());
				$errors->notify_errors();
			}
			//need to delete temp file success or failed cases
			if(file_exists($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME)){
				unlink($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME);
			}

			
			
		}
		
		return true;
	}
	public static function retrieve_tasks($defaultcategoryid){
		global $DB;
		return $DB->get_records_sql("select beb.*,cat.name as internalcategoryname  ,dcat.name as defaultcategoryname from {block_external_backuprestore} beb left join {course_categories} cat on cat.id=beb.internalcategory left join {course_categories} dcat on dcat.id=:default where status=:status order by beb.timecreated asc", array('status'=>block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED, 'default'=>$defaultcategoryid));
	}
}
class block_my_external_backup_restore_courses_task{
	private $task/*stdclass id, userid,externalcourseid,externalmoodleurl,externalmoodlesitename,externalmoodletoken,internalcategory,status*/=null;
	private $task_errors = array();
	public function __construct($task){
		$this->task = $task;
		$this->task->username = $this->get_username();
	}
	protected function enrol_editingteacher($courseid){
		global $DB;
		$instance = $this->get_manual_enrol($courseid);
		$role = $DB->get_record('role', array('shortname'=> 'editingteacher'));
		$this->enrol_user(enrol_get_plugin('manual'), $instance, $role->id);
	}
	public function retrieve_external_moodle_name(){
		global $CFG;
		$functionname = 'core_webservice_get_site_info';
		$params=array();
		$siteinfo = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($this->task->externalmoodleurl, $this->task->externalmoodletoken, $functionname, $params, $restformat='json', $method='post');
		$sitename = $siteinfo->sitename;
		if(empty($sitename)){
			$this->task_errors[]=new block_my_external_backup_restore_courses_task_error($this->task,'$sitename : no response');
			return false;
		}
		return $sitename;
	}
	public function download_external_backup_courses($username) {
		global $CFG;
		$functionname = 'block_my_external_backup_restore_courses_get_courses_zip';
		$params = array('username' => $username, 'courseid' => $this->task->externalcourseid);
		$file_returned = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client($this->task->externalmoodleurl, $this->task->externalmoodletoken, $functionname, $params, $restformat='json', $method='post');
		if(empty($file_returned)){
			$this->task_errors[]=new block_my_external_backup_restore_courses_task_error($this->task,'file retrieve : no response');
			return false;
		}
		// DOWNLOAD File
		$url = $this->task->externalmoodleurl . '/blocks/my_external_backup_restore_courses/get_user_backup_course_webservice.php'; //NOTE: normally you should get this download url from your previous call of core_course_get_contents()
		$url .= '?token=' . $this->task->externalmoodletoken; //NOTE: in your client/app don't forget to attach the token to your download url
		$url .= '&filerecordid='.$file_returned->filerecordid;
		//serve file
		return $this->download_backup_course($url);
	}
	public function restore_course_from_backup_file($defaultcategoryid) {
		global $CFG, $DB;
		$categoryid = $this->task->internalcategory==0?$defaultcategoryid:$this->task->internalcategory; 
		require_once($CFG->dirroot . "/backup/util/includes/backup_includes.php");
		require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");
		require_once($CFG->dirroot.'/backup/util/loggers/base_logger.class.php');
		require_once($CFG->dirroot.'/backup/util/loggers/output_text_logger.class.php');
		//check if category is OK
		//temp dir
		if (empty($CFG->tempdir)) {
			$CFG->tempdir = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
		}
		//look for file
		$archivefile = $CFG->tempdir . DIRECTORY_SEPARATOR. "backup" . DIRECTORY_SEPARATOR . block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME;
		$path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR .block_my_external_backup_restore_courses_task_helper::BACKUP_TEMPDIRNAME;
		//unlink path just in case
		if(file_exists($path)) {
			block_my_external_backup_restore_courses_tools::delTree($path);
		}
		$fp = get_file_packer('application/vnd.moodle.backup');
		$fp->extract_to_pathname($archivefile, $path);
		if(!$fp->list_files($archivefile)){
			$this->task_errors[]=new block_my_external_backup_restore_courses_task_error($this->task,'file retrieve : no files in zip');
			return false;
		}
		// Get unique shortname if creating new course. from xml
		$xmlfile = $path . DIRECTORY_SEPARATOR . "course" . DIRECTORY_SEPARATOR . "course.xml";
		$xml = simplexml_load_file($xmlfile);
		$fullname = $xml->xpath('/course/fullname');
		if (!$fullname) {
			$fullname = $xml->xpath('/MOODLE_BACKUP/COURSE/HEADER/FULLNAME');
		}
		$shortname = $xml->xpath('/course/shortname');
		if (!$shortname) {
			$shortname = $xml->xpath('/MOODLE_BACKUP/COURSE/HEADER/SHORTNAME');
		}
		$fullname = (string)($fullname[0]);
		$shortname = (string)($shortname[0]);
		//restore
		$courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
		try {
            $rc = new restore_controller(block_my_external_backup_restore_courses_task_helper::BACKUP_TEMPDIRNAME, $courseid, backup::INTERACTIVE_NO,
                backup::MODE_GENERAL, $this->task->userid, backup::TARGET_NEW_COURSE);
        }catch(restore_controller_exception $re){
		    print_error($re->getMessage()." ".$re->a->capability." for user ".$re->a->userid);
        }
		if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
			$rc->convert();
		}

        if (!$rc->execute_precheck()) {
            $check = $rc->get_precheck_results();
            print_error("Restore failed : ".var_dump($check));
            die();
        }

		$rc->execute_plan();
        $rc->destroy();
		$logs = $DB->get_records_sql('select * from {backup_logs} where backupid=:backupid and (loglevel=:warning or loglevel=:error)', array('backupid'=> $rc->get_restoreid(),'warning'=> backup::LOG_WARNING, 'error'=> backup::LOG_ERROR));
		if($logs){
			foreach($logs as $log){
				$this->task_errors[] = new block_my_external_backup_restore_courses_task_error($this->task, $log->message);
			}
		}
		//delete file
		if(file_exists($archivefile)){
			unlink($archivefile);
		}
		if(file_exists($path)){
			unlink($path);
		}
		$this->enrol_editingteacher($courseid);
		return true;
	}
	protected function download_backup_course($url) {
		global $CFG;
		ignore_user_abort(true);
		set_time_limit(0);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		
		//prepare file
		if (empty($CFG->tempdir)) {
			$CFG->tempdir = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
		}
		//check if backup directory exists,if not create it
		check_dir_exists($CFG->tempdir . '/backup');
		//first ulink just in case
		if(file_exists($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME)){
			unlink($CFG->tempdir . DIRECTORY_SEPARATOR. "backup" . DIRECTORY_SEPARATOR  .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME);
		}
			
		$fp = fopen($CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME, 'w');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		//execute curl
		
		$out = curl_exec($ch);
		fclose($fp);
		$details = curl_getinfo($ch);
		curl_close($ch);
		
		if(!$out){
			$this->task_errors[]=new block_my_external_backup_restore_courses_task_error($this->task,'backup course file not generated');
			return false;
		}
		return true;
	}
	public function change_task_status($status){
		global $DB;
		$this->task->status = $status;
		$this->task->timescheduleprocessed = time();
		$DB->update_record('block_external_backuprestore', $this->task);
	}
	public function get_username(){
		global $DB;
		$user = $DB->get_record('user', array('id'=>$this->task->userid));
		if(!$user){
			return false;
		}
		return $user->username;
	}
	public function get_user(){
		global $DB;
		$user = $DB->get_record('user', array('id'=>$this->task->userid));
		return $user;
	}
	protected function get_manual_enrol($courseid){
		global $DB;
		$instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'));//only one istance allowed
		$course = $DB->get_record('course', array('id'=>$courseid));
		if(!$instance){
			//create new instance
			$enrol_manual = enrol_get_plugin('manual');
			$fields = array(
					'status'          => 0,
					'roleid'          => $enrol_manual->get_config('roleid'),
					'enrolperiod'     => $enrol_manual->get_config('enrolperiod'),
					'expirynotify'    => $enrol_manual->get_config('expirynotify'),
					'notifyall'       => 0,
					'expirythreshold' => $enrol_manual->get_config('expirythreshold')
			);
			$instanceid = $enrol_manual->add_instance($course, $fields);
			$instance = $DB->get_record('enrol', array('id'=>$instanceid));
		}
		return $instance;
	}
	protected function enrol_user($enrol_plugin, $instance, $roleid){
		$enrol_plugin->enrol_user($instance, $this->task->userid, $roleid);
	}
	public function get_errors(){
        return $this->task_errors;
    }
    public function get_lang_object(){
    	global $SITE, $CFG, $DB;
    	$langobject = new stdClass();
    	$langobject->externalcourseid=$this->task->externalcourseid;
    	$langobject->externalmoodleurl = $this->task->externalmoodleurl;
    	$langobject->externalmoodlesitename = $this->task->externalmoodlesitename;
    	$langobject->userid=$this->task->userid;
    	$langobject->internalcategory=$this->task->internalcategory;
    	$langobject->status=$this->task->status;
    	$langobject->externalcoursename = $this->task->externalcoursename; 
    	$langobject->internalcategoryname = !isset($this->task->internalcategoryname) || empty($this->task->internalcategoryname)? $this->task->externalcoursename : $this->task->internalcategoryname;
    	$langobject->defaultcategoryname = $this->task->defaultcategoryname;
    	$langobject->site = $SITE->fullname;
    	$langobject->siteurl = $CFG->wwwroot;
    	$langobject->username = $this->task->username;
    	//special parameters
    	$includeexternalurlinmail = get_config('block_my_external_backup_restore_courses','includeexternalurlinmail');
    	if($includeexternalurlinmail == 1 && $this->task->externalmoodlesitename !==false){
    		$langobject->externalmoodle = get_string('mailexternalmoodleinfo','block_my_external_backup_restore_courses',$langobject);
    	}else{
    		$langobject->externalmoodle = $this->task->externalmoodlesitename;
    	}
    	$langobject->localmoodle = get_string('maillocalmoodleinfo','block_my_external_backup_restore_courses',$langobject);
    	return $langobject;
    }
    public function notify_success(){
    	global $SITE, $CFG;
    	//current messaging
    	$eventdata = new \core\message\message();
    	$eventdata->component = 'block_my_external_backup_restore_courses';
    	$eventdata->name = 'restorationsuccess';
    	$eventdata->userfrom = core_user::get_noreply_user() ;
    	$eventdata->subject = get_string('success_mail_subject','block_my_external_backup_restore_courses');
    	$eventdata->fullmessageformat = FORMAT_HTML;
    	$eventdata->notification = '0';
    	$eventdata->contexturl = $CFG->wwwroot;
    	$eventdata->contexturlname = $SITE->fullname;
    	$eventdata->replyto = core_user::get_noreply_user();
    	$eventdata->fullmessage=get_string('success_mail_main_message','block_my_external_backup_restore_courses',$this->get_lang_object());
    	$eventdata->fullmessagehtml =str_replace('\n', '<br/>',$eventdata->fullmessage);
    	//for owner
    	$eventdata->userto = $this->get_user();
    	
    	//for admins or if setting ok for users
    	$errors = $this->get_errors();
    	$fullmessage='';
    	foreach($errors as $error){
    		$fullmessage .= get_string('error_mail_task_error_message'.($error->_get('courseid')==0?'':'_courseid'),'block_my_external_backup_restore_courses',$error->get_lang_object());
    	}
    	$warningstoowner=get_config('block_my_external_backup_restore_courses','warningstoowner');
    	if($warningstoowner ==1){
    		$eventdata->fullmessage.=$fullmessage;
    		$eventdata->fullmessagehtml =str_replace('\n', '<br/>',$eventdata->fullmessage);
    	}
    	//send message to task owner    	
    	$result = message_send($eventdata);
    	
    	//for admins
    	if($warningstoowner !=1){
    		$eventdata->fullmessage.=$fullmessage;
    		$eventdata->fullmessagehtml =str_replace('\n', '<br/>',$eventdata->fullmessage);
    	}
    	$admins = get_admins();
    	foreach($admins as $admin){
    		$eventdata->userto = $admin;
    		$result = message_send($eventdata);
    	}
    }
    public function add_error($message){
    	$this->task_errors[]=new block_my_external_backup_restore_courses_task_error($this->task,$message);
    }
}
	

class block_my_external_backup_restore_courses_task_error extends stdClass{

	private $externalcourseid=0;
	private $externalmoodleurl = null;
	private $externalmoodlesitename = null;
	private $courseid = 0;
	private $message = null;
	private $usernameorid=null;
	private $user=false;
	private $externalcoursename=null;
	private $internalcategoryname = null;
	private $defaultcategoryname = null;
	
	
	public function _get($property){
		return $this->$property;
	}
	
	public function __construct($task,$message,$courseid=0) {
		$this->externalcourseid = $task->externalcourseid;
		$this->externalmoodleurl = $task->externalmoodleurl;
		$this->externalmoodlesitename = property_exists($task, 'externalmoodlesitename')?$task->externalmoodlesitename:get_string('NA','block_my_external_backup_restore_courses');
		$this->externalcourseid = $task->externalcourseid;
		$this->courseid = $courseid;
		$this->usernameorid = $task->userid;
		$this->externalcoursename = $task->externalcoursename;
		$this->message = $message;
		$this->internalcategoryname = $task->internalcategoryname;
		$this->defaultcategoryname = $task->defaultcategoryname;
		mtrace($message);
	}
	public function get_user(){
		global $DB;
		if(!$this->user){
			if(is_numeric($this->usernameorid)){
				$this->user = $DB->get_record('user', array('id'=>$this->usernameorid));
			}else{
				$this->user = $DB->get_record('user', array('username'=>$this->usernameorid));
			}
		}
		return $this->user;
	}
	//because of cast ()array problem in get_string 
	public function get_lang_object(){
		global $SITE,$CFG;
		$langobject = new stdClass();
		$langobject->externalcourseid=$this->externalcourseid;
		$langobject->externalmoodleurl = $this->externalmoodleurl;
		$langobject->externalmoodlesitename = $this->externalmoodlesitename;
		$langobject->courseid = $this->courseid;
		$langobject->message = $this->message;
		$langobject->usernameorid=$this->usernameorid;
		$langobject->username=$this->usernameorid;
		$langobject->externalcoursename = $this->externalcoursename;
		$langobject->internalcategoryname = !isset($this->internalcategoryname) || empty($this->internalcategoryname)? $this->externalcoursename : $this->internalcategoryname;
		$langobject->defaultcategoryname = $this->defaultcategoryname;
		$langobject->site = $SITE->fullname;
		$langobject->siteurl = $CFG->wwwroot;
		//special parameters
		$includeexternalurlinmail = get_config('block_my_external_backup_restore_courses','includeexternalurlinmail');
		if($includeexternalurlinmail == 1 && $this->externalmoodlesitename!== false){
			$langobject->externalmoodle = get_string('mailexternalmoodleinfo','block_my_external_backup_restore_courses',$langobject);
		}else{
			$langobject->externalmoodle = $this->externalmoodlesitename;
		}
		$langobject->localmoodle = get_string('maillocalmoodleinfo','block_my_external_backup_restore_courses',$langobject);
		return $langobject;
	}
	
}
class block_my_external_backup_restore_courses_task_error_list {

	private $task_errors=array();
	public function add_error($task/*block_my_external_backup_restore_courses_task_error*/){
		$this->task_errors[] = $task;
	}
	public function add_errors($tasks){
		$this->task_errors = array_merge($this->task_errors, $tasks);
	}
	public function has_errors(){
		return count($this->task_errors);
	}
	public function format_error_for_admin($username=null,$externalcourseid=null){
		$text='';
		foreach($this->task_errors as $task_error){
			if( ($username == null || $task_error->username == $username) && ($externalcourseid=null || $task_error->externalcourseid == $externalcourseid)){
				$text.=get_string('error_msg_admin', 'block_my_external_backup_restore_courses', array('externalcourseid'=>$task_error->externalcourseid,'courseid'=>$task_error->courseid,'externalmoodleurl'=>$task_error->externalmoodleurl,'externalmoodlesitename'=>$task_error->externalmoodlesitename,'user'=>$task_error->user,'message'=>$message));
			}
		}
	}
	public function notify_errors(){
		global $SITE,$CFG,$DB;
		//error message
		if(count($this->task_errors)){
			$eventdata=null;
			$fullmessage = '';
			$user= false;
			foreach($this->task_errors as $task_error){
				if($eventdata == null){
					$user = $task_error->get_user();
					if(!$user){
						print_error('user '.$task_error->_get('usernameorid').' not found');
					}
					//current messaging
					$eventdata = new \core\message\message();
					$eventdata->component = 'block_my_external_backup_restore_courses';
					$eventdata->name = 'restorationfailed';
					$eventdata->userfrom = core_user::get_noreply_user() ;
					$eventdata->subject = get_string('error_mail_subject','block_my_external_backup_restore_courses');
					$eventdata->fullmessageformat = FORMAT_HTML;
					$eventdata->notification = '0';
					$eventdata->contexturl = $CFG->wwwroot;
					$eventdata->contexturlname = $SITE->fullname;
					$eventdata->replyto = core_user::get_noreply_user();
					$eventdata->fullmessage=get_string('error_mail_main_message','block_my_external_backup_restore_courses',$task_error->get_lang_object());				
				}
				$fullmessage .= get_string('error_mail_task_error_message'.($task_error->_get('courseid')==0?'':'_courseid'),'block_my_external_backup_restore_courses',$task_error->get_lang_object());
			}
			//for owner
			$eventdata->userto = $user;
			$eventdata->fullmessagehtml =str_replace('\n', '<br/>',$eventdata->fullmessage); 
			$result = message_send($eventdata);
			//for admins
			$admins = get_admins();
			$eventdata->fullmessage .= $fullmessage;
			$eventdata->fullmessagehtml =str_replace('\n', '<br/>',$eventdata->fullmessage);
			foreach($admins as $admin){
				$eventdata->userto = $admin;
				$result = message_send($eventdata);
			}
			
		}
	}


}