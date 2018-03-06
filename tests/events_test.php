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
 * Unit tests for lib.php
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for pcast events.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_events_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test comment_created event.
     */
    public function test_comment_created() {
        global $CFG;
        require_once($CFG->dirroot . '/comment/lib.php');

        // Create a record for adding comment.
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');

        $episode1 = $pcastgenerator->create_content($pcast);

        $context = context_module::instance($pcast->cmid);
        $cm = get_coursemodule_from_instance('pcast', $pcast->id, $course->id);
        $commentinfo = new stdClass();
        $commentinfo->component = 'mod_pcast';
        $commentinfo->context = $context;
        $commentinfo->course = $course;
        $commentinfo->cm = $cm;
        $commentinfo->area = 'pcast_episode';
        $commentinfo->itemid = $episode1->id;
        $commentinfo->showcount = true;
        $comment = new comment($commentinfo);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $comment->add('New comment');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\comment_created', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $pcast->cmid));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test comment_deleted event.
     */
    public function test_comment_deleted() {
        global $CFG;
        require_once($CFG->dirroot . '/comment/lib.php');

        // Create a record for deleting comment.
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');

        $episode = $pcastgenerator->create_content($pcast);

        $context = context_module::instance($pcast->cmid);
        $cm = get_coursemodule_from_instance('pcast', $pcast->id, $course->id);
        $cmt = new stdClass();
        $cmt->component = 'mod_pcast';
        $cmt->context = $context;
        $cmt->course = $course;
        $cmt->cm = $cm;
        $cmt->area = 'pcast_episode';
        $cmt->itemid = $episode->id;
        $cmt->showcount = true;
        $comment = new comment($cmt);
        $newcomment = $comment->add('New comment 1');

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $comment->delete($newcomment->id);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\comment_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $pcast->cmid));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
    }

    public function test_course_module_viewed() {
        global $DB;
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course->id));

        $dbcourse = $DB->get_record('course', array('id' => $course->id));
        $dbpcast = $DB->get_record('pcast', array('id' => $pcast->id));
        $context = context_module::instance($pcast->cmid);
        $mode = 'letter';

        $event = \mod_pcast\event\course_module_viewed::create(array(
            'objectid' => $dbpcast->id,
            'context' => $context,
            'other' => array('mode' => $mode)
        ));

        $event->add_record_snapshot('course', $dbcourse);
        $event->add_record_snapshot('pcast', $dbpcast);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\course_module_viewed', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $this->assertEquals($pcast->id, $event->objectid);
        $expected = array($course->id, 'pcast', 'view', 'view.php?id=' . $pcast->cmid,
            $pcast->id, $pcast->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEquals(new moodle_url('/mod/pcast/view.php', array('id' => $pcast->cmid, 'mode' => $mode)), $event->get_url());
        $this->assertEventContextNotUsed($event);
    }

    public function test_course_module_instance_list_viewed() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $course = $this->getDataGenerator()->create_course();

        $event = \mod_pcast\event\course_module_instance_list_viewed::create(array(
            'context' => context_course::instance($course->id)
        ));

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\course_module_instance_list_viewed', $event);
        $this->assertEquals(CONTEXT_COURSE, $event->contextlevel);
        $this->assertEquals($course->id, $event->contextinstanceid);
        $expected = array($course->id, 'pcast', 'view all', 'index.php?id='.$course->id, '');
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_episode_created() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $context = context_module::instance($pcast->cmid);

        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $episode = $pcastgenerator->create_content($pcast);

        $eventparams = array(
            'context' => $context,
            'objectid' => $episode->id,
            'other' => array('name' => $episode->name)
        );
        $event = \mod_pcast\event\episode_created::create($eventparams);
        $event->add_record_snapshot('pcast_episodes', $episode);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_created', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "add episode",
            "showepisode.php?eid={$episode->id}", $episode->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_episode_updated() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $context = context_module::instance($pcast->cmid);

        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $episode = $pcastgenerator->create_content($pcast);

        $eventparams = array(
            'context' => $context,
            'objectid' => $episode->id,
            'other' => array('name' => $episode->name)
        );
        $event = \mod_pcast\event\episode_updated::create($eventparams);
        $event->add_record_snapshot('pcast_episodes', $episode);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_updated', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "update episode",
            "showepisode.php?eid={$episode->id}", $episode->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_episode_deleted() {
        global $DB;
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $context = context_module::instance($pcast->cmid);
        $prevmode = 'view';
        $hook = 'ALL';

        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $episode = $pcastgenerator->create_content($pcast);

        $DB->delete_records('pcast_episodes', array('id' => $episode->id));

        $eventparams = array(
            'context' => $context,
            'objectid' => $episode->id,
            'other' => array(
                'mode' => $prevmode,
                'hook' => $hook,
                'name' => $episode->name
            )
        );
        $event = \mod_pcast\event\episode_deleted::create($eventparams);
        $event->add_record_snapshot('pcast_episodes', $episode);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_deleted', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "delete episode",
            "view.php?id={$pcast->cmid}&amp;mode={$prevmode}&amp;hook={$hook}", $episode->id, $pcast->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }


    public function test_episode_approved() {
        global $DB;
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $rolestudent = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $rolestudent->id);
        $teacher = $this->getDataGenerator()->create_user();
        $roleteacher = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $roleteacher->id);

        $this->setUser($teacher);
        $pcast = $this->getDataGenerator()->create_module('pcast',
                array('course' => $course, 'requireapproval' => 1));
        $context = context_module::instance($pcast->cmid);

        $this->setUser($student);
        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $episode = $pcastgenerator->create_content($pcast);
        $this->assertEquals(0, $episode->approved);

        // Approve episode, trigger and validate event.
        $this->setUser($teacher);
        $newepisode = new stdClass();
        $newepisode->id           = $episode->id;
        $newepisode->approved     = true;
        $newepisode->timemodified = time();
        $DB->update_record("pcast_episodes", $newepisode);
        $params = array(
            'context' => $context,
            'objectid' => $episode->id
        );
        $event = \mod_pcast\event\episode_approved::create($params);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_approved', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "approve episode",
            "showepisode.php?eid={$episode->id}", $episode->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        // Disapprove episode, trigger and validate event.
        $this->setUser($teacher);
        $newepisode = new stdClass();
        $newepisode->id           = $episode->id;
        $newepisode->approved     = false;
        $newepisode->timemodified = time();
        $DB->update_record("pcast_episodes", $newepisode);
        $params = array(
            'context' => $context,
            'objectid' => $episode->id
        );
        $event = \mod_pcast\event\episode_disapproved::create($params);

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_disapproved', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "disapprove episode",
            "showepisode.php?eid={$episode->id}", $episode->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_episode_viewed() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $context = context_module::instance($pcast->cmid);

        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $episode = $pcastgenerator->create_content($pcast);

        $event = \mod_pcast\event\episode_viewed::create(array(
            'objectid' => $episode->id,
            'context' => $context
        ));

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_pcast\event\episode_viewed', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($pcast->cmid, $event->contextinstanceid);
        $expected = array($course->id, "pcast", "view episode",
            "showepisode.php?eid={$episode->id}", $episode->id, $pcast->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }
}
