@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and limit the size of the episodes
  As a teacher
  I need to create a podcast and limit the file size

  Scenario: Create a podcast and add an episode that is larger than the file limit
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
      | activity | course | idnumber | name              | intro                    | userscanpost | maxbytes |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 10240    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    When I press "Add a new episode"
    Then I should see "Maximum size for new files: 10KB"
    And I press "Cancel"

  Scenario: Create a podcast and add an episode that is smaller than the limit
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
      | activity | course | idnumber | name              | intro                    | userscanpost | maxbytes   |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 2097152    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    When I press "Add a new episode"
    Then I should see "Maximum size for new files: 2MB"
    And I press "Cancel"
