# block my_external_backup_courses : Restore courses from remote moodle platforms

my_external_backup_restore_courses is a Moodle block that enable a user to restore courses from external moodles
this block must be installed in each moodle course clients and course servers involved

Moodles must be on the same major version otherwise it will not function well.
Additional plugins should be the same on all Moodle platforms involved.

## Features
  * enable a user to program course restoration, where courses comes from external moodles
  * depending of his role capabilities he can restore courses with user datas
  * possibility to find the original category based on unique category identifier threw plugin settings on database relation (user must hace moodle/course:create in the category context)
  * restore cours in a default category (user must have moodle/course:create in that context)
  * a scheduled task will launch remote backups and restorations of these courses
  * Log and messaging include to notify of success or failure
  * possibilily to restrict to only one restoration by course
  * admin tool to see list of backup/restore tasks
  * admin tool to restore a course for a user

## Security warning
* This plugin use a capability block/my_external_backup_restore_courses:can_retrieve_courses that enable webservice account to donload backup files of other users
* To improve security it is strongly recommended to generate token with IPrestriction on server side IPs

## mnet warning usage
this plugin may not work in MNet environments fully because the username in that conditions username is not unique

## Download
from moodle plugin repository

## Installation
Given that two moodle are installed :
* moodleC : course client
* moodleS : course serveur
* these two moodle must be able to communicate trough network
  * check this on servers by testing with a curl command
```shell
# On moodleA
curl https://moodleS.domain
```
### install plugin
* unzip the block_my_external_backup_courses content in /moodlepath/blocks/my_external_backup_courses
* launch plugin install with moodle web admin or with cli
```shell
php /moodlepath/admin/cli/upgrade.php
```
### Prepare moodle course server webservices
* on moodle course server moodleS
* install the plugin webservice with cli, moosh or web administration interface
#### Installing with cli command
* Execute cli, that will install webservice, rôle and user
* you will only need to generate token (see after)
```shell
/moodlepath/blocks/my_external_backup_restore_courses/cli/install_server.php
```
##### generate token
* Under Site administration -> Plugins -> Web Services -> Manage Tokens
  * create a new token, restricted on your php server(s) for the custom external service previously created
    * cli user is named block_my_external_backup_restore_courses_user
  * This token will be one to be entered in the block parameters of block_my_external_backup_restore_courses on course client.
  * for more security restrict webservice usage on IP
#### With moosh
```shell
moosh -p /moodlepath -n webservice-install wsblockmyexternalbakcuprestorecourses 'block/my_external_backup_restore_courses:can_retrieve_courses,block/my_external_backup_restore_courses:can_see_backup_courses"
```
This will return the webservice token.
### Enable the two Moodle to communicate
#### On moodle course client moodleC
* set curl security parameters depending of your servers
* Administration -> Security -> HTTP security
  * curlsecurityblockedhosts
  * curlsecurityallowedport
### Configure plugins
#### On moodle course client moodleC
* Go to settings page Site administration -> Plugins -> Restore courses from remote Moodles -> Settings
* And set the following value
*  moodle_role
* Choose "Course client"
* search_roles
  * Set the moodle roles that will be used to search courses for users
    * The available courses for restauration will be the one where the current user is enrolled in course or course category with the defined role assignment
* external_moodles
  * set the moodle course server moodleS informations
    * `https://moodleS,<moodleS_TOKEN>`
    * Where <moodleS_TOKEN> is the previous generated token
* defaultcategory
  * The category where courses will be restored by default
* defaultcategorychecked
  * Default category option will be set for users
* restorecourseinoriginalcategory
  * id checked the system will try to find original category comparing informations based on categorytable, categorytable_foreignkey and categorytable_categoryfield
* categorytable, categorytable_foreignkey and categorytable_categoryfield
  * The category tables and field enabling to retrieve course category informations
    * Default values are sufficient, feel free to change them depending of your usages
* onlyoneremoteinstance
  * Only one remote course restored instance will be authorized
* enrollrole
  * The role that will be assigned to users while clickin on "enrol me" button and in case of auto enrol requester mode
* autoenrol_requester
  * If checked the course restoration requester will be enrolled in the restored course with enrolrole role
* includeexternalurlinmail
  * Include moodle course server platform url in notification mail
* warningstoowner
  * Show warning notifications to course owner
* checkrequestercapascoursecreate
  * Check that the requester is authorized to restore course in the choosed category, based on moodle/course:create capability

You can set all this value using moosh or config moodle cli command
```shell
# cli command
php /moodle_path/admin/cli/cfg.php --component='block_my_external_backup_restore_courses' --name=<parameter_key> --set=<value>
```
### On moodle course serveur moodleS
* Go to settings page Site administration -> Plugins -> Restore courses from remote Moodles -> Settings
* And set the following value
*  moodle_role
* Choose "Course server"
  * settings value view will be simplified
* categorytable, categorytable_foreignkey and categorytable_categoryfield
  * The category tables and field enabling to retrieve course category informations
    * Default values are sufficient, feel free to change them depending of your usages

### block task
* The plugin launch backup/restore process on a asynchronous mode with a moodle task
  * \block_my_external_backup_restore_courses\task\backup_restore_task
* You can launch this through moodle cron by choosing the frequency on Site administration -> Scheduled tasks
* You can also disable it and lauch it directly from your crontab
```shell
php /moodlepath/admin/cli/scheduled_task.php --execute="\block_my_external_backup_restore_courses\task\backup_restore_task"
```

## Configure block for users capability
On moodle course client moodleC
* In order to use this block in dashboard a capability block/my_external_backup_restore_courses:view is provided and by default allowed for coursecreator and manager profile
* This enable to control block visibility in dashboard
* course restore and backup is virtually proceed with an admin account so the resquester user does not need special capabilities anymore
  * except course:create in category if checkrequestercapascoursecreate setting is checked in plugin settings

## Messaging System for notifications
* Site administration / ► Plugins / ► Message outputs / ► Default message outputs
* 2 message outputs :
  * Notify that an external course as failed to restore
  * Notify that an external course is successfully restored
* by default allowed and permitted for mails
  

## Troubleshooting
In case of troubles with message "error/site name can't be retrieved for ..."
* check that the token is correct and that external_moodles is correctly filled in plugin settings
* check that your https configuration is correct in web server including valid ssl certificate
* you can test the webservice with url with a browser and with curl on course client server:
  * `https://mywebsite/webservice/rest/server.php?wstoken=xxxxxxxxxxxxxxx&moodlewsrestformat=json&wsfunction=block_my_external_backup_restore_courses_get_courses&username=USERNAME&searchroles=AROLE
    * will return the list of remote courses
    * for user with username USERNAME
    * who has AROLE role in courses
* `https://mywebsite/webservice/rest/server.php?wstoken=XXXXXXXXXXXXXXX&moodlewsrestformat=json&wsfunction=block_my_external_backup_restore_courses_get_courses_zip&username=USERNAME&courseid=COURSEID&searchroles=AROLE`
    * will return {"filename":"", "filerecord":XXXXXXX}
    * for user USERNAME
    * in course COURSEID
## if curl requests seems to be blocked
* Go to HttpSettings of the moodles
  * Go to Administration -> Security -> HTTP security
  * check settings
  * and above all check curlsecurityblockedhosts content, you maybe have to had remote moodle ip



## Contributions
Contributions of any form are welcome. Github pull requests are preferred.
Fill any bugs, improvements, or feature requiests in our [issue tracker][issues].

## License
* http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
[my_external_backup_restore_courses_github]: 
[issues]: 
