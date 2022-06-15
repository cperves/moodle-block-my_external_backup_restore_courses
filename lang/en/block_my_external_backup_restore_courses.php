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

$string['pluginname'] = 'Restore courses from remote Moodles';
$string["roles_included_in_external_courses_search"] = "Roles in course to add to the external course search";
$string["roles_included_in_external_courses_search_Desc"] = "Roles in course to add to the external course search while searching into user fields : shortnames delimited by simple quote and  separated by commas";
$string['external_moodle'] = 'external moodle list to connect to';
$string['external_moodleDesc'] = 'a formatted list of external moodle as moodle_url1,token_compte_webservice_moodle_externe1;moodle_url2,token_compte_webservice_moodle_externe2;...';
$string['my_external_backup_restore_courses:addinstance'] = 'add instance of retrieve backup restore courses from external moodle';
$string['my_external_backup_restore_courses:can_see_backup_courses'] = 'View backup courses';
$string['my_external_backup_restore_courses:can_retrieve_courses'] = 'Retrieve external backup files';
$string['my_external_backup_restore_courses:myaddinstance'] = 'add instance of retrieve backup courses from external moodle to Dashboard';
$string['my_external_backup_restore_courses:can_see_external_course_link'] = 'external course name is a web link to external moodle course reference';
$string['noexternalmoodleconnected'] = 'No external moodle connected';
$string['externalmoodlecourselist'] = 'External moodles course list';
$string['externalmoodlehelpsection'] =
'In the folling table :<ul><li> check to select the remote courses that you want to restore on the current plate-forme</li><li>Next click on "Send" button</ul>
Courses are then scheduled to be restored.<br><br>
You can consult the state of your scheduled for restoration courses (scheduled date, resotoration completed, ...).<br>
A message notification will be send once your course will be restored.
';
$string['invalidusername'] = 'You have no account on this platform';
$string['restore'] = 'Restore a course';
$string['restorecourses'] = 'Restore courses';
$string['choose_to_restore'] = 'Select for restore';
$string['keepcategory'] = 'Keep original category';
$string['restorecourseinoriginalcategory'] = 'Restore course in its category if possible';
$string['restorecourseinoriginalcategory_desc'] = 'Restore course in its category if possible. This requires a field in a table common beetween remote course categories and local ones.';
$string['defaultcategorychecked'] = 'Is original category choice checked by default';
$string['defaultcategorychecked_desc'] = 'Is original category choice checked by default';
$string['categorytable'] = 'Database table name where category informations are stored';
$string['categorytable_desc'] = 'Database table name where category informations are stored';
$string['categorytable_foreignkey'] = 'Database table foreign key for category id';
$string['categorytable_foreignkey_desc'] = 'Database table foreign key for category id';
$string['categorytable_categoryfield'] = 'Unique database table field that represent category same for current and foreign moodle implied';
$string['categorytable_categoryfield_desc'] = 'Unique database table field that represent category same for current and foreign moodle implied';
$string['defaultcategory'] = 'Category id where restoring courses by default';
$string['defaultcategory_desc'] = 'Category id where restoring courses by default';
$string['misconfigured_plugin'] = 'Misconfigured plugin';
$string['status_0'] = 'Scheduled';
$string['status_1'] = 'In progess';
$string['status_2'] = 'Performed';
$string['status_-1'] = 'Error';
$string['status_0_byuser'] = 'Scheduled by {$a->firstname} {$a->lastname}}';
$string['status_1_byuser'] = 'In progress by {$a->firstname} {$a->lastname}';
$string['status_2_byuser'] = 'Performed by {$a->firstname} {$a->lastname}';
$string['status_-1_byuser'] = 'Error by {$a->firstname} {$a->lastname}';
$string['my_external_backup_restore_courses_task'] = 'Restore course from remote Moodles task';
$string['error_msg_admin'] = 'error for course with external id  {$externalcourseid} and internal id {$courseid}, from {$externalmoodleurl} , for username {$username} :\n{$message}';
$string['messageprovider:restorationsuccess'] = 'Notify that an external course is successfully restored';
$string['messageprovider:restorationfailed'] = 'Notify that an external course as failed to restore';
$string['error_mail_subject'] = '[Moodle course restore] : error while restoring an external course';
$string['error_mail_main_message'] = 'Errors occured while restoring course "{$a->externalcoursename}" from moodle platform {$a->externalmoodle} to moodle platform {$a->localmoodle}.\nSee details below.\n';
$string['error_mail_task_error_message'] = '{$a->message}.\n';
$string['error_mail_task_error_message_courseid'] = 'internal courseid {$a->courseid} : {$a->message}.\n';
$string['success_mail_subject'] = '[Moodle course restore] : an external course successfully restore';
$string['success_mail_main_message'] = 'course restoration "{$a->externalcoursename}" from moodle platform {$a->externalmoodle} to moodle platform {$a->localmoodle} completed successfully.';
$string['cantrestorecourseincategorycontext'] = 'User "{$a->username}" can\'t restore course in category "{$a->internalcategoryname}" because hasn\'t capability moodle/course:create.\n The course will be restored in category "{$a->defaultcategoryname}".';
$string['cantrestorecourseindefaultcategorycontext'] = 'User {$a->username} can\'t restore course "{$a->externalcoursename}" in default category "{$a->defaultcategoryname}" because hasn\'t capability moodle/course:create.';
$string['notexistinginternalcategory'] = 'User "{$a->username}" can\'t restore course in category "{$a->internalcategoryname}" because the given internal category does not exists anymore\n. The course will be restored in category "{$a->defaultcategoryname}".';
$string['cantrestorecourseindefaultcategorycontext'] = 'User "{$a->username}" can\'t restore course in default category "{$a->defaultcategoryname}" because hasn\'t capability moodle/course:create.';
$string['my_external_backup_restore_courses:view'] = 'See \'Restore courses from remote Moodles\' block';
$string['nextruntime'] = 'Estimated execution time';
$string['timelimitedmod'] = 'Execution beetween two time mod';
$string['timelimitedmod_desc'] = 'Execution beetween two time mod means that associated task that import and restore courses from external moodle will not work after before and after define time limits';
$string['limitstart'] = 'Starting execution time';
$string['limitend'] = 'Ending execution time';
$string['limitstart_desc'] = 'Starting execution time';
$string['limitend_desc'] = 'Ending execution time';
$string['executiontimemixed'] = 'Performed';
$string['executiontimeyourself'] = 'Performed by youself';
$string['warningstoowner'] = 'Show warnings to restored course owner';
$string['warningstoowner_desc'] = 'Show warnings to restored course owner';
$string['includeexternalurlinmail'] = 'Include external platform url in notification mail';
$string['includeexternalurlinmail_desc'] = 'Include external platform url in notification mail';
$string['maillocalmoodleinfo'] = '{$a->site} ({$a->siteurl})';
$string['mailexternalmoodleinfo'] = '{$a->externalmoodlesitename} ({$a->externalmoodleurl})';
$string['NA'] = 'N/A';
$string['executioninformationbyuser'] = '{$a->executiontime} by {$a->firstname} {$a->lastname}';
$string['executioninformationyourself'] = '{$a->executiontime} by yourself';
$string['executiontimebyothers'] = 'Performed by someone else';
$string['onlyoneremoteinstance'] = 'Only on restoration is autorized by external course';
$string['onlyoneremoteinstance_desc'] = 'Only on restoration is autorized by external course. All users included';
$string['courselabel'] = '{$a->fullname} ({$a->shortname})';
$string['enrollbutton'] = 'enroll button activated';
$string['enrollbutton_desc'] = 'concerned users with search_roles role in remote course will have an enroll button to enrol as enrollrole';
$string['enrollrole'] = 'enroll role';
$string['enrollrole_desc'] = 'role that will be assigned to user after clicking enroll button';
$string['enrollbuttonlabel'] = 'enroll to this course';
$string['enrollbuttonlabelcoursexuserx'] = 'enroll to course restored by {$a->firstname} {$a->lastname}';
$string['cantenrollocourserolex'] = 'Your registration to course with role {$a} failed';
$string['coursenotfound'] = 'course to enroll not found';
$string['nomanualenrol'] = 'No enrol method found for the course you wan\'t to enrol in. pLeaser contact the person who restore this course';
$string['alreadyenrolledincoursexuserx'] = 'you are already enrolled into course restored by {$a->firstname} {$a->lastname}';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:remote_moodle:externalcourseid'] = 'on remote moodle from wich course will be restored a backup file is created.';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:remote_moodle'] = 'remote moodle datas';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:userid'] = 'moodle userid';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalcourseid'] = 'moodle remote courseid that will be/ was restored locally';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalcoursename'] = 'moodle remote coursename that will be/ was restored locally';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:externalmoodleurl'] = 'remote moodle url';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:internalcategory'] = 'category where the course will be restored';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:status'] = 'status of course restoration';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:courseid'] = 'local courseid of the restored course';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timecreated'] = 'creation time of the restoration task for a given course and a given user';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timemodified'] = 'modification time of the restoration task for a given course and a given user';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore:timescheduleprocessed'] = 'scheduled time for restoration';
$string['privacy:metadata:blocks_my_external_backup_restore_courses:block_external_backuprestore'] = 'data table block_external_backuprestore that store datas concerning remote course restoration';
$string['privacy:metadata:core_enrol'] = '\'Restore courses from remote Moodles\' generate enrolments to generated courses and so store user datas as enrollee';

