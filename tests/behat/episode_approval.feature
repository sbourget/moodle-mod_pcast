@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and require episodes to be approved before viewing
  As a teacher
  I need to create a podcast and require episode approval

  @javascript
  Scenario: Create a podcast and approve episodes
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | Yes |
    And I log out
    When I log in as "student1"
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
    And I follow "View"
    And I should see "0 minutes 20 seconds"
    And I log out

    Then I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should not see "Test episode name"
    And I should not see "Test episode summary"
    And I log out

    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should not see "Test episode name"
    And I should not see "Test episode summary"
    And I navigate to "Approve episodes" node in "Podcast administration"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Approve this episode"
    And I log out

    Then I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I log out

    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "Disapprove this episode"
    And I log out

    Then I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should not see "Test episode name"
    And I should not see "Test episode summary"
    And I log out