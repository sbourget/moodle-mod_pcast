@mod @mod_pcast @_file_upload @javascript
Feature: Pcast reset
  In order to reuse past podcast activities
  As a teacher
  I need to remove all previous data.

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
      | activity | course | name              | intro                    | userscancomment | displayviews | userscanpost | requireapproval |
      | pcast    | C1     | Test podcast name | Test podcast description | 1               | 1            | 1            | 0               |

  Scenario: Use course reset to remove all episode comments
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out

    And I am on the "Test podcast name" Activity page logged in as "student1"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I select "Commenting" from the "Browse the podcast using this index" singleselect
    And I click on ".comment-link" "css_element"
    And I set the field "content" to "First student comment"
    And I follow "Save comment"
    And I should see "First student comment"
    And I log out

    And I log in as "teacher1"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I set the following fields to these values:
        | id_reset_pcast_comments | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "All comments"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I select "Commenting" from the "Browse the podcast using this index" singleselect
    And I should not see "First student comment"

  Scenario: Use course reset to delete all episodes
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I set the following fields to these values:
        | All episodes from all podcasts | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "All episodes from all podcasts"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should not see "Test episode name"

  Scenario: Use course reset to remove episodes of non-enrolled users
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out

    And I am on the "Test podcast name" Activity page logged in as "admin"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode admin |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I set the following fields to these values:
        | Episodes by users not enrolled | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "Episodes by users not enrolled"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should see "Test episode name"
    And I should not see "Test episode admin"

  Scenario: Use course reset to remove the episode view history
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I follow "audio/mp3"
    And I wait until the page is ready
    And I press the "back" button in the browser
    And I follow "Course 1"
    And I am on the "Test podcast name" Activity page
    And I follow "View"
    And I should see "2" in the "Total views" "table_row"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I expand all fieldsets
    And I set the following fields to these values:
        | Episode view history | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "Episode view history"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
   # And I follow "audio/mp3"
   # And I wait until the page is ready
   # And I press the "back" button in the browser
    And I should see "0" in the "Total views" "table_row"

  Scenario: Use course reset to remove all episode ratings
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

    And I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I follow "View"
    And I select "Rating" from the "Browse the podcast using this index" singleselect
    And I set the field "rating" to "Mostly connected knowing"
    And I select "View Episode" from the "Browse the podcast using this index" singleselect
    And I should see "1" in the "Total ratings" "table_row"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I expand all fieldsets
    And I set the following fields to these values:
        | id_reset_pcast_ratings | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "All ratings"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I should see "0" in the "Total ratings" "table_row"

  Scenario: Use course reset to remove all episode tags
    Given I am on the "Test podcast name" Activity page logged in as "teacher1"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
      | Tags | Example, Entry, Cool |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    Then I should see "Test episode name"
    And I should see "Example" in the ".pcast-tags" "css_element"
    When I am on the "Course 1" "reset" page
    And I press "Deselect all"
    And I expand all fieldsets
    And I set the following fields to these values:
        | id_reset_pcast_tags | 1 |
    And I press "Reset course"
    And I click on "Reset course" "button" in the "Reset course?" "dialogue"
    And I should see "Episode tags"
    Then I should see "Done"
    And I press "Continue"
    And I am on the "Test podcast name" Activity page
    And I should see "Test episode name"
    And I should not see "Example" in the ".lastcol" "css_element"
    And I should not see "Entry" in the ".lastcol" "css_element"
    And I should not see "Cool" in the ".lastcol" "css_element"
