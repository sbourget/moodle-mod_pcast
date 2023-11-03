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
 * Class standard_action_bar - Display the action bar
 *
 * @package   mod_pcast
 * @copyright 2021 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class standard_action_bar implements renderable, templatable {
    /** @var object $cm The course module. */
    private $cm;
    /** @var object $pcast instance of the pcast module */
    private $module;
    /** @var string $mode The type of view. */
    private $mode;
    /** @var string $hook The term, entry, cat, etc... to look for based on mode. */
    private $hook;
    /** @var string $sortkey Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME. */
    private $sortkey;
    /** @var string $sortorder The sort order (ASC or DESC). */
    private $sortorder;
    /** @var int $context The context of the pcst. */
    private $context;

    /**
     * standard_action_bar constructor.
     *
     * @param object $cm
     * @param object $module
     * @param int $mode
     * @param string $hook
     * @param string $sortkey
     * @param string $sortorder
     * @throws \coding_exception
     */
    public function __construct(object $cm, object $module, int $mode, string $hook, string $sortkey, string $sortorder) {
        $this->cm = $cm;
        $this->module = $module;
        $this->mode = $mode;
        $this->hook = $hook;
        $this->sortkey = $sortkey;
        $this->sortorder = $sortorder;
        $this->context = context_module::instance($this->cm->id);

    }

    /**
     * Export the action bar
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        // Show the add entry button if allowed (usercan post + write or write + manage  or write + approve caps).
        if (((has_capability('mod/pcast:write', $this->context)) && ($this->module->userscanpost))
            || (has_capability('mod/pcast:write', $this->context) && has_capability('mod/pcast:manage', $this->context))
            || (has_capability('mod/pcast:write', $this->context) && has_capability('mod/pcast:approve', $this->context))) {
            return [
                'addnewbutton' => $this->create_add_button($output),
                'tools' => $this->get_additional_tools($output),
                'tabjumps' => $this->generate_tab_jumps($output),
            ];
        } else {
            // No access to the Add new episode button.
            return ['tabjumps' => $this->generate_tab_jumps($output)];
        }
    }

    /**
     * Render the add entry button
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    private function create_add_button(renderer_base $output): \stdClass {
        $btn = new single_button(new moodle_url('/mod/pcast/edit.php', ['cmid' => $this->cm->id]),
            get_string('addnewepisode', 'pcast'), 'post', single_button::BUTTON_PRIMARY);
        return $btn->export_for_template($output);
    }

    /**
     * Render the additional tools required by the pcast
     *
     * @param renderer_base $output
     * @return array
     */
    private function get_additional_tools(renderer_base $output): array {
        global $USER, $CFG, $PAGE;
        $items = [];
        $buttons = [];
        $pcastconfig = get_config('mod_pcast');
        if (!empty($CFG->enablerssfeeds) && !empty($pcastconfig->enablerssfeeds) && $this->module->enablerssfeed) {
            require_once("$CFG->libdir/rsslib.php");
            $string = get_string('rsslink', 'pcast');

            // Calculate group for path.
            $groupmode = groups_get_activity_groupmode($PAGE->cm);
            if ($groupmode > 0) {
                $currentgroup = groups_get_activity_group($PAGE->cm);
            } else {
                $currentgroup = 0;
            }

            $args = $this->module->id . '/'.$currentgroup;
            $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $USER->id, 'mod_pcast', $args));
            $buttons[$string] = $url->out(false);
        }

        if (!empty($pcastconfig->enablerssitunes) && $this->module->enablerssitunes) {
            $string = get_string('pcastlink', 'pcast');
            require_once("$CFG->dirroot/mod/pcast/rsslib.php");
            $url = pcast_rss_get_url($PAGE->cm->context->id, $USER->id, 'pcast', $args);
            $buttons[$string] = $url->out(false);
        }

        foreach ($items as $key => $value) {
            $items[$key] = $value->export_for_template($output);
        }

        if ($buttons) {
            foreach ($buttons as $index => $value) {
                $items['select']['options'][] = [
                    'url' => $value,
                    'string' => $index,
                ];
            }
        }

        return $items;
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

        $stdbaseurl = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_STANDARD_VIEW));
        $options[get_string('standardview', 'pcast')] = $stdbaseurl->out(false);

        if ($this->module->userscancategorize) {
            $catbaseurl = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_CATEGORY_VIEW));
            $options[get_string('categoryview', 'pcast')] = $catbaseurl->out(false);
        }
        $datebaseurl = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_DATE_VIEW));
        $options[get_string('dateview', 'pcast')] = $datebaseurl->out(false);

        $authorbaseurl = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_AUTHOR_VIEW));
        $options[get_string('authorview', 'pcast')] = $authorbaseurl->out(false);

        if ($this->module->requireapproval) {
            if (has_capability('mod/pcast:approve', $cm->context)) {
                $approvebaseurl = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_APPROVAL_VIEW));
                $options[get_string('waitingapproval', 'pcast')] = $approvebaseurl->out(false);
            }
        }

        if ($mode == PCAST_STANDARD_VIEW) {
            $active = $stdbaseurl->out(false);
        } else if ($mode == PCAST_CATEGORY_VIEW) {
            $active = $catbaseurl->out(false);
        } else if ($mode == PCAST_DATE_VIEW) {
            $active = $datebaseurl->out(false);
        } else if ($mode == PCAST_AUTHOR_VIEW) {
            $active = $authorbaseurl->out(false);
        } else if ($mode == PCAST_APPROVAL_VIEW) {
            $active = $approvebaseurl->out(false);
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
