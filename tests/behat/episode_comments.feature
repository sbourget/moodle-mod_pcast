@mod @mod_pcast @file_upload
Feature: A teacher can create a podcast activity and allow student comments
  As a teacher
  I need to create a podcast and enable commenting

  @javascript
  Scenario: Create a podcast and add an episode
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
    When I add a "Podcast" to section "1" and I fill the form with:
      | Podcast name | Test podcast name |
      | Description | Test podcast description |
      | Allow users to post episodes | Yes |
      | Require approval for episodes | No |
      | Allow user comments | Yes |
    And I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I follow "Comment"
    And I click on ".comment-link" "css_element"
    And I set the field "content" to "First student comment"
    And I follow "Save comment"
    And I should see "First student comment"
    And I log out

    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I follow "Comment"
    And I click on ".comment-link" "css_element"
    And I set the field "content" to "Second student BROKEN comment"
    And I follow "Save comment"
    And I should see "Second student BROKEN comment"
    And I follow "Delete this comment"
    # Wait for the animation to finish.
    And I wait "2" seconds
    And I should not see "Second student BROKEN comment"
    And I set the field "content" to "Second student comment"
    And I follow "Save comment"
    And I log out

    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test podcast name"
    And I should see "Test episode name"
    And I should see "Test episode summary"
    And I follow "View"
    And I should see "Total comments"
    And I should see "2"
    And I log out