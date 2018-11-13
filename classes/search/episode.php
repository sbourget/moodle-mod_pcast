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
 * Pcast episodes search.
 *
 * @package   mod_pcast
 * @copyright 2016 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pcast\search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pcast/locallib.php');
require_once($CFG->dirroot . '/mod/pcast/lib.php');


/**
 * Pcast episode search.
 *
 * @package   mod_pcast
 * @copyright 2016 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class episode extends \core_search\base_mod {

    /**
     * @var array Internal quick static cache.
     */
    protected $episodedata = array();

    /**
     * Returns recordset containing required data for indexing pcast episodes.
     *
     * @param int $modifiedfrom timestamp
     * @return moodle_recordset
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        global $DB;

        $sql = "SELECT pe.*, p.course FROM {pcast_episodes} pe
                  JOIN {pcast} p ON p.id = pe.pcastid
                 WHERE pe.timemodified >= ?";
        return $DB->get_recordset_sql($sql, array($modifiedfrom));
    }

    /**
     * Returns the documents associated with this pcast episode id.
     *
     * @param stdClass $episode pcast episode.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($episode, $options = array()) {

        try {
            $cm = $this->get_cm('pcast', $episode->pcastid, $episode->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_pcast ' . $episode->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_pcast' . $episode->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($episode->id, $this->componentname, $this->areaname);
        $doc->set('title', $episode->name);
        $doc->set('content', content_to_text($episode->summary, $episode->summaryformat));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $episode->course);
        $doc->set('userid', $episode->userid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $episode->timemodified);

        // Check if this document should be considered new.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $episode->timecreated)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        // Adding keywords as extra info.
            $doc->set('description1', $episode->keywords);

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Pcast episode id
     * @return bool
     */
    public function check_access($id) {

        try {
            $episode = $this->get_episode($id);
            $cminfo = $this->get_cm('pcast', $episode->pcastid, $episode->course);
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Check to see if the user can actually see the episode.
        if (!pcast_episode_allowed_viewing($episode, $cminfo, groups_get_activity_groupmode($cminfo))) {
                return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to pcast episode.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {

        return new \moodle_url('/mod/pcast/showepisode.php', array('eid' => $doc->get('itemid')));
    }

    /**
     * Link to the pcast.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/mod/pcast/view.php', array('id' => $contextmodule->instanceid));
    }

    /**
     * Returns the specified pcast episode checking the internal cache.
     *
     * Store minimal information as this might grow.
     *
     * @throws \dml_exception
     * @param int $episodeid
     * @return stdClass
     */
    protected function get_episode($episodeid) {
        global $DB;

        if (empty($this->episodedata[$episodeid])) {
            $this->episodedata[$episodeid] = $DB->get_record_sql("SELECT pe.*, p.course, p.requireapproval
                                                                      FROM {pcast_episodes} pe
                                                                      JOIN {pcast} p ON p.id = pe.pcastid
                                                                     WHERE pe.id = ?", array('id' => $episodeid), MUST_EXIST);
        }
        return $this->episodedata[$episodeid];
    }

    /**
     * Allows file indexing of attached files
     * @return boolean
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Index attached media files.
     * @param object $document
     */
    public function attach_files($document) {

        $episodeid = $document->get('itemid');
        try {
            $episode = $this->get_episode($episodeid);
        } catch (\dml_missing_record_exception $e) {
            unset($this->postsdata[$episodeid]);
            debugging('Could not get record to attach files to '.$document->get('id'), DEBUG_DEVELOPER);
            return;
        }

        // Because this is used during indexing, we don't want to cache episodes. Would result in memory leak.
        unset($this->episodedata[$episodeid]);
        $cm = $this->get_cm('pcast', $episode->pcastid, $document->get('courseid'));
        $context = \context_module::instance($cm->id);

        // Get the files and attach them.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_pcast', 'episode', $episode->id, "timemodified", false);

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }
}
