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

use moodle_url;
use context_module;
use renderable;
use renderer_base;
use single_button;
use templatable;
use url_select;

/**
 * Class episode_action_bar - Display the action bar
 *
 * @package   mod_pcast
 * @copyright 2021 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class episode_action_bar implements renderable, templatable {
    /** @var object $cm The course module. */
    private $cm;
    /** @var object $pcast instance of the pcast module */
    private $module;
    /** @var string $mode The type of view. */
    private $eid;
    /** @var int episode ID number */
    private $mode;
    /** @var string the type of view for episode. */
    private $epmode;
    /** @var bool is rating enabled. */
    private $rate;
    /** @var bool views are allowed. */
    private $views;
    /** @var bool comment are comments enabled. */
    private $comment;
    /** @var int $context The context of the pcst. */
    private $context;

    /**
     * episode_action_bar constructor.
     *
     * @param object $cm
     * @param object $module
     * @param int $eid
     * @param int $mode
     * @param bool $rate
     * @param bool $comment
     * @param bool $views
     * @throws \coding_exception
     */
    public function __construct(object $cm, object $module, int $eid, int $mode, bool $rate, bool $comment, bool $views) {
        $this->cm = $cm;
        $this->module = $module;
        $this->eid = $eid;
        $this->mode = $mode;
        $this->rate = $rate;
        $this->comment = $comment;
        $this->views = $views;
        $this->context = context_module::instance($this->cm->id);

    }

    /**
     * Export the action bar
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return [
            'addnewbutton' => $this->create_back_button($output),
            'tabjumps' => $this->generate_tab_jumps($output),
        ];

    }

    /**
     * Render the add entry button
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    private function create_back_button(renderer_base $output): \stdClass {
        $btn = new single_button(new moodle_url('/mod/pcast/view.php', array('id' => $this->cm->id, 'mode' => PCAST_STANDARD_VIEW)),
            get_string('backtoepisodes', 'pcast'), 'post', single_button::BUTTON_SECONDARY);
        return $btn->export_for_template($output);
    }

    /**
     * Generate a url select to match any types of pcast views
     *
     * @param renderer_base $output
     * @return \stdClass|null
     */
    private function generate_tab_jumps(renderer_base $output) {
        $cm = $this->cm;
        $mode = $this->mode;
        $options = [];

        $stdbaseurl = new moodle_url('/mod/pcast/showepisode.php', array('eid' => $this->eid, 'mode' => PCAST_EPISODE_VIEW));
        $options[get_string('episodeview', 'pcast')] = $stdbaseurl->out(false);

        if ($this->comment || $this->rate) {
            $rateurl = new moodle_url('/mod/pcast/showepisode.php', array('eid' => $this->eid,
                'mode' => PCAST_EPISODE_COMMENT_AND_RATE),
                );

            if ($this->comment && $this->rate) {
                // Both comments and ratings.
                $options[get_string('episodecommentandrateview', 'pcast')] = $rateurl->out(false);
            } else if ($this->comment && !$this->rate) {
                // Comments only.
                $options[get_string('episodecommentview', 'pcast')] = $rateurl->out(false);
            } else if (!$this->comment && $this->rate) {
                // Ratings only.
                $options[get_string('episoderateview', 'pcast')] = $rateurl->out(false);
            }
        }

        if ($this->views) {
            $viewsbaseurl = new moodle_url('/mod/pcast/showepisode.php', array('eid' => $this->eid, 'mode' => PCAST_EPISODE_VIEWS));
            $options[get_string('episodeviews', 'pcast')] = $viewsbaseurl->out(false);
        }

        if ($mode == PCAST_EPISODE_VIEW) {
            $active = $stdbaseurl->out(false);
        } else if ($mode == PCAST_EPISODE_COMMENT_AND_RATE) {
            $active = $rateurl->out(false);
        } else if ($mode == PCAST_EPISODE_VIEWS) {
            $active = $viewsbaseurl->out(false);
        } else {
            $active = $stdbaseurl->out(false);
        }

        if (count($options) > 1) {
            $select = new url_select(array_flip($options), $active, null);
            $select->set_label(get_string('explainalphabet', 'pcast'), ['class' => 'sr-only']);
            return $select->export_for_template($output);
        }

        return null;
    }
}
