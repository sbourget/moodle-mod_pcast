<?php

require_once('../../config.php');
require_once('lib.php');
require_once('edit_form.php');

$cmid = required_param('cmid', PARAM_INT);            // Course Module ID
$id   = optional_param('id', 0, PARAM_INT);           // EntryID


// Check for required stuff
if ($cmid) {
    $cm         = get_coursemodule_from_id('pcast', $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pcast      = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);

} else {
    print_error('invalidcmorid','pcast');
}


require_login($course, false, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);


$url = new moodle_url('/mod/pcast/edit.php', array('cmid'=>$cm->id));
if (!empty($id)) {
    $url->param('id', $id);
}
$PAGE->set_url($url);

if ($id) { // if entry is specified
    if (!has_capability('mod/pcast:write', $context)){
        print_error('noeditprivlidges', 'pcast', "$CFG->wwwroot/mod/pcast/view.php?id=$cmid");
    }

    if (!$episode = $DB->get_record('pcast_episodes', array('id'=>$id, 'pcastid'=>$pcast->id))) {
        print_error('invalidentry');
    }

    //TODO: This is from the glossary code.  Rethink how editing will work.
    $ineditperiod = ((time() - $episode->timecreated <  $CFG->maxeditingtime) || $pcast->editalways);
    if (!has_capability('mod/pcast:manage', $context) and !($episode->userid == $USER->id and ($ineditperiod and has_capability('mod/pcast:write', $context)))) {
        if ($USER->id != $fromdb->userid) {
            print_error('errcannoteditothers', 'pcast', "view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
        } elseif (!$ineditperiod) {
            print_error('erredittimeexpired', 'pcast', "view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
        }
    }

} else { // new entry
    require_capability('mod/pcast:write', $context);
    $episode = new object();
    $episode->id = null;
}

$maxfiles = 1;
$maxbytes = $course->maxbytes;



$attachmentoptions = array('subdirs'=>false, 'maxfiles'=>$maxfiles, 'maxbytes'=>$maxbytes);
$episode = file_prepare_standard_filemanager($episode, 'attachment', $attachmentoptions, $context, 'pcast_episode', $episode->id);
$episode->cmid = $cm->id;

// create form and set initial data
$mform = new mod_pcast_entry_form(null, array('current'=>$episode, 'cm'=>$cm, 'pcast'=>$pcast));

if ($mform->is_cancelled()){
    if ($id){
        redirect("view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }

    //TODO: FINISH FROM HERE DOWN!!!
} else if ($episode = $mform->get_data()) {
    echo'<pre>';
    print_r($episode);
    echo'</pre>';
    $timenow = time();

    $categories = empty($episode->categories) ? array() : $episode->categories;
    unset($episode->categories);
    $aliases = trim($episode->aliases);
    unset($episode->aliases);

    if (empty($episode->id)) {
        $episode->pcastid       = $pcast->id;
        $episode->timecreated      = $timenow;
        $episode->userid           = $USER->id;
        $episode->timecreated      = $timenow;
        $episode->sourcepcastid = 0;
        $episode->teacherentry     = has_capability('mod/pcast:manageentries', $context);
    }

    $episode->concept          = trim($episode->concept);
    $episode->definition       = '';          // updated later
    $episode->definitionformat = FORMAT_HTML; // updated later
    $episode->definitiontrust  = 0;           // updated later
    $episode->timemodified     = $timenow;
    $episode->approved         = 0;
    $episode->usedynalink      = isset($episode->usedynalink) ?   $episode->usedynalink : 0;
    $episode->casesensitive    = isset($episode->casesensitive) ? $episode->casesensitive : 0;
    $episode->fullmatch        = isset($episode->fullmatch) ?     $episode->fullmatch : 0;

    if ($pcast->defaultapproval or has_capability('mod/pcast:approve', $context)) {
        $episode->approved = 1;
    }

    if (empty($episode->id)) {
        //new entry
        $episode->id = $DB->insert_record('pcast_entries', $episode);
        add_to_log($course->id, "pcast", "add entry",
                   "view.php?id=$cm->id&amp;mode=entry&amp;hook=$episode->id", $episode->id, $cm->id);

    } else {
        //existing entry
        $DB->update_record('pcast_entries', $episode);
        add_to_log($course->id, "pcast", "update entry",
                   "view.php?id=$cm->id&amp;mode=entry&amp;hook=$episode->id",
                   $episode->id, $cm->id);
    }

    // save and relink embedded images and save attachments
    $episode = file_postupdate_standard_editor($episode, 'definition', $definitionoptions, $context, 'pcast_entry', $episode->id);
    $episode = file_postupdate_standard_filemanager($episode, 'attachment', $attachmentoptions, $context, 'pcast_attachment', $episode->id);

    // store the updated value values
    $DB->update_record('pcast_entries', $episode);

    //refetch complete entry
    $episode = $DB->get_record('pcast_entries', array('id'=>$episode->id));

    // update entry categories
    $DB->delete_records('pcast_entries_categories', array('entryid'=>$episode->id));
    // TODO: this deletes cats from both both main and secondary pcast :-(
    if (!empty($categories) and array_search(0, $categories) === false) {
        foreach ($categories as $catid) {
            $newcategory = new object();
            $newcategory->entryid    = $episode->id;
            $newcategory->categoryid = $catid;
            $DB->insert_record('pcast_entries_categories', $newcategory, false);
        }
    }

    // update aliases
    $DB->delete_records('pcast_alias', array('entryid'=>$episode->id));
    if ($aliases !== '') {
        $aliases = explode("\n", $aliases);
        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if ($alias !== '') {
                $newalias = new object();
                $newalias->entryid = $episode->id;
                $newalias->alias   = $alias;
                $DB->insert_record('pcast_alias', $newalias, false);
            }
        }
    }

    redirect("view.php?id=$cm->id&amp;mode=entry&amp;hook=$episode->id");
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



