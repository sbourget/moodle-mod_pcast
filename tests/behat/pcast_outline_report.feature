@mod @mod_pcast @file_upload
Feature: Teachers can review student progress on all podcasts in a course by viewing the outline report
  As a teacher
  I need to view the outline report for one of my students.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | No |
    And I log out

  Scenario: A student does not upload anything
    Given I log in as "teacher1"
    When I follow "Course 1"
    And I follow "Participants"
    And I follow "Student 1"
    And I follow "Outline report"
    Then I should see "-"

  @javascript
  Scenario: A student uploads an episode
    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I log out
    When I log in as "teacher1"
    When I follow "Course 1"
    And I follow "Participants"
    And I follow "Student 1"
    And I follow "Outline report"
    Then I should see "Test podcast name"
    And I should see "1 episodes"