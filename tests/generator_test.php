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
 * mod_pcast generator tests
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Genarator tests class for mod_pcast.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('pcast', array('course' => $course->id)));
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $records = $DB->get_records('pcast', array('course' => $course->id), 'id');
        $this->assertCount(1, $records);
        $this->assertTrue(array_key_exists($pcast->id, $records));

        $params = array('course' => $course->id, 'name' => 'Another pcast');
        $pcast = $this->getDataGenerator()->create_module('pcast', $params);
        $records = $DB->get_records('pcast', array('course' => $course->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals('Another pcast', $records[$pcast->id]->name);
    }

    public function test_create_content() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $pcast = $this->getDataGenerator()->create_module('pcast', array('course' => $course));
        $pcastgenerator = $this->getDataGenerator()->get_plugin_generator('mod_pcast');

        $episode1 = $pcastgenerator->create_content($pcast);
        $episode2 = $pcastgenerator->create_content($pcast, array('name' => 'Custom episode'));
        $records = $DB->get_records('pcast_episodes', array('pcastid' => $pcast->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($episode1->id, $records[$episode1->id]->id);
        $this->assertEquals($episode2->id, $records[$episode2->id]->id);
        $this->assertEquals('Custom episode', $records[$episode2->id]->name);
    }
}
