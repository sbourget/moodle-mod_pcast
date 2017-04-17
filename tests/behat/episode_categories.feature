@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and users can assign categories to their episodes
  As a teacher
  I need to create a podcast, and allow users to set categories

  @javascript
  Scenario: Create a podcast and set categories for each episode.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Zero  | teacher1@example.com |
      | student1 | Student | One   | student1@example.com |
      | student2 | Student | Two   | student2@example.com |
      | student3 | Student | Three | student3@example.com |
      | student4 | Student | Four  | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following config values are set as admin:
      | enablerssfeeds | 1 |
      | pcast_enablerssfeeds | 1 |
      | pcast_enablerssitunes | 1 |
    And the following "activities" exist:
      | activity | name              | intro                    | course | idnumber |episodesperpage | requireapproval | enablerssfeed | enablerssitunes | explicit | userscancategorize |
      | pcast    | Test podcast name | Test podcast description | C1     | pcast1   | 5              | 0               | 1             | 1               | 1        | 1                  |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 1 Episode |
      | Summary | Student 1 episode summary |
      | Category | Food |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 2 Episode |
      | Summary | Student 2 Test episode summary |
      | Category | Shopping |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 3 Episode |
      | Summary | Student 3 Test episode summary |
      | Category | Podcasting |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Student 4 Episode |
      | Summary | Student 4 Test episode summary |
      | Category | K-12 |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"

    # View the categories:
    Then I should see "Student 1 Episode"
    And I should see "Student 2 Episode"
    And I should see "Student 3 Episode"
    And I should see "Student 4 Episode"
    And I follow "Browse by category"

    And I set the field "hook" to "K-12"
    And I click on "K-12" "option" in the "#catmenu select" "css_element"
    And I should not see "Student 1 Episode"
    And I should not see "Student 2 Episode"
    And I should not see "Student 3 Episode"
    And I should see "Student 4 Episode"

    And I set the field "hook" to "Shopping"
    And I click on "Shopping" "option" in the "#catmenu select" "css_element"
    And I should not see "Student 1 Episode"
    And I should see "Student 2 Episode"
    And I should not see "Student 3 Episode"
    And I should not see "Student 4 Episode"
