@block @block_my_external_backup_restore_courses

Feature:
  As a developper I wan't to check that my new step is working well

  Background:
    Given a myexternalbackuprestorecourses mock server is configured

  @javascript
  Scenario: Test that the externalmoodle field is correctly filled
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Server > Web services > Manage tokens" in site administration
    Then I should see "wsuser block_my_external_backup_restore_courses_user"
    And I navigate to "Plugins > Blocks > Restore courses from remote Moodles" in site administration
    And "external moodle list to connect to" "field" should exist
