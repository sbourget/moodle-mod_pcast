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
 * The mod_pcast entry deleted event.
 *
 * @package    mod_pcast
 * @copyright  2014 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pcast\event;

/**
 * The mod_pcast episode deleted event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string concept: the concept of deleted entry.
 *      - string mode: (optional) view mode user was in before deleting entry.
 *      - int|string hook: (optional) hook parameter in the previous view mode.
 * }
 *
 * @package    mod_pcast
 * @since      Moodle 2.7
 * @copyright  2014 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class episode_deleted extends \core\event\base {
    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'pcast_episodes';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventepisodedeleted', 'mod_pcast');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has deleted the podcast episode with id '$this->objectid' in " .
            "the podcast activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        // Entry does not exist any more, returning link to the module view page in the mode it was before deleting entry.
        $params = array('id' => $this->contextinstanceid);
        if (isset($this->other['hook'])) {
            $params['hook'] = $this->other['hook'];
        }
        if (isset($this->other['mode'])) {
            $params['mode'] = $this->other['mode'];
        }
        return new \moodle_url("/mod/pcast/view.php", $params);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (!$this->contextlevel === CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

    /**
     * Used for mapping log data upon restore.
     * @return array
     */
    public static function get_objectid_mapping() {
        return array('db' => 'pcast_episodes', 'restore' => 'pcast_episode');
    }
}

