@mod @mod_pcast @_file_upload
Feature: A teacher can create a podcast activity with episodes and apply tags.
  As a teacher
  I need to create a podcast episode with tags

  @javascript
  Scenario: Create a podcast and add an episode with tags
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                    |
      | pcast    | C1     | pcast    | Test podcast name | Test podcast description |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "Test podcast name"
    And I press "Add a new episode"
    And I set the following fields to these values:
      | Title | Test episode name |
      | Summary | Test episode summary |
      | Tags | Example, Entry, Cool |
    And I upload "mod/pcast/tests/fixtures/sample.mp3" file to "Media file" filemanager
    And I press "Save changes"
    Then I should see "Test episode name"
    And I should see "Test episode summary"
    And I should see "Example" in the ".pcast-tags" "css_element"
    And I should see "Entry" in the ".pcast-tags" "css_element"
    And I should see "Cool" in the ".pcast-tags" "css_element"
    And I follow "View"
    And I should see "0 minutes 20 seconds"
    And I should see "Example" in the ".pcast-tags" "css_element"
    And I should see "Entry" in the ".pcast-tags" "css_element"
    And I should see "Cool" in the ".pcast-tags" "css_element"
    And I click on "Edit this episode" "link"
    And I expand all fieldsets
    And I should see "Example" in the ".form-autocomplete-selection" "css_element"
    And I should see "Entry" in the ".form-autocomplete-selection" "css_element"
    And I should see "Cool" in the ".form-autocomplete-selection" "css_element"
    And I log out
