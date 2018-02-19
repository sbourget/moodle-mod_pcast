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
 * Glossary search unit tests.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2016 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');
require_once($CFG->dirroot . '/mod/pcast/tests/generator/lib.php');

/**
 * Provides the unit tests for pcast search.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2016 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_search_testcase extends advanced_testcase {

    /**
     * @var string Area id
     */
    protected $episodeareaid = null;

    public function setUp() {
        $this->resetAfterTest(true);
        set_config('enableglobalsearch', true);

        // Set \core_search::instance to the mock_search_engine as we don't require the search engine to be working to test this.
        $search = testable_core_search::instance();

        $this->episodeareaid = \core_search\manager::generate_areaid('mod_pcast', 'episode');
    }

    /**
     * Availability.
     *
     * @return void
     */
    public function test_search_enabled() {

        $searcharea = \core_search\manager::get_search_area($this->episodeareaid);
        list($componentname, $varname) = $searcharea->get_config_var_name();

        // Enabled by default once global search is enabled.
        $this->assertTrue($searcharea->is_enabled());

        set_config($varname . '_enabled', 0, $componentname);
        $this->assertFalse($searcharea->is_enabled());

        set_config($varname . '_enabled', 1, $componentname);
        $this->assertTrue($searcharea->is_enabled());
    }

    /**
     * Indexing contents.
     *
     * @return void
     */
    public function test_entries_indexing() {
        global $DB;

        $searcharea = \core_search\manager::get_search_area($this->episodeareaid);
        $this->assertInstanceOf('\mod_pcast\search\episode', $searcharea);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');

        $record = new stdClass();
        $record->course = $course1->id;

        $this->setUser($user1);

        // Approved entries by default pcast.
        $pcast1 = self::getDataGenerator()->create_module('pcast', $record);
        $episode1 = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast1);
        $episode2 = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast1);

        // All records.
        $recordset = $searcharea->get_recordset_by_timestamp(0);
        $this->assertTrue($recordset->valid());
        $nrecords = 0;
        foreach ($recordset as $record) {
            $this->assertInstanceOf('stdClass', $record);
            $doc = $searcharea->get_document($record);
            $this->assertInstanceOf('\core_search\document', $doc);

            // Static caches are working.
            $dbreads = $DB->perf_get_reads();
            $doc = $searcharea->get_document($record);

            $this->assertEquals($dbreads, $DB->perf_get_reads());
            $this->assertInstanceOf('\core_search\document', $doc);
            $nrecords++;
        }
        // If there would be an error/failure in the foreach above the recordset would be closed on shutdown.
        $recordset->close();
        $this->assertEquals(2, $nrecords);

        // The +2 is to prevent race conditions.
        $recordset = $searcharea->get_recordset_by_timestamp(time() + 2);

        // No new records.
        $this->assertFalse($recordset->valid());
        $recordset->close();
    }

    /**
     * Document contents.
     *
     * @return void
     */
    public function test_entries_document() {

        $searcharea = \core_search\manager::get_search_area($this->episodeareaid);

        $user = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'teacher');

        $record = new stdClass();
        $record->course = $course1->id;

        $this->setUser($user);
        $pcast = self::getDataGenerator()->create_module('pcast', $record);
        $episode = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast);
        $episode->course = $pcast->course;

        $doc = $searcharea->get_document($episode);
        $this->assertInstanceOf('\core_search\document', $doc);
        $this->assertEquals($episode->id, $doc->get('itemid'));
        $this->assertEquals($course1->id, $doc->get('courseid'));
        $this->assertEquals($user->id, $doc->get('userid'));
        $this->assertEquals($episode->name, $doc->get('title'));
        $this->assertEquals(content_to_text($episode->summary, $episode->summaryformat), $doc->get('content'));
    }

    /**
     * Document accesses.
     *
     * @return void
     */
    public function test_entries_access() {

        // Returns the instance as long as the component is supported.
        $searcharea = \core_search\manager::get_search_area($this->episodeareaid);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $course1 = self::getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');

        $record = new stdClass();
        $record->course = $course1->id;

        // Approved entries by default pcast, created by teacher.
        $this->setUser($user1);
        $pcast1 = self::getDataGenerator()->create_module('pcast', $record);
        $teacherapproved = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast1);
        $teachernotapproved = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast1,
                array('approved' => false));

        // Entries need to be approved and created by student.
        $pcast2 = self::getDataGenerator()->create_module('pcast', $record);
        $this->setUser($user2);
        $studentapproved = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast2);
        $studentnotapproved = self::getDataGenerator()->get_plugin_generator('mod_pcast')->create_content($pcast2,
                array('approved' => false));

        $this->setUser($user2);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($teacherapproved->id));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $searcharea->check_access($teachernotapproved->id));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($studentapproved->id));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $searcharea->check_access($studentnotapproved->id));
    }
}

