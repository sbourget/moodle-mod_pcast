@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and see who has viewed the episodes.
  As a teacher
  I need to create a podcast and check the number of views

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Display names of viewers  | Yes |
      | Require approval for episodes | No |
    And I log out

  @javascript
  Scenario: A student uploads a video and a teacher views the totals
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
    And I follow "View"
    And I should see "0 minutes 20 seconds"
    And I log out
    When I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I log out

    Then I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "View"
    And I follow "Views"
    And I should see "2" in the "Student 2" "table_row"
    And I should see "1" in the "Student 3" "table_row"
    And I should see "1" in the "Teacher 1" "table_row"
    And I log out

    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "View"
    And I should see "5" in the "Total views" "table_row"
    And I follow "Views"
    And I should see "2" in the "Student 2" "table_row"
    And I should see "1" in the "Student 3" "table_row"
    And I should see "1" in the "Teacher 1" "table_row"
    And I log out