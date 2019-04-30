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
 * mod_pcast data generator.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_pcast data generator class.
 *
 * @package    mod_pcast
 * @category   test
 * @copyright  2015 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pcast_generator extends testing_module_generator {

    /**
     * @var int keep track of how many episodes have been created.
     */
    protected $episodecount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->episodecount = 0;
        parent::reset();
    }

    /**
     * Create an instance of mod_pcast with some default settings
     * @param object $record
     * @param array $options
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;

        // Add default values for pcast.
        $record = (array)$record + array(
            'userscancomment' => 0,
            'userscancategorize' => 0,
            'userscanpost' => 1,
            'maxbytes' => $CFG->maxbytes,
            'episodesperpage' => 10,
            'requireapproval' => 1,
            'displayauthor' => 0,
            'displayviews' => 0,
            'image' => 0,
            'imageheight' => 144,
            'imagewidth' => 144,
            'rssepisodes' => 0,
            'rssorder' => 0,
            'enablerssfeed' => 0,
            'enablerssitunes' => 0,
            'explicit' => 0,
            'topcategory' => 0,
            'nestedcategory' => 0,
            'scale' => 100,
            'assessed' => 0,
        );

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a pcast episode (without the attachment)
     * @param object $pcast podcast object
     * @param array $record podcast settings
     * @return object
     */
    public function create_content($pcast, $record = array()) {
        global $DB, $USER;
        $this->episodecount++;
        $now = time();
        $record = (array)$record + array(
            'pcastid' => $pcast->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'userid' => $USER->id,
            'name' => 'Episode '.$this->episodecount,
            'summary' => 'Description of pcast entry '.$this->episodecount,
            'summaryformat' => FORMAT_MOODLE
        );

        // Media File.

        // Episode approval.
        if (!isset($record['approved'])) {
            if ($pcast->requireapproval) {
                $context = context_module::instance($pcast->cmid);
                if (has_capability('mod/pcast:approve', $context)) {
                    // User can approve episodes so automatically approve theirs.
                    $record['approved'] = 1;
                } else {
                    // Episode needs approval.
                    $record['approved'] = 0;
                }

            } else {
                // No approval required.
                $record['approved'] = 1;
            }

        }

        $id = $DB->insert_record('pcast_episodes', $record);

        // Tags.
        if (array_key_exists('tags', $record)) {
            $tags = is_array($record['tags']) ? $record['tags'] : preg_split('/,/', $record['tags']);

            core_tag_tag::set_item_tags('mod_pcast', 'pcast_episodes', $id,
                context_module::instance($pcast->cmid), $tags);
        }

        return $DB->get_record('pcast_episodes', array('id' => $id), '*', MUST_EXIST);
    }
}
