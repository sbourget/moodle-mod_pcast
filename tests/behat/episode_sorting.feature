@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and sort the episodes a variety of ways
  As a teacher
  I need to create a podcast, add an episode, and view the episodes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Zero | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
      | student2 | Student | Xray | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    | userscanpost | requireapproval | episodesperpage |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 0               | 1               |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | First Episode |
      | Summary | Test episode summary 1 |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Next Episode|
      | Summary | Test episode summary 2 |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Sort episodes alphabetically
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "Browse by alphabet"
    Then I should see "First Episode"
    And I should not see "Next Episode"
    And I follow "F"
    And I should see "First Episode"
    And I should not see "Next Episode"
    And I follow "N"
    And I should not see "First Episode"
    And I should see "Next Episode"

  @javascript
  Scenario: Sort episodes by date
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "Browse by date"
    # Sort by oldest updated.
    Then I should see "First Episode"
    And I should not see "Next Episode"
    And I follow "Date updated"
    # Sort by newest updated.
    And I should not see "First Episode"
    And I should see "Next Episode"
    And I follow "Date created"
    # Sort by oldest created.
    And I should see "First Episode"
    And I should not see "Next Episode"
    And I follow "Date created"
    # Sort by newest created.
    And I should not see "First Episode"
    And I should see "Next Episode"

  @javascript
  Scenario: Sort episodes by author
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 1 Episode |
      | Summary | Student 1 episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 2 Episode |
      | Summary | Student 2 Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    Then I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I follow "Browse by author"
    And I should see "Student 1 Episode"
    And I follow "O"
    And I should see "Student One"
    And I follow "X"
    And I should see "Student Xray"