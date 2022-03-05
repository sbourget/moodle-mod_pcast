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

namespace mod_pcast\output;

use plugin_renderer_base;

/**
 * Class actionbar - Display the action bar
 *
 * @package   mod_pcast
 * @copyright 2021 Stephen Bouorget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the pcast tertiary nav
     *
     * @param standard_action_bar $actionmenu
     * @return bool|string
     * @throws \moodle_exception
     */
    public function main_action_bar(standard_action_bar $actionmenu) {
        $context = $actionmenu->export_for_template($this);
        return $this->render_from_template('mod_pcast/standard_action_menu', $context);
    }

    /**
     * Render the pcast tertiary nav for episodes
     *
     * @param episode_action_bar $actionmenu
     * @return bool|string
     * @throws \moodle_exception
     */
    public function episode_action_bar(episode_action_bar $actionmenu) {
        $context = $actionmenu->export_for_template($this);
        return $this->render_from_template('mod_pcast/episode_action_menu', $context);
    }
}
