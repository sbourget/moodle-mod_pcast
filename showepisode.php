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
 *
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget and Jillaine Beeckman
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');


$eid      = optional_param('eid', 0, PARAM_INT); // pcast episode id
$mode      = optional_param('mode', PCAST_EPISODE_VIEW, PARAM_INT); // pcast episode id

$popup = optional_param('popup',0, PARAM_INT);

$url = new moodle_url('/mod/pcast/showepisode.php');
$url->param('eid', $eid);
$PAGE->set_url($url);

if ($eid) {
    $sql = pcast_get_episode_sql();
    $sql .=  " WHERE p.id = ?";
    $episode = $DB->get_record_sql($sql,array($eid), MUST_EXIST);
    $pcast = $DB->get_record('pcast', array('id'=>$episode->pcastid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('pcast', $pcast->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $episode->pcastname = $pcast->name;
    $episode->cmid = $cm->id;
    $episode->courseid = $cm->course;
    $episodes = array($episode);

} else {
    print_error('invalidelementid');
}

if (!empty($episode->courseid)) {
    $strpcasts = get_string('modulenameplural', 'pcast');

    $CFG->framename = 'newwindow';

    $PAGE->navbar->add($strpcasts);
    $PAGE->set_title(strip_tags("$course->shortname: $strpcasts"));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
} else {
    echo $OUTPUT->header();    // Needs to be something here to allow linking back to the whole pcast
}

// Replace the following lines with you own code
echo $OUTPUT->heading('Yay! It works!- DISPLAY THE EPISODE');

// Print the tabs
$toolsrow = array();
$browserow = array();
$inactive = array();
$activated = array();


$browserow[] = new tabobject(PCAST_EPISODE_VIEW,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_VIEW,
                             get_string('episodeview', 'pcast'));

$browserow[] = new tabobject(PCAST_EPISODE_COMMENT_AND_RATE,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_COMMENT_AND_RATE,
                             get_string('episodecommentview', 'pcast'));

$browserow[] = new tabobject(PCAST_EPISODE_VIEWS,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_VIEWS,
                             get_string('episodeviews', 'pcast'));


if ($mode < PCAST_EPISODE_VIEW || $mode > PCAST_EPISODE_VIEWS) {   // We are on second row
    $inactive = array('edit');
    $activated = array('edit');

    $browserow[] = new tabobject('edit', '#', get_string('edit'));
}

/// Put all this info together

$tabrows = array();
$tabrows[] = $browserow;     // Always put these at the top
if ($toolsrow) {
    $tabrows[] = $toolsrow;
}


echo'  <div class="pcastdisplay">';
print_tabs($tabrows, $mode, $inactive, $activated);

switch ($mode) {
    case PCAST_EPISODE_VIEW:

        echo 'VIEW';
        pcast_display_episode_full($episode, $cm);

        break;
    case PCAST_EPISODE_COMMENT_AND_RATE:

        echo 'COMMENT';
        pcast_display_episode_brief($episode, $cm);

        break;
    case PCAST_EPISODE_VIEWS:

        echo 'VIEWS';
        pcast_display_episode_brief($episode, $cm);

        break;
    default:

    break;
}





//        // make sure the episode is approved (or approvable by current user)
//        if (!$episode->approved and ($USER->id != $episode->userid)) {
//            $context = get_context_instance(CONTEXT_MODULE, $episode->cmid);
//            if (!has_capability('mod/pcast:approve', $context)) {
//
//            }


if ($popup) {
    echo $OUTPUT->close_window_button();
}

/// Show one reduced footer
echo '</div>';
echo $OUTPUT->footer();



?>