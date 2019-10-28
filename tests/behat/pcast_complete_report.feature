@mod @mod_pcast @_file_upload
Feature: Teachers can review student progress on all podcasts in a course by viewing the complete report
  As a teacher
  I need to view the complete report for one of my students.

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
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    | userscanpost | requireapproval |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 0               |

  Scenario: A student does not upload anything
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Participants"
    And I follow "Student 1"
    And I follow "Complete report"
    Then I should see "No episodes posted"

  @javascript
  Scenario: A student uploads an episode
    Given I log in as "student1"
    And I am on "Course 1" course homepage
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
    And I am on "Course 1" course homepage
    And I follow "Participants"
    And I follow "Student 1"
    And I follow "Complete report"
    Then I should see "Test podcast name"
    And I should see "Test episode summary"
