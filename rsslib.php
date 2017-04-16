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
 * Library of rss generation functions for module pcast
 *
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function is the main entry point to pcast
 * rss feeds generation.
 *
 * @global object $CFG
 * @global object $DB
 * @param string? $context
 * @param array $args
 * @return string (path)
 */
function pcast_rss_get_feed($context, $args) {
    global $CFG, $DB;

    if (empty($CFG->pcast_enablerssfeeds)) {
        debugging("DISABLED (module configuration)");
        return null;
    }

    $status = true;
    $token  = clean_param($args[1], PARAM_ALPHANUM);
    $pcastid  = clean_param($args[3], PARAM_INT);
    $groupid  = clean_param($args[4], PARAM_INT);
    $uservalidated = false;

    // Check capabilities.
    $cm = get_coursemodule_from_instance('pcast', $pcastid, 0, false, MUST_EXIST);
    if ($cm) {
            $modcontext = context_module::instance($cm->id);

        // Context id from db should match the submitted one.
        if ($context->id == $modcontext->id && has_capability('mod/pcast:view', $modcontext)) {
            $uservalidated = true;
        }
    }

    // Get userid from Token.
    $userid = rss_get_userid_from_token($token);

    // Check group mode 0/1/2 (All participants).
    $groupmode = groups_get_activity_groupmode($cm);

    // Using Groups, check to see if user should see all participants.
    if ($groupmode == SEPARATEGROUPS) {
        // User must have the capability to see all groups or be a member of that group.
        $members = get_enrolled_users($context, 'mod/pcast:write', $groupid, 'u.id', 'u.id ASC');

        // Is a member of the current group?
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
        return null;
    }

    // OK the user can view the RSS feed.

    $pcast = $DB->get_record('pcast', array('id' => $pcastid), '*', MUST_EXIST);

    // Check to se if RSS is enabled.
    // NOTE: cannot use the rss_enabled_for_mod() function due to the functions internals and naming conflicts.
    if (($pcast->rssepisodes == 0)||(empty($pcast->rssepisodes))) {
        return null;
    }

    // Get the appropriate SQL.
    $sql = pcast_rss_get_sql($pcast);

    // Get the cache file info.
    $filename = rss_get_file_name($pcast, $sql);

    // Append the GroupID to the end of the filename.
    $filename .= '_'.$groupid;
    $cachedfilepath = rss_get_file_full_name('mod_pcast', $filename);

    // Is the cache out of date?
    $cachedfilelastmodified = 0;
    if (file_exists($cachedfilepath)) {
        $cachedfilelastmodified = filemtime($cachedfilepath);
    }
    // If the cache is more than 60 seconds old and there's new stuff.
    $dontrecheckcutoff = time() - 60;
    if ( $dontrecheckcutoff > $cachedfilelastmodified && pcast_rss_newstuff($pcast, $cachedfilelastmodified)) {
        if (!$recs = $DB->get_records_sql($sql, array(), 0, $pcast->rssepisodes)) {
            return null;
        }
        $items = array();

        $formatoptions = new stdClass();
        $formatoptions->trusttext = true;

        foreach ($recs as $rec) {
            $item = new stdClass();
            $item->title = $rec->episodename;
            $item->pcastid = $rec->pcastid;
            $item->id = $rec->episodeid;
            $item->userid = $rec->userid;
            $item->course = $rec->course;

            if ($pcast->displayauthor == 1) {
                // Include author name and email.
                $item->author = fullname($rec);
                $item->email = $rec->email;
            }

            $item->keywords = $rec->keywords;
            $item->subtitle = $rec->subtitle;
            $item->duration = $rec->duration;
            $item->pubdate = $rec->episodetimecreated;
            $item->link = new moodle_url('/mod/pcast/showepisode.php', array('eid' => $rec->episodeid));

            $item->description = file_rewrite_pluginfile_urls($rec->episodesummary, 'pluginfile.php',
                                                $context->id, 'mod_pcast',
                                                'summary', $rec->episodeid);

            $item->description = format_text($item->description, 'HTML', null, $pcast->course);

            if ($pcast->userscancategorize) {
                // TODO: This is very inefficient (this generates 2 DB queries per entry).
                $category = pcast_rss_category_lookup($rec);
                if (!empty($item->topcategory)) {
                    $item->topcategory = $category->top->name;
                    if (!empty($item->nestedcategory)) {
                        $item->nestedcategory = $category->nested->name;
                    }
                }
            }
            $items[] = $item;

        }
        // First all rss feeds common headers.
        $url = new moodle_url('/mod/pcast/view.php', array('id' => $cm->id));
        $header = pcast_rss_header(format_string($pcast->name, true), $url, format_string($pcast->intro, true), $pcast);

        // Do we need iTunes tags?
        if (isset($pcast->enablerssitunes) && ($pcast->enablerssitunes == 1)) {
            $itunes = true;
        } else {
            $itunes = false;
        }

        // Now all the rss items.
        if (!empty($header)) {
            $episodes = pcast_rss_add_items($context, $items, $itunes, $groupid);
        }
        // Now all rss feeds common footers.
        if (!empty($header) && !empty($episodes)) {
            $footer = pcast_rss_footer();
        }
        // Now, if everything is ok, concatenate it.
        if (!empty($header) && !empty($episodes) && !empty($footer)) {
            $rss = $header.$episodes.$footer;

            // Save the XML contents to file.
            $status = rss_save_file('mod_pcast', $filename, $rss);
        }
    }
    if (!$status) {
        $cachedfilepath = null;
    }

    return $cachedfilepath;
}

/**
 * Generate the SQL needed to get the episodes for the RSS feed
 * (approved episodes only)
 * @param object $pcast
 * @param bool $time
 * @return string
 */
function pcast_rss_get_sql($pcast, $time=0) {
    // Do we only want new items?
    if ($time) {
        $time = "AND e.timecreated > $time";
    } else {
        $time = "";
    }

    if ($pcast->rsssortorder == 0) {
        // Newest items first.
        $sort = "ORDER BY e.timecreated desc";

    } else {
        // Oldest items first.
        $sort = "ORDER BY e.timecreated asc";
    }

    if ($pcast->displayauthor == 1) {
        // Include author name.
        $allnamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT e.id AS episodeid,
                  e.pcastid AS pcastid,
                  e.name AS episodename,
                  e.summary AS episodesummary,
                  e.mediafile AS mediafile,
                  e.duration AS duration,
                  e.subtitle AS subtitle,
                  e.keywords AS keywords,
                  e.topcategory AS topcategory,
                  e.nestedcategory AS nestedcategory,
                  e.timecreated AS episodetimecreated,
                  u.id AS userid,
                  $allnamefields,
                  u.email AS email,
                  p.course AS course
             FROM {pcast_episodes} e,
                  {user} u,
                  {pcast} p
            WHERE e.pcastid = {$pcast->id} AND
                  u.id = e.userid AND
                  p.id = pcastid AND
                  e.approved = 1 $time $sort";

    } else {
        // Without author name.
        $sql = "SELECT e.id AS episodeid,
                  e.pcastid AS pcastid,
                  e.name AS episodename,
                  e.summary AS episodesummary,
                  e.mediafile AS mediafile,
                  e.duration AS duration,
                  e.subtitle AS subtitle,
                  e.keywords AS keywords,
                  e.topcategory AS topcategory,
                  e.nestedcategory AS nestedcategory,
                  e.timecreated AS episodetimecreated,
                  u.id AS userid,
                  p.course AS course
             FROM {pcast_episodes} e,
                  {user} u,
                  {pcast} p
            WHERE e.pcastid = {$pcast->id} AND
                  u.id = e.userid AND
                  p.id = pcastid AND
                  e.approved = 1 $time $sort";
    }

    return $sql;
}

/**
 * If there is new stuff in since $time this returns true
 * Otherwise it returns false.
 *
 * @param object $pcast the pcast activity object
 * @param int $time timestamp
 * @return bool
 */
function pcast_rss_newstuff($pcast, $time) {
    global $DB;

    $sql = pcast_rss_get_sql($pcast, $time);

    $recs = $DB->get_records_sql($sql, null, 0, 1); // If we get even 1 back we have new stuff.
    return ($recs && !empty($recs));
}

/**
 * Given a pcast object, deletes all cached RSS files associated with it.
 *
 * @param stdClass $pcast
 */
function pcast_rss_delete_file($pcast) {
    global $CFG;
    require_once("$CFG->libdir/rsslib.php");

    rss_delete_file('mod_pcast', $pcast);
}

/**
 * Looks up the author information from the id
 * @global object $DB
 * @param int $userid
 * @return object
 */
function pcast_rss_author_lookup($userid) {
    global $DB;
    $author = $DB->get_record('user', array("id" => $userid), '*', true);
    return $author;
}

/**
 * Lookup the names of the category for this podcast or episode
 * @global object $DB
 * @param object $pcast
 * @return \stdClass
 */
function pcast_rss_category_lookup($pcast) {
    global $DB;
    $category = new stdClass();
    // TODO: We should use MUC here to make prevent multiple queries.
    $category->top = $DB->get_record('pcast_itunes_categories', array("id" => $pcast->topcategory), '*', true);
    $category->nested = $DB->get_record('pcast_itunes_nested_cat', array("id" => $pcast->nestedcategory), '*', true);
    return $category;
}

/**
 * This function return all the headers for every pcast rss feed.
 * @global object $CFG
 * @global stdclass $USER
 * @global stdclass $OUTPUT
 * @param string $title
 * @param string $link
 * @param string $description
 * @param object $pcast
 * @return boolean|string
 */
function pcast_rss_header($title = null, $link = null, $description = null, $pcast = null) {

    global $CFG, $USER, $OUTPUT;

    $status = true;
    $result = "";
    if (isset($pcast->enablerssitunes) && ($pcast->enablerssitunes == 1)) {
        $itunes = true;
    } else {
        $itunes = false;
    }
    if (isset($pcast->userid) && !empty($pcast->userid)) {
        $author = pcast_rss_author_lookup($pcast->userid);
    }

    if (isset($pcast->topcategory)) {
        $categories = pcast_rss_category_lookup($pcast);
    }

    if (!$site = get_site()) {
        $status = false;
    }

    if ($status) {

        // Calculate title, link and description.
        if (empty($title)) {
            $title = format_string($site->fullname);
        }
        if (empty($link)) {
            $link = $CFG->wwwroot;
        }
        if (empty($description)) {
            $description = $site->summary;
        }

        // XML headers.
        $result .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if ($itunes) {
            $result .= "<rss xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\" version=\"2.0\">\n";
        } else {
            $result .= "<rss version=\"2.0\">\n";
        }

        // Open the channel.
        $result .= rss_start_tag('channel', 1, true);

        // Write the channel info.
        $result .= rss_full_tag('title', 2, false, strip_tags($title));
        $result .= rss_full_tag('link', 2, false, $link);
        $result .= rss_full_tag('description', 2, false, $description);
        $result .= rss_full_tag('generator', 2, false, 'Moodle');
        if (!empty($USER->lang)) {
            $result .= rss_full_tag('language', 2, false, substr($USER->lang, 0, 2));
        }
        $today = getdate();
        $result .= rss_full_tag('copyright', 2, false, '&#169; '. $today['year'] .' '. format_string($site->fullname));
        $result .= rss_full_tag('lastBuildDate', 2, false, gmdate('D, d M Y H:i:s', $today[0]).' GMT');
        $result .= rss_full_tag('pubDate', 2, false, gmdate('D, d M Y H:i:s', $today[0]).' GMT');

        // Custom image handling.
        $cm = get_coursemodule_from_instance('pcast', $pcast->id, 0, false, MUST_EXIST);
        if (!$context = context_module::instance($cm->id)) {
            return '';
        }

        $fs = get_file_storage();
        $image = new stdClass();

        if ($files = $fs->get_area_files($context->id, 'mod_pcast', 'logo', 0, "timemodified", false)) {
            foreach ($files as $file) {
                $image->filename = $file->get_filename();
                $image->type = $file->get_mimetype();
                $image->size = $file->get_filesize();
                $image->url = file_encode_url($CFG->wwwroot.'/pluginfile.php',
                              '/'.$context->id.'/mod_pcast/logo/0/'.$image->filename);
            }
        }
        // Write the image info.
        if (isset($image->url)) {
            $rsspix = $image->url;
        } else {
            $rsspix = $OUTPUT->image_url('i/rsssitelogo');
        }

        // Write the image.
        $result .= rss_start_tag('image', 2, true);
        $result .= rss_full_tag('url', 3, false, $rsspix);
        $result .= rss_full_tag('title', 3, false, strip_tags($title));
        $result .= rss_full_tag('link', 3, false, $link);
        if (($pcast->imagewidth > 0) && ($pcast->imageheight > 0)) {
            $result .= rss_full_tag('width', 3, false, $pcast->imagewidth);
            $result .= rss_full_tag('height', 3, false, $pcast->imageheight);
        }
        $result .= rss_end_tag('image', 2, true);

        // Itunes tags.
        if ($itunes) {
            if (isset($author)) {
                $result .= rss_full_tag('itunes:author', 2, false, fullname($author));
            }

            $result .= rss_full_tag('itunes:subtitle', 2, false, s($pcast->subtitle));
            // Implement summary from pcast intro.
            $result .= rss_full_tag('itunes:summary', 2, false, s($pcast->intro));
            $result .= rss_full_tag('itunes:keywords', 2, false, s($pcast->keywords));

            if (isset($author)) {
                $result .= rss_start_tag('itunes:owner', 2, true);
                $result .= rss_full_tag('itunes:name', 3, false, fullname($author));
                $result .= rss_full_tag('itunes:email', 3, false, $author->email);
                $result .= rss_end_tag('itunes:owner', 2, true);
            }

            // Explicit Content?
            if ($pcast->explicit == 0) {
                $result .= rss_full_tag('itunes:explicit', 2, false, 'no');
            } else if ($pcast->explicit == 1) {
                $result .= rss_full_tag('itunes:explicit', 2, false, 'yes');
            } else {
                $result .= rss_full_tag('itunes:explicit', 2, false, 'clean');
            }

            // Categories.
            if (isset($categories->top->name)) {
                $result .= rss_start_tag('itunes:category text="'.$categories->top->name .'"', 2, true);
                if (isset($categories->nested->name)) {
                    $result .= rss_start_tag('itunes:category text="'.$categories->nested->name .'"/', 4, true);
                }
                $result .= rss_end_tag('itunes:category', 2, true);
            }
            // Image.
            $result .= rss_start_tag('itunes:image href="'.$rsspix.'"/', 2, true);
        }

    }

    if (!$status) {
        return false;
    } else {
        return $result;
    }
}

/**
 * This function returns the rss XML code for every item passed in the array
 * item->title: The title of the item
 * item->author: The author of the item. Optional !!
 * item->pubdate: The pubdate of the item
 * item->link: The link url of the item
 * item->description: The content of the item
 * @global object $CFG
 * @param stdclass $context
 * @param object $items
 * @param boolean $itunes
 * @param int $currentgroup
 * @return boolean
 */
function pcast_rss_add_items($context, $items, $itunes=false, $currentgroup =0) {

    global $CFG;

    $result = '';

    // Get Group members.
    $members = get_enrolled_users($context, 'mod/pcast:write', $currentgroup, 'u.id', 'u.id ASC');
    if (!empty($items)) {
        foreach ($items as $item) {
            // Only display group members entries in regular courses, Display everything when used on the front page.
            if (((isset($members[$item->userid]->id) and ($members[$item->userid]->id == $item->userid))
                                                     or ($item->course === SITEID))) {
                $result .= rss_start_tag('item', 2, true);
                // Include the category if exists (some rss readers will use it to group items).
                if (isset($item->topcategory)) {
                    $result .= rss_full_tag('category', 3, false, $item->topcategory);
                }
                if (isset($item->nestedcategory) && $itunes) {
                    $result .= rss_full_tag('category', 3, false, $item->nestedcategory);
                }

                $result .= rss_full_tag('title', 3, false, strip_tags($item->title));
                $result .= rss_full_tag('link', 3, false, $item->link);
                $result .= rss_full_tag('pubDate', 3, false, gmdate('D, d M Y H:i:s', $item->pubdate).' GMT');  // MDL-12563.

                // Rewrite the URLs for the description fields.
                if ($CFG->pcast_allowhtmlinsummary) {
                    // Re-write the url paths to be valid.
                    $description = file_rewrite_pluginfile_urls($item->description,
                                   'pluginfile.php', $context->id, 'mod_pcast', 'summary', $item->id);
                } else {
                    // Strip out all HTML.
                    $description = strip_tags($item->description);
                }

                $result .= rss_full_tag('description', 3, false, $description);
                $result .= rss_full_tag('guid', 3, false, $item->link, array('isPermaLink' => 'true'));

                // Include the author's name / email if exists.
                if (isset($item->email) && (isset($item->author))) {
                    $result .= rss_full_tag('author', 3, false, $item->email .'('.$item->author .')');
                }
                $result .= rss_start_tag(pcast_rss_add_enclosure($item), 3, true);

                // Add iTunes tags.
                if ($itunes) {
                    if (isset($item->author)) {
                        $result .= rss_full_tag('itunes:author', 3, false, $item->author);
                    }
                    if (isset($item->subtitle)) {
                        $result .= rss_full_tag('itunes:subtitle', 3, false, $item->subtitle);
                    }
                    if (isset($item->duration)) {
                        $result .= rss_full_tag('itunes:duration', 3, false, $item->duration);
                    }
                    if (isset($item->keywords)) {
                        $result .= rss_full_tag('itunes:keywords', 3, false, $item->keywords);
                    }
                }

                $result .= rss_end_tag('item', 2, true);

            }
        }
    } else {
        $result = false;
    }
    return $result;
}

/**
 * Generates the enclosure tags for the media files.
 * @global object $CFG
 * @global object $DB
 * @param object $item
 * @return string
 */
function pcast_rss_add_enclosure($item) {

    global $CFG, $DB;
    $enclosure = new stdClass();

    // Set some defaults to prevent notices whe the attachment is missing.
    $enclosure->type = '';
    $enclosure->size = '';
    $enclosure->url = '';

    $pcast  = $DB->get_record('pcast', array('id' => $item->pcastid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('pcast', $pcast->id, 0, false, MUST_EXIST);
    if (!$context = context_module::instance($cm->id)) {
        return '';
    }

    $fs = get_file_storage();

    if ($files = $fs->get_area_files($context->id, 'mod_pcast', 'episode', $item->id, "timemodified", false)) {
        foreach ($files as $file) {
            $enclosure->filename = $file->get_filename();
            $enclosure->type = $file->get_mimetype();
            $enclosure->size = $file->get_filesize();
            $enclosure->url = file_encode_url($CFG->wwwroot.'/pluginfile.php',
                              '/'.$context->id.'/mod_pcast/episode/'.$item->id.'/'.$enclosure->filename);
        }
    }

    return 'enclosure url="'.$enclosure->url.'" length="'.$enclosure->size.'" type ="'.$enclosure->type.'" /';
}

/**
 * This function return all the common footers for every rss feeds.
 * @param string $title
 * @param string $link
 * @param string $description
 * @return string
 */
function pcast_rss_footer($title = null, $link = null, $description = null) {

    // Close the chanel.
    $result = rss_end_tag('channel', 1, true);
    // Close the rss tag.
    $result .= '</rss>';

    return $result;
}




/**
 * This function returns the URL for the RSS XML file.
 *
 * @global object
 * @param int contextid the course id
 * @param int userid the current user id
 * @param string modulename the name of the current module. For example "forum"
 * @param string $additionalargs For modules, module instance id
 * @todo THIS WILL NOT WORK WITH SLASHARGS DISABLED
 */
function pcast_rss_get_url($contextid, $userid, $componentname, $additionalargs) {
    global $CFG;
    require_once($CFG->libdir.'/rsslib.php');
    $usertoken = rss_get_token($userid);
    $args = '/'.$contextid.'/'.$usertoken.'/'.$componentname.'/'.$additionalargs.'/rss.pcast';
    $url = new moodle_url('/mod/pcast/subscribe.php'.$args);
    return $url;

}


/**
 * Generates the file path to the .pcast file
 * @global object $CFG
 * @param string $componentname
 * @param string $filename
 * @return string URL
 */
function pcast_rss_get_file_full_name($componentname, $filename) {
    global $CFG;
    return "$CFG->dataroot/cache/rss/$componentname/$filename.pcast";
}

/**
 * Builds the .pcast file with the users RSS token
 * @param object $pcast
 * @param string $url
 * @return string
 */
function pcast_build_pcast_file($pcast, $url) {

    // XML headers.
    $result = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $result .= "<!DOCTYPE pcast PUBLIC \"-//Apple Computer//DTD PCAST 1.0//EN\" \"http://www.itunes.com/DTDs/pcast-1.0.dtd\">\n";
    $result .= rss_start_tag('pcast version="1.0"', 1, true);
    $result .= rss_start_tag('channel', 1, true);
    $result .= rss_start_tag('link rel="feed" type="application/rss+xml" href="'.$url.'" /', 2, true);
    $result .= rss_full_tag('title', 2, false, $pcast->name);

    $category = pcast_rss_category_lookup($pcast);
    if (isset($category->top->name) && !empty($category->top->name)) {
        $result .= rss_full_tag('category', 2, false, $category->top->name);
    }
    if (isset($category->nested->name) && !empty($category->nested->name)) {
        $result .= rss_full_tag('category', 2, false, $category->nested->name);
    }
    if (isset($pcast->subtitle) && !empty($category->subtitle)) {
        $result .= rss_full_tag('subtitle', 2, false, $pcast->subtitle);
    }
    $result .= rss_end_tag('channel', 1, true);
    $result .= rss_end_tag('pcast', 1, true);

    return $result;
}

/**
 * This function saves to file the rss feed specified in the parameters
 *
 * @global object
 * @param string $componentname the module name ie forum. Used to create a cache directory.
 * @param string $filename the name of the file to be created ie "1234"
 * @param string $contents the data to be written to the file
 */
function pcast_rss_save_file($componentname, $filename, $contents, $expandfilename=true) {

    $status = true;

    if (! $basedir = make_cache_directory ('rss/'. $componentname)) {
        // File Cannot be created, so error out.
        $status = false;
    }

    if ($status) {
        $fullfilename = $filename;
        if ($expandfilename) {
            $fullfilename = pcast_rss_get_file_full_name($componentname, $filename);
        }

        $rssfile = fopen($fullfilename, "w");
        if ($rssfile) {
            $status = fwrite ($rssfile, $contents);
            fclose($rssfile);
        } else {
            $status = false;
        }
    }
    return $status;
}