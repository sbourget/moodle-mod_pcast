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
$url->param('mode', $mode);
$PAGE->set_url($url);

if ($eid) {
    $sql = pcast_get_episode_sql();
    $sql .=  " WHERE p.id = ?";
    $episode = $DB->get_record_sql($sql,array($eid), MUST_EXIST);
    $pcast = $DB->get_record('pcast', array('id'=>$episode->pcastid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('pcast', $pcast->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
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
// TODO: FIX THIS!

echo $OUTPUT->heading('Yay! It works!- DISPLAY THE EPISODE');

// Print the tabs
$toolsrow = array();
$browserow = array();
$inactive = array();
$activated = array();


$browserow[] = new tabobject(PCAST_EPISODE_VIEW,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_VIEW,
                             get_string('episodeview', 'pcast'));

$comment = false;
$rate = false;
//TODO: Ratings here need to be revisited =(
if(($episode->userscancomment) or ($episode->assessed)){
    // Can they use comments?
    if(($CFG->usecomments) and ($episode->userscancomment) and ((has_capability('moodle/comment:post', $context)) or (has_capability('moodle/comment:view', $context)))) {
        $tabname = get_string('episodecommentview', 'pcast');
        $comment = true;
    }
    // Can they use ratings?
    if(($episode->assessed) and
        ((has_capability('moodle/rating:rate', $context)) or
         ((has_capability('moodle/rating:view', $context)) and ($episode->user == $USER->id)) or
         (has_capability('moodle/rating:viewall', $context)) or
         (has_capability('moodle/rating:viewany', $context)))) {

        $tabname = get_string('episoderateview', 'pcast');
        $rate = true;
    }
    // Can they use both?
    if(($comment) and ($rate)) {
        $tabname = get_string('episodecommentandrateview', 'pcast');
    }


    $browserow[] = new tabobject(PCAST_EPISODE_COMMENT_AND_RATE,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_COMMENT_AND_RATE,
                             $tabname);
}

if(($episode->displayviews) or (has_capability('mod/pcast:manage', $context))) {
    $browserow[] = new tabobject(PCAST_EPISODE_VIEWS,
                             $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$eid.'&amp;mode='.PCAST_EPISODE_VIEWS,
                             get_string('episodeviews', 'pcast'));
}


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


// Check to see if any content should be displayed (prevents guessing of URLs)
if(((!$pcast->userscancomment) and (!$pcast->assessed)) and ($mode == PCAST_EPISODE_COMMENT_AND_RATE)) {
    print_error('errorinvalidmode','pcast');
} else if((!$pcast->displayviews and !has_capability('mod/pcast:manage', $context)) and ($mode == PCAST_EPISODE_VIEWS)) {
    print_error('errorinvalidmode','pcast');
}

echo'  <div class="pcast-display">';
print_tabs($tabrows, $mode, $inactive, $activated);

switch ($mode) {
    case PCAST_EPISODE_VIEW:

        pcast_display_episode_full($episode, $cm);

        break;
    case PCAST_EPISODE_COMMENT_AND_RATE:

        // Load comment API
        require_once($CFG->dirroot . '/comment/lib.php');
        comment::init();

        pcast_display_episode_comments($episode, $cm, $course);
        pcast_display_episode_ratings($episode, $cm);

        break;
    case PCAST_EPISODE_VIEWS:

        pcast_display_episode_views($episode, $cm);

        break;
    default:

    break;
}

if ($popup) {
    echo $OUTPUT->close_window_button();
}

/// Show one reduced footer
echo '</div>';
echo $OUTPUT->footer();



?>