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
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

class block_my_external_backup_restore_courses_tools{
    const STATUS_SCHEDULED = 0;
    const STATUS_INPROGRESS = 1;
    const STATUS_PERFORMED = 2;
    const STATUS_ERROR = -1;

    public static function  del_tree   ($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::del_tree   ("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function enrol_get_courses_with_role($courseid, $userid, $roleid) {
        global $DB;
        $sql = 'SELECT distinct e.id
                   FROM {enrol} e
                   inner JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                   inner JOIN {course} c ON (c.id = e.courseid)
                   inner join {context} ctx on ctx.instanceid = c.id and ctx.contextlevel=:coursecontextlevel
                   inner join {role_assignments} ra on ra.contextid=ctx.id and ra.userid=:userid
                   inner join {role} r on ra.roleid=r.id and r.id=:roleid
                  WHERE c.id=:courseid';
        try {
            return $DB->get_records_sql($sql,
                    array('coursecontextlevel' => CONTEXT_COURSE,
                            'userid' => $userid,
                            'roleid' => $roleid,
                            'courseid' => $courseid
                    ));
        } catch (Exception $ex) {
            print_error(var_dump($ex));
            return false;
        }

    }
    public static function external_backup_course_sitename($domainname, $token) {
        $siteinfo = null;
        try {
            $siteinfo = self::rest_call_external_courses_client($domainname, $token, 'core_webservice_get_site_info');
        } catch (Exception $e) {
            throw new Exception('site name can \'t be retrieved : '.$e->getMessage());
        }
        $sitename = $siteinfo->sitename;
        if (!isset($sitename)) {
            throw new Exception('site name can \'t be retrieved');
        }
        return $sitename;
    }
    public static function get_all_users_courses($username, $onlyactive = false,
                                                 $fields = null,
                                                 $sort = 'visible DESC,sortorder ASC') {
        global $DB;
        $config = get_config('block_my_external_backup_restore_courses');
        $restorecourseinoriginalcategory = $config->restorecourseinoriginalcategory;
        $categorytable = $config->categorytable;
        $categorytableforeignkey = $config->categorytable_foreignkey;
        $categorytablecategoryfield = $config->categorytable_categoryfield;
        $categoryselect = '';
        $categoryjoin = '';
        $categorywhere = '';

        if ($restorecourseinoriginalcategory == 1 && !empty($categorytable)
            && !empty($categorytableforeignkey) && !empty($categorytablecategoryfield)) {
            $categoryselect = ", ct.$categorytablecategoryfield as categoryidentifier ";
            $categoryjoin = " left join {".$categorytable."} ct on ct.$categorytableforeignkey=c.category ";
            $categorywhere = " and ct.$categorytableforeignkey=c.category ";
        }

        // Guest account does not have any courses.
        $userrecord = $DB->get_record('user', array('username' => $username));
        if (!$userrecord) {
            throw new block_my_external_backup_restore_courses_invalid_username_exception('user with username not found');
        }

        $userid = $userrecord->id;

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
            // Turn the fields from a string to an array.
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
            $subwhere =
                ' AND  ue.status = :active AND e.status = :enabled'
                .' AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)';
            $params['now1']    = round(time(), -2); // Improves db caching.
            $params['now2']    = $params['now1'];
            $params['active']  = ENROL_USER_ACTIVE;
            $params['enabled'] = ENROL_INSTANCE_ENABLED;
        } else {
            $subwhere = "";
        }

        $coursefields = 'c.'.join(',c.', $fields);
        $select = ", " . context_helper::get_preload_record_columns_sql('ctx');
        $join = "LEFT JOIN mdl_context ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")";
        list($ccselect, $ccjoin) = array($select, $join);

        $newformattedroles = self::get_formatted_concerned_roles_shortname();
        if (count($newformattedroles) == 0) {
            return false;
        }

        // Note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there.
        $sql = "SELECT $coursefields $ccselect $categoryselect
                      FROM {course} c
                      INNER JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")
                      INNER JOIN (
                        SELECT ra.contextid AS contextid, usr.firstname AS firstname, usr.lastname AS lastname
                        FROM {role_assignments} ra
                        INNER JOIN {role} r ON (r.id = ra.roleid and r.shortname IN (".implode(',', $newformattedroles)."))
                        INNER JOIN {user} usr ON (ra.userid = usr.id AND usr.id = $userid AND usr.deleted=0)
                        ) AS u ON (u.contextid = ctx.id)
                    $categoryjoin
                    WHERE c.id <> ".SITEID.$categorywhere
                 ." UNION
                SELECT $coursefields $ccselect $categoryselect
                    FROM {course} c
                    INNER JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")
                    INNER JOIN {enrol} e ON e.enrol = 'category' AND e.courseid = c.id
                    INNER JOIN (SELECT cctx.path, ra.userid, MIN(ra.timemodified) AS estart, ra.roleid as roleid
                                FROM {course_categories} cc
                                JOIN {context} cctx ON (cctx.instanceid = cc.id AND cctx.contextlevel = ".CONTEXT_COURSECAT.")
                                JOIN {role_assignments} ra ON (ra.contextid = cctx.id)
                                JOIN {role} ro ON (ra.roleid = ro.id and ro.shortname in (".implode(',', $newformattedroles)."))
                                GROUP BY cctx.path, ra.userid, ra.roleid
                      ) cat ON (ctx.path LIKE cat.path || '/%')
                    INNER JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cat.userid)
                    INNER JOIN {user} u ON u.id = cat.userid AND u.id = ue.userid AND u.deleted=0
                    INNER JOIN {role} r ON r.id = cat.roleid AND r.shortname IN (".implode(',', $newformattedroles).")
                    $categoryjoin
                    WHERE u.id = $userid AND c.id <> ".SITEID.$categorywhere
                      .$subwhere." ".$orderby;

        $courses = $DB->get_records_sql($sql, $params);
        return $courses;
    }

    public static function print_content() {
        global $OUTPUT;
        $output = '';
        $externalmoodles = get_config('block_my_external_backup_restore_courses', 'external_moodles');
        if (isset($externalmoodles) && !empty($externalmoodles)) {
            $externalmoodles = explode(';', $externalmoodles);
            if (count($externalmoodles) > 0) {
                $backupcoursesurl = new moodle_url('/blocks/my_external_backup_restore_courses/index.php');
                $output = html_writer::link($backupcoursesurl, get_string('restorecourses',
                    'block_my_external_backup_restore_courses'));
            }
        }
        return $output;
    }

    public static function rest_call_external_courses_client($domainname, $token, $functionname, $params=array(),
                                                             $restformat='json', $method='get') {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/my_external_backup_restore_courses/locallib.php');
        require_once($CFG->dirroot.'/lib/filelib.php');
        require_once($CFG->dirroot.'/webservice/lib.php');
        $serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;
        $curl = new curl;
        // Ff rest format == 'xml', then we do not add the param for backward compatibility with Moodle < 2.2.
        $restformat = ($restformat == 'json') ? '&moodlewsrestformat=' . $restformat : '';
        if ($method == 'get') {
            $resp = $curl->get($serverurl . $restformat, $params);
        } else if ($method == 'post') {
            $resp = $curl->post($serverurl . $restformat, $params);
        }
        $resp = json_decode($resp);
        // Check if errors encountered.
        if (!isset($resp)) {
            throw new Exception($resp);
        }
        if (isset($resp->errorcode)) {
            if ($resp->exception == 'block_my_external_backup_restore_courses_invalid_username_exception') {
                throw new block_my_external_backup_restore_courses_invalid_username_exception($resp->errorcode);
            }
            throw new moodle_exception($resp->errorcode);
        }
        return $resp;
    }
    public static function get_authorized_repository_to_restore() {
        $authorizedrepositories = array();
        $config = get_config("block_my_external_backup_restore_courses");
        if ($config->authorizeremoterepositoryrestore && !empty($config->repositorytypestorestore)) {
            $repositorytypes = explode(';', $config->repositorytypestorestore);
            foreach ($repositorytypes as $repositorytype) {
                // Check repository exists and is activated.
                $repositorytypeobj = repository::get_type_by_typename($repositorytype);
                if (isset($repositorytypeobj) && $repositorytypeobj->get_visible()) {
                    array_push($authorizedrepositories, $repositorytypeobj->get_typename());
                }
            }
        }
        return $authorizedrepositories;
    }

    public static function get_formatted_concerned_roles_shortname() {
        $config = get_config("block_my_external_backup_restore_courses");
        $roles = $config->search_roles;
        if (empty($roles)) {
            return array();
        }
        $roles = explode(',', $roles);
        $newformattedroles = array();
        foreach ($roles as $key => $role) {
            $newformattedroles[] = '\''.$role.'\'';
        }
        return $newformattedroles;
    }

    public static function get_concerned_roles_shortname() {
        $config = get_config("block_my_external_backup_restore_courses");
        $roles = $config->search_roles;
        $roles = str_replace("'", '', $roles);
         return empty($roles) ? array() : explode(',', $roles);
    }

    public static function format_string_list_for_sql($stringlist, $delimiter=',') {
        $list = explode($delimiter, $stringlist);
        foreach ($list as $index => $element) {
            $list[$index] = '\''.$element.'\'';
        }
        return implode($delimiter, $list);
    }

    public static function is_repository_authorized_to_restore($repositorytype) {
        $authorizedrepositories = self::get_authorized_repository_to_restore();
        if (in_array($repositorytype, $authorizedrepositories)) {
            return true;
        }
        return false;
    }

    public static function external_course_restored_or_on_way_by_other_users($externalcourseid, $externalmoodleurl, $localuserid) {
        global $DB;
        $sql = 'select b.*, u.username, u.lastname, u.firstname from {block_external_backuprestore} b
                    inner join {user} u on u.id=b.userid
                    where externalcourseid=:externalcourseid and externalmoodleurl=:externalmoodleurl
                        and userid<>:localuserid and status>:errorstatus';
        $alreadyrestoredcourses = $DB->get_records_sql($sql,
                    array('externalcourseid' => $externalcourseid, 'externalmoodleurl' => $externalmoodleurl,
                            'localuserid' => $localuserid,
                            'errorstatus' => self::STATUS_ERROR));
        return $alreadyrestoredcourses;
    }

    public static function get_other_users_for_course_restored_or_on_way_by_other_users($externalcourseid,
            $externalmoodleurl, $localuserid
    ) {
        global $DB;
        $sql = 'select * from {block_external_backuprestore}
                    where externalcourseid=:externalcourseid and externalmoodleurl=:externalmoodleurl
                        and userid<>:localuserid and status>:errorstatus;';
        $alreadyrestoredcourses = $DB->get_records($sql,
                array('externalcoursename' => $externalcourseid,
                        'externalmoodleurl' => $externalmoodleurl,
                        'userid' => $localuserid,
                        'errorstatus' => self::STATUS_ERROR
                ));
        if (!$alreadyrestoredcourses) {
            return null;
        }
        $concernedusers = array();
        foreach ($alreadyrestoredcourses as $alreadyrestoredcourse) {
            $currentuser = $DB->get_record('user', array('id' => $alreadyrestoredcourse->id));
            $concernedusers[$currentuser->username] = $currentuser;
        }
        return $concernedusers;
    }

    public static function array_contains_object_with_properties($array, $propertyname, $values) {
        foreach ($array as $elt) {
            if (in_array($elt->$propertyname, $values)) {
                return true;
            }
        }
        return false;
    }


}

class block_my_external_backup_restore_courses_invalid_username_exception extends moodle_exception {
    /**
     * Constructor
     * @param string $debuginfo some detailed information
     */
    public function __construct($debuginfo=null) {
        parent::__construct('invalidusername', 'debug', '', null, $debuginfo);
    }
}

abstract class block_my_external_backup_restore_courses_task_helper{
    const BACKUP_FILENAME = 'currentbackupedcourse.mbz';
    const BACKUP_TEMPDIRNAME = 'currentbackupedcourse';
    public static function run_automated_backup_restore() {
        global $CFG, $SITE, $DB;
        $config = get_config('block_my_external_backup_restore_courses');
        $timelimitedmod = $config->timelimitedmod == 1 ? true : false;
        $errors = new block_my_external_backup_restore_courses_task_error_list();
        // This could take a while!
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);
        $defaultcategoryid = get_config('block_my_external_backup_restore_courses', 'defaultcategory');
        if (empty($defaultcategoryid)) {
            $errors->add_error(new block_my_external_backup_restore_courses_task_error(null,
                'defaultcategoryid not defined for my_external_backup_restore_courses plugin, please correct this.'));
            $errors->notify_errors();
            return false;
        }
        // Check that category exists.
        $cat = $DB->get_record('course_categories', array('id' => $defaultcategoryid));
        if (!$cat) {
            $errors->add_error(new block_my_external_backup_restore_courses_task_error(null,
                'defaultcategoryid not defined for my_external_backup_restore_courses plugin, please correct this.'));
            $errors->notify_errors();
            return false;
        }
        $tasks = self::retrieve_tasks($defaultcategoryid);
        $defaultcategorycontext = context_coursecat::instance($defaultcategoryid);
        $externalmoodlesitenames = array();
        foreach ($tasks as $task) {
            if ($timelimitedmod) {
                $currenttime = new DateTime();
                $limitstarttime = clone $currenttime;
                $limitstarttime->setTimezone(core_date::get_server_timezone_object());
                $limitstarttime->setTime($config->limitstart_hour, $config->limitstart_minute);
                $limitendtime = clone $currenttime;
                $limitendtime->setTimezone(core_date::get_server_timezone_object());
                $limitendtime->setTime($config->limitend_hour, $config->limitend_minute);
                if ($limitstarttime > $limitendtime) {
                    // Changing day during interval.
                    // Starttime in day before.
                    $limitstarttime->sub(new DateInterval('P1D'));
                }

                if ($limitstarttime == $limitendtime || ($currenttime > $limitstarttime && $currenttime > $limitendtime)) {
                    $errors->add_error(new block_my_external_backup_restore_courses_task_error($task,
                        'execution time outdated'));
                    return true;
                }
            }

            $errors = new block_my_external_backup_restore_courses_task_error_list();
            $taskobject = new block_my_external_backup_restore_courses_task($task);
            // Search externalmoodlesitename.
            if (!array_key_exists($task->externalmoodleurl, $externalmoodlesitenames)) {
                $sitename = $taskobject->retrieve_external_moodle_name();
                $externalmoodlesitenames[$task->externalmoodleurl] = $sitename;
                $task->externalmoodlesitename = $sitename;
            } else {
                $task->externalmoodlesitename = $externalmoodlesitenames[$task->externalmoodleurl];
            }
            $username = $taskobject->get_username();
            $user = $DB->get_record('user', array('username' => $username));
            if (!$username) {
                $errors->add_error(new block_my_external_backup_restore_courses_task_error($task,
                    'user not found for task : '.$task->id));
                $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
                $errors->add_errors($task->get_errors());
                $errors->notify_errors();
                continue;
            }
            // Check user rights to restore his course in cateogry.
            $taskcategorycontext = null;
            if ($task->internalcategory == 0) {
                $taskcategorycontext = $defaultcategorycontext;
            } else {
                $internalcategoryrecord = $DB->get_record('course_categories', array('id' => $task->internalcategory));
                if (!$internalcategoryrecord) {
                    $taskobject->add_error(get_string('notexistinginternalcategory',
                        'block_my_external_backup_restore_courses',
                        $taskobject->get_lang_object()));
                } else {
                    $taskcategorycontext = context_coursecat::instance($task->internalcategory);
                }
            }
            if ($task->internalcategory != 0 && ($taskcategorycontext == null
                    || !has_capability('moodle/course:create', $taskcategorycontext, $task->userid))) {
                // Trying to check if ok in defaultcategory context.
                $taskobject->add_error(get_string('cantrestorecourseincategorycontext',
                    'block_my_external_backup_restore_courses', $taskobject->get_lang_object()));
                // Changing category.
                $task->internalcategory = $defaultcategoryid;
                if (!has_capability('moodle/course:create', $defaultcategorycontext, $task->userid)) {
                    $taskobject->add_error(get_string('cantrestorecourseindefaultcategorycontext',
                        'block_my_external_backup_restore_courses', $taskobject->get_lang_object()));
                    $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
                    $errors->add_errors($taskobject->get_errors());
                    $errors->notify_errors();
                    continue;
                }
            } else if ($task->internalcategory == 0 && !has_capability('moodle/course:create',
                    $defaultcategorycontext, $task->userid)) {
                        $taskobject->add_error(get_string('cantrestorecourseindefaultcategorycontext',
                            'block_my_external_backup_restore_courses', $taskobject->get_lang_object()));
                        $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
                        $errors->add_errors($taskobject->get_errors());
                        $errors->notify_errors();
                        continue;
            }
            $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS);
            $result = $taskobject->download_external_backup_courses($username);
            if ($result) {
                $result = $taskobject->restore_course_from_backup_file($defaultcategoryid);
                if (!empty($result)) {
                    $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_PERFORMED);
                    $taskobject->set_local_courseid($result);
                    $taskobject->notify_success();
                }
            }
            if (!$result) {
                $taskobject->change_task_status(block_my_external_backup_restore_courses_tools::STATUS_ERROR);
                $errors->add_errors($taskobject->get_errors());
                $errors->notify_errors();
            }
            // Need to delete temp file success or failed cases.
            if (file_exists($CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR.self::BACKUP_FILENAME)) {
                unlink($CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR.self::BACKUP_FILENAME);
            }
        }
        return true;
    }

    public static function retrieve_tasks($defaultcategoryid) {
        global $DB;
        return $DB->get_records_sql(
            "select beb.*,cat.name as internalcategoryname ,dcat.name as defaultcategoryname
                    from {block_external_backuprestore} beb left join {course_categories} cat on cat.id=beb.internalcategory
                        left join {course_categories} dcat on dcat.id=:default
                    where status=:status order by beb.timecreated asc",
            array('status' => block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED, 'default' => $defaultcategoryid));
    }
}
class block_my_external_backup_restore_courses_task{
    private $task/*stdclass id, userid,externalcourseid,externalmoodleurl,externalmoodlesitename,externalmoodletoken,internalcategory,status*/ = null;
    private $taskerrors = array();
    public function __construct($task) {
        $this->task = $task;
        $this->task->username = $this->get_username();
    }
    protected function enrol_editingteacher($courseid) {
        global $DB;
        $instance = $this->get_manual_enrol($courseid);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->enrol_user(enrol_get_plugin('manual'), $instance, $role->id);
    }
    public function retrieve_external_moodle_name() {
        global $CFG;
        $functionname = 'core_webservice_get_site_info';
        $params = array();
        $siteinfo = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client(
            $this->task->externalmoodleurl, $this->task->externalmoodletoken, $functionname,
            $params, $restformat = 'json', $method = 'post');
        $sitename = $siteinfo->sitename;
        if (empty($sitename)) {
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                '$sitename : no response');
            return false;
        }
        return $sitename;
    }
    public function download_external_backup_courses($username) {
        global $CFG;
        $functionname = 'block_my_external_backup_restore_courses_get_courses_zip';
        $params = array('username' => $username, 'courseid' => $this->task->externalcourseid);
        $filereturned = block_my_external_backup_restore_courses_tools::rest_call_external_courses_client(
            $this->task->externalmoodleurl, $this->task->externalmoodletoken,
            $functionname, $params, $restformat = 'json', $method = 'post');
        if (empty($filereturned)) {
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                'file retrieve : no response');
            return false;
        }
        // DOWNLOAD File.
        $url = $this->task->externalmoodleurl.'/blocks/my_external_backup_restore_courses/get_user_backup_course_webservice.php';
        // NOTE: normally you should get this download url from your previous call of core_course_get_contents().
        $url .= '?token=' . $this->task->externalmoodletoken;
        // NOTE: in your client/app don't forget to attach the token to your download url.
        $url .= '&filerecordid='.$filereturned->filerecordid;
        // Serve file.
        return $this->download_backup_course($url);
    }
    public function restore_course_from_backup_file($defaultcategoryid) {
        global $CFG, $DB;
        $categoryid = $this->task->internalcategory == 0 ? $defaultcategoryid : $this->task->internalcategory;
        require_once($CFG->dirroot . "/backup/util/includes/backup_includes.php");
        require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");
        require_once($CFG->dirroot.'/backup/util/loggers/base_logger.class.php');
        require_once($CFG->dirroot.'/backup/util/loggers/output_text_logger.class.php');
        // Check if category is OK.
        // Temp dir.
        if (empty($CFG->tempdir)) {
            $CFG->tempdir = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
        }
        // Look for file.
        $archivefile = $CFG->tempdir.DIRECTORY_SEPARATOR."backup"
            .DIRECTORY_SEPARATOR.block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME;
        $path = $CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR
            .block_my_external_backup_restore_courses_task_helper::BACKUP_TEMPDIRNAME;
        // Unlink path just in case.
        if (file_exists($path)) {
            block_my_external_backup_restore_courses_tools::del_tree($path);
        }
        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($archivefile, $path);
        if (!$fp->list_files($archivefile)) {
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                'file retrieve : no files in zip');
            return false;
        }
        // Get unique shortname if creating new course. from xml.
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
        // Restore.
        $courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
        try {
            $rc = new restore_controller(block_my_external_backup_restore_courses_task_helper::BACKUP_TEMPDIRNAME,
                $courseid, backup::INTERACTIVE_NO,
                backup::MODE_GENERAL, $this->task->userid, backup::TARGET_NEW_COURSE);
        } catch (restore_controller_exception $re) {
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                    $re->getMessage()." ".$re->a->capability." for user ".$re->a->userid);
            // Exit.
            return false;
        }
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }

        if (!$rc->execute_precheck()) {
            $check = $rc->get_precheck_results();
            $errormessage = '';
            $haserrors = false;
            if (is_array($check)) {
                $haserrors = array_key_exists('errors', $check);
                foreach ($check as $index => $messages) {
                    if ($index == '') {
                        $index = 'unknown level';
                    }
                    foreach ($messages as $message) {
                        $errormessage .= $index . '=' . $message . PHP_EOL;
                    }
                }
            } else {
                $errormessage = $check;
            }
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                    "Restore failed : ".PHP_EOL.$errormessage);
            // Exit.
            if ($haserrors) {
                return false;
            }
        }

        $rc->execute_plan();
        $rc->destroy();
        $logs = $DB->get_records_sql(
            'select * from {backup_logs} where backupid=:backupid and (loglevel=:warning or loglevel=:error)',
            array('backupid' => $rc->get_restoreid(), 'warning' => backup::LOG_WARNING, 'error' => backup::LOG_ERROR));
        if ($logs) {
            foreach ($logs as $log) {
                $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task, $log->message);
            }
        }
        // Delete file.
        if (file_exists($archivefile)) {
            unlink($archivefile);
        }
        if (file_exists($path)) {
            unlink($path);
        }
        $this->enrol_editingteacher($courseid);
        return $courseid;
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

        // Prepare file.
        if (empty($CFG->tempdir)) {
            $CFG->tempdir = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
        }
        // Check if backup directory exists,if not create it.
        check_dir_exists($CFG->tempdir . '/backup');
        // First ulink just in case.
        if (file_exists($CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR
            .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME)) {
            unlink($CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR
                .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME);
        }

        $fp = fopen($CFG->tempdir.DIRECTORY_SEPARATOR."backup".DIRECTORY_SEPARATOR
            .block_my_external_backup_restore_courses_task_helper::BACKUP_FILENAME, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        // Execute curl.

        $out = curl_exec($ch);
        fclose($fp);
        $details = curl_getinfo($ch);
        curl_close($ch);

        if (!$out) {
            $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task,
                'backup course file not generated');
            return false;
        }
        return true;
    }
    public function change_task_status($status) {
        global $DB;
        $this->task->status = $status;
        $this->task->timescheduleprocessed = time();
        $this->task->timemodified = $this->task->timescheduleprocessed;
        $DB->update_record('block_external_backuprestore', $this->task);
    }
    public function set_local_courseid($courseid) {
        global $DB;
        $this->task->courseid = $courseid;
        $this->task->timemodified = time();
        $DB->update_record('block_external_backuprestore', $this->task);
    }
    public function get_username() {
        global $DB;
        $user = $DB->get_record('user', array('id' => $this->task->userid));
        if (!$user) {
            return false;
        }
        return $user->username;
    }
    public function get_user() {
        global $DB;
        $user = $DB->get_record('user', array('id' => $this->task->userid));
        return $user;
    }
    protected function get_manual_enrol($courseid) {
        global $DB;
        $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual')); // Only one istance allowed.
        $course = $DB->get_record('course', array('id' => $courseid));
        if (!$instance) {
            // Create new instance.
            $enrolmanual = enrol_get_plugin('manual');
            $fields = array(
                    'status'          => 0,
                    'roleid'          => $enrolmanual->get_config('roleid'),
                    'enrolperiod'     => $enrolmanual->get_config('enrolperiod'),
                    'expirynotify'    => $enrolmanual->get_config('expirynotify'),
                    'notifyall'       => 0,
                    'expirythreshold' => $enrolmanual->get_config('expirythreshold')
            );
            $instanceid = $enrolmanual->add_instance($course, $fields);
            $instance = $DB->get_record('enrol', array('id' => $instanceid));
        }
        return $instance;
    }
    protected function enrol_user($enrolplugin, $instance, $roleid) {
        $enrolplugin->enrol_user($instance, $this->task->userid, $roleid);
    }
    public function get_errors() {
        return $this->taskerrors;
    }
    public function get_lang_object() {
        global $SITE, $CFG, $DB;
        $langobject = new stdClass();
        $langobject->externalcourseid = $this->task->externalcourseid;
        $langobject->externalmoodleurl = $this->task->externalmoodleurl;
        $langobject->externalmoodlesitename = $this->task->externalmoodlesitename;
        $langobject->userid = $this->task->userid;
        $langobject->internalcategory = $this->task->internalcategory;
        $langobject->status = $this->task->status;
        $langobject->externalcoursename = $this->task->externalcoursename;
        $langobject->internalcategoryname = !isset($this->task->internalcategoryname)
            || empty($this->task->internalcategoryname) ? $this->task->externalcoursename : $this->task->internalcategoryname;
        $langobject->defaultcategoryname = $this->task->defaultcategoryname;
        $langobject->site = $SITE->fullname;
        $langobject->siteurl = $CFG->wwwroot;
        $langobject->username = $this->task->username;
        // Special parameters.
        $includeexternalurlinmail = get_config('block_my_external_backup_restore_courses', 'includeexternalurlinmail');
        if ($includeexternalurlinmail == 1 && $this->task->externalmoodlesitename !== false) {
            $langobject->externalmoodle = get_string('mailexternalmoodleinfo',
                'block_my_external_backup_restore_courses', $langobject);
        } else {
            $langobject->externalmoodle = $this->task->externalmoodlesitename;
        }
        $langobject->localmoodle = get_string('maillocalmoodleinfo',
            'block_my_external_backup_restore_courses', $langobject);
        return $langobject;
    }
    public function notify_success() {
        global $SITE, $CFG;
        // Current messaging.
        $eventdata = new \core\message\message();
        $eventdata->component = 'block_my_external_backup_restore_courses';
        $eventdata->courseid = SITEID;
        $eventdata->name = 'restorationsuccess';
        $eventdata->userfrom = core_user::get_noreply_user();
        $eventdata->subject = get_string('success_mail_subject', 'block_my_external_backup_restore_courses');
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->notification = '1';
        $eventdata->contexturl = $CFG->wwwroot;
        $eventdata->contexturlname = $SITE->fullname;
        $eventdata->fullmessage = get_string('success_mail_main_message',
            'block_my_external_backup_restore_courses', $this->get_lang_object());
        $eventdata->fullmessagehtml = str_replace('\n', '<br/>', $eventdata->fullmessage);
        // For owner.
        $eventdata->userto = $this->get_user();

        // For admins or if setting ok for users.
        $errors = $this->get_errors();
        $fullmessage = '';
        foreach ($errors as $error) {
            $fullmessage .= get_string('error_mail_task_error_message'.($error->_get('courseid') == 0 ? '' : '_courseid'),
                'block_my_external_backup_restore_courses', $error->get_lang_object());
        }
        $warningstoowner = get_config('block_my_external_backup_restore_courses', 'warningstoowner');
        if ($warningstoowner == 1) {
            $eventdata->fullmessage .= $fullmessage;
            $eventdata->fullmessagehtml = str_replace('\n', '<br/>', $eventdata->fullmessage);
        }
        // Send message to task owner.
        $result = message_send($eventdata);

        // For admins.
        if ($warningstoowner != 1) {
            $eventdata->fullmessage .= $fullmessage;
            $eventdata->fullmessagehtml = str_replace('\n', '<br/>', $eventdata->fullmessage);
        }
        $admins = get_admins();
        foreach ($admins as $admin) {
            $eventdata->userto = $admin;
            $result = message_send($eventdata);
        }
    }
    public function add_error($message) {
        $this->taskerrors[] = new block_my_external_backup_restore_courses_task_error($this->task, $message);
    }
}

class block_my_external_backup_restore_courses_task_error extends stdClass{

    private $externalcourseid = 0;
    private $externalmoodleurl = null;
    private $externalmoodlesitename = null;
    private $courseid = 0;
    private $message = null;
    private $usernameorid = null;
    private $user = false;
    private $externalcoursename = null;
    private $internalcategoryname = null;
    private $defaultcategoryname = null;

    public function _get($property) {
        return $this->$property;
    }

    public function __construct($task, $message, $courseid=0) {
        $this->externalcourseid = $task->externalcourseid;
        $this->externalmoodleurl = $task->externalmoodleurl;
        $this->externalmoodlesitename = property_exists($task, 'externalmoodlesitename') ? $task->externalmoodlesitename
            : get_string('NA', 'block_my_external_backup_restore_courses');
        $this->externalcourseid = $task->externalcourseid;
        $this->courseid = $courseid;
        $this->usernameorid = $task->userid;
        $this->externalcoursename = $task->externalcoursename;
        $this->message = $message;
        $this->internalcategoryname = $task->internalcategoryname;
        $this->defaultcategoryname = $task->defaultcategoryname;
        mtrace($message);
    }
    public function get_user() {
        global $DB;
        if (!$this->user) {
            if (is_numeric($this->usernameorid)) {
                $this->user = $DB->get_record('user', array('id' => $this->usernameorid));
            } else {
                $this->user = $DB->get_record('user', array('username' => $this->usernameorid));
            }
        }
        return $this->user;
    }
    // Because of cast ()array problem in get_string.
    public function get_lang_object() {
        global $SITE, $CFG;
        $langobject = new stdClass();
        $langobject->externalcourseid = $this->externalcourseid;
        $langobject->externalmoodleurl = $this->externalmoodleurl;
        $langobject->externalmoodlesitename = $this->externalmoodlesitename;
        $langobject->courseid = $this->courseid;
        $langobject->message = $this->message;
        $langobject->usernameorid = $this->usernameorid;
        $langobject->username = $this->usernameorid;
        $langobject->externalcoursename = $this->externalcoursename;
        $langobject->internalcategoryname = !isset($this->internalcategoryname)
        || empty($this->internalcategoryname) ? $this->externalcoursename : $this->internalcategoryname;
        $langobject->defaultcategoryname = $this->defaultcategoryname;
        $langobject->site = $SITE->fullname;
        $langobject->siteurl = $CFG->wwwroot;
        // Special parameters.
        $includeexternalurlinmail = get_config('block_my_external_backup_restore_courses', 'includeexternalurlinmail');
        if ($includeexternalurlinmail == 1 && $this->externalmoodlesitename !== false) {
            $langobject->externalmoodle = get_string('mailexternalmoodleinfo',
                'block_my_external_backup_restore_courses', $langobject);
        } else {
            $langobject->externalmoodle = $this->externalmoodlesitename;
        }
        $langobject->localmoodle = get_string('maillocalmoodleinfo',
            'block_my_external_backup_restore_courses', $langobject);
        return $langobject;
    }

}
class block_my_external_backup_restore_courses_task_error_list {

    private $taskerrors = array();
    public function add_error($task/*block_my_external_backup_restore_courses_task_error*/) {
        $this->taskerrors[] = $task;
    }
    public function add_errors($tasks) {
        $this->taskerrors = array_merge($this->taskerrors, $tasks);
    }
    public function has_errors() {
        return count($this->taskerrors);
    }
    public function format_error_for_admin($username=null, $externalcourseid=null) {
        $text = '';
        foreach ($this->taskerrors as $taskerrors) {
            if ( ($username == null || $taskerrors->username == $username) && ($externalcourseid == null
                    || $taskerrors->externalcourseid == $externalcourseid)) {
                $text .= get_string('error_msg_admin', 'block_my_external_backup_restore_courses',
                    array('externalcourseid' => $taskerrors->externalcourseid,
                        'courseid' => $taskerrors->courseid,
                        'externalmoodleurl' => $taskerrors->externalmoodleurl,
                        'externalmoodlesitename' => $taskerrors->externalmoodlesitename,
                        'user' => $taskerrors->user,
                        'message' => $taskerrors->message));
            }
        }
    }
    public function notify_errors() {
        global $SITE, $CFG;
        // Error message.
        if (count($this->taskerrors)) {
            $eventdata = null;
            $fullmessage = '';
            $user = false;
            foreach ($this->taskerrors as $taskerrors) {
                if ($eventdata == null) {
                    $user = $taskerrors->get_user();
                    if (!$user) {
                        print_error('user '.$taskerrors->_get('usernameorid').' not found');
                    }
                    // Current messaging.
                    $eventdata = new \core\message\message();
                    $eventdata->component = 'block_my_external_backup_restore_courses';
                    $eventdata->name = 'restorationfailed';
                    $eventdata->userfrom = core_user::get_noreply_user();
                    $eventdata->subject = get_string('error_mail_subject', 'block_my_external_backup_restore_courses');
                    $eventdata->fullmessageformat = FORMAT_HTML;
                    $eventdata->notification = '1';
                    $eventdata->contexturl = $CFG->wwwroot;
                    $eventdata->contexturlname = $SITE->fullname;
                    $eventdata->replyto = core_user::get_noreply_user()->email;
                    $eventdata->fullmessage = get_string('error_mail_main_message', 'block_my_external_backup_restore_courses',
                        $taskerrors->get_lang_object());
                    $eventdata->courseid = SITEID;
                }
                $fullmessage .= get_string('error_mail_task_error_message'
                    .($taskerrors->_get('courseid') == 0 ? '' : '_courseid'),
                    'block_my_external_backup_restore_courses', $taskerrors->get_lang_object());
            }
            // For owner.
            $eventdata->userto = $user;
            $eventdata->fullmessagehtml = str_replace('\n', '<br/>', $eventdata->fullmessage);
            $result = message_send($eventdata);
            // For admins.
            $admins = get_admins();
            $eventdata->fullmessage .= $fullmessage;
            $eventdata->fullmessagehtml = str_replace('\n', '<br/>', $eventdata->fullmessage);
            foreach ($admins as $admin) {
                $eventdata->userto = $admin;
                $result = message_send($eventdata);
            }
        }
    }

}