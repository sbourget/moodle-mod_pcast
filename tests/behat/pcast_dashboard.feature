@mod @mod_pcast @file_upload
Feature: Users can review the status of a podcast in the course overview block
  As a User
  I need to view the course overview block to check the status of a podcast.

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
      | activity | course | idnumber | name              | intro                    | userscanpost | requireapproval |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description | 1            | 1               |

  Scenario: Nothing has been added to a podcast
    Given I log in as "teacher1"
    Then I should not see "You have Podcasts that need attention"

  @javascript
  Scenario: A student uploads an episode
    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I log out
    When I log in as "teacher1"
    Then I should see "You have Podcasts that need attention"
    And I click on ".collapsibleregioncaption" "css_element"
    And I should see "There are 1 episodes awaiting approval"

  @javascript
  Scenario: A student uploads an episode which is approved
    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    When I log in as "teacher1"
    And I should see "You have Podcasts that need attention"
    And I click on ".collapsibleregioncaption" "css_element"
    And I follow "Test podcast name"
    And I navigate to "Approve episodes" node in "Podcast administration"
    And I should see "Test episode name"
    And I follow "Approve this episode"
    And I log out
    Then I log in as "student1"
    And I should see "You have Podcasts that need attention"
    And I click on ".collapsibleregioncaption" "css_element"
    And I should see "There are 1 new episodes since you last login"
