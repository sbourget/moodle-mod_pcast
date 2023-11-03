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
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.

// Get Parameters.
$mode       = optional_param('mode', PCAST_STANDARD_VIEW, PARAM_ALPHANUM); // Episode entry cat date letter search author approval.
$hook       = optional_param('hook', 'ALL', PARAM_CLEAN);                  // The Episode, cat, etc... to look for based on mode.
$sortkey    = optional_param('sortkey', '', PARAM_ALPHANUM);               // Sorted view: CREATION | UPDATE | AUTHOR...
$sortorder  = optional_param('sortorder', 'asc', PARAM_ALPHA);             // It defines the order of the sorting (ASC or DESC).
$page       = optional_param('page', 0, PARAM_INT);                        // Page to show (for paging purposes).

if ($id) {
    $cm         = get_coursemodule_from_id('pcast', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $pcast  = $DB->get_record('pcast', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    throw new moodle_exception('invalidcmorid', 'pcast');
}

$cm = cm_info::create($cm);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pcast:view', $context);

// Trigger module viewed event.
$event = \mod_pcast\event\course_module_viewed::create(array(
    'objectid' => $pcast->id,
    'context' => $context,
    'other' => array('mode' => $mode),
));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('pcast', $pcast);
$event->trigger();

// Mark as viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print the page header.
$PAGE->set_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => $mode));
$PAGE->set_title($pcast->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$renderer = $PAGE->get_renderer('mod_pcast');
$actionbar = new \mod_pcast\output\standard_action_bar($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
$PAGE->force_settings_menu();

// Output starts here.
echo $OUTPUT->header();
$hassecondary = $PAGE->has_secondary_navigation();

// Set Up Groups.
$groupmode = groups_get_activity_groupmode($cm);

// Output navigatiion bar.
echo $renderer->main_action_bar($actionbar);

// Output the Group selector.
if ($groupmode) {
    groups_get_activity_group($cm, true);
    groups_print_activity_menu($cm, new moodle_url('/mod/pcast/view.php', array('id' => $id)));
}

// Check to see if any content should be displayed (prevents guessing of URLs).
if ((!$pcast->userscancategorize) && ($mode == PCAST_CATEGORY_VIEW)) {
    throw new moodle_exception('errorinvalidmode', 'pcast');
} else if ((!$pcast->displayauthor && !has_capability('mod/pcast:manage', $context)) && ($mode == PCAST_AUTHOR_VIEW)) {
    throw new moodle_exception('errorinvalidmode', 'pcast');
} else if ((!has_capability('mod/pcast:approve', $context)) && ($mode == PCAST_APPROVAL_VIEW)) {
    throw new moodle_exception('errorinvalidmode', 'pcast');
}

switch ($mode) {

    case PCAST_CATEGORY_VIEW:
         pcast_print_categories_menu($cm, $pcast, $hook);
    break;

    case PCAST_APPROVAL_VIEW:
        if (!$sortkey) {
            $sortkey = PCAST_DATE_CREATED;
        }
        if (!$sortorder) {
            $sortorder = 'asc';
        }
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
echo html_writer::empty_tag('hr'). "\n";

// Print the main part of the page (The content).
echo html_writer::start_tag('div', array('id' => 'pcast-view', 'class' => 'generalbox')). "\n";
echo html_writer::start_tag('div', array('class' => 'generalboxcontent')). "\n";
// Next print the list of episodes.

switch($mode) {
    case PCAST_STANDARD_VIEW:

        pcast_display_standard_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);
        break;

    case PCAST_CATEGORY_VIEW:

        pcast_display_category_episodes($pcast, $cm, $groupmode, $hook, $page);
        break;

    case PCAST_DATE_VIEW:

        pcast_display_date_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);
        break;

    case PCAST_AUTHOR_VIEW:

        pcast_display_author_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);
        break;

    case PCAST_APPROVAL_VIEW:
        pcast_display_approval_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);

        break;

    case PCAST_ADDENTRY_VIEW:
        pcast_display_standard_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);
        break;

    default:

        pcast_display_standard_episodes($pcast, $cm, $groupmode, $hook, $sortkey, $sortorder, $page);
        break;
}

echo html_writer::end_tag('div'). "\n";
echo html_writer::end_tag('div'). "\n";

// Finish the page.
echo $OUTPUT->footer();
