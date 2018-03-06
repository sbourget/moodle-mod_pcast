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
 * Restore date tests.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2018 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");
require_once($CFG->dirroot . '/rating/lib.php');

/**
 * Restore date tests.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2018 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_restore_date_testcase extends restore_date_testcase {

    /**
     * Test restore dates.
     */
    public function test_restore_dates() {
        global $DB, $USER;

        $gg = $this->getDataGenerator()->get_plugin_generator('mod_pcast');
        $record = ['assesstimefinish' => 100, 'assesstimestart' => 100, 'ratingtime' => 1, 'assessed' => 2, 'scale' => 1];
        list($course, $pcast) = $this->create_course_and_module('pcast', $record);

        // Pcast episodes.
        $episode1 = $gg->create_content($pcast, array('approved' => 1));
        $gg->create_content($pcast, array('approved' => 0, 'userid' => $USER->id));
        $gg->create_content($pcast, array('approved' => 0, 'userid' => -1));
        $gg->create_content($pcast, array('approved' => 1));
        $timestamp = 10000;
        $DB->set_field('pcast_episodes', 'timecreated', $timestamp);
        $DB->set_field('pcast_episodes', 'timemodified', $timestamp);
        $ratingoptions = new stdClass;
        $ratingoptions->context = context_module::instance($pcast->cmid);
        $ratingoptions->ratingarea = 'episode';
        $ratingoptions->component = 'mod_pcast';
        $ratingoptions->itemid  = $episode1->id;
        $ratingoptions->scaleid = 2;
        $ratingoptions->userid  = $USER->id;
        $rating = new rating($ratingoptions);
        $rating->update_rating(2);
        $rating = $DB->get_record('rating', ['itemid' => $episode1->id]);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newpcast = $DB->get_record('pcast', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($pcast, $newpcast, ['timecreated', 'timemodified']);
        $props = ['assesstimefinish', 'assesstimestart'];
        $this->assertFieldsRolledForward($pcast, $newpcast, $props);

        $newepisodes = $DB->get_records('pcast_episodes', ['pcastid' => $newpcast->id]);
        $newcm = $DB->get_record('course_modules', ['course' => $newcourseid, 'instance' => $newpcast->id]);

        // Episodes test.
        foreach ($newepisodes as $episode) {
            $this->assertEquals($timestamp, $episode->timecreated);
            $this->assertEquals($timestamp, $episode->timemodified);
        }

        // Rating test.
        $newrating = $DB->get_record('rating', ['contextid' => context_module::instance($newcm->id)->id]);
        debugging();
        $this->assertEquals($rating->timecreated, $newrating->timecreated);
        $this->assertEquals($rating->timemodified, $newrating->timemodified);
    }
}
