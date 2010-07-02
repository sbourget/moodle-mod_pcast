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
 * Prints a particular instance of pcast
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget and Jillaine Beeckman
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/completionlib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID.

// COPIED FROM GLOSSARY
$mode       = optional_param('mode', PCAST_STANDARD_VIEW, PARAM_ALPHANUM); // term entry cat date letter search author approval
$hook       = optional_param('hook', '', PARAM_CLEAN);           // the term, entry, cat, etc... to look for based on mode
$sortkey    = optional_param('sortkey', '', PARAM_ALPHANUM);        // Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
$sortorder  = optional_param('sortorder', 'ASC', PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)
$page       = optional_param('page', 0,PARAM_INT);               // Page to show (for paging purposes)



// $displayformat = optional_param('displayformat',-1, PARAM_INT);  // override of the glossary display format

// $fullsearch = optional_param('fullsearch', 0,PARAM_INT);         // full search (concept and definition) when searching?
// $offset     = optional_param('offset', 0,PARAM_INT);             // entries to bypass (for paging purposes)
// $show       = optional_param('show', '', PARAM_ALPHA);           // [ concept | alias ] => mode=term hook=$show

// END COPY

if ($id) {
    $cm         = get_coursemodule_from_id('pcast', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pcast  = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

// Load comment API
require_once($CFG->dirroot . '/comment/lib.php');
comment::init();


add_to_log($course->id, 'pcast', 'view', "view.php?id=$cm->id", $pcast->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/pcast/view.php', array('id' => $cm->id));
$PAGE->set_title($pcast->name);
$PAGE->set_heading($course->shortname);
// $PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'pcast')));

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');

// Output starts here
echo $OUTPUT->header();

// Replace the following lines with you own code
echo $OUTPUT->heading('Yay! It works!- LIST THE PCASTS');

/// Print the Add/edit episode button
/// Need capability manage OR add with allow users to post option set
echo'<a href = "'.$CFG->wwwroot.'/mod/pcast/edit.php?cmid='.$cm->id.'">'.get_string('addnewepisode', 'pcast').'</a>';

    echo $OUTPUT->heading_with_help(get_string("viewpcast","pcast",$pcast->name), 'pcast' ,'pcast', 'icon');

 
    // Print heading and tabs
    //include('tabs.php');

    // *************************************************************************
    //Sorting info
    if (!isset($sortorder)) {
        $sortorder = '';
    }
    if (!isset($sortkey)) {
        $sortkey = '';
    }

    //make sure variables are properly cleaned
    $sortkey   = clean_param($sortkey, PARAM_ALPHANUM);// Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
    $sortorder = clean_param($sortorder, PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)

    $toolsrow = array();
    $browserow = array();
    $inactive = array();
    $activated = array();


    $browserow[] = new tabobject(PCAST_STANDARD_VIEW,
                                 $CFG->wwwroot.'/mod/pcast/view.php?id='.$id.'&amp;mode='.PCAST_STANDARD_VIEW,
                                 get_string('standardview', 'pcast'));

    $browserow[] = new tabobject(PCAST_CATEGORY_VIEW,
                                 $CFG->wwwroot.'/mod/pcast/view.php?id='.$id.'&amp;mode='.PCAST_CATEGORY_VIEW,
                                 get_string('categoryview', 'pcast'));

    $browserow[] = new tabobject(PCAST_DATE_VIEW,
                                 $CFG->wwwroot.'/mod/pcast/view.php?id='.$id.'&amp;mode='.PCAST_DATE_VIEW,
                                 get_string('dateview', 'pcast'));

    $browserow[] = new tabobject(PCAST_AUTHOR_VIEW,
                                 $CFG->wwwroot.'/mod/pcast/view.php?id='.$id.'&amp;mode='.PCAST_AUTHOR_VIEW,
                                 get_string('authorview', 'pcast'));


    if (has_capability('mod/pcast:approve', $context)) {
        $browserow[] = new tabobject(PCAST_APPROVAL_VIEW,
                                 $CFG->wwwroot.'/mod/pcast/view.php?id='.$id.'&amp;mode='.PCAST_APPROVAL_VIEW,
                                 get_string('approvalview', 'pcast'));
    }

    // TODO: Do we need a row for "my episodes" ?
    if ($mode < PCAST_STANDARD_VIEW || $mode > PCAST_APPROVAL_VIEW) {   // We are on second row
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

echo'  <div class="entrybox">';
echo '</div></div>';


    if (!isset($category)) {
        $category = "";
    }


    switch ($mode) {

        case PCAST_CATEGORY_VIEW:
             pcast_print_categories_menu($cm, $pcast, $hook);
        break;

        case PCAST_APPROVAL_VIEW:
             pcast_print_approval_menu($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
        break;

        case PCAST_AUTHOR_VIEW:
            if (!$sortkey) {
                $sortkey = PCAST_AUTHOR_LNAME;
            }
            if (!$sortorder) {
                $sortorder = 'asc';
            }
             pcast_print_author_menu($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
        break;

        case PCAST_DATE_VIEW:
            if (!$sortkey) {
                $sortkey = PCAST_DATE_UPDATED;
            }
            if (!$sortorder) {
                $sortorder = 'desc';
            }
             pcast_print_date_menu($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
        break;

        case PCAST_STANDARD_VIEW:
        default:
             pcast_print_alphabet_menu($cm, $pcast, $mode, $hook, $sortkey, $sortorder);

        break;
    }
    echo '<hr />';

    //**************************************************************************

    // Print the main part of the page (The content)
    echo'<div id="pcast-view" class="generalbox"><div class="generalboxcontent">';


/// Next print the list of episodes

    switch($mode) {
        case PCAST_STANDARD_VIEW:

            pcast_display_standard_episodes($pcast, $cm, $hook, $sortorder);
            break;

        case PCAST_CATEGORY_VIEW:

            pcast_display_category_episodes($pcast, $cm, $hook);
            break;

        case PCAST_DATE_VIEW:

            pcast_display_date_episodes($pcast, $cm, $hook, $sortkey, $sortorder);
            break;

        case PCAST_AUTHOR_VIEW:

            pcast_display_author_episodes($pcast, $cm, $hook, $sortkey, $sortorder);
            break;

        case PCAST_APPROVAL_VIEW:

            pcast_display_approval_episodes($pcast, $cm, $hook, $sortkey);

            break;

        case PCAST_ADDENTRY_VIEW:

            echo 'ADD';
            break;

        default:

            echo 'NORMAL';
            break;    }

    echo '</div></div>';


// Finish the page
echo $OUTPUT->footer();

/// Mark as viewed
$completion=new completion_info($course);
$completion->set_module_viewed($cm);

