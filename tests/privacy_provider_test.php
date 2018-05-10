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
 * Privacy provider tests.
 *
 * @package     mod_pcast
 * @copyright   2018 Stephen Bourget
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use mod_pcast\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/comment/lib.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_pcast
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The student object. */
    protected $student;

    /** @var stdClass The teacher object. */
    protected $teacher;

    /** @var stdClass The pcast object. */
    protected $pcast;

    /** @var stdClass The course object. */
    protected $course;

    /** @var stdClass The plugin generator object. */
    protected $plugingenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        $this->resetAfterTest();

        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->course = $course;

        $this->plugingenerator = $generator->get_plugin_generator('mod_pcast');

        // The pcast activity the user will answer.
        $pcast = $this->plugingenerator->create_instance(['course' => $course->id]);
        $this->pcast = $pcast;

        $cm = get_coursemodule_from_instance('pcast', $pcast->id);
        $context = context_module::instance($cm->id);

        // Create a student which will add an episode to a pcast.
        $student = $generator->create_user();
        $generator->enrol_user($student->id,  $course->id, 'student');
        $this->student = $student;

        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id,  $course->id, 'editingteacher');
        $this->teacher = $teacher;

        $this->setUser($student->id);
        $pe1 = $this->plugingenerator->create_content($pcast, ['concept' => 'first', 'approved' => 1]);

        // Student create a comment on a pcast episode.
        $this->setUser($student);
        $comment = $this->get_comment_object($context, $pe1->id);
        $comment->add('Hello, it\'s me!');

        // Attach tags.
        core_tag_tag::set_item_tags('mod_pcast', 'pcast_episodes', $pe1->id, $context, ['Beer', 'Golf']);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_pcast');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(5, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('pcast_episodes', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('pcastid', $privacyfields);
        $this->assertArrayHasKey('name', $privacyfields);
        $this->assertArrayHasKey('summary', $privacyfields);
        $this->assertArrayHasKey('mediafile', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);

        $this->assertEquals('privacy:metadata:pcast_episodes', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $cm = get_coursemodule_from_instance('pcast', $this->pcast->id);

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $cm = get_coursemodule_from_instance('pcast', $this->pcast->id);
        $cmcontext = context_module::instance($cm->id);

        // Export all of the data for the context.
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);
        $contextlist = new \core_privacy\local\request\approved_contextlist($this->student, 'mod_pcast' , [$cmcontext->id]);

        \mod_pcast\privacy\provider::export_user_data($contextlist);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([]);

        $this->assertEquals('Podcast 1', $data->name);
        $this->assertEquals('Episode 1', $data->episodes[0]['name']);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_instance('pcast', $this->pcast->id);
        $context = context_module::instance($cm->id);
        // Create another student who will add an episode the pcast activity.
        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $this->course->id, 'student');

        $this->setUser($student2);
        $pe3 = $this->plugingenerator->create_content($this->pcast, ['name' => 'Episode 1', 'approved' => 1]);
        $comment = $this->get_comment_object($context, $pe3->id);
        $comment->add('User 2 comment');

        core_tag_tag::set_item_tags('mod_pcast', 'pcast_episodes', $pe3->id, $context, ['Pizza', 'Noodles']);

        // As a teacher, rate student 2 episode.
        $this->setUser($this->teacher);
        $rating = $this->get_rating_object($context, $pe3->id);
        $rating->update_rating(2);

        // Before deletion, we should have 2 episodes.
        $count = $DB->count_records('pcast_episodes', ['pcastid' => $this->pcast->id]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        provider::delete_data_for_all_users_in_context($context);

        // After deletion, the pcast episodes for that pcast activity should have been deleted.
        $count = $DB->count_records('pcast_episodes', ['pcastid' => $this->pcast->id]);
        $this->assertEquals(0, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_pcast', 'itemtype' => 'pcast_episodes',
            'itemid' => $pe3->id]);
        $this->assertEquals(0, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_pcast', 'commentarea' => 'pcast_episode',
            'itemid' => $pe3->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $commentcount);

        $ratingcount = $DB->count_records('rating', ['component' => 'mod_pcast', 'ratingarea' => 'episode',
            'itemid' => $pe3->id]);
        $this->assertEquals(0, $ratingcount);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;
        $generator = $this->getDataGenerator();

        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $this->course->id, 'student');

        $cm1 = get_coursemodule_from_instance('pcast', $this->pcast->id);
        $pcast2 = $this->plugingenerator->create_instance(['course' => $this->course->id]);
        $cm2 = get_coursemodule_from_instance('pcast', $pcast2->id);

        $ge1 = $this->plugingenerator->create_content($this->pcast, ['concept' => 'first user pcast episode', 'approved' => 1]);
        $this->plugingenerator->create_content($pcast2, ['concept' => 'first user second pcast episode', 'approved' => 1]);

        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);
        core_tag_tag::set_item_tags('mod_pcast', 'pcast_episodes', $ge1->id, $context1, ['Parmi', 'Sushi']);

        $this->setUser($student2);
        $pe3 = $this->plugingenerator->create_content($this->pcast, ['concept' => 'second user pcast episode',
                'approved' => 1]);

        $comment = $this->get_comment_object($context1, $pe3->id);
        $comment->add('User 2 comment');

        core_tag_tag::set_item_tags('mod_pcast', 'pcast_episodes', $pe3->id, $context1, ['Pizza', 'Noodles']);

        // As a teacher, rate student 2 episode.
        $this->setUser($this->teacher);
        $rating = $this->get_rating_object($context1, $pe3->id);
        $rating->update_rating(2);

        // Before deletion, we should have 3 episodes, one rating and 2 tag instances.
        $count = $DB->count_records('pcast_episodes', ['pcastid' => $this->pcast->id]);
        $this->assertEquals(3, $count);
        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_pcast', 'itemtype' => 'pcast_episodes',
            'itemid' => $pe3->id]);
        $this->assertEquals(2, $tagcount);
        $ratingcount = $DB->count_records('rating', ['component' => 'mod_pcast', 'ratingarea' => 'episode',
            'itemid' => $pe3->id]);
        $this->assertEquals(1, $ratingcount);
        // Create another student who will add an episode to the first pcast.
        $contextlist = new \core_privacy\local\request\approved_contextlist($student2, 'pcast',
            [$context1->id, $context2->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the pcast episode and tags for the second student should have been deleted.
        $count = $DB->count_records('pcast_episodes', ['pcastid' => $this->pcast->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_pcast', 'itemtype' => 'pcast_episodes',
                'itemid' => $pe3->id]);
        $this->assertEquals(0, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_pcast', 'commentarea' => 'pcast_episode',
                'itemid' => $pe3->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $commentcount);

        $ratingcount = $DB->count_records('rating', ['component' => 'mod_pcast', 'ratingarea' => 'episode',
                'itemid' => $pe3->id]);
        $this->assertEquals(0, $ratingcount);

        // Student's 1 episodes, comments and tags should not be removed.
        $count = $DB->count_records('pcast_episodes', ['pcastid' => $this->pcast->id,
                'userid' => $this->student->id]);
        $this->assertEquals(2, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_pcast', 'itemtype' => 'pcast_episodes',
            'itemid' => $ge1->id]);
        $this->assertEquals(2, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_pcast', 'commentarea' => 'pcast_episode',
             'userid' => $this->student->id]);
        $this->assertEquals(1, $commentcount);
    }

    /**
     * Get the comment area for pcast module.
     *
     * @param context $context The context.
     * @param int $itemid The item ID.
     * @return comment
     */
    protected function get_comment_object(context $context, $itemid) {
        $args = new stdClass();

        $args->context = $context;
        $args->course = get_course(SITEID);
        $args->area = 'pcast_episode';
        $args->itemid = $itemid;
        $args->component = 'mod_pcast';
        $comment = new comment($args);
        $comment->set_post_permission(true);

        return $comment;
    }

    /**
     * Get the rating area for pcast module.
     *
     * @param context $context The context.
     * @param int $itemid The item ID.
     * @return rating object
     */
    protected function get_rating_object(context $context, $itemid) {
        global $USER;

        $ratingoptions = new stdClass;
        $ratingoptions->context = $context;
        $ratingoptions->ratingarea = 'episode';
        $ratingoptions->component = 'mod_pcast';
        $ratingoptions->itemid  = $itemid;
        $ratingoptions->scaleid = 2;
        $ratingoptions->userid  = $USER->id;
        return new rating($ratingoptions);
    }
}
