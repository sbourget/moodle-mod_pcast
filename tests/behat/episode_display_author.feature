@mod @mod_pcast @_file_upload
Feature: A teacher can set a podcast activity to display the names of the authors
  As a teacher
  I need to create a podcast and set it to display author names

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
      | student2 | Student | Two | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario: Create a podcast and do not display author names
    Given the following "activities" exist:
      | activity | name              | intro                    | course | episodesperpage | requireapproval | userscanpost | displayauthor |
      | pcast    | Test podcast name | Test podcast description | C1     | 5               | 0               | 1            | 0             |
    And I am on the "Test podcast name" Activity page logged in as "student1"
    When I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    Then I am on the "Test podcast name" Activity page logged in as "student2"
    And I should see "Test episode name"
    And I should not see "Student One"
    And I log out

  @javascript
  Scenario: Create a podcast and do display author names
    Given the following "activities" exist:
      | activity | name              | intro                    | course | episodesperpage | requireapproval | userscanpost | displayauthor |
      | pcast    | Test podcast name | Test podcast description | C1     | 5               | 0               | 1            | 1             |
    And I am on the "Test podcast name" Activity page logged in as "student1"
    When I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    Then I am on the "Test podcast name" Activity page logged in as "student2"
    And I should see "Test episode name"
    And I should see "Student One"
    And I log out
