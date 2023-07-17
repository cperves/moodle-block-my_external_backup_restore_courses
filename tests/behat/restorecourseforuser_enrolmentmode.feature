@block @block_my_external_backup_restore_courses

Feature:
  As a editingteacher I Want to restore a course from a remote plate-forme

  Background:
    Given a myexternalbackuprestorecourses mock server is configured
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort One | Ch1 |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
      | student1 | S1        | Student1 | student1@moodle.com |
      | student2 | S2        | Student2 | student2@moodle.com |
    And the following "roles" exist:
      | name | shortname |
    And the following "system role assigns" exist:
      | user      | role            | contextlevel |
      | teacher1  | coursecreator  | System       |
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
    And the following "cohort members" exist:
      | user  | cohort |
      | student2 | Ch1    |
    Then I log in as "admin"
    And I add "Cohort sync" enrolment method in "C1" with:
      | Cohort | Cohort one |
    And the following "course enrolments" exist:
      | user     | course | role   |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student |
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I add the "Restore courses from remote Moodles" block
    And I press "Reset Dashboard for all users"
    And I wait "1" seconds
    And I click on "Continue" "button"
    Then I navigate to "Server > Web services > Manage protocols" in site administration
    And I click on "#webserviceprotocols .cell.c2 a" "css_element"


  @javascript
  Scenario: Restore a course for other user, without usersdatas and with enolmentmode to ENROL_ALWAYS
    When I log in as "admin"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Restore course for user" in site administration
    And I set the field "External course id" to last created course id
    And the "#id_enrolmentmode option[value='1']" "css_element" should be disabled
    And I select "Yes, always" from the "Include enrolment methods mode" singleselect
    And I click on "Planify course restoration" "button"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Backup/restore task administration tool" in site administration
    And I should see "Test course 1"
    And I should see "Scheduled"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I am on the "Test course 1 copy 1" "enrolment methods" page
    And I should see "Cohort One - Student"
    And I should see "1" in the "Cohort sync (Cohort One - Student)" "table_row"
    And I should see "0" in the "Manual enrolments" "table_row"
    And I am on the "Test course 1 copy 1" "enrolled users" page
    And I should see "S2 Student2"
    And I should not see "S1 Student1"
    And I should not see "T1 Teacher1"

  @javascript
  Scenario: Restore a course for other user, without usersdatas and with enolmentmode to ENROL_NEVER
    When I log in as "admin"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Restore course for user" in site administration
    And I set the field "External course id" to last created course id
    And the "#id_enrolmentmode option[value='1']" "css_element" should be disabled
    And I select "No, restore users as manual enrolments" from the "Include enrolment methods mode" singleselect
    And I click on "Planify course restoration" "button"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Backup/restore task administration tool" in site administration
    And I should see "Test course 1"
    And I should see "Scheduled"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I am on the "Test course 1 copy 1" "enrolment methods" page
    And I should not see "Cohort sync (Cohort One - Student)"
    And I should see "0" in the "Manual enrolments" "table_row"
    And I am on the "Test course 1 copy 1" "enrolled users" page
    And I should not see "S2 Student2"
    And I should not see "S1 Student1"
    And I should not see "T1 Teacher1"

  @javascript
  Scenario: Restore a course for other user, with usersdatas and with enolmentmode to ENROL_ALWAYS
    When I log in as "admin"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Restore course for user" in site administration
    And I set the field "External course id" to last created course id
    And I set the field "withuserdatas" to "checked"
    And I select "Yes, always" from the "Include enrolment methods mode" singleselect
    And I click on "Planify course restoration" "button"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Backup/restore task administration tool" in site administration
    And I should see "Test course 1"
    And I should see "Scheduled"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I am on the "Test course 1 copy 1" "enrolment methods" page
    And I should see "Cohort sync (Cohort One - Student)"
    And I should see "1" in the "Cohort sync (Cohort One - Student)" "table_row"
    And I should see "2" in the "Manual enrolments" "table_row"
    And I am on the "Test course 1 copy 1" "enrolled users" page
    And I should see "S2 Student2"
    And I should see "S1 Student1"
    And I should see "T1 Teacher1"

  @javascript
  Scenario: Restore a course for other user, with usersdatas and with enolmentmode to ENROL_ALWAYS
    When I log in as "admin"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Restore course for user" in site administration
    And I set the field "External course id" to last created course id
    And I set the field "withuserdatas" to "checked"
    And I select "Yes, but only if users are included" from the "Include enrolment methods mode" singleselect
    And I click on "Planify course restoration" "button"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles > Backup/restore task administration tool" in site administration
    And I should see "Test course 1"
    And I should see "Scheduled"
    And I run the scheduled task "\block_my_external_backup_restore_courses\task\backup_restore_task"
    And I am on the "Test course 1 copy 1" "enrolment methods" page
    And I should see "Cohort sync (Cohort One - Student)"
    And I should see "1" in the "Cohort sync (Cohort One - Student)" "table_row"
    And I should see "2" in the "Manual enrolments" "table_row"
    And I am on the "Test course 1 copy 1" "enrolled users" page
    And I should see "S2 Student2"
    And I should see "S1 Student1"
    And I should see "T1 Teacher1"



