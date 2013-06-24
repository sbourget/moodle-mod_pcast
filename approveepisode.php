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
 * Page for approving pcast episodes
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$eid = required_param('eid', PARAM_INT);    // Episode ID.

$mode = optional_param('mode', PCAST_APPROVAL_VIEW, PARAM_ALPHANUM);
$hook = optional_param('hook', 'ALL', PARAM_CLEAN);


$episode = $DB->get_record('pcast_episodes', array('id'=> $eid), '*', MUST_EXIST);
$pcast = $DB->get_record('pcast', array('id'=> $episode->pcastid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('pcast', $pcast->id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=> $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/pcast:approve', $context);

$url = new moodle_url('/mod/pcast/approveepisode.php', array('eid'=>$eid, 'mode'=>$mode, 'hook'=>$hook));

$PAGE->set_url($url);
$PAGE->set_context($context);

if (!$episode->approved and confirm_sesskey()) {
    $newepisode = new object();
    $newepisode->id           = $episode->id;
    $newepisode->approved     = 1;
    $newepisode->timemodified = time();
    $DB->update_record("pcast_episodes", $newepisode);
    add_to_log($course->id, "pcast", "approve episode", "showepisode.php?eid=$eid", "$eid", $cm->id);
}

redirect("view.php?id=$cm->id&amp;mode=$mode&amp;hook=$hook");
