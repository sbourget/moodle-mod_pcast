@mod @mod_pcast @_file_upload @javascript
Feature: Pcast ratings
  In order to rate past podcast activities
  As a teacher
  I need to enable ratings.

  Background:
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
      | activity | course | name              | intro                    | userscanpost | requireapproval |
      | pcast    | C1     | Test podcast name | Test podcast description | 1            | 0               |

  Scenario: Use ratings to rate student episodes
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Aggregate type" to "Count of ratings"
    And I set the field "id_scale_modgrade_type" to "Scale"
    And I set the field "id_scale_modgrade_scale" to "Separate and Connected ways of knowing"
    And I press "Save and display"
    And I log out

    And I am on the "Test podcast name" Activity page logged in as "student1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out

    When I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I follow "View"
    And I select "Ratings" from the "Browse the podcast using this index" singleselect
    And I set the field "rating" to "Mostly connected knowing"
    And I log out
    And I am on the "Test podcast name" Activity page logged in as "student1"
    And I follow "View"
    Then I should see "1" in the "Total ratings" "table_row"
