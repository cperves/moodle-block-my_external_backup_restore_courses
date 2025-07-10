@block @block_my_external_backup_restore_courses

Feature:
  As a editingteacher I Want to restore a course from a remote plate-forme

  Background:
    Given a myexternalbackuprestorecourses mock server is configured
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
      | restorer1 | R1        | Restorer1 | restorer1@moodle.com |
    And the following "roles" exist:
      | name | shortname |
      | restorer | restorer |
    And the following "system role assigns" exist:
      | user      | role            | contextlevel |
      | teacher1  | coursecreator  | System       |
      | restorer1  | restorer  | System       |
    And I set the following system permissions of "restorer" role:
      | capability                  | permission |
      | block/my_external_backup_restore_courses:restore_courses_for_users | Allow    |
    And the following config values are set as admin:
      | defaultcategory|1|block_my_external_backup_restore_courses|
      | restorecourseinoriginalcategory| 0|block_my_external_backup_restore_courses|
      | defaultcategorychecked| 0|block_my_external_backup_restore_courses|
      | onlyoneremoteinstance | 1 |block_my_external_backup_restore_courses|
      | checkrequestercapascoursecreate | 0 |block_my_external_backup_restore_courses|
    And the following course exists:
      | name      | Test course |
      | shortname | C1          |
    And the following "course enrolments" exist:
      | user     | course | role   |
      | teacher1 | C1     | editingteacher |



  @javascript
  Scenario: Restore a course for other user
    When I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I add the "Restore courses from remote Moodles" block
    And I press "Reset Dashboard for all users"
    And I wait "1" seconds
    And I click on "Continue" "button"
    And I logout
    And I log in as "restorer1"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Restore course for user" in site administration
    And I set the field "Remote course id" to last created course id
    And I click on "Planify course restoration" "button"
    And I logout
    And I login as admin
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Backup/restore task administration tool" in site administration
    And I should see "Test course 1"
    And I should see "Scheduled"
    Then I log out
    And I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    And I should see "Test course 1"
    And I should see "Scheduled" in the "Test course 1" "table_row"
    And I log out
    And I log in as "admin"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I log out
    Then I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    And I should see "Test course 1"
    And I should see "Performed" in the "Test course 1 (C1)" "table_row"
    And I should see "by  internal moodle administrator"
    And "input[name=enrolltocourse]" "css_element" should exist