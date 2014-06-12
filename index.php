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
 * This is a one-line short description of the file
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

$id = required_param('id', PARAM_INT);   // Course.

if (! $course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse', 'pcast');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');
$context = context_course::instance($course->id);

$event = \mod_pcast\event\course_module_instance_list_viewed::create(array(
    'context' => $context
));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Print the header.

$PAGE->set_url('/mod/pcast/view.php', array('id' => $id));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->shortname);

echo $OUTPUT->header();

// Get all the appropriate data.

if (!$pcasts = get_all_instances_in_course('pcast', $course)) {
    echo $OUTPUT->heading(get_string('nopcasts', 'pcast'), 2);
    echo $OUTPUT->continue_button("view.php?id=$course->id");
    echo $OUTPUT->footer();
    die();
}

// Print the list of instances (your module will probably extend this).

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($pcasts as $pcast) {
    if (!$pcast->visible) {
        // Show dimmed if the mod is hidden.
        $url = new moodle_url('/mod/pcast/view.php', array('id'=>$pcast->coursemodule));
        $link = html_writer::tag('a', format_string($pcast->name) , array('href'=>$url, 'class'=>'dimmed'));
    } else {
        // Show normal if the mod is visible.
        $url = new moodle_url('/mod/pcast/view.php', array('id'=>$pcast->coursemodule));
        $link = html_writer::tag('a', format_string($pcast->name) , array('href'=>$url));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($pcast->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'pcast'), 2);
echo html_writer::table($table);


// Finish the page.

echo $OUTPUT->footer();
