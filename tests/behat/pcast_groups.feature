@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and use it with groups of students
  As a teacher
  I need to create a podcast, add an episode, and view the RSS feed

  @javascript
  Scenario: Create a podcast and view episodes by newest first
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Zero | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
      | student2 | Student | Two | student2@example.com |
      | student3 | Student | Three | student3@example.com |
      | student4 | Student | Four | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group   |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G1 |
      | student4 | G2 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | No |
      | Group mode | Separate groups |
      | Episodes shown per page | 5 |
    And I follow "Test podcast name"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 1 Episode |
      | Summary | Student 1 episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 2 Episode |
      | Summary | Student 2 Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 3 Episode |
      | Summary | Student 3 Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 4 Episode |
      | Summary | Student 4 Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"

    # Group Access:
    Then I should not see "Student 1 Episode"
    And I should see "Student 2 Episode"
    And I should not see "Student 3 Episode"
    And I should see "Student 4 Episode"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I should see "Student 1 Episode"
    And I should not see "Student 2 Episode"
    And I should see "Student 3 Episode"
    And I should not see "Student 4 Episode"
    And I log out

    # Teacher (All groups)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I should see "Student 1 Episode"
    And I should see "Student 2 Episode"
    And I should see "Student 3 Episode"
    And I should see "Student 4 Episode"

    And I set the field "Separate groups" to "Group 1"
    And I should see "Student 1 Episode"
    And I should not see "Student 2 Episode"
    And I should see "Student 3 Episode"
    And I should not see "Student 4 Episode"

    And I set the field "Separate groups" to "Group 2"
    And I should not see "Student 1 Episode"
    And I should see "Student 2 Episode"
    And I should not see "Student 3 Episode"
    And I should see "Student 4 Episode"
