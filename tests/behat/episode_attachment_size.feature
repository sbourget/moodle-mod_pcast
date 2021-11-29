@mod @mod_pcast @_file_upload
Feature: A teacher can create a podcast activity and limit the size of the episodes
  As a teacher
  I need to create a podcast and limit the file size

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Create a podcast and add an episode that is larger than the file limit
    Given the following "activities" exist:
      | activity | course | name              | intro                    | userscanpost | maxbytes |
      | pcast    | C1     | Test podcast name | Test podcast description | 1            | 10240    |
    And I am on the "Test podcast name" Activity page logged in as "teacher1"
    When I press "Add a new episode"
    Then I should see "Maximum file size: 10 KB"
    And I press "Cancel"

  @javascript
  Scenario: Create a podcast and add an episode that is smaller than the limit
    Given the following "activities" exist:
      | activity | course | name              | intro                    | userscanpost | maxbytes |
      | pcast    | C1     | Test podcast name | Test podcast description | 1            | 2097152  |
    And I am on the "Test podcast name" Activity page logged in as "teacher1"
    When I press "Add a new episode"
    Then I should see "Maximum file size: 2 MB"
    And I press "Cancel"
