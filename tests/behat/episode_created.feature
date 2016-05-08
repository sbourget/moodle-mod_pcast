@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and add, edit, and delete episodes
  As a teacher
  I need to create a podcast and add / update/ create episodes

  @javascript
  Scenario: Create a podcast and add an episode
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description |
    And I log in as "teacher1"
    And I follow "Course 1"
    When I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    Then I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I should see "0 minutes 20 seconds"
    And I log out

  @javascript
  Scenario: Create a podcast and add then update an episode
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    When I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    Then I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Edit"
    And I set the following fields to these values:
      | Title | NEW episode name |
      | Summary | NEW episode summary |
    And I press "Save changes"
    And I should see "NEW episode name"
    And I should see "NEW episode summary"
    And I log out

  @javascript
  Scenario: Create a podcast and add then delete an episode
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    When I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    Then I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Delete"
    And I press "Continue"
    And I should not see "Test episode name"
    And I should not see "Test episode summary"
    And I log out