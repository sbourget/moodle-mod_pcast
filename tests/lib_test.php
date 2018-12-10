<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Pcast lib tests.
 *
 * @package    mod_pcast
 * @copyright  2018 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/pcast/lib.php');
require_once($CFG->dirroot . '/mod/pcast/locallib.php');

/**
 * Pcast lib testcase.
 *
 * @package    mod_pcast
 * @copyright  2018 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_lib_testcase extends advanced_testcase {

    /**
     * Test calendar event creation.
     */
    public function test_pcast_core_calendar_provide_event_action() {
        $this->resetAfterTest();
        $this->setAdminUser();
        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id));
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory);
        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event read as a non-user.
     */
    public function test_pcast_core_calendar_provide_event_action_as_non_user() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id));
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event for the student.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory);
        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event read as a user.
     */
    public function test_pcast_core_calendar_provide_event_action_for_user() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // Create the activity.
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id));
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event for the student.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory, $student->id);
        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event read for an activity in a hidden section.
     */
    public function test_pcast_core_calendar_provide_event_action_in_hidden_section() {
        $this->resetAfterTest();
        $this->setAdminUser();
        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // Create the activity.
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id));
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Set sections 0 as hidden.
        set_section_visible($course->id, 0, 0);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event for the student.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory, $student->id);
        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event read for an activity already completed.
     */
    public function test_pcast_core_calendar_provide_event_action_already_completed() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));
        // Get some additional data.
        $cm = get_coursemodule_from_instance('pcast', $pcast->id);
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Mark the activity as completed.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory);
        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event read for an activity already completed by the user.
     */
    public function test_pcast_core_calendar_provide_event_action_already_completed_for_user() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // Create the activity.
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id),
                array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));
        // Get some additional data.
        $cm = get_coursemodule_from_instance('pcast', $pcast->id);
        // Create a calendar event.
        $event = $this->create_action_event($course->id, $pcast->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
        // Mark the activity as completed for the user.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $student->id);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        // Decorate action event.
        $actionevent = mod_pcast_core_calendar_provide_event_action($event, $factory, $student->id);
        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'pcast';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();
        return calendar_event::create($event);
    }
}