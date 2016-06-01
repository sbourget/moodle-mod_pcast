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
 * Page for deleting pcast episodes
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');

$id       = required_param('id', PARAM_INT);          // Course module ID.
$confirm  = optional_param('confirm', 0, PARAM_INT);  // Commit the operation?
$episode    = optional_param('episode', 0, PARAM_INT);    // Episode id.
$prevmode = required_param('prevmode', PARAM_ALPHANUM);   // Display mode.
$hook     = optional_param('hook', '', PARAM_ALPHANUM);   // Alphabet bar filter.

$url = new moodle_url('/mod/pcast/deleteepisode.php', array('id' => $id, 'prevmode' => $prevmode));
if ($confirm !== 0) {
    $url->param('confirm', $confirm);
}
if ($episode !== 0) {
    $url->param('episode', $episode);
}
if ($hook !== '') {
    $url->param('hook', $hook);
} else {
    $hook = 'ALL';
}

$strpcast   = get_string("modulename", "pcast");
$strglossaries = get_string("modulenameplural", "pcast");
$stredit       = get_string("edit");
$episodedeleted  = get_string("episodedeleted", "pcast");

if ($id) {
    $cm         = get_coursemodule_from_id('pcast', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $episode    = $DB->get_record('pcast_episodes', array('id' => $episode), '*', MUST_EXIST);
    $pcast      = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    print_error('invalidcmorid', 'pcast');
}

require_login($course->id, false, $cm);

$PAGE->set_url($url);
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$manageentries = has_capability('mod/pcast:manage', $context);

if (($episode->userid != $USER->id) and !$manageentries) { // Guest id is never matched, no need for special check here.
    print_error('nopermissiontodelepisode');
}
$ineditperiod = ((time() - $episode->timecreated < $CFG->maxeditingtime));
if (!$ineditperiod and !$manageentries) {
    print_error('errdeltimeexpired', 'pcast');
}

// If data is submitted, then process and store.

if ($confirm and confirm_sesskey()) { // The operation was confirmed.

    $origionalepisode = fullclone($episode);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'pcast_episode', $episode->id);
    $DB->delete_records("comments", array('itemid' => $episode->id, 'commentarea' => 'pcast_episode', 'contextid' => $context->id));
    $DB->delete_records("pcast_episodes", array("id" => $episode->id));

    // Delete pcast episode ratings.
    require_once($CFG->dirroot.'/rating/lib.php');
    $delopt = new stdClass();
    $delopt->contextid = $context->id;
    $delopt->itemid = $episode->id;
    $delopt->component = 'mod_pcast';
    $delopt->ratingarea = 'episode';
    $rm = new rating_manager();
    $rm->delete_ratings($delopt);

    // Delete cached RSS feeds.
    if (!empty($CFG->enablerssfeeds)) {
        require_once($CFG->dirroot.'/mod/pcast/rsslib.php');
        pcast_rss_delete_file($pcast);
    }

    $event = \mod_pcast\event\episode_deleted::create(array(
        'context' => $context,
        'objectid' => $origionalepisode->id,
        'other' => array(
            'mode' => $prevmode,
            'hook' => $hook,
            'name' => $origionalepisode->name
        )
    ));

    $event->add_record_snapshot('pcast_episodes', $origionalepisode);
    $event->trigger();

    // Update completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $pcast->completionepisodes) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $episode->userid);
    }

    redirect("view.php?id=$cm->id&amp;mode=$prevmode&amp;hook=$hook");

} else {        // The operation has not been confirmed yet so ask the user to do so.
    $PAGE->navbar->add(get_string('delete'));
    $PAGE->set_title($pcast->name);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();

    $areyousure = html_writer::start_tag('div', array('class' => 'pcast-bold'));
    $areyousure .= format_string($episode->name);
    $areyousure .= html_writer::end_tag('div');
    $areyousure .= html_writer::start_tag('div');
    $areyousure .= get_string("areyousuredelete", "pcast");
    $areyousure .= html_writer::end_tag('div');
    $linkyes    = 'deleteepisode.php';
    $linkno     = 'view.php';
    $optionsyes = array('id' => $cm->id,
                        'episode' => $episode->id,
                        'confirm' => 1,
                        'sesskey' => sesskey(),
                        'prevmode' => $prevmode,
                        'hook' => $hook);
    $optionsno  = array('id' => $cm->id, 'mode' => $prevmode, 'hook' => $hook);

    echo $OUTPUT->confirm($areyousure, new moodle_url($linkyes, $optionsyes), new moodle_url($linkno, $optionsno));

    echo $OUTPUT->footer();
}
