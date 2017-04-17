@mod @mod_pcast @file_upload @javascript
Feature: A teacher can create a podcast activity and see who has viewed the episodes.
  As a teacher
  I need to create a podcast and check the number of views

  Scenario: A student uploads a video and a teacher views the totals
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    | userscanpost | requireapproval | displayviews |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 0               | 1            |
    And I log in as "student1"
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
    And I follow "View"
    And I follow "audio/mp3"
    And I press the "back" button in the browser
    And I log out
    # Student 2 view
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I follow "View"
    And I follow "audio/mp3"
    And I press the "back" button in the browser
    And I log out
    Then I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I follow "View"
    And I follow "Views"
    And I should see "1" in the "Student 2" "table_row"
    And I should see "1" in the "Student 1" "table_row"
    And I follow "Episode"
    And I follow "audio/mp3"
    And I press the "back" button in the browser
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I follow "View"
    And I should see "4" in the "Total views" "table_row"
    And I follow "Views"
    And I should see "1" in the "Student 2" "table_row"
    And I should see "1" in the "Student 1" "table_row"
    And I should see "2" in the "Teacher 1" "table_row"
