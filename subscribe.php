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
 * This file is used to generate and serve the .pcast file which is used
 * by iTunes to subscribe to the RSS feed
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output.
// Comment this out to see any error messages during RSS generation.
define('NO_DEBUG_DISPLAY', true);

// Sessions not used here, we recreate $USER every time we are called.
define('NO_MOODLE_COOKIES', true);

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/rsslib.php');
require_once($CFG->dirroot.'/mod/pcast/rsslib.php');

// RSS feeds must be enabled site-wide.
if (empty($CFG->enablerssfeeds)) {
    debugging('DISABLED (admin variables)');
    pcast_rss_error();
}

// RSS must be enabled for the module.
if (empty($CFG->pcast_enablerssfeeds)) {
    debugging("DISABLED (module configuration)");
    pcast_rss_error();
}

// All the arguments are in the path.
$relativepath = get_file_argument();
if (!$relativepath) {
    pcast_rss_error();
}

// Extract relative path components into variables.
$args = explode('/', trim($relativepath, '/'));
if (count($args) < 5) {
    pcast_rss_error();
}

// Validate the feed.
$contextid   = (int)$args[0];
$token  = clean_param($args[1], PARAM_ALPHANUM);
$componentname = clean_param($args[2], PARAM_FILE);
$pcastid  = clean_param($args[3], PARAM_INT);
$groupid  = clean_param($args[4], PARAM_INT);
$uservalidated = false;


// Authenticate the user from the token.
$userid = rss_get_userid_from_token($token);
if (!$userid) {
    pcast_rss_error('rsserrorauth');
}

$user = get_complete_user_data('id', $userid);
\core\session\manager::set_user($user); // For login and capability checks.


// Check the context actually exists.
list($context, $course, $cm) = get_context_info_array($contextid);

if (!$context) {
    pcast_rss_error();
}

try {
    $autologinguest = true;
    $setwantsurltome = true;
    $preventredirect = true;
    require_course_login($course, $autologinguest, $cm, $setwantsurltome, $preventredirect);
} catch (Exception $e) {
    if (isguestuser()) {
        pcast_rss_error('rsserrorguest');
    } else {
        pcast_rss_error('rsserrorauth');
    }
}

// Now that we know that the user is vaid, lets generate see if they can see the feed.

// Check capabilities.
$cm = get_coursemodule_from_instance('pcast', $pcastid, 0, false, MUST_EXIST);
if ($cm) {
        $modcontext = context_module::instance($cm->id);

    // Context id from db should match the submitted one.
    if ($context->id == $modcontext->id && has_capability('mod/pcast:view', $modcontext)) {
        $uservalidated = true;
    }
}

// Check group mode 0/1/2 (All participants).
$groupmode = groups_get_activity_groupmode($cm);

// Using Groups, check to see if user should see all participants.
if ($groupmode == SEPARATEGROUPS) {
    // User must have the capability to see all groups or be a member of that group.
    $members = get_enrolled_users($context, 'mod/pcast:write', $groupid, 'u.id', 'u.id ASC');

    // Is a member of the current group.
    if (!isset($members[$userid]->id) or ($members[$userid]->id != $userid)) {

        // Not a member of the group, can you see all groups (from CAPS).
        if (!has_capability('moodle/site:accessallgroups', $context, $userid)) {
            $uservalidated = false;
        }

    } else {
        // Are a member of the current group.
        // Is the group #0 (Group 0 is all users).
        if ($groupid == 0 and !has_capability('moodle/site:accessallgroups', $context, $userid)) {
            $uservalidated = false;
        }
    }

}

if (!$uservalidated) {
    pcast_rss_error('rsserrorauth');
}

// OK, the use should be able to see the feed, generate the .pcast file.

$pcast = $DB->get_record('pcast', array('id' => $pcastid), '*', MUST_EXIST);

// Check to se if RSS is enabled.
// NOTE: cannot use the rss_enabled_for_mod() function due to the functions internals and naming conflicts.
if (($pcast->rssepisodes == 0)||(empty($pcast->rssepisodes))) {
    pcast_rss_error();
}

$sql = pcast_rss_get_sql($pcast);

$filename = rss_get_file_name($pcast, $sql);

// Append the GroupID to the end of the filename.
$filename .= '_'.$groupid;
$cachedfilepath = pcast_rss_get_file_full_name('mod_pcast', $filename);

// Figure out the URL for the podcast based on the user info.
$args = $pcast->id . '/' .$groupid;
$url = new moodle_url(rss_get_url($context->id, $userid, 'pcast', $args));

// Build the .pcast file.
$rss = pcast_build_pcast_file($pcast, $url);

// Save the XML contents to file.
$status = pcast_rss_save_file('mod_pcast', $filename, $rss);

if (!$status) {
    $cachedfilepath = null;
}

// Check that file exists.
if (empty($cachedfilepath) || !file_exists($cachedfilepath)) {
    die($cachedfilepath);
    pcast_rss_error();
}

// Send the .pcast file to the user!
send_file($cachedfilepath, 'rss.pcast', 0, 0, 0, 1);   // DO NOT CACHE.


/*
 * Sends an error formatted as an rss file and then dies.
 */
function pcast_rss_error($error='rsserror', $filename='rss.xml', $lifetime=0) {
    send_file(rss_geterrorxmlfile($error), $filename, $lifetime, false, true);
    exit;
}
