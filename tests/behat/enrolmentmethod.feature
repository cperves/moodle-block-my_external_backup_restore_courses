@block @block_my_external_backup_restore_courses

Feature:
  As a editingteacher I Want to restore a course from a remote pletform with enrolmentmethod options

  Background:
    Given a myexternalbackuprestorecourses mock server is configured
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "roles" exist:
      | name | shortname |
    And the following "system role assigns" exist:
      | user      | role            | contextlevel |
      | teacher1  | coursecreator  | System       |
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
  Scenario: Test if teacher1 can restore user datas
    When I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I should not see "Restore user datas in course"
    And I should not see "Enrolment mode"

  @javascript
  Scenario: Test if teacher1 can access user datas checkbox and choose enrolmentmode options correctly
    Given the following "permission overrides" exist:
      | capability                                                                        | permission     | role          | contextlevel | reference |
      | block/my_external_backup_restore_courses:can_restore_user_datas                   | Allow          | coursecreator | System       |           |
    When I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    Then I should see "Test course 1 (C1)"
    And I should see "Restore user datas in course"
    And I should see "Enrolment mode"
    And the "[id^=menuenrolmentmode] option[value='1']" "css_element" should be disabled
    And I click on "[name^=withuserdatas]" "css_element"
    And the "[id^=menuenrolmentmode] option[value='1']" "css_element" should be enabled

  @javascript
  Scenario: Test if teacher1 choose userdatas and enrolmentmode options and that they are correcly saved
    Given the following "permission overrides" exist:
      | capability                                                                        | permission     | role          | contextlevel | reference |
      | block/my_external_backup_restore_courses:can_restore_user_datas                   | Allow          | coursecreator | System       |           |
    When I log in as "teacher1"
    And I click on ".block_my_external_backup_restore_courses a" "css_element"
    And I click on "[name^=withuserdatas]" "css_element"
    And I click on "[id^=menuenrolmentmode] option[value=2]" "css_element"
    And I click on "input[name='selectedcourses[]']" "css_element"
    And I click on "Submit" "button"
    And "[name^=withuserdatas]" "css_element" should exist
    And "[id^=menuenrolmentmode] option[value=2][selected=selected]" "css_element" should exist
