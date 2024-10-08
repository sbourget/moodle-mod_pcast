@mod @mod_pcast @_file_upload
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
    Given I add a pcast activity to course "Course 1" section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | ID number | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | No |
      | Add requirements  | 1 |
      | View the activity | 1 |
    And I am on the "Test podcast name" Activity page
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And the "View" completion condition of "Test podcast name" is displayed as "todo"
    Then I am on the "Test podcast name" Activity page
    And I follow "Course 1"
    And the "View" completion condition of "Test podcast name" is displayed as "done"

  @javascript
  Scenario: Automatic completion upload 1 episodes
    Given I add a pcast activity to course "Course 1" section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | ID number | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | Yes |
      | Add requirements  | 1 |
      | completionepisodesenabled | 1 |
      | completionepisodes | 1 |
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Add 1 episode(s)" completion condition of "Test podcast name" is displayed as "todo"
    When I am on the "Test podcast name" Activity page
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I follow "Course 1"
    And the "Add 1 episode(s)" completion condition of "Test podcast name" is displayed as "todo"
    And I log out

    And I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I navigate to "Approve episodes" in current page administration
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Approve this episode"
    And I log out

    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Add 1 episode(s)" completion condition of "Test podcast name" is displayed as "done"
