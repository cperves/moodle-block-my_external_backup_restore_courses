# CHANGES
* 2023-05-10
  * trim key of moodle external config setting to prevent empty domain and resulting bug
  * php 8.1 deprecated corrections
  * use namespace to have unique test while using phpunit.xml in git CI
  * behat test on itself : moodle is course server and course client to preform behat tests
* 2023-02-17
  * new 4.0 version
  * courses are backup/restores with an admin user enabling to simplify requester capabilities
  * time limitation removed since task is used and can do this with cron
  * administration tool included :
    * list of backup/restore tasks with change status possibility
    * tool to restore for a user entering an external course id
* 2020-05-10 : new 3.9/3.10 version
  * compatibility
  * unit tests
  * patch to restore external repositories
* 2019-06-24 3.5 New version with possiblity to restrict to only one restoration by course. Add a button to enable the user to re-enrol in course if already retored by someone else

