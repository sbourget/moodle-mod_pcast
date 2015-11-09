@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and limit the size of the episodes
  As a teacher
  I need to create a podcast and limit the file size

  @javascript
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
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    When I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Maximum attachment size | 10KB |
    And I follow "Test podcast name"
    And I press "Add a new episode"
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
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    When I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Maximum attachment size | 5MB |
    And I follow "Test podcast name"
    And I press "Add a new episode"
    Then I should see "Maximum size for new files: 5MB"
    And I press "Cancel"
