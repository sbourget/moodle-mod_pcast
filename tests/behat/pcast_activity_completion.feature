@mod @mod_pcast @file_upload
Feature: Teachers can use activity completion to track student progress
  As a teacher
  I need to enable activity completion for podcasts.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1 | 0 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on

  @javascript
  Scenario: Automatic completion view
    Given I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | No |
      | Completion tracking | Show activity as complete when conditions are met |
      | id_completionview | 1 |
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test podcast name" "pcast" activity with "auto" completion should be marked as not complete
    Then I follow "Test podcast name"
    And I follow "Course 1"
    And the "Test podcast name" "pcast" activity with "auto" completion should be marked as complete

  @javascript
  Scenario: Automatic completion upload 1 episodes
    Given I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | Yes |
      | Completion tracking | Show activity as complete when conditions are met |
      | id_completionepisodesenabled | 1 |
      | id_completionepisodes | 1 |
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test podcast name" "pcast" activity with "auto" completion should be marked as not complete
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I follow "Course 1"
    And the "Test podcast name" "pcast" activity with "auto" completion should be marked as not complete
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I navigate to "Approve episodes" in current page administration
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Approve this episode"
    And I log out

    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test podcast name" "pcast" activity with "auto" completion should be marked as complete
