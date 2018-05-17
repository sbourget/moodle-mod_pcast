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
 * Structure step to restore one pcast activity.
 * @package mod_pcast
 * @subpackage backup-moodle2
 * @copyright 2011 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one pcast activity.
 *
 * @package mod_pcast
 * @subpackage backup-moodle2
 * @copyright 2011 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_pcast_activity_structure_step extends restore_activity_structure_step {

    /**
     * DB structure for a podcast.
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('pcast', '/activity/pcast');
        if ($userinfo) {
            $paths[] = new restore_path_element('pcast_episode', '/activity/pcast/episodes/episode');
            $paths[] = new restore_path_element('pcast_tag', '/activity/pcast/episodes/episode/tags/tag');
            $paths[] = new restore_path_element('pcast_view', '/activity/pcast/episodes/episode/views/view');
            $paths[] = new restore_path_element('pcast_rating', '/activity/pcast/episodes/episode/ratings/rating');

        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Function to restore Podcast activity
     * @param class $data
     */
    protected function process_pcast($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);

        // Insert the pcast record.
        $newitemid = $DB->insert_record('pcast', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('pcast', $oldid, $newitemid);
    }

    /**
     * Function to restore a single episode
     * @param class $data
     */
    protected function process_pcast_episode($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->pcastid = $this->get_new_parentid('pcast');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('pcast_episodes', $data);
        $this->set_mapping('pcast_episode', $oldid, $newitemid, true); // Files by this itemname.
    }

    /**
     * Function to restore a user view records for  single episode
     * @param class $data
     */
    protected function process_pcast_view($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->episodeid = $this->get_mappingid('pcast_episode', $oldid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('pcast_views', $data);
        $this->set_mapping('pcast_views', $oldid, $newitemid, false); // No files attached.

    }

    /**
     * Function to restore user ratings
     * @param class $data
     */
    protected function process_pcast_rating($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created).
        $data->contextid = $this->task->get_contextid();
        $data->itemid    = $this->get_new_parentid('pcast_episode');

        if ($data->scaleid < 0) { // Scale found, get the mapping.
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.

        // Make sure that we have both component and ratingarea set. These were added in 2.1.
        // Prior to that all ratings were for entries so we know what to set them too.
        if (empty($data->component)) {
            $data->component = 'mod_pcast';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'episode';
        }

        $newitemid = $DB->insert_record('rating', $data);

    }

    /**
     * Function to restore user tags
     * @param class $data
     */
    protected function process_pcast_tag($data) {

        $data = (object)$data;
        if (!core_tag_tag::is_enabled('mod_pcast', 'pcast_episodes')) { // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;
        if (!$itemid = $this->get_mappingid('pcast_episode', $data->itemid)) {
            // Some orphaned tag, we could not find the pcast episodes for it - ignore.
            return;
        }

        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_pcast', 'pcast_episodes', $itemid, $context, $tag);
    }

    /**
     * After restore hook, process file attachments.
     */
    protected function after_execute() {
        // Add pcast related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_pcast', 'intro', null);
        $this->add_related_files('mod_pcast', 'logo', null);

        // Add pcast related files, matching by itemname (pcast_episode).

        $this->add_related_files('mod_pcast', 'episode', 'pcast_episode');
    }
}