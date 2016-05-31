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
 * Page for editing pcast episodes
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');


$cmid = required_param('cmid', PARAM_INT);            // Course Module ID.
$id   = optional_param('id', 0, PARAM_INT);           // EntryID.


// Check for required stuff.
if ($cmid) {
    $cm         = get_coursemodule_from_id('pcast', $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pcast      = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    print_error('invalidcmorid', 'pcast');
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/pcast/edit.php', array('cmid' => $cm->id));
if (!empty($id)) {
    $url->param('id', $id);
}
$PAGE->set_url($url);
$PAGE->set_context($context);

if ($id) { // If the entry is specified.
    if (!has_capability('mod/pcast:write', $context)) {

        print_error('noeditprivlidges', 'pcast', new moodle_url('/mod/pcast/view.php', array('id' => $cmid)));
    }

    if (!$episode = $DB->get_record('pcast_episodes', array('id' => $id, 'pcastid' => $pcast->id))) {
        print_error('invalidentry', 'pcast');
    }

    // Calculate the editing period.
    $ineditperiod = ((time() - $episode->timecreated < $CFG->maxeditingtime));

    if (has_capability('mod/pcast:manage', $context)) {
        // Teacher.
        if (!has_capability('mod/pcast:write', $context)) {
            // No permissions.
            print_error('errcannotedit', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }

    } else {
        // Not A Teacher.
        if (!has_capability('mod/pcast:write', $context)) {
            // No permissions.
            print_error('errcannotedit', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        } else if ($episode->userid != $USER->id) {
            // Not the origional author.
            print_error('errcannoteditothers', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        } else if (!$ineditperiod) {
            // After the editing period.
            print_error('erredittimeexpired', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }

    }

} else { // A new entry.
    require_capability('mod/pcast:write', $context);
    $episode = new stdClass();
    $episode->id = null;
    $episode->summary = '';                // This will be updated later.
    $episode->summaryformat = FORMAT_HTML; // This will be updated later.
    $episode->summarytrust = 0;            // This will be updated later.
}

$draftitemid = file_get_submitted_draft_itemid('mediafile');
file_prepare_draft_area($draftitemid, $context->id, 'mod_pcast', 'episode', $episode->id,
                        array('subdirs' => 0, 'maxbytes' => $pcast->maxbytes, 'maxfiles' => 1,
                              'filetypes' => array('audio', 'video'))
                        );

$episode->mediafile = $draftitemid;
$episode->cmid = $cm->id;

$draftideditor = file_get_submitted_draft_itemid('summary');
$currenttext = file_prepare_draft_area($draftideditor, $context->id, 'mod_pcast', 'summary',
                                       $episode->id, array('subdirs' => true), $episode->summary);
$episode->summary = array('text' => $currenttext, 'format' => $episode->summaryformat, 'itemid' => $draftideditor);

// Create the form and set the initial data.
$mform = new mod_pcast_entry_form(null, array('current' => $episode, 'cm' => $cm, 'pcast' => $pcast, 'context' => $context));

if ($mform->is_cancelled()) {
    if ($id) {
        redirect("view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }

} else if ($episode = $mform->get_data()) {
    $timenow = time();

    // Calculated settings.
    if (empty($episode->id)) {
        $episode->pcastid       = $pcast->id;
        $episode->timecreated   = $timenow;
        $episode->userid        = $USER->id;
        $episode->course        = $COURSE->id;
    }
    $episode->summaryformat    = $episode->summary['format'];
    $episode->summary          = $episode->summary['text'];
    $episode->timemodified     = $timenow;
    $episode->approved         = 0;
    $episode->name = clean_param($episode->name, PARAM_TEXT);

    // Get the episode category information.
    $episode = pcast_get_itunes_categories($episode, $pcast);

    // Episode approval.
    if (!$pcast->requireapproval or has_capability('mod/pcast:approve', $context)) {
        $episode->approved = 1;
    }

    if (empty($episode->id)) {
        // A new entry.
        $episode->id = $DB->insert_record('pcast_episodes', $episode);
        $isnewentry = true;
    } else {
        // An existing entry.
        $DB->update_record('pcast_episodes', $episode);
        $isnewentry = false;
    }

    file_save_draft_area_files($episode->mediafile, $context->id, 'mod_pcast', 'episode', $episode->id,
                            array('subdirs' => 0, 'maxbytes' => $pcast->maxbytes,
                                  'maxfiles' => 1, 'filetypes' => array('audio', 'video'))
                            );

    // Get the duration if an MP3 file.
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_pcast', 'episode', $episode->id, "timemodified", false)) {
        foreach ($files as $file) {
            $hash = $file->get_contenthash();
            $mime = $file->get_mimetype();
            $mediainfo = pcast_get_media_information(pcast_file_path_lookup ($hash));
            if (!empty($mediainfo['playtime_string'])) {
                $episode->duration = $mediainfo['playtime_string'];
            }
        }
    }

    // Save files in summary field and re-write hyperlinks.
    $episode->summary = file_save_draft_area_files($draftideditor, $context->id, 'mod_pcast', 'summary',
                                          $episode->id, array('subdirs' => true), $episode->summary);

    // Store the updated value values.
    $DB->update_record('pcast_episodes', $episode);

    // Refetch the complete entry.
    $episode = $DB->get_record('pcast_episodes', array('id' => $episode->id));

    // Trigger event and update completion (if entry was created).
    $eventparams = array(
        'context' => $context,
        'objectid' => $episode->id,
        'other' => array('name' => $episode->name)
    );

    if ($isnewentry) {
        $event = \mod_pcast\event\episode_created::create($eventparams);
    } else {
        $event = \mod_pcast\event\episode_updated::create($eventparams);
    }

    $event->add_record_snapshot('pcast_episodes', $episode);
    $event->trigger();

    // Update completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $pcast->completionepisodes) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $episode->userid);
    }

    // Calculate hook.
    $hook = core_text::substr($episode->name, 0, 1);
    redirect("view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$hook");
}

if (!empty($id)) {
    $PAGE->navbar->add(get_string('edit'));
}

$PAGE->set_title($pcast->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($pcast->name));

$mform->display();

echo $OUTPUT->footer();