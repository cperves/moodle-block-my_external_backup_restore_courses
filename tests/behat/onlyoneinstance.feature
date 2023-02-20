@block @block_my_external_backup_restore_courses

Feature:
  As a editingteacher I Want to restore a course from a remote plate-forme

  Background:
    Given a myexternalbackuprestorecourses mock server is configured
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
      | teacher2 | T2        | Teacher2 | teacher2@moodle.com |
    And the following "roles" exist:
      | name | shortname |
    And the following "system role assigns" exist:
      | user      | role            | contextlevel |
      | teacher1  | coursecreator  | System       |
      | teacher2  | coursecreator  | System       |
    And the following "permission overrides" exist:
      | capability                                    | permission     | role          | contextlevel | reference |
      | moodle/restore:restorecourse                  | Allow          | coursecreator | System       |           |
      | block/my_external_backup_restore_courses:view | Allow          | coursecreator | System       |           |
    And the following config values are set as admin:
      | defaultcategory|1|block_my_external_backup_restore_courses|
      | restorecourseinoriginalcategory| 0|block_my_external_backup_restore_courses|
      | defaultcategorychecked| 0|block_my_external_backup_restore_courses|
      | onlyoneremoteinstance | 1 |block_my_external_backup_restore_courses|
      | checkrequestercapascoursecreate | 0 |block_my_external_backup_restore_courses|
      | enablewebservices               | 1 ||
    And the following course exists:
      | name      | Test course |
      | shortname | C1          |
    And the following "course enrolments" exist:
      | user     | course | role   |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    Then I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I add the "Restore courses from remote Moodles" block
    And I press "Reset Dashboard for all users"
    And I wait "1" seconds
    And I click on "Continue" "button"
    Then I navigate to "Server > Web services > Manage protocols" in site administration
    And I click on "#webserviceprotocols .cell.c2 a" "css_element"


  @javascript
  Scenario: Test that if a course is even restored nby teacher1 , teacher2 can't restore it
    When I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I click on "input[name='selectedcourses[]']" "css_element" in the "Test course 1 (C1)" "table_row"
    And I click on "Submit" "button"
    Then I should see "Scheduled" in the "Test course 1 (C1)" "table_row"
    And the "input[name='selectedcourses[]']" "css_element" should be enabled
    And I log out
    Then I log in as "teacher2"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I should see "Scheduled by T1 Teacher1" in the "Test course 1 (C1)" "table_row"
    And the "input[name='selectedcourses[]']" "css_element" should be disabled
    Then I log in as "admin"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I wait "10" seconds
    And I log out
    Then I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I should see "Performed" in the "Test course 1 (C1)" "table_row"
    And I should see "by yourself" in the "Test course 1 (C1)" "table_row"
    And the ".admintable tbody tr:nth-child(2) td.c1 input[name='selectedcourses[]']" "css_element" should be disabled
    And I should see "Test course 1 copy 1 (C1_1)"
    And the ".admintable tbody tr:nth-child(1) td.c1 input[name='selectedcourses[]']" "css_element" should be enabled
    And I log out
    Then I log in as "teacher2"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I should see "Performed" in the "Test course 1 (C1)" "table_row"
    And I should see "by T1 Teacher1" in the "Test course 1 (C1)" "table_row"
    And the ".admintable tbody tr:nth-child(1) td.c1 input[name='selectedcourses[]']" "css_element" should be disabled
    And I should not see "Test course 1 copy 1 (C1_1)"
    Then I click on "enroll to course restored by T1 Teacher1" "button" in the "Test course 1 (C1)" "table_row"
    And I should see "you are already enrolled into course restored by T1 Teacher1" in the "Test course 1 (C1)" "table_row"
    And I should see "Test course 1 copy 1 (C1_1)"
    And the ".admintable tbody tr:nth-child(1) td.c1 input[name='selectedcourses[]']" "css_element" should be enabled







