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


$cmid = required_param('cmid', PARAM_INT);            // Course Module ID
$id   = optional_param('id', 0, PARAM_INT);           // EntryID


// Check for required stuff
if ($cmid) {
    $cm         = get_coursemodule_from_id('pcast', $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pcast      = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    print_error('invalidcmorid', 'pcast');
}


require_login($course, false, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);


$url = new moodle_url('/mod/pcast/edit.php', array('cmid'=>$cm->id));
if (!empty($id)) {
    $url->param('id', $id);
}
$PAGE->set_url($url);
$PAGE->set_context($context);

if ($id) { // if entry is specified
    if (!has_capability('mod/pcast:write', $context)) {

        print_error('noeditprivlidges', 'pcast', new moodle_url('/mod/pcast/view.php', array('id'=>$cmid)));
    }

    if (!$episode = $DB->get_record('pcast_episodes', array('id'=>$id, 'pcastid'=>$pcast->id))) {
        print_error('invalidentry', 'pcast');
    }

    //Calculate editing period
    $ineditperiod = ((time() - $episode->timecreated <  $CFG->maxeditingtime));

    if (has_capability('mod/pcast:manage', $context)) {
        //Teacher
        if (!has_capability('mod/pcast:write', $context)) {
            //No permissions
            print_error('errcannotedit', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }

    } else {
        //Not Teacher
        if (!has_capability('mod/pcast:write', $context)) {
            //No permissions
            print_error('errcannotedit', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }
        else if ($episode->userid != $USER->id) {
            // Not the origional author
            print_error('errcannoteditothers', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }
        else if (!$ineditperiod) {
            // After the editing period
            print_error('erredittimeexpired', 'pcast', "view.php?id=$cm->id&amp;mode=".PCAST_STANDARD_VIEW."&amp;hook=$id");
        }

    }


} else { // new entry
    require_capability('mod/pcast:write', $context);
    $episode = new object();
    $episode->id = null;
}

$draftitemid = file_get_submitted_draft_itemid('mediafile');
file_prepare_draft_area($draftitemid, $context->id, 'mod_pcast', 'episode', $episode->id, array('subdirs' => 0, 'maxbytes'=>$COURSE->maxbytes, 'maxfiles' => 1, 'filetypes' => array('audio', 'video')));
$episode->mediafile = $draftitemid;

$episode->cmid = $cm->id;
if (isset($episode->summary)) {
    $episode->summary =array('text' => $episode->summary, 'format' => '1');
}
// create form and set initial data
$mform = new mod_pcast_entry_form(null, array('current'=>$episode, 'cm'=>$cm, 'pcast'=>$pcast));

if ($mform->is_cancelled()) {
    if ($id) {
        redirect("view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }

} else if ($episode = $mform->get_data()) {
    $timenow = time();

    //Calculated settings
    if (empty($episode->id)) {
        $episode->pcastid       = $pcast->id;
        $episode->timecreated   = $timenow;
        $episode->userid        = $USER->id;
        $episode->course        = $COURSE->id;
    }
    $episode->summary          = $episode->summary['text'];
    $episode->timemodified     = $timenow;
    $episode->approved         = 0;
    $episode->name = clean_param($episode->name, PARAM_ALPHANUM);

    // Get the episode category information
    $episode = pcast_get_itunes_categories($episode, $pcast);

    // Episode approval
    if (!$pcast->requireapproval or has_capability('mod/pcast:approve', $context)) {
        $episode->approved = 1;
    }

    if (empty($episode->id)) {
        //new entry
        $episode->id = $DB->insert_record('pcast_episodes', $episode);
        add_to_log($course->id, "pcast", "add episode",
                   "view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$episode->id",
                   $episode->id, $cm->id);

    } else {
        //existing entry
        $DB->update_record('pcast_episodes', $episode);
        add_to_log($course->id, "pcast", "update episode",
                   "view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$episode->id",
                   $episode->id, $cm->id);
    }

    file_save_draft_area_files($episode->mediafile, $context->id, 'mod_pcast', 'episode', $episode->id, array('subdirs' => 0, 'maxbytes'=>$COURSE->maxbytes, 'maxfiles' => 1, 'filetypes' => array('audio', 'video')));

    //Get the duration if an MP3 file
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_pcast', 'episode', $episode->id, "timemodified", false)) {
        foreach ($files as $file) {
            $hash = $file->get_contenthash();
            $mime = $file->get_mimetype();
            if ($mime == 'audio/mp3') {
                // $mp3info=pcast_get_mp3_info(pcast_file_path_lookup ($hash));
                // $episode->duration = $mp3info->length;
                $mediainfo=pcast_get_media_information(pcast_file_path_lookup ($hash));;
                if (!empty($mediainfo['playtime_string'])) {
                    $episode->duration = $mediainfo['playtime_string'];
                }
            }
        }
    }

    // store the updated value values
    $DB->update_record('pcast_episodes', $episode);

    //refetch complete entry
    $episode = $DB->get_record('pcast_episodes', array('id'=>$episode->id));

    redirect("view.php?id=$cm->id&amp;mode=".PCAST_ADDENTRY_VIEW."&amp;hook=$episode->id");
}

if (!empty($id)) {
    $PAGE->navbar->add(get_string('edit'));
}

$PAGE->set_title(format_string($pcast->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($pcast->name));

$mform->display();

echo $OUTPUT->footer();
