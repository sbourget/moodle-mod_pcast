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
 * Internal library of functions for module pcast
 *
 * All the pcast specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

/**
 * Function to get information from media files
 * used for <itunes::duration>
 * @global stdClass $CFG
 * @param string $filename
 * @return array
 */


function pcast_get_media_information($filename) {
    global $CFG;
    include_once($CFG->dirroot.'/mod/pcast/lib/getid3/getid3/getid3.php');

    // Initialize getID3 engine.
    $getid3 = new getID3;

    // Analyze file and store returned data in $ThisFileInfo.
    $mp3info = $getid3->analyze($filename);
    return $mp3info;  // Playtime in minutes:seconds, formatted string.

}


/**
 * Get the complete file path based on the SHA1 hash
 *
 * @global stdClass $CFG
 * @param object $filehash (This is the content hash)
 * @return path to file in dataroot, false on error
 **/
function pcast_file_path_lookup ($filehash) {
    global $CFG;
    if (!empty($filehash)) {
        $hash1 = substr($filehash, 0, 2);
        $hash2 = substr($filehash, 2, 2);
        $filepath = $CFG->dataroot . '/filedir/' . $hash1 .'/' .$hash2 . '/' . $filehash;
        return $filepath;

    } else {
        return false;
    }
}

/**
 * Prints the approval menu
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_approval_menu($cm, $pcast, $mode, $hook, $sortkey = '', $sortorder = '') {

    echo html_writer::start_tag('div', array('class' => 'pcast-links')). "\n";
    echo html_writer::tag('div', get_string("explainalphabet", "pcast"), array('class' => 'pcastexplain'));
    echo html_writer::empty_tag('br');

    pcast_print_special_links($cm, $pcast, $mode, $hook);
    pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
    pcast_print_all_links($cm, $pcast, $mode, $hook);
    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
    echo html_writer::end_tag('div'). "\n";
}

/**
 * Prints the alphabet menu
 * @param object $cm
 * @param object $pcast
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_alphabet_menu($cm, $pcast, $mode, $hook, $sortkey='', $sortorder = '') {

    echo html_writer::start_tag('div', array('class' => 'pcast-links')). "\n";
    echo html_writer::tag('div', get_string("explainalphabet", "pcast"), array('class' => 'pcastexplain'));
    echo html_writer::empty_tag('br');

    pcast_print_special_links($cm, $pcast, $mode, $hook);
    pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
    pcast_print_all_links($cm, $pcast, $mode, $hook);
    echo html_writer::end_tag('div'). "\n";

}

/**
 * Prints the date menu
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_date_menu($cm, $pcast, $mode, $hook, $sortkey='', $sortorder = '') {
    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
}

/**
 * Prints the author menu link
 * @param object $cm
 * @param object $pcast
 * @param string mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_author_menu($cm, $pcast, $mode, $hook, $sortkey = '', $sortorder = '') {

    echo html_writer::start_tag('div', array('class' => 'pcast-links')). "\n";
    echo html_writer::tag('div', get_string("explainalphabet", "pcast"), array('class' => 'pcastexplain'));
    echo html_writer::empty_tag('br');

    if (empty($sortkey)) {
        $sortkey = PCAST_AUTHOR_LNAME;
    }
    if (empty($sortorder)) {
        $sortkey = 'asc';
    }
    pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
    pcast_print_all_links($cm, $pcast, $mode, $hook);
    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
    echo html_writer::end_tag('div'). "\n";
}

/**
 * Prints the category menu
 * @global stdClass $DB
 * @global stdClass $OUTPUT
 * @param object $cm
 * @param object $pcast
 * @param string $hook
 * @param object $category
 * @todo This should use html_writer::tag()
 * @todo These styles should not be hard coded
 */
function pcast_print_categories_menu($cm, $pcast, $hook=PCAST_SHOW_ALL_CATEGORIES) {
     global $DB, $OUTPUT;

     echo '<table border="0" width="100%">';
     echo '<tr>';

     echo '<td class="pcast-menu20">';

     echo '</td>';

     echo '<td class="pcast-menu60">';
     echo '<span class="pcast-bold">';

     $menu = array();
     $menu[PCAST_SHOW_ALL_CATEGORIES] = get_string("allcategories", "pcast");
     $menu[PCAST_SHOW_NOT_CATEGORISED] = get_string("notcategorised", "pcast");

    // Generate Top Categories.
    if ($topcategories = $DB->get_records("pcast_itunes_categories")) {
        foreach ($topcategories as $topcategory) {
            $value = (int)$topcategory->id * 1000;
            $menu[(int)$value] = $topcategory->name;
        }
    }

    // Generate Secondary Category.
    if ($nestedcategories = $DB->get_records("pcast_itunes_nested_cat")) {
        foreach ($nestedcategories as $nestedcategory) {
            $value = (int)$nestedcategory->topcategoryid * 1000;
            $value = $value + (int)$nestedcategory->id;
            $menu[(int)$value] = '&nbsp;&nbsp;' .$nestedcategory->name;
        }
    }
    ksort($menu);

    // Print the category name.
    if ( $hook == PCAST_SHOW_NOT_CATEGORISED ) {
        echo get_string("episodeswithoutcategory", "pcast");

    } else if ( $hook == PCAST_SHOW_ALL_CATEGORIES ) {
        echo get_string("allcategories", "pcast");
    } else {
        // Lookup the category name by 4 digit ID.
        $category = new stdClass();
        $category->category = $hook;
        $category = pcast_get_itunes_categories($category, $pcast);

        // Print the category names in the format top: nested.
        if ($category->nestedcategory == 0) {
            echo $menu[(int)$hook];
        } else {
            // TODO: convert to lang file later.
            echo $menu[(int)$category->topcategory * 1000].': '.$menu[(int)$hook];
        }
    }

    echo '</span></td>';
    echo '<td class="pcast-menu20">';

    $select = new single_select(new moodle_url("/mod/pcast/view.php",
                                 array('id' => $cm->id, 'mode' => PCAST_CATEGORY_VIEW)),
                                 'hook', $menu, $hook, null, "catmenu");
    $select->set_label(get_string('categories', 'pcast'), array('class' => 'accesshide'));

    echo $OUTPUT->render($select);
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

/**
 * Prints the link to display all episodes.
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 */
function pcast_print_all_links($cm, $pcast, $mode, $hook) {

    $strallentries = get_string("allentries", "pcast");
    if ( $hook == 'ALL' ) {
        echo html_writer::tag('span', $strallentries, array('class' => 'pcast-bold'));
    } else {
        $strexplainall = strip_tags(get_string("explainall", "pcast"));
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => $mode, 'hook' => 'ALL'));
        echo html_writer::tag('a', $strallentries, array('title' => $strexplainall, 'href' => $url));
    }

}

/**
 * Prints the symbols links used to sort the episodes.
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 */
function pcast_print_special_links($cm, $pcast, $mode, $hook) {

    $strspecial = get_string("special", "pcast");
    if ( $hook == 'SPECIAL' ) {
        echo html_writer::tag('span', $strspecial, array('class' => 'pcast-bold'));
        echo "  | ";
    } else {
        $strexplainspecial = strip_tags(get_string("explainspecial", "pcast"));
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id, 'mode' => $mode, 'hook' => 'SPECIAL'));
        echo html_writer::tag('a', $strspecial, array('title' => $strexplainspecial, 'href' => $url));
        echo "  | ";
    }

}

/**
 * Prints the individual letter links used to sort the episodes.
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder) {

    $alphabet = explode(",", get_string('alphabet', 'langconfig'));
    $lettersbyline = 26;
    for ($i = 0; $i < count($alphabet); $i++) {
        if ( $hook == $alphabet[$i] and $hook) {
            echo html_writer::tag('span', $alphabet[$i], array('class' => 'pcast-bold'));
        } else {
            $url = new moodle_url('/mod/pcast/view.php',
                   array('id' => $cm->id,
                         'mode' => $mode,
                         'hook' => urlencode($alphabet[$i]),
                         'sortkey' => $sortkey,
                         'sortorder' => $sortorder)
                   );

            echo html_writer::tag('a', $alphabet[$i], array('href' => $url));
        }
        if ((int) ($i % $lettersbyline) != 0 or $i == 0) {
            echo ' | ';
        } else {
            echo html_writer::empty_tag('br');
        }
    }

}

/**
 * Prints the sort by ASC / DSC links on the view page.
 * @global stdClass $OUTPUT
 * @param object $cm
 * @param string $mode
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_sorting_links($cm, $mode, $sortkey = '', $sortorder = '', $hook='') {
    global $OUTPUT;

    // Get our strings.
    $asc    = get_string("ascending", "pcast");
    $desc   = get_string("descending", "pcast");
    $strsortcreation = get_string("sortbycreation", "pcast");
    $strsortlastupdate = get_string("sortbylastupdate", "pcast");
    $strsortfname = get_string("firstname");
    $strsortlname = get_string("lastname");
    $strsortby = get_string("sortby", "pcast");
    $strsep = get_string('labelsep', 'langconfig');

    // Determine our sort order ASC or DESC.
    switch ($sortorder) {
        case 'desc':
            $currentorder = $desc;
            $neworder = 'asc';
            $strchangeto = get_string('changeto', 'pcast', $asc);
            $icon = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url($sortorder, 'pcast'),
                                                        'class' => 'icon',
                                                        'alt' => $strchangeto));

            break;

        case 'asc':
            $currentorder = $asc;
            $neworder = 'desc';
            $strchangeto = get_string('changeto', 'pcast', $desc);
            $icon = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url($sortorder, 'pcast'),
                                                        'class' => 'icon',
                                                        'alt' => $strchangeto));

            break;
        default:
            // Pick some reasonable defaults if sort order is not specified.
            switch ($sortkey) {
                case PCAST_DATE_UPDATED:
                case PCAST_DATE_CREATED:
                case PCAST_AUTHOR_FNAME:
                case PCAST_AUTHOR_LNAME:
                    $strchangeto = get_string('changeto', 'pcast', $asc);
                    $neworder = 'asc';
                    $icon = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('asc', 'pcast'),
                                                                'class' => 'icon',
                                                                'alt' => $strchangeto));
                    $currentorder = '';
                    break;

                default:
                    $icon = "";
                    $neworder = '';
                    $currentorder = '';
                    $strchangeto = get_string('changeto', 'pcast', $asc);

                    break;
            }

    }

    switch ($sortkey) {
        case PCAST_DATE_UPDATED:

            // URLs.
            $url1 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_DATE_UPDATED));
            $url2 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_DATE_CREATED));

            if ($neworder != '') {
                $url1->param('sortorder', $neworder);
            }

            // Hyperlinks.
            $link1 = html_writer::tag('a', $strsortlastupdate.$icon,
                     array('href' => $url1, 'title' => $strsortlastupdate.' '.$strchangeto));
            $link2 = html_writer::tag('a', $strsortcreation,
                    array('href' => $url2, 'title' => $strsortcreation.' '.$asc));

            // Output.
            $html = html_writer::tag('span', get_string('current', 'pcast', $strsortlastupdate .' ' . $currentorder),
                                             array('class' => 'accesshide'));
            $html .= $strsortby.$strsep;
            $html .= $link1 . ' | ';
            $html .= html_writer::tag('span', $link2, array('class' => 'pcast-bold'));

            break;

        case PCAST_DATE_CREATED:

            // URLs.
            $url1 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_DATE_UPDATED));
            $url2 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_DATE_CREATED));

            if ($neworder != '') {
                $url2->param('sortorder', $neworder);
            }

            // Hyperlinks.
            $link1 = html_writer::tag('a', $strsortlastupdate,
                     array('href' => $url1, 'title' => $strsortlastupdate.' '.$asc));
            $link2 = html_writer::tag('a', $strsortcreation.$icon,
                     array('href' => $url2, 'title' => $strsortcreation.' '.$strchangeto));

            // Output.
            $html = html_writer::tag('span', get_string('current', 'pcast', $strsortcreation .' ' . $currentorder),
                                             array('class' => 'accesshide'));
            $html .= $strsortby.$strsep;
            $html .= $link1 . ' | ';
            $html .= html_writer::tag('span', $link2, array('class' => 'pcast-bold'));

            break;

        case PCAST_AUTHOR_FNAME:

            // URLs.
            $url1 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_AUTHOR_LNAME));
            $url2 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_AUTHOR_FNAME));

            if ($neworder != '') {
                $url2->param('sortorder', $neworder);
            }

            // Hyperlinks.
            $link1 = html_writer::tag('a', $strsortlname,
                     array('href' => $url1, 'title' => $strsortlname.' '.$asc));
            $link2 = html_writer::tag('a', $strsortfname.$icon,
                     array('href' => $url2, 'title' => $strsortfname.' '.$strchangeto));

            // Output.
            $html = html_writer::tag('span', get_string('current', 'pcast', $strsortlname .' ' . $currentorder),
                                             array('class' => 'accesshide'));
            $html .= $strsortby.$strsep;
            $html .= $link1 . ' | ';
            $html .= html_writer::tag('span', $link2, array('class' => 'pcast-bold'));

            break;

        case PCAST_AUTHOR_LNAME:

            // URLs.
            $url1 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_AUTHOR_LNAME));
            $url2 = new moodle_url('/mod/pcast/view.php',
                    array('id' => $cm->id, 'mode' => $mode, 'hook' => $hook, 'sortkey' => PCAST_AUTHOR_FNAME));

            if ($neworder != '') {
                $url1->param('sortorder', $neworder);
            }

            // Hyperlinks.
            $link1 = html_writer::tag('a', $strsortlname.$icon,
                     array('href' => $url1, 'title' => $strsortlname.' '.$strchangeto));
            $link2 = html_writer::tag('a', $strsortfname,
                     array('href' => $url2, 'title' => $strsortfname.' '.$asc));

            // Output.
            $html = html_writer::tag('span', get_string('current', 'pcast', $strsortfname .' ' . $currentorder),
                                             array('class' => 'accesshide'));
            $html .= $strsortby.$strsep;
            $html .= $link1 . ' | ';
            $html .= html_writer::tag('span', $link2, array('class' => 'pcast-bold'));

            break;

        default:

            $html = '';

    }

    // Display the links.
    echo html_writer::empty_tag('br'). $html . html_writer::empty_tag('br');

}


/**
 * Function to display Pcast episodes
 * @global stdClass $OUTPUT
 * @global stdClass $DB
 * @global stdClass $USER
 * @param object $pcast
 * @param object $cm
 * @param int $groupmode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 * @param int $page
 * @return boolean
 */

function pcast_display_standard_episodes($pcast, $cm, $groupmode = 0, $hook='', $sortkey='', $sortorder='asc', $page = 0) {
    global $DB, $USER, $OUTPUT;

    $context = context_module::instance($cm->id);

    // Get the current group.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

    // Get the episodes for this pcast.
    if (!empty($sortorder)) {
        $sort = 'p.name '. $sortorder;
    } else {
        $sort = 'p.name ASC';
    }
    $sql = pcast_get_episode_sql();
    $sql .= " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";

    if (empty($hook) or ($hook == 'ALL')) {

        $sql .= " ORDER BY ". $sort;
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id));
    } else if ($hook == 'SPECIAL') {
        // Match Other Characters.
        $sql .= " AND (". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 )
                ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id,
                                         '1%', '2%', '3%', '4%', '5%',
                                         '6%', '7%', '8%', '9%', '0%'));
    } else {
        $sql .= " and ". $DB->sql_like('p.name', '?', false)." ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, $hook.'%'));
    }

    // Get Episode count.
    $count = 0;

    // Calculate starting episode.
    $start = $page * $pcast->episodesperpage;
    $end = ($page + 1) * $pcast->episodesperpage;
    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    foreach ($episodes as $episode) {
        if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
            // Display this episode (User is in the group).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        } else if ($currentgroup == 0) {
            // Display this episode (NO GROUPS USED or user is the author).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        }
    }

    if ($count > $pcast->episodesperpage) {
        // Print a paging bar here.
        $url = new moodle_url('/mod/pcast/view.php',
                array('id' => $cm->id,
                      'mode' => PCAST_STANDARD_VIEW,
                      'hook' => $hook,
                      'sortkey' => $sortkey,
                      'sortorder' => $sortorder)
                );

        echo html_writer::start_tag('div', array('class' => 'pcast-paging'));
        echo $OUTPUT->paging_bar($count, $page, $pcast->episodesperpage, $url);
        echo html_writer::end_tag('div');
    }
    return true;
}

/**
 * Determine if the user is able to view the specified episode.
 * This takes in account group member access.
 * @param object $episode
 * @param object $cm
 * @param int $groupmode
 * @return bool (true if allowed, false if denied)
 */
function pcast_episode_allowed_viewing($episode, $cm, $groupmode) {
    global $USER;

    $context = context_module::instance($cm->id);
    $currentgroup = 0;

    // Does the user have the ability to view this episode?
    if (!has_capability('mod/pcast:view', $context)) {
        return false;
    }

    // Has the episode been approved? If not then should they be able to see it?
    if ($episode->userid !== $USER->id) {
        // Not the author, Is it approved?
        if ($episode->approved == PCAST_EPISODE_DISAPPROVE) {
            // Not Approved, Can they approve it?
            if (!has_capability('mod/pcast:approve', $context)) {
                // Cannot approve it, so they cannot see it.
                return false;
            }
        }
    }

    // Get the current group info.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        // No groups.
        return true;
    }

    // See if user can view all groups.
    if (has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');

    // See if episode is created by a member of the group.
    if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
        // Member of the group.
        return true;

    } else {
        // Not a member.
        return false;
    }

    // Not able to view episode (should never get here).
    return false;
}

/**
 * Function to display episodes by category
 * @global stdClass $OUTPUT
 * @global stdClass $DB
 * @global stdClass $USER
 * @param object $pcast
 * @param object $cm
 * @param int $groupmode
 * @param string $hook
 * @param int $page
 */
function pcast_display_category_episodes($pcast, $cm, $groupmode = 0, $hook = PCAST_SHOW_ALL_CATEGORIES, $page = 0) {
    global $DB, $USER, $OUTPUT;

    $context = context_module::instance($cm->id);

    // Get the current group.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

    // Get the episodes for this pcast.
    $sql = pcast_get_episode_sql();
    $sql .= " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";

    if ($hook == PCAST_SHOW_ALL_CATEGORIES) {
        $sql .= " ORDER BY cat.name, ncat.name, p.name ASC";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id));

    } else if ($hook == PCAST_SHOW_NOT_CATEGORISED) {
        $sql .= " AND
                p.topcategory = ?
                ORDER BY p.name ASC";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, '0'));

    } else {
        $category = new stdClass();
        $category->category = $hook;
        $category = pcast_get_itunes_categories($category, $pcast);
        if ($category->nestedcategory == 0) {
            $sql .= " AND
                    p.topcategory = ?
                    ORDER BY cat.name, ncat.name, p.name ASC";
            $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, $category->topcategory));

        } else {
            $sql .= " AND
                    p.nestedcategory = ?
                    ORDER BY cat.name, ncat.name, p.name ASC";
            $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, $category->nestedcategory));

        }

    }

    // Get Episode count.
    $count = 0;

    // Calculate starting episode.
    $start = $page * $pcast->episodesperpage;
    $end = ($page + 1) * $pcast->episodesperpage;

    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    foreach ($episodes as $episode) {
        if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
            // Display this episode (User is in the group).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        } else if ($currentgroup == 0) {
            // Display this episode (NO GROUPS USED or user is the author).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        }
    }

    if ($count > $pcast->episodesperpage) {
        // Print a paging bar here.
        $url = new moodle_url('/mod/pcast/view.php',
                array('id' => $cm->id,
                      'mode' => PCAST_CATEGORY_VIEW,
                      'hook' => $hook)
                );

        echo html_writer::start_tag('div', array('class' => 'pcast-paging'));
        echo $OUTPUT->paging_bar($count, $page, $pcast->episodesperpage, $url);
        echo html_writer::end_tag('div');
    }
}

/**
 * Display all episodes that sorted by date
 * @global stdClass $OUTPUT
 * @global stdClass $DB
 * @global stdClass $USER
 * @param object $pcast
 * @param object $cm
 * @param int $groupmode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 * @param int $page
 */
function pcast_display_date_episodes($pcast, $cm, $groupmode = 0, $hook='',
                                     $sortkey = PCAST_DATE_CREATED, $sortorder='desc', $page=0) {
    global $DB, $USER, $OUTPUT;

    $context = context_module::instance($cm->id);

    // Get the current group.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

    // Get the episodes for this pcast.
    $sql = pcast_get_episode_sql();
    $sql .= " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";

    switch ($sortkey) {
        case PCAST_DATE_UPDATED:
            $sql .= " ORDER BY p.timemodified";
            break;

        case PCAST_DATE_CREATED:
        default:
            $sql .= " ORDER BY p.timecreated";
            break;
    }

    switch ($sortorder) {
        case 'asc':
            $sql .= " ASC , p.name ASC";
            break;
        case 'desc':
        default:
            $sql .= " DESC, p.name ASC";
            break;
    }

    $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id));

    // Get Episode count.
    $count = 0;

    // Calculate starting episode.
    $start = $page * $pcast->episodesperpage;
    $end = ($page + 1) * $pcast->episodesperpage;

    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    foreach ($episodes as $episode) {
        if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
            // Display this episode (User is in the group).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        } else if ($currentgroup == 0) {
            // Display this episode (NO GROUPS USED or user is the author).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        }
    }

    if ($count > $pcast->episodesperpage) {
        // Print a paging bar here.
        $url = new moodle_url('/mod/pcast/view.php',
                array('id' => $cm->id,
                      'mode' => PCAST_DATE_VIEW,
                      'hook' => $hook,
                      'sortkey' => $sortkey,
                      'sortorder' => $sortorder)
                );

        echo html_writer::start_tag('div', array('class' => 'pcast-paging'));
        echo $OUTPUT->paging_bar($count, $page, $pcast->episodesperpage, $url);
        echo html_writer::end_tag('div');
    }
}

/**
 * Display all episodes that sorted by author
 * @global stdClass $OUTPUT
 * @global stdClass $DB
 * @global stdClass $USER
 * @param object $pcast
 * @param object $cm
 * @param int $groupmode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 * @param int $page
 */
function pcast_display_author_episodes($pcast, $cm, $groupmode = 0, $hook='', $sortkey='', $sortorder='asc', $page = 0) {
    global $DB, $USER, $OUTPUT;

    $context = context_module::instance($cm->id);

    // Get the current group.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

    // Get the episodes for this pcast.
    $sql = pcast_get_episode_sql();
    $sql .= " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";

    // Setup for ASC or DESC sorting.
    switch ($sortorder) {
        case 'asc':
            $sort = "ASC";
            break;
        case 'desc':
        default:
            $sort = "DESC";
            break;
    }

    // Construct lookups based on FNAME / LNAME and HOOK.
    switch ($sortkey) {
        case PCAST_AUTHOR_LNAME:
            // Order is constant for all LNAME sorts.
            $order = " ORDER BY u.lastname " .$sort .", u.firstname " . $sort. ", p.name ASC";

            // Handle cases where you lookup by first letter of name (last / first).
            if (empty($hook) or ($hook == 'ALL')) {
                $sql .= $order;
                $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id));

            } else {
                $sql .= " and ". $DB->sql_like('u.lastname', '?', false) . $order;
                $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, $hook.'%'));
            }

            break;

        case PCAST_AUTHOR_FNAME:
        default:
            // Order is constant for all FNAME sorts.
            $order = " ORDER BY u.firstname " .$sort .", u.lastname " . $sort. ", p.name ASC";

            // Handle cases where you lookup by first letter of name (last / first).
            if (empty($hook) or ($hook == 'ALL')) {
                $sql .= $order;
                $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id));

            } else {
                $sql .= " and ". $DB->sql_like('u.firstname', '?', false) . $order;
                $episodes = $DB->get_records_sql($sql, array($pcast->id, '1', $USER->id, $hook.'%'));
            }

            break;
    }

    // Get Episode count.
    $count = 0;

    // Calculate starting episode.
    $start = $page * $pcast->episodesperpage;
    $end = ($page + 1) * $pcast->episodesperpage;

    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    foreach ($episodes as $episode) {
        if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
            // Display this episode (User is in the group).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        } else if ($currentgroup == 0) {
            // Display this episode (NO GROUPS USED or user is the author).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        }
    }

    if ($count > $pcast->episodesperpage) {
        // Print a paging bar here.
        $url = new moodle_url('/mod/pcast/view.php',
                array('id' => $cm->id,
                      'mode' => PCAST_AUTHOR_VIEW,
                      'hook' => $hook,
                      'sortkey' => $sortkey,
                      'sortorder' => $sortorder)
                );

        echo html_writer::start_tag('div', array('class' => 'pcast-paging'));
        echo $OUTPUT->paging_bar($count, $page, $pcast->episodesperpage, $url);
        echo html_writer::end_tag('div');
    }
}

/**
 * Display all episodes that have not yet been approved
 * @global stdClass $OUTPUT
 * @global stdClass $DB
 * @param object $pcast
 * @param object $cm
 * @param int $groupmode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 * @param int $page
 */
function pcast_display_approval_episodes($pcast, $cm, $groupmode = 0, $hook='', $sortkey='', $sortorder='asc', $page = 0) {
    global $DB, $OUTPUT;

    $context = context_module::instance($cm->id);

    // Get the current group.
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

    // Get the episodes for this pcast.
    $sql = pcast_get_episode_sql();
    $sql .= " WHERE p.pcastid = ? AND p.approved =?";

    if (!empty($sortorder)) {
        $sort = 'p.name '. $sortorder;
    } else {
        $sort = 'p.name ASC';
    }

    if (empty($hook) or ($hook == 'ALL')) {

        $sql .= " ORDER BY ". $sort;
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '0'));
    } else if ($hook == 'SPECIAL') {
        // Match Other Characters.
        $sql .= " AND (". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 OR ". $DB->sql_like('p.name', '?', false)."
                 )
                ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '0', '1%', '2%', '3%', '4%', '5%', '6%', '7%', '8%', '9%', '0%'));
    } else {
        $sql .= " and ". $DB->sql_like('p.name', '?', false)." ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql, array($pcast->id, '0', $hook.'%'));
    }

    // Get Episode count.
    $count = 0;

    // Calculate starting episode.
    $start = $page * $pcast->episodesperpage;
    $end = ($page + 1) * $pcast->episodesperpage;
    // Get Group members.

    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    foreach ($episodes as $episode) {
        if (isset($members[$episode->userid]->id) and ($members[$episode->userid]->id == $episode->userid)) {
            // Display this episode (User is in the group).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        } else if ($currentgroup == 0) {
            // Display this episode (NO GROUPS USED or user is the author).
            if (($count >= $start) and ($count < $end)) {
                pcast_display_episode_brief($episode, $cm);
            }
            $count++;
        }
    }

    if ($count > $pcast->episodesperpage) {
        // Print a paging bar here.
        $url = new moodle_url('/mod/pcast/view.php',
                array('id' => $cm->id,
                      'mode' => PCAST_APPROVAL_VIEW,
                      'hook' => $hook,
                      'sortkey' => $sortkey,
                      'sortorder' => $sortorder)
                );

        echo html_writer::start_tag('div', array('class' => 'pcast-paging'));
        echo $OUTPUT->paging_bar($count, $page, $pcast->episodesperpage, $url);
        echo html_writer::end_tag('div');
    }

}

/**
 * Generates the SQL needed to get all episodes belonging to a specific pcast.
 * @return string
 */
function pcast_get_episode_sql() {
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
                u.firstname as firstname,
                u.lastname as lastname
            FROM {pcast_episodes} p
            LEFT JOIN
                {pcast} AS pcast ON
                p.pcastid = pcast.id
            LEFT JOIN
                {user} AS u ON
                p.userid = u.id
            LEFT JOIN
                {pcast_itunes_categories} AS cat ON
                p.topcategory = cat.id
            LEFT JOIN
                {pcast_itunes_nested_cat} AS ncat ON
                p.nestedcategory = ncat.id";

    return $sql;
}

/**
 * Function to print overview of the episode
 * @global stdClass $CFG
 * @global stdClass $DB
 * @param type $episode
 * @param type $cm
 * @param type $showmedia
 * @param type $showlinks
 */
function pcast_display_episode_brief($episode, $cm, $showmedia= true, $showlinks = true) {
    global $CFG, $DB;

    $context = context_module::instance($cm->id);

    $strsep = get_string('labelsep', 'langconfig');
    $html = html_writer::start_tag('div', array('class' => 'no-overflow')). "\n";
    $html .= html_writer::start_tag('div', array('class' => 'pcast-episode')). "\n";

    $table = new html_table();
    $table->style = 'views';
    $table->cellpadding = '5';
    $table->colclasses = array('pcast-header', 'pcast-data');
    $table->width = '100%';
    $table->align = array ("RIGHT", "LEFT");
    // Name of episode.
    $table->data[] = array (get_string("name", "pcast"), format_text($episode->name, FORMAT_HTML, array('context' => $context)));

    // Description.
    $summarytext = file_rewrite_pluginfile_urls($episode->summary, 'pluginfile.php',
                                                $context->id, 'mod_pcast',
                                                'summary', $episode->id);

    $summarytext = format_text($summarytext, $episode->summaryformat, array('context' => $context));

    $table->data[] = array (get_string("summary", "pcast"),  $summarytext);

    // Category -Display only if enabled.
    if ((isset($episode->userscancategorize))and ($episode->userscancategorize != '0')) {
        if ((isset($episode->topcategory))and ($episode->topcategory != '0')) {
            $episode->category = $episode->topcategory;

            if ((isset($episode->nestedcategory))and ($episode->nestedcategory != '0')) {
                $episode->category .= $strsep. ' '.$episode->nestedcategory;

            }
        }
        if (isset($episode->category)) {
            $table->data[] = array (get_string("category", "pcast"), s($episode->category));
        }
    }

    // Attachment.
    if ($showmedia) {
        $table->data[] = array (get_string("pcastmediafile", "pcast"), pcast_display_mediafile_link($episode, $cm));
    }
    // Author.
    // Only print author if allowed or has manage rights.
    if (((isset($episode->displayauthor))and ($episode->displayauthor != '0')) or (has_capability('mod/pcast:manage', $context))) {
        $user = $DB->get_record("user", array("id" => $episode->userid));
        $table->data[] = array (get_string("author", "pcast"), fullname($user));
    }

    // Created.
    $table->data[] = array (get_string("created", "pcast"), userdate($episode->timecreated));

    // Updated.
    $table->data[] = array (get_string("updated", "pcast"), userdate($episode->timemodified));

    // Calculate editing period.
    $ineditingperiod = ((time() - $episode->timecreated < $CFG->maxeditingtime));
    $link = '';

    // Management Links.
    if ((has_capability('mod/pcast:manage', $context)) or ($ineditingperiod)) {

        // Edit Link.
        $url = new moodle_url('/mod/pcast/edit.php',
                 array('cmid' => $cm->id, 'id' => $episode->id));
        $link .= html_writer::tag('a', get_string('edit'),
                 array('href' => $url, 'title' => get_string('editthisepisode', 'pcast')));
        $link .= ' | '."\n";

        // Delete link.
        $url = new moodle_url('/mod/pcast/deleteepisode.php',
                 array('id' => $cm->id, 'episode' => $episode->id, 'prevmode' => 0));
        $link .= html_writer::tag('a', get_string('delete'),
                 array('href' => $url, 'title' => get_string('deletethisepisode', 'pcast')));
        $link .= ' | '."\n";

    }
        // View Link.
        $url = new moodle_url('/mod/pcast/showepisode.php',
                 array('eid' => $episode->id));
        $link .= html_writer::tag('a', get_string('view'),
                 array('href' => $url, 'title' => get_string('viewepisode', 'pcast')));

    // Approve / Disapprove Link.
    if ((has_capability('mod/pcast:approve', $context)) and ($episode->requireapproval)) {
        if (!$episode->approved) {
            // Approve link.
            $link .= ' | '."\n";
            $url = new moodle_url('/mod/pcast/approveepisode.php',
                     array('eid' => $episode->id, 'mode' => PCAST_APPROVAL_VIEW, 'sesskey' => sesskey()));
            $link .= html_writer::tag('a', get_string('approve'),
                     array('href' => $url, 'title' => get_string('approvethisepisode', 'pcast')));
        } else {
            // Disapprove link.
            $link .= ' | '."\n";
            $url = new moodle_url('/mod/pcast/approveepisode.php',
                     array('eid' => $episode->id, 'newstate' => PCAST_EPISODE_DISAPPROVE, 'sesskey' => sesskey()));
            $link .= html_writer::tag('a', get_string('disapprove', 'pcast'),
                     array('href' => $url, 'title' => get_string('disapprovethisepisode', 'pcast')));
        }
    }

    // Construct links.
    if ($showlinks) {
        $table->data[] = array ('', $link);
    }

    echo $html;
    echo html_writer::table($table);
    echo html_writer::end_tag('div') . "\n";
    echo html_writer::end_tag('div') . "\n";

}

/**
 * Display the full pcast episode
 * @global stdClass $CFG
 * @global stdClass $DB
 * @global stdClass $USER
 * @param object $episode
 * @param object $cm
 * @param object $course
 */
function pcast_display_episode_full($episode, $cm, $course) {
    global $CFG, $DB, $USER;

    $context = context_module::instance($cm->id);

    $strsep = get_string('labelsep', 'langconfig');
    $html = html_writer::start_tag('div', array('class' => 'pcast-episode')). "\n";

    $table = new html_table();
    $table->style = 'views';
    $table->cellpadding = '5';
    $table->colclasses = array('pcast-header', 'pcast-data');
    $table->width = '100%';
    $table->align = array ("RIGHT", "LEFT");
    // Name of episode.
    $table->data[] = array (get_string("name", "pcast"), $episode->name);

    // Description.
    $summarytext = file_rewrite_pluginfile_urls($episode->summary, 'pluginfile.php',
                                                $context->id, 'mod_pcast',
                                                'summary', $episode->id);

    $summarytext = format_text($summarytext, $episode->summaryformat, array('context' => $context));

    $table->data[] = array (get_string("summary", "pcast"),  $summarytext);

    // Category -Display only if enabled.
    if ((isset($episode->userscancategorize))and ($episode->userscancategorize != '0')) {
        if ((isset($episode->topcategory))and ($episode->topcategory != '0')) {
            $episode->category = $episode->topcategory;

            if ((isset($episode->nestedcategory))and ($episode->nestedcategory != '0')) {
                $episode->category .= $strsep. ' '.$episode->nestedcategory;

            }
        }
        if (isset($episode->category)) {
            $table->data[] = array (get_string("category", "pcast"), $episode->category);
        }
    }

    // Attachment.
    $table->data[] = array (get_string("pcastmediafile", "pcast"), pcast_display_mediafile_link($episode, $cm));

    // Duration.
    $length = array();
    $length2 = new stdClass();
    if (!empty($episode->duration)) {
        // Split up duration for printing.
        $length = explode(":", $episode->duration);
        if (count($length) == 2) {
            $length2->hour = 0;
            $length2->min = $length[0];
            $length2->sec = $length[1];
        } else {
            $length2->hour = $length[0];
            $length2->min = $length[1];
            $length2->sec = $length[2];
        }
    } else {
        $length2->hour = 0;
        $length2->min = 0;
        $length2->sec = 0;
    }
    if ($length2->hour == 0) {
        $table->data[] = array (get_string("duration", "pcast"), get_string("durationlength2", "pcast", $length2));
    } else {
        $table->data[] = array (get_string("duration", "pcast"), get_string("durationlength", "pcast", $length2));
    }

    // Author.
    // Only print author if allowed or has manage rights.
    if (((isset($episode->displayauthor)) and ($episode->displayauthor != '0')) or (has_capability('mod/pcast:manage', $context))) {
        $user = $DB->get_record("user", array("id" => $episode->userid));
        $table->data[] = array (get_string("author", "pcast"), fullname($user));
    }

    // Created.
    $table->data[] = array (get_string("created", "pcast"), userdate($episode->timecreated));

    // Updated.
    $table->data[] = array (get_string("updated", "pcast"), userdate($episode->timemodified));

    // Total views.
    $table->data[] = array (get_string("totalviews", "pcast"), pcast_get_episode_view_count($episode));

    // Total comments.
    if (($CFG->usecomments) and ($episode->userscancomment) and (has_capability('moodle/comment:view', $context))) {
        $table->data[] = array (get_string("totalcomments", "pcast"), pcast_get_episode_comment_count($episode, $cm));
    }

    // Total Ratings.
    if (($episode->assessed) and ((has_capability('moodle/rating:view', $context))
                             and ($episode->userid == $USER->id))
                             or (has_capability('moodle/rating:viewany', $context))) {

        $table->data[] = array (get_string("totalratings", "pcast"), pcast_get_episode_rating_count($episode, $cm));
    }

    // Calculate editing period.
    $ineditingperiod = ((time() - $episode->timecreated < $CFG->maxeditingtime));
    $manage = '';
    $approve = '';

        // Management Links.
    if ((has_capability('mod/pcast:manage', $context)) or ($ineditingperiod)) {

        // Edit Link.
        $url = new moodle_url('/mod/pcast/edit.php',
                   array('cmid' => $cm->id, 'id' => $episode->id));
        $manage .= html_writer::tag('a', get_string('edit'),
                   array('href' => $url, 'title' => get_string('editthisepisode', 'pcast')));
        $manage .= ' | '."\n";

        // Delete link.
        $url = new moodle_url('/mod/pcast/deleteepisode.php',
                   array('id' => $cm->id, 'episode' => $episode->id, 'prevmode' => 0));
        $manage .= html_writer::tag('a', get_string('delete'),
                   array('href' => $url, 'title' => get_string('deletethisepisode', 'pcast')));

    }

    // Approve / Disapprove Link.
    if ((has_capability('mod/pcast:approve', $context)) and ($episode->requireapproval)) {
        if (!$episode->approved) {
            // Approve link.
            $url = new moodle_url('/mod/pcast/approveepisode.php',
                        array('eid' => $episode->id, 'mode' => PCAST_APPROVAL_VIEW, 'sesskey' => sesskey()));
            $approve .= html_writer::tag('a', get_string('approve'),
                        array('href' => $url, 'title' => get_string('approvethisepisode', 'pcast')));
        } else {
            // Disapprove link.
            $url = new moodle_url('/mod/pcast/approveepisode.php',
                        array('eid' => $episode->id, 'newstate' => PCAST_EPISODE_DISAPPROVE, 'sesskey' => sesskey()));
            $approve .= html_writer::tag('a', get_string('disapprove', 'pcast'),
                        array('href' => $url, 'title' => get_string('disapprovethisepisode', 'pcast')));
        }
    }

    // Construct links.
    if ((!empty($manage)) and (!empty($approve))) {
        $link = $manage . ' | '."\n" . $approve;
    } else {
        $link = $manage . $approve;
    }
    $table->data[] = array ('', $link);

    echo $html;
    echo html_writer::table($table);
    echo html_writer::end_tag('div') . "\n";

}

/**
 * Displays all views for a single episode
 * @global stdClass $DB
 * @param object $episode
 * @param object $cm
 */
function pcast_display_episode_views($episode, $cm) {

    global $DB;

    if (!$views = $DB->get_records("pcast_views", array( "episodeid" => $episode->id))) {
        echo get_string('noviews', 'pcast', get_string('modulename', 'pcast'));
    } else {
        $strviews  = get_string("views", "pcast");
        $struser = get_string("user", "pcast");
        $strdate = get_string("date");

        $table = new html_table();
        $table->attributes['class'] = 'views';
        $table->head  = array ($struser, $strdate, $strviews);
        $table->align = array ("CENTER", "LEFT", "CENTER");
        $table->width = '100%';

        foreach ($views as $view) {
            $user = $DB->get_record("user", array("id" => $view->userid));
            $linedata = array (fullname($user), userdate($view->lastview), $view->views);
            $table->data[] = $linedata;
        }
        echo html_writer::empty_tag('br');
        echo html_writer::table($table);
    }

    // Trigger view list has been viewed event.
    $params = array(
            'context' => context_module::instance($cm->id),
            'objectid' => $episode->id
            );

    $event = \mod_pcast\event\episode_views_viewed::create($params);
    $event->add_record_snapshot('pcast_episodes', $episode);
    $event->trigger();
}

/**
 * Displays all comments for a single episode
 * @global stdClass $CFG
 * @param object $episode
 * @param object $cm
 * @param object $course
 */
function pcast_display_episode_comments($episode, $cm, $course) {

    global $CFG;
    $html = '';

    if ($episode->approved) {
        if ($episode->userscancomment) {
            // Get episode comments and display the comment box.
            $context = context_module::instance($cm->id);

            // Generate comment box using API.
            if (!empty($CFG->usecomments)) {
                require_once($CFG->dirroot . '/comment/lib.php');
                $cmt = new stdClass();
                $cmt->component = 'pcast';
                $cmt->context  = $context;
                $cmt->course   = $course;
                $cmt->cm       = $cm;
                $cmt->area     = 'pcast_episode';
                $cmt->itemid   = $episode->id;
                $cmt->showcount = true;
                $comment = new comment($cmt);
                $html = html_writer::tag('div', $comment->output(true), array('class' => 'pcast-comments'));

            }
            // Trigger comment viewed event.
            $params = array(
                    'context' => $context,
                    'objectid' => $episode->id
                    );

            $event = \mod_pcast\event\comments_viewed::create($params);
            $event->add_record_snapshot('pcast_episodes', $episode);
            $event->trigger();
        }
    } else {
        $html = html_writer::tag('div', get_string("nocommentuntilapproved", "pcast"), array('class' => 'pcast-episode-notice'));
    }

    echo $html;

}

/**
 * Displays the ratingsfor a specific episode
 * @global stdClass $CFG
 * @global stdClass $USER
 * @global stdClass $DB
 * @global stdClass $OUTPUT
 * @param object $episode
 * @param object $cm
 * @param object $course
 */
function pcast_display_episode_ratings($episode, $cm, $course) {

    global $CFG, $USER, $DB, $OUTPUT;

    if ($episode->approved) {
        $sql = pcast_get_episode_sql();
        $sql .= " WHERE p.id = ?";
        $episodes = $DB->get_records_sql($sql, array('id' => $episode->id));
        $context = context_module::instance($cm->id);

        // Load ratings.
        require_once($CFG->dirroot.'/rating/lib.php');
        if ($episode->assessed != RATING_AGGREGATE_NONE) {

            $ratingoptions = new stdClass();
            $ratingoptions->component = 'mod_pcast';
            $ratingoptions->context = $context;
            $ratingoptions->items = $episodes;
            $ratingoptions->aggregate = $episode->assessed; // The aggregation method.
            $ratingoptions->scaleid = $episode->scale;
            $ratingoptions->userid = $USER->id;
            $ratingoptions->assesstimestart = $episode->assesstimestart;
            $ratingoptions->assesstimefinish = $episode->assesstimefinish;
            $ratingoptions->ratingarea = 'episode';
            $ratingoptions->returnurl = new moodle_url('/mod/pcast/showepisode.php',
                                        array('eid' => $episode->id, 'mode' => PCAST_EPISODE_COMMENT_AND_RATE));

            $rm = new rating_manager();
            $allepisodes = $rm->get_ratings($ratingoptions);
        }
        foreach ($allepisodes as $thisepisode) {
            if (!empty($thisepisode->rating)) {
                echo html_writer::tag('div', $OUTPUT->render($thisepisode->rating), array('class' => 'pcast-episode-rating'));
            }
        }

        // Trigger ratings viewed event.
        $params = array(
                'context' => $context,
                'objectid' => $episode->id
                );

        $event = \mod_pcast\event\ratings_viewed::create($params);
        $event->add_record_snapshot('pcast_episodes', $episode);
        $event->trigger();

    } else {
        echo html_writer::tag('div', get_string("noratinguntilapproved", "pcast"), array('class' => 'pcast-episode-notice'));
    }

}

/**
 * Get the total number of views for a specific episode
 * @global stdClass $DB
 * @param object $episode
 * @return string
 */
function pcast_get_episode_view_count($episode) {
    global $DB;
    $count = 0;
    // Get all views.
    if (!$views = $DB->get_records("pcast_views", array( "episodeid" => $episode->id))) {
        // No views.
        return $count;
    } else {
        foreach ($views as $view) {
            // Total up the views.
            $count += (int)$view->views;
        }
    }
    return $count;
}

/**
 * Get the total number of comments for a specific episode
 * @global stdClass $DB
 * @param object $episode
 * @param object $cm
 * @return string
 */
function pcast_get_episode_comment_count($episode, $cm) {
    global $DB;
    $context = context_module::instance($cm->id);
    if ($count = $DB->count_records('comments', array('itemid' => $episode->id,
                                                      'commentarea' => 'pcast_episode',
                                                      'contextid' => $context->id))) {
        return $count;
    } else {
        return 0;
    }
}

/**
 * Get the total number of ratings for a specific episode
 * @global stdClass $DB
 * @param object $episode
 * @param object $cm
 * @return string
 */
function pcast_get_episode_rating_count($episode, $cm) {

    global $DB;
    $context = context_module::instance($cm->id);
    if ($count = $DB->count_records('rating', array('itemid' => $episode->id,
                                                    'scaleid' => $episode->scale,
                                                    'contextid' => $context->id))) {
        return $count;
    } else {
        return 0;
    }
}

/**
 * Print the podcast attachment and the media player if appropriate
 *
 * @global stdClass $CFG
 * @global stdClass $OUTPUT
 * @param object $episode
 * @param object $cm
 * @return string image string or nothing depending on $type param
 */

function pcast_display_mediafile_link($episode, $cm) {

    global $CFG, $OUTPUT;

    if (!$context = context_module::instance($cm->id)) {
        return '';
    }

    $fs = get_file_storage();

    if ($files = $fs->get_area_files($context->id, 'mod_pcast', 'episode', $episode->id, "timemodified", false)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url(file_mimetype_icon($mimetype)),
                                                'class' => 'icon', 'alt' => $mimetype));
            $path = file_encode_url($CFG->wwwroot.'/pluginfile.php',
                                    '/'.$context->id.'/mod_pcast/episode/'.$episode->id.'/'.$filename);
        }
    }

    $templink = get_string('nopcastmediafile', 'pcast');
    // Make sure there is actually an attachment before trying to render the file link and player.
    if (!empty($filename)) {

            $out = html_writer::start_tag('div', array('class' => 'pcast-media'));
            $out .= html_writer::tag('a', $iconimage, array('href' => $path)); // Icon.
            $out .= html_writer::tag('a', s($filename), array('href' => $path)); // File.
            $out .= html_writer::end_tag('div');

        $templink = format_text($out, FORMAT_HTML, array('context' => $context));
    }

    return $templink;
}



/**
 * Class representing the virtual node with all itemids in the file browser
 *
 * @category  files
 * @copyright 2012 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class pcast_file_info_container extends file_info {
    /** @var file_browser */
    protected $browser;
    /** @var stdClass */
    protected $course;
    /** @var stdClass */
    protected $cm;
    /** @var string */
    protected $component;
    /** @var stdClass */
    protected $context;
    /** @var array */
    protected $areas;
    /** @var string */
    protected $filearea;

    /**
     * Constructor (in case you did not realize it ;-)
     *
     * @param file_browser $browser
     * @param stdClass $course
     * @param stdClass $cm
     * @param stdClass $context
     * @param array $areas
     * @param string $filearea
     */
    public function __construct($browser, $course, $cm, $context, $areas, $filearea) {
        parent::__construct($browser, $context);
        $this->browser = $browser;
        $this->course = $course;
        $this->cm = $cm;
        $this->component = 'mod_pcast';
        $this->context = $context;
        $this->areas = $areas;
        $this->filearea = $filearea;
    }

    /**
     * @return array with keys contextid, filearea, itemid, filepath and filename
     */
    public function get_params() {
        return array(
            'contextid' => $this->context->id,
            'component' => $this->component,
            'filearea' => $this->filearea,
            'itemid' => null,
            'filepath' => null,
            'filename' => null,
        );
    }

    /**
     * Can new files or directories be added via the file browser
     *
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Should this node be considered as a folder in the file browser
     *
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns localised visible name of this node
     *
     * @return string
     */
    public function get_visible_name() {
        return $this->areas[$this->filearea];
    }

    /**
     * Returns list of children nodes
     *
     * @return array of file_info instances
     */
    public function get_children() {
        global $DB;

        $sql = "SELECT DISTINCT f.itemid, pe.name
                  FROM {files} f
                  JOIN {modules} m ON (m.name = 'pcast' AND m.visible = 1)
                  JOIN {course_modules} cm ON (cm.module = m.id AND cm.id = ?)
                  JOIN {pcast} p ON p.id = cm.instance
                  JOIN {pcast_episodes} pe ON (pe.pcastid = p.id AND pe.id = f.itemid)
                 WHERE f.contextid = ? AND f.component = ? AND f.filearea = ?
              ORDER BY pe.name, f.itemid";
        $params = array($this->context->instanceid, $this->context->id, $this->component, $this->filearea);

        $rs = $DB->get_recordset_sql($sql, $params);
        $children = array();
        foreach ($rs as $file) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_pcast', $this->filearea, $file->itemid)) {
                $children[] = $child;
            }
        }
        $rs->close();

        return $children;
    }

    /**
     * Returns parent file_info instance
     *
     * @return file_info or null for root
     */
    public function get_parent() {
        return $this->browser->get_file_info($this->context);
    }
}