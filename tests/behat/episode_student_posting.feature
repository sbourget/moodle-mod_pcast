@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity which allow students to post
  As a teacher
  I need to create a podcast and allow users to post episodes
    
  @javascript
  Scenario: Create a podcast and do allow users to post episodes
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
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 1               |
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    Then I press "Save changes"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I should see "0 minutes 20 seconds"
    And I log out

  @javascript
  Scenario: Create a podcast and do not allow users to post episodes
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
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 0            | 1               |
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    Then I should not see "Add a new episode"
    And I log out