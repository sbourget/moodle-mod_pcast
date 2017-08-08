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
 * Library of interface functions and constants for module pcast
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the pcast specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

define("PCAST_SHOW_ALL_CATEGORIES", 0);
define("PCAST_SHOW_NOT_CATEGORISED", -1);

define("PCAST_NO_VIEW", -1);
define("PCAST_STANDARD_VIEW", 0);
define("PCAST_CATEGORY_VIEW", 1);
define("PCAST_DATE_VIEW", 2);
define("PCAST_AUTHOR_VIEW", 3);
define("PCAST_ADDENTRY_VIEW", 4);
define("PCAST_APPROVAL_VIEW", 5);
define("PCAST_ENTRIES_PER_PAGE", 20);

define("PCAST_DATE_UPDATED", 100);
define("PCAST_DATE_CREATED", 101);
define("PCAST_AUTHOR_LNAME", 200);
define("PCAST_AUTHOR_FNAME", 201);

define("PCAST_EPISODE_VIEW", 300);
define("PCAST_EPISODE_COMMENT_AND_RATE", 301);
define("PCAST_EPISODE_VIEWS", 302);
define("PCAST_EPISODE_APPROVE", 1);
define("PCAST_EPISODE_DISAPPROVE", 0);

/**
 * If you for some reason need to use global variables instead of constants, do not forget to make them
 * global as this file can be included inside a function scope. However, using the global variables
 * at the module level is not a recommended.
 */

/**
 * Lists supported features
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_SHOW_DESCRIPTION
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 **/
function pcast_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_RATE:
            return true;
        default:
            return null;
    }
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $pcast An object from the form in mod_form.php
 * @global stdClass $DB
 * @global stdClass $USER
 * @return int The id of the newly inserted pcast record
 */
function pcast_add_instance($pcast) {
    global $DB, $USER;

    $pcast->timecreated = time();

    // If it is a new instance time created is the same as modified.
    $pcast->timemodified = $pcast->timecreated;

    // Handle ratings.
    if (empty($pcast->assessed)) {
        $pcast->assessed = 0;
    }

    if (empty($pcast->ratingtime) or empty($pcast->assessed)) {
        $pcast->assesstimestart  = 0;
        $pcast->assesstimefinish = 0;
    }

    // If no owner then set it to the instance creator.
    if (isset($pcast->enablerssitunes) and ($pcast->enablerssitunes == 1)) {
        if (!isset($pcast->userid)) {
            $pcast->userid = $USER->id;
        }
    }

    // Get the episode category information.
    $defaults = new stdClass();
    $defaults->topcategory = 0;
    $defaults->nestedcategory = 0;
    $pcast = pcast_get_itunes_categories($pcast, $defaults);

    $result = $DB->insert_record('pcast', $pcast);
    $pcast->id = $result;

    $cmid = $pcast->coursemodule;
    $draftitemid = $pcast->image;
    // We need to use context now, so we need to make sure all needed info is already in db.
    $context = context_module::instance($cmid);
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_pcast', 'logo', 0, array('subdirs' => false));
    }

    pcast_grade_item_update($pcast);

    // Add action event for dashboard.
    $completiontimeexpected = !empty($pcast->completionexpected) ? $pcast->completionexpected : null;
    \core_completion\api::update_completion_date_event($pcast->coursemodule,
        'pcast', $pcast->id, $completiontimeexpected);

    return $result;

}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $pcast An object from the form in mod_form.php
 * @global stdClass $DB
 * @global stdClass $USER
 * @return boolean Success/Fail
 */
function pcast_update_instance($pcast) {
    global $DB, $USER;

    $pcast->timemodified = time();

    // Handle ratings.
    if (empty($pcast->assessed)) {
        $pcast->assessed = 0;
    }

    if (empty($pcast->ratingtime) or empty($pcast->assessed)) {
        $pcast->assesstimestart  = 0;
        $pcast->assesstimefinish = 0;
    }

    $pcast->id = $pcast->instance;

    // If no owner then set it to the instance creator.
    if (isset($pcast->enablerssitunes) and ($pcast->enablerssitunes == 1)) {
        if (!isset($pcast->userid)) {
            $pcast->userid = $USER->id;
        }
    }

    // Get the episode category information.
    $defaults = new stdClass();
    $defaults->topcategory = 0;
    $defaults->nestedcategory = 0;
    $pcast = pcast_get_itunes_categories($pcast, $defaults);

    $result = $DB->update_record('pcast', $pcast);

    $cmid = $pcast->coursemodule;
    $draftitemid = $pcast->image;
    // We need to use context now, so we need to make sure all needed info is already in db.
    $context = context_module::instance($cmid);
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_pcast', 'logo', 0, array('subdirs' => false));
    }

    pcast_grade_item_update($pcast);

    // Update action event for dashboard.
    $completiontimeexpected = !empty($pcast->completionexpected) ? $pcast->completionexpected : null;
    \core_completion\api::update_completion_date_event($pcast->coursemodule,
        'pcast', $pcast->id, $completiontimeexpected);

    return $result;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @global stdClass $DB
 * @global stdClass $USER
 * @return boolean Success/Failure
 */
function pcast_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/rating/lib.php');

    if (! $pcast = $DB->get_record('pcast', array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('pcast', $id)) {
        return false;
    }
    if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
        return false;
    }

    // Delete any dependent records here.

    // Delete Comments.
    $episodeselect = "SELECT id FROM {pcast_episodes} WHERE pcastid = ?";
    $DB->delete_records_select('comments', "contextid=? AND commentarea=? AND itemid IN ($episodeselect)",
                               array($id, 'pcast_episode', $context->id));

    // Delete all files.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    // Delete ratings.
    $rm = new rating_manager();
    $ratingdeloptions = new stdClass();
    $ratingdeloptions->contextid = $context->id;
    $rm->delete_ratings($ratingdeloptions);

    // Delete Views.
    $episodeselect = "SELECT id FROM {pcast_episodes} WHERE pcastid = ?";
    $DB->delete_records_select('pcast_views', "episodeid  IN ($episodeselect)", array($pcast->id));

    // Delete Episodes.
    $DB->delete_records('pcast_episodes', array('pcastid' => $pcast->id));

    // Delete Grades.
    pcast_grade_item_delete($pcast);

    // Delete action events.
    \core_completion\api::update_completion_date_event($cm->id, 'pcast', $pcast->id, null);

    // Delete Podcast.
    $DB->delete_records('pcast', array('id' => $pcast->id));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @global stdClass $DB
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $pcast
 * @return object $result
 */
function pcast_user_outline($course, $user, $mod, $pcast) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'pcast', $pcast->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }
    if ($entries = pcast_get_user_episodes($pcast->id, $user->id)) {
        $result = new stdClass();
        $result->info = get_string("episodes", "pcast", count($entries));
        $lastentry = array_pop($entries);
        $result->time = $lastentry->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new stdClass();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;
        // Datesubmitted == time created. dategraded == time modified or time overridden.
        // If grade was last modified by the user themselves use date graded. Otherwise use date submitted.
        // TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704.
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }
        return $result;
    }
    return null;
}

/**
 * Get all the episodes for a user in a podcast.
 * @global object
 * @param int $pcastid
 * @param int $userid
 * @return array
 */
function pcast_get_user_episodes($pcastid, $userid) {
    global $DB;
    $allnamefields = get_all_user_name_fields(true, 'u');
    $sql = "SELECT p.id AS id,
                   p.pcastid AS pcastid,
                   p.course AS course,
                   p.userid AS userid,
                   p.name AS name,
                   p.summary AS summary,
                   p.summaryformat AS summaryformat,
                   p.summarytrust AS summarytrust,
                   p.mediafile AS mediafile,
                   p.duration AS duration,
                   p.explicit AS explicit,
                   p.subtitle AS subtitle,
                   p.keywords AS keywords,
                   p.topcategory as topcatid,
                   p.nestedcategory as nestedcatid,
                   p.timecreated as timecreated,
                   p.timemodified as timemodified,
                   p.approved as approved,
                   p.sequencenumber as sequencenumber,
                   pcast.userscancomment as userscancomment,
                   pcast.userscancategorize as userscancategorize,
                   pcast.userscanpost as userscanpost,
                   pcast.requireapproval as requireapproval,
                   pcast.displayauthor as displayauthor,
                   pcast.displayviews as displayviews,
                   pcast.assessed as assessed,
                   pcast.assesstimestart as assesstimestart,
                   pcast.assesstimefinish as assesstimefinish,
                   pcast.scale as scale,
                   cat.name as topcategory,
                   ncat.name as nestedcategory,
                   $allnamefields
              FROM {pcast_episodes} p
         LEFT JOIN {pcast} AS pcast ON
                   p.pcastid = pcast.id
         LEFT JOIN {user} AS u ON
                   p.userid = u.id
         LEFT JOIN {pcast_itunes_categories} AS cat ON
                   p.topcategory = cat.id
         LEFT JOIN {pcast_itunes_nested_cat} AS ncat ON
                   p.nestedcategory = ncat.id
             WHERE pcast.id = ?
               AND p.pcastid = pcast.id
               AND p.userid = ?
               AND p.userid = u.id
          ORDER BY p.timemodified ASC";
    return $DB->get_records_sql($sql, array($pcastid, $userid));

}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @global stdClass $DB
 * @global stdClass $CFG
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $pcast
 * @return object $result
 */
function pcast_user_complete($course, $user, $mod, $pcast) {
    global $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");
    require_once($CFG->dirroot.'/mod/pcast/locallib.php');

    $cm = get_coursemodule_from_instance("pcast", $pcast->id, $course->id);

    $grades = grade_get_grades($course->id, 'mod', 'pcast', $pcast->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
        if ($grade->str_feedback) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }
    if ($episodes = pcast_get_user_episodes($pcast->id, $user->id)) {
        foreach ($episodes as $episode) {
            pcast_display_episode_brief($episode, $cm, false, false);
        }
    } else {
        // Not contributed to.
        echo get_string('noepisodesposted', 'pcast');
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in pcast activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @global stdClass $DB
 * @global stdClass $OUTPUT
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return bool
 */

function pcast_print_recent_activity($course, $viewfullnames, $timestart) {
    global $DB, $OUTPUT;

    $modinfo = get_fast_modinfo($course);
    $ids = array();
    foreach ($modinfo->cms as $cm) {
        if ($cm->modname != 'pcast') {
            continue;
        }
        if (!$cm->uservisible) {
            continue;
        }
        $ids[$cm->instance] = $cm->instance;
    }

    if (!$ids) {
        return false;
    }

    $plist = implode(',', $ids); // There should not be hundreds of podcasts in one course, right?

    $allnamefields = get_all_user_name_fields(true, 'u');

    if (!$episodes = $DB->get_records_sql("SELECT e.id, e.name, e.approved, e.timemodified, e.pcastid,
                                                  e.userid, $allnamefields
                                             FROM {pcast_episodes} e
                                             JOIN {user} u ON u.id = e.userid
                                            WHERE e.pcastid IN ($plist) AND e.timemodified > ?
                                         ORDER BY e.timemodified ASC", array($timestart))) {
        return false;
    }

    $editor  = array();

    foreach ($episodes as $episodeid => $episode) {
        if ($episode->approved) {
            continue;
        }

        if (!isset($editor[$episode->pcastid])) {
            $editor[$episode->pcastid] = has_capability('mod/pcast:approve',
                                         context_module::instance($modinfo->instances['pcast'][$episode->pcastid]->id));
        }

        if (!$editor[$episode->pcastid]) {
            unset($episodes[$episodeid]);
        }
    }

    if (!$episodes) {
        return false;
    }
    echo $OUTPUT->heading(get_string('newepisodes', 'pcast').':', 3);

    $strftimerecent = get_string('strftimerecent');
    foreach ($episodes as $episode) {
        $link = new moodle_url('/mod/pcast/showepisode.php', array('eid' => $episode->id));
        if ($episode->approved) {
            $out = html_writer::start_tag('div', array('class' => 'head')). "\n";
        } else {
            $out = html_writer::start_tag('div', array('class' => 'head dimmed_text')). "\n";
        }

        $out .= html_writer::start_tag('div', array('class' => 'date')). "\n";
        $out .= userdate($episode->timemodified, $strftimerecent);
        $out .= html_writer::end_tag('div') . "\n";
        $out .= html_writer::start_tag('div', array('class' => 'name')). "\n";
        $out .= fullname($episode, $viewfullnames);
        $out .= html_writer::end_tag('div') . "\n";
        $out .= html_writer::end_tag('div') . "\n";
        $out .= html_writer::start_tag('div', array('class' => 'info')). "\n";
        $out .= html_writer::tag('a', format_text($episode->name, true), array('href' => $link));
        $out .= html_writer::end_tag('div') . "\n";

        echo $out;

    }

    return true;
}

/**
 * Function used to display updates in the course overview block
 */

function pcast_print_overview($courses, &$htmlarray) {
    global $DB, $OUTPUT;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return;
    }

    if (!$pcasts = get_all_instances_in_courses('pcast', $courses)) {
        return;
    }

    foreach ($pcasts as $pcast) {

        // Visibility.
        $class = (!$pcast->visible) ? 'dimmed' : '';
        // Link to activity.
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $pcast->coursemodule));
        $url = html_writer::link($url, format_string($pcast->name), array('class' => $class));
        $str = $OUTPUT->box(get_string('pcastactivityname', 'pcast', $url), 'name');
        $display = false;

        // Display relevant info based on permissions.
        if (has_capability('mod/pcast:view', context_module::instance($pcast->coursemodule))) {
            // For everyone list new episodes.
            $newepisodes = $DB->count_records_select('pcast_episodes', "pcastid = ? AND approved = ? AND timemodified > ?",
                array($pcast->id, PCAST_EPISODE_APPROVE, $courses[$pcast->course]->lastaccess));
            if ($newepisodes > 0) {
                $str .= $OUTPUT->box(get_string('viewnewepisodes', 'pcast', $newepisodes), 'info');
                $display = true;
            }
        }
        if (has_capability('mod/pcast:approve', context_module::instance($pcast->coursemodule))) {
            // For teachers also list unapproved episodes.
            $pending = $DB->count_records('pcast_episodes',
                array('pcastid' => $pcast->id, 'approved' => PCAST_EPISODE_DISAPPROVE));

            if ($pending > 0) {
                $str .= $OUTPUT->box(get_string('viewunapprovedepisodes', 'pcast', $pending), 'info');
                $display = true;
            }
        }
        if (!has_any_capability(array('mod/pcast:approve', 'mod/pcast:view'),
                context_module::instance($pcast->coursemodule))) {
            // Does not have permission to do anything on this pcast activity.
            $str = '';
        }
        // Make sure we have something to display.
        if (!empty($str) and $display) {
            // Generate the containing div.
            $str = $OUTPUT->box($str, 'pcast overview');
            if (empty($htmlarray[$pcast->course]['pcast'])) {
                $htmlarray[$pcast->course]['pcast'] = $str;
            } else {
                $htmlarray[$pcast->course]['pcast'] .= $str;
            }
        }
    }
    return;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
function pcast_cron () {
    return true;
}

/**
 * This function returns if a scale is being used by one pcast
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $pcastid ID of an instance of this module
 * @param int $scaleid
 * @global $DB
 * @return mixed
 */
function pcast_scale_used($pcastid, $scaleid) {
    global $DB;

    $return = false;

    $rec = $DB->get_record("pcast", array("id" => "$pcastid", "scale" => "-$scaleid"));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of pcast.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any pcast
 */
function pcast_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('pcast', array('scale' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}


/**
 * Lists all browsable file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function pcast_get_file_areas($course, $cm, $context) {

    $areas = array();
    $areas['logo']   = get_string('arealogo', 'pcast');
    $areas['episode'] = get_string('areaepisode', 'pcast');
    $areas['summary'] = get_string('areasummary', 'pcast');

    return $areas;
}

/**
 * Support for the Reports (Participants)
 * @return array()
 */
function pcast_get_view_actions() {
    return array('view', 'view all', 'get attachment');
}
/**
 * Support for the Reports (Participants)
 * @return array()
 */
function pcast_get_post_actions() {
    return array('add', 'update');
}

/**
 * Tells if files in moddata are trusted and can be served without XSS protection.
 *
 * @return bool (true if file can be submitted by teacher only, otherwise false)
 */
function pcast_is_moddata_trusted() {
    return false;
}

/**
 * Obtains the automatic completion state for this pcast based on any conditions
 * in pcast settings.
 *
 * @global object $DB
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function pcast_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get pcast details.
    if (!($pcast = $DB->get_record('pcast', array('id' => $cm->instance)))) {
        throw new Exception("Can't find podcast {$cm->instance}");
    }

    // Default return value.
    $result = $type;

    if ($pcast->completionepisodes) {
        $value = $pcast->completionepisodes <= $DB->count_records('pcast_episodes',
                array('pcastid' => $pcast->id, 'userid' => $userid, 'approved' => PCAST_EPISODE_APPROVE));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}

/**
 * Adds module specific settings to the navigation block
 * @global stdClass $CFG
 * @param stdClass $navigation
 * @param stdClass $course
 * @param stdClass $module
 * @param stdClass $cm
 */

function pcast_extend_navigation($navigation, $course, $module, $cm) {
    $navigation->add(get_string('standardview', 'pcast'),
                     new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_STANDARD_VIEW)));
    if ($module->userscancategorize) {
        $navigation->add(get_string('categoryview', 'pcast'),
                         new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_CATEGORY_VIEW)));
    }
    $navigation->add(get_string('dateview', 'pcast'),
                     new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_DATE_VIEW)));
    $navigation->add(get_string('authorview', 'pcast'),
                     new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => PCAST_AUTHOR_VIEW)));
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $pcastnode The node to add module settings to
 */
function pcast_extend_settings_navigation(settings_navigation $settings, navigation_node $pcastnode) {
    global $PAGE, $DB, $CFG, $USER;

    $group = optional_param('group', '', PARAM_ALPHANUM);
    $pcast = $DB->get_record('pcast', array("id" => $PAGE->cm->instance));

    // Display approval link only when required.
    if ($pcast->requireapproval) {
        if (has_capability('mod/pcast:approve', $PAGE->cm->context)) {
            $pcastnode->add(get_string('waitingapproval', 'pcast'), new moodle_url('/mod/pcast/view.php',
                    array('id' => $PAGE->cm->id, 'mode' => PCAST_APPROVAL_VIEW)));
        }
    }

    // Display add new episode link. (Must have write + manage / approve as teacher or write + allow user episodes).
    if (has_capability('mod/pcast:write', $PAGE->cm->context) and (has_capability('mod/pcast:manage', $PAGE->cm->context)
                                                               or has_capability('mod/pcast:approve', $PAGE->cm->context))) {
        // This is a teacher.
        $pcastnode->add(get_string('addnewepisode', 'pcast'),
                        new moodle_url('/mod/pcast/edit.php',
                        array('cmid' => $PAGE->cm->id)));

    } else if (has_capability('mod/pcast:write', $PAGE->cm->context)) {
        // See if the activity allows student posting.
        if ($pcast->userscanpost == true) {
            // Add a link to ad an episode.
            $pcastnode->add(get_string('addnewepisode', 'pcast'),
                        new moodle_url('/mod/pcast/edit.php',
                        array('cmid' => $PAGE->cm->id)));
        }
    }

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->pcast_enablerssfeeds)
    && $pcast->enablerssfeed) {
        require_once("$CFG->libdir/rsslib.php");

        $string = get_string('rsslink', 'pcast');

        // Sort out groups.
        if (is_numeric($group)) {
            $currentgroup = $group;

        } else {
            $groupmode = groups_get_activity_groupmode($PAGE->cm);
            if ($groupmode > 0) {
                $currentgroup = groups_get_activity_group($PAGE->cm);
            } else {
                $currentgroup = 0;
            }

        }
        $args = $pcast->id . '/'.$currentgroup;

        $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $USER->id, 'pcast', $args));
        $pcastnode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));

        if (!empty($CFG->pcast_enablerssitunes) && $pcast->enablerssitunes) {
            $string = get_string('pcastlink', 'pcast');
            require_once("$CFG->dirroot/mod/pcast/rsslib.php");
            $url = pcast_rss_get_url($PAGE->cm->context->id, $USER->id, 'pcast', $args);
            $pcastnode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));

        }

    }
}


function pcast_get_itunes_categories($item, $pcast) {

    // Split the category info into the top category and nested category.
    if (isset($item->category)) {
        $length = strlen($item->category);
        switch ($length) {
            case 4:
                $item->topcategory = substr($item->category, 0, 1);
                $item->nestedcategory = (int)substr($item->category, 1, 3);
                break;
            case 5:
                $item->topcategory = substr($item->category, 0, 2);
                $item->nestedcategory = (int)substr($item->category, 2, 3);
                break;
            case 6:
                $item->topcategory = substr($item->category, 0, 3);
                $item->nestedcategory = (int)substr($item->category, 3, 3);
                break;

            default:
                // SHOULD NEVER HAPPEN.
                $item->topcategory = $pcast->topcategory;
                $item->nestedcategory = $pcast->nestedcategory;
                break;
        }
    } else {
        // Will only happen if categories are disabled.
        $item->topcategory = $pcast->topcategory;
        $item->nestedcategory = $pcast->nestedcategory;
    }
    return $item;
}


 /**
  * File browsing support for pcast module.
  *
  * @param file_browser $browser
  * @param array $areas
  * @param stdClass $course
  * @param cm_info $cm
  * @param context $context
  * @param string $filearea
  * @param int $itemid
  * @param string $filepath
  * @param string $filename
  * @return file_info_stored file_info_stored instance or null if not found
  */
function mod_pcast_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    if ($filearea === 'summary' or $filearea === 'episode') {
        if (!$episode = $DB->get_record('pcast_episodes', array('id' => $itemid))) {
            return null;
        }

        if (!$pcast = $DB->get_record('pcast', array('id' => $cm->instance))) {
            return null;
        }

        if ($pcast->requireapproval and !$episode->approved and !has_capability('mod/pcast:approve', $context)) {
            return null;
        }

        if (is_null($itemid)) {
            require_once($CFG->dirroot.'/mod/pcast/locallib.php');
            return new pcast_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
        }

        $filecontext = context_module::instance($cm->id);

        $fs = get_file_storage();
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!($storedfile = $fs->get_file($filecontext->id, 'mod_pcast', $filearea, $itemid, $filepath, $filename))) {
            return null;
        }
        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        return new file_info_stored($browser, $filecontext, $storedfile, $urlbase, $filearea, $itemid, true, true, false, false);

    } else if ($filearea === 'logo') {

        if (!$pcast = $DB->get_record('pcast', array('id' => $cm->instance))) {
            return null;
        }
        if (is_null($itemid)) {
            require_once($CFG->dirroot.'/mod/pcast/locallib.php');
            return new pcast_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
        }

        $filecontext = context_module::instance($cm->id);
        $fs = get_file_storage();
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!($storedfile = $fs->get_file($filecontext->id, 'mod_pcast', $filearea, $itemid, $filepath, $filename))) {
            return null;
        }
        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        return new file_info_stored($browser, $filecontext, $storedfile, $urlbase, $filearea, $itemid, true, true, false, false);
    }

    return null;
}

/**
 * Serves all files for the pcast module.
 *
 * @global stdClass $CFG
 * @global stdClass $DB
 * @global stdClass $USER (used only for logging if available)
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function pcast_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea === 'episode' or $filearea === 'summary') {
        $episodeid = (int)array_shift($args);

        if (!$episode = $DB->get_record('pcast_episodes', array('id' => $episodeid))) {

            return false;
        }

        if (!$pcast = $DB->get_record('pcast', array('id' => $cm->instance))) {

            return false;
        }

        if ($pcast->requireapproval and !$episode->approved and !has_capability('mod/pcast:approve', $context)) {

            return false;
        }
        $relativepath = implode('/', $args);
        $filecontext = context_module::instance($cm->id);
        $fullpath = "/$filecontext->id/mod_pcast/$filearea/$episodeid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {

            return false;
        }

        // Log the file as viewed.
        $pcast->URL = $CFG->wwwroot . '/pluginfile.php' . $fullpath;
        $pcast->filename = implode('/', $args);
        if (!empty($USER->id)) {
            pcast_add_view_instance($pcast, $episode, $USER->id, $context);
        }

        // Finally send the file.
        send_stored_file($file, 0, 0, $forcedownload, $options); // Download MUST be forced - security!

    } else if ($filearea === 'logo') {

        $relativepath = implode('/', $args);
        $filecontext = context_module::instance($cm->id);
        $fullpath = "/$filecontext->id/mod_pcast/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {

            return false;
        }

        // Finally send the file.
        send_stored_file($file, 0, 0, $forcedownload, $options); // Download MUST be forced - security!

    }

    return false;
}

/**
 * logs the pcast files.
 *
 * @global stdClass $CFG
 * @param stdClass $pcast
 * @param string $userid
 * @return bool false if error else true
 */
function pcast_add_view_instance($pcast, $episode, $userid, $context) {
    global $DB;

    // Lookup the user add add to the view count.
    if (!$view = $DB->get_record("pcast_views", array("episodeid" => $episode->id, "userid" => $userid))) {

        // User has never seen the podcast episode.
        $view = new stdClass();
        $view->userid = $userid;
        $view->views = 1;
        $view->episodeid = $episode->id;
        $view->lastview = time();

        if (!$result = $DB->insert_record("pcast_views", $view)) {
            print_error('databaseerror', 'pcast');
        }

    } else {
        // The user has viewed the episode before.
        $view->views = $view->views + 1;
        $view->lastview = time();
        if (!$result = $DB->update_record("pcast_views", $view)) {
            print_error('databaseerror', 'pcast');
        }
    }

    $event = \mod_pcast\event\episode_viewed::create(array(
        'objectid' => $view->episodeid,
        'context' => $context
    ));

    $event->add_record_snapshot('pcast_episodes', $episode);
    $event->add_record_snapshot('pcast', $pcast);
    $event->trigger();

    return $result;
}

/**
 * Returns all other caps used in module
 * @return array
 */
function pcast_get_extra_capabilities() {
    return array('moodle/comment:post',
                 'moodle/comment:view',
                 'moodle/site:viewfullnames',
                 'moodle/site:trustcontent',
                 'moodle/rating:view',
                 'moodle/rating:viewany',
                 'moodle/rating:viewall',
                 'moodle/rating:rate',
                 'moodle/site:accessallgroups');
}

// Course reset code.
/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the pcast.
 * @param stdClass $mform form passed by reference
 */
function pcast_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'pcastheader', get_string('modulenameplural', 'pcast'));
    $mform->addElement('checkbox', 'reset_pcast_all', get_string('resetpcastsall', 'pcast'));

    $mform->addElement('checkbox', 'reset_pcast_notenrolled', get_string('deletenotenrolled', 'pcast'));
    $mform->disabledIf('reset_pcast_notenrolled', 'reset_pcast_all', 'checked');

    $mform->addElement('checkbox', 'reset_pcast_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_pcast_ratings', 'reset_pcast_all', 'checked');

    $mform->addElement('checkbox', 'reset_pcast_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_pcast_comments', 'reset_pcast_all', 'checked');

    $mform->addElement('checkbox', 'reset_pcast_views', get_string('deleteallviews', 'pcast'));
    $mform->disabledIf('reset_pcast_views', 'reset_pcast_all', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function pcast_reset_course_form_defaults($course) {
    return array('reset_pcast_all' => 0,
                 'reset_pcast_ratings' => 1,
                 'reset_pcast_comments' => 1,
                 'reset_pcast_notenrolled' => 0,
                 'reset_pcast_views' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @param int $courseid
 * @param string optional type
 */
// TODO: LOOK AT AFTER GRADES ARE IMPLEMENTED!
function pcast_reset_gradebook($courseid, $type='') {
    global $DB;

    $sql = "SELECT g.*, cm.idnumber as cmidnumber, g.course as courseid
              FROM {pcast} g, {course_modules} cm, {modules} m
             WHERE m.name='pcast' AND m.id=cm.module AND cm.instance=g.id AND g.course=?";

    if ($pcasts = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($pcasts as $pcast) {
            pcast_grade_item_update($pcast, 'reset');
        }
    }
}
/**
 * Actual implementation of the rest coures functionality, delete all the
 * pcast responses for course $data->courseid.
 *
 * @global stdClass
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function pcast_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'pcast');
    $status = array();

    $allepisodessql = "SELECT e.id
                        FROM {pcast_episodes} e
                             JOIN {pcast} p ON e.pcastid = p.id
                       WHERE p.course = ?";

    $allpcastssql = "SELECT p.id
                           FROM {pcast} p
                          WHERE p.course = ?";

    $params = array($data->courseid);

    $fs = get_file_storage();

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass();
    $ratingdeloptions->component = 'mod_pcast';
    $ratingdeloptions->ratingarea = 'episode';

    // Delete entries if requested.
    if (!empty($data->reset_pcast_all)) {

        $params[] = 'pcast_episode';
        $DB->delete_records_select('comments', "itemid IN ($allepisodessql) AND commentarea=?", $params);
        $DB->delete_records_select('pcast_episodes', "pcastid IN ($allpcastssql)", $params);

        // Now get rid of all attachments.
        if ($pcasts = $DB->get_records_sql($allpcastssql, $params)) {
            foreach ($pcasts as $pcastid => $unused) {
                if (!$cm = get_coursemodule_from_instance('pcast', $pcastid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_pcast', 'episode');

                // Delete ratings.
                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            pcast_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('resetpcastsall', 'pcast'), 'error' => false);

    } else if (!empty($data->reset_pcast_notenrolled)) {
        // Remove entries by users not enrolled into course.

        // Get list of enrolled users.
        $people = get_enrolled_users(context_course::instance($data->courseid));
        $list = '';
        $list2 = '';
        foreach ($people as $person) {
            $list .= ' AND e.userid != ?';
            $list2 .= ' AND userid != ?';
            $params[] = $person->id;

        }
        // Construct SQL to episodes from users whe are no longer enrolled.
            $unenrolledepisodessql = "SELECT e.id
                                      FROM {pcast_episodes} e
                                      WHERE e.course = ? " . $list;

        $params[] = 'pcast_episode';
        $DB->delete_records_select('comments', "itemid IN ($unenrolledepisodessql) AND commentarea=?", $params);
        $DB->delete_records_select('pcast_episodes', "course =? ". $list2, $params);

        // Now get rid of all attachments.
        if ($pcasts = $DB->get_records_sql($unenrolledepisodessql, $params)) {
            foreach ($pcasts as $pcastid => $unused) {
                if (!$cm = get_coursemodule_from_instance('pcast', $pcastid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_pcast', 'episode');

                // Delete ratings.
                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            pcast_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('deletenotenrolled', 'pcast'), 'error' => false);

    }

    // Remove all ratings.
    if (!empty($data->reset_pcast_ratings)) {
        // Remove ratings.
        if ($pcasts = $DB->get_records_sql($allpcastssql, $params)) {
            foreach ($pcasts as $pcastid => $unused) {
                if (!$cm = get_coursemodule_from_instance('pcast', $pcastid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);

                // Delete ratings.
                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            pcast_reset_gradebook($data->courseid);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallratings'), 'error' => false);
    }

    // Remove comments.
    if (!empty($data->reset_pcast_comments)) {
        $params[] = 'pcast_episode';
        $DB->delete_records_select('comments', "itemid IN ($allepisodessql) AND commentarea= ? ", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallcomments'), 'error' => false);
    }

    // Remove views.
    if (!empty($data->reset_pcast_views)) {
        $DB->delete_records_select('pcast_views', "episodeid IN ($allepisodessql) ", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallviews', 'pcast'), 'error' => false);
    }
    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        shift_course_mod_dates('pcast', array('assesstimestart', 'assesstimefinish'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}


/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */

function pcast_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-pcast-*' => get_string('page-mod-pcast-x', 'pcast'),
        'mod-pcast-view' => get_string('page-mod-pcast-view', 'pcast'),
        'mod-pcast-edit' => get_string('page-mod-pcast-edit', 'pcast'));
    return $modulepagetype;

}


// TODO: RATINGS CODE -UNTESTED!

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @param int $pcastid id of pcast
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function pcast_get_user_grades($pcast, $userid = 0) {

    global $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');
    $rm = new rating_manager();

    $ratingoptions = new stdClass();

    // Need these to work backwards to get a context id. Is there a better way to get contextid from a module instance?
    $ratingoptions->modulename = 'pcast';
    $ratingoptions->moduleid   = $pcast->id;
    $ratingoptions->component = 'mod_pcast';
    $ratingoptions->ratingarea = 'episode';

    $ratingoptions->userid = $userid;
    $ratingoptions->aggregationmethod = $pcast->assessed;
    $ratingoptions->scaleid = $pcast->scale;
    $ratingoptions->itemtable = 'pcast_episodes';
    $ratingoptions->itemtableusercolumn = 'userid';

    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @param stdClass $commentparam {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function pcast_comment_permissions($commentparam) {
    return array('post' => true, 'view' => true);
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @param stdClass $commentparam {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function pcast_comment_validate($commentparam) {
    global $DB;
    // Validate comment area.
    if ($commentparam->commentarea != 'pcast_episode') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$record = $DB->get_record('pcast_episodes', array('id' => $commentparam->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$pcast = $DB->get_record('pcast', array('id' => $record->pcastid))) {
        throw new comment_exception('invalidid', 'data');
    }
    if (!$course = $DB->get_record('course', array('id' => $pcast->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('pcast', $pcast->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    $context = context_module::instance($cm->id);

    if ($pcast->requireapproval and !$record->approved and !has_capability('mod/pcast:approve', $context)) {
        throw new comment_exception('notapproved', 'pcast');
    }
    // Validate context id.
    if ($context->id != $commentparam->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // Validation for comment deletion.
    if (!empty($commentparam->commentid)) {
        if ($comment = $DB->get_record('comments', array('id' => $commentparam->commentid))) {
            if ($comment->commentarea != 'pcast_episode') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $commentparam->context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $commentparam->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return rating related permissions
 * @param string $options the context id
 * @return array an associative array of the user's rating permissions
 */
function pcast_rating_permissions($contextid, $component, $ratingarea) {

    if ($component != 'mod_pcast' || $ratingarea != 'episode') {

        // We don't know about this component/ratingarea so just return null to get the
        // default restrictive permissions.
        return null;

    }
    $context = context::instance_by_id($contextid);

    if (!$context) {
        print_error('invalidcontext');
        return null;
    } else {
        return array('view' => has_capability('mod/pcast:viewrating', $context),
                     'viewany' => has_capability('mod/pcast:viewanyrating', $context),
                     'viewall' => has_capability('mod/pcast:viewallratings', $context),
                     'rate' => has_capability('mod/pcast:rate', $context));
    }
}


/**
 * Validates a submitted rating
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated.
 *                           NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [optional]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function pcast_rating_validate($params) {
    global $DB, $USER;

    // Check the component is mod_pcast.

    if ($params['component'] != 'mod_pcast') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is episode (the only rating area in pcast).

    if ($params['ratingarea'] != 'episode') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own posts.
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $pcastsql = "SELECT p.id as pcastid,
                        p.scale,
                        p.course,
                        e.userid as userid,
                        e.approved,
                        e.timecreated,
                        p.assesstimestart,
                        p.assesstimefinish
                      FROM {pcast_episodes} e
                      JOIN {pcast} p ON e.pcastid = p.id
                     WHERE e.id = :itemid";

    $pcastparams = array('itemid' => $params['itemid']);
    $info = $DB->get_record_sql($pcastsql, $pcastparams);
    if (!$info) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }

    if ($info->scale != $params['scaleid']) {
        // The scale being submitted doesnt match the one in the database.
        throw new rating_exception('invalidscaleid');
    }

    // Check that the submitted rating is valid for the scale.
    if ($params['rating'] < 0) {
        throw new rating_exception('invalidnum');
    } else if ($info->scale < 0) {
        // Its a custom scale.
        $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->scale) {
        // If its numeric and submitted rating is above maximum.
        throw new rating_exception('invalidnum');
    }

    if (!$info->approved) {
        // Item isnt approved.
        throw new rating_exception('nopermissiontorate');
    }

    // Check the item we're rating was created in the assessable time window.
    if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
            throw new rating_exception('notavailable');
        }
    }

    $cm = get_coursemodule_from_instance('pcast', $info->pcastid, $info->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    // If the supplied context doesnt match the item's context.
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    return true;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_pcast_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['pcast'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/pcast/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Add a get_coursemodule_info function in case any pcast type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function pcast_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, completionepisodes';
    if (!$pcast = $DB->get_record('pcast', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $pcast->name;

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionepisodes'] = $pcast->completionepisodes;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param object $cm the cm_info object.
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_pcast_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (!$cm instanceof cm_info || !isset($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionepisodes':
                if (empty($val)) {
                    continue;
                }
                $descriptions[] = get_string('completionepisodes', 'pcast', $val);
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

// Gradebook functions (Based on mod_glossary) Not sure how these are called.

/**
 * Update activity grades
 *
 * @global stdClass
 * @global stdClass
 * @param stdClass $pcast null means all glossaries (with extra cmidnumber property)
 * @param int $userid specific user only, 0 means all
 */
function pcast_update_grades($pcast=null, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$pcast->assessed) {
        pcast_grade_item_update($pcast);

    } else if ($grades = pcast_get_user_grades($pcast, $userid)) {
        pcast_grade_item_update($pcast, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new object();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        pcast_grade_item_update($pcast, $grade);

    } else {
        pcast_grade_item_update($pcast);
    }
}

/**
 * Create/update grade item for given pcast
 *
 * @global stdClass
 * @param stdClass $pcast object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int, 0 if ok, error code otherwise
 */
function pcast_grade_item_update($pcast, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $pcast->name, 'idnumber' => $pcast->cmidnumber);

    if (!$pcast->assessed or $pcast->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($pcast->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $pcast->scale;
        $params['grademin']  = 0;

    } else if ($pcast->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$pcast->scale;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/pcast', $pcast->course, 'mod', 'pcast', $pcast->id, 0, $grades, $params);
}

/**
 * Delete grade item for given pcast
 *
 * @global stdClass
 * @param stdClass $pcast object
 */
function pcast_grade_item_delete($pcast) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/pcast', $pcast->course, 'mod', 'pcast', $pcast->id, 0, null, array('deleted' => 1));
}
