# block my_external_backup_courses : Restore courses from remote moodle platforms

my_external_backup_restore_courses is a Moodle block that enable a user to restore courses from external moodles
this block must be installed in each moodle course clients and course servers involved

## Features
  * enable a user to program course restoration where courses comes from external moodles
  * possibility to find the original category based on unique category identifier threw plugin settings on database relation (user must hace moodle/course:create in the category context)
  * restore cours in a default category (user must hace moodle/course:create in that context)
  * a scheduled task will launch remote backups and restorations of these courses
  * Log and messaging include to notify of succes or failure

## Security warning
* This plugin use a capability block/my_external_backup_restore_courses:can_retrieve_courses that enable webservice account to donload backup files of other users
* To improve security it is strongly recommended to generate token with IPrestriction on server side IPs

## mnet warning usage
this plugin may not work in MNet environments fully because the username in that conditions username is not unique

## Download

from moodle plugin repository

## Installation

### Block installation
Install block on blocks directory in course clients moodles and in each course servers moodle you need to connect to

### webservice settings
On moodles that serves courses
* create role for webservice
  * add the protocol rest capability to this role webservice/rest:use
  * add capabilility block/my_external_backup_restore_courses:can_retrieve_courses
  * add capbility block/my_external_backup_restore_courses:can_see_backup_courses
 * Create a user account for webservice account 
* assign role on system context for this newly created account
* Under webservice administration :
  * activate rest protocole
  * Under Site administration -> Plugins -> Web Services -> External services, add a new custom service
    * check Enabled
    * ckeck Authorised users only
    * check  Can download files
    * select capability block/my_external_backup_restore_courses:can_see_backup_courses
  * once created add funtions to the new custom external service
    * core_webservice_get_site_info
    * block_my_external_backup_restore_courses_get_courses
    * block_my_external_backup_restore_courses_get_courses_zip
  *  add the webservice user account created previously to the authorized users of the new custom service
  * Under Site administration -> Plugins -> Web Services -> Manage Tokens
    * create a new token, restrited on your php server(s) for the custom external sservice previously created
    * This token will be one to enter in the block parameters off block_my_external_backup_restore_courses 

### Block setting
Under Plugins -> Blocks -> Restore courses from remote Moodles
For each moodles you need to fill the following setting parameters

  * in my_external_backup_course | search_roles enter roles to include in course search simple quote delimited text shortname separated by commas
  * in my_external_backup_course | restorecourseinoriginalcategory activate the mode that enable to try to search the original category of a remote course 
  * in my_external_backup_course | categorytable the database table name where to find unique identifier information in order to search/find category, common for both client and server moodles
  * in my_external_backup_course | categorytable_foreignkey the database foreign key for categorytable
  * in my_external_backup_course | categorytable_categoryfield the database field in categorytable unique for a category and common for both client and server moodles

course clients moodles
  * in my_external_backup_course | defaultcategory the categoryid where the course will be restored by default, users that restore must have capability to moodle/course:create
  * in my_external_backup_course | externalmoodles formatted list of course servers moodles formatted as moodle_url1,token_compte_webservice_moodle_externe1;moodle_url2,token_compte_webservice_moodle_externe2;...

### Messaging
  * Site administration / ► Plugins / ► Message outputs / ► Default message outputs
  * 2 message outputs :
    * Notify that an external course as failed to restore
    * Notify that an external course is successfully restored
  * by default allowed and permitted for mails

### capability
in order to use this block in dashboard a capability block/my_external_backup_restore_courses:view is provided and by default allowed for coursecreator and manager profile
This enable to controle block visibility in dashboard


## Contributions

Contributions of any form are welcome. Github pull requests are preferred.

Fill any bugs, improvements, or feature requiests in our [issue tracker][issues].

## License
* http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
[my_external_backup_restore_courses_github]: 
[issues]: 
