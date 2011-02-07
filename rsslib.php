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
 * @copyright 2010 Stephen Bourget and Jillaine Beeckman
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


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
        $pcastid  = clean_param($args[3], PARAM_INT);
        $uservalidated = false;

        //check capabilities
        $cm = get_coursemodule_from_instance('pcast', $pcastid, 0, false, MUST_EXIST);
        if ($cm) {
                $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

            //context id from db should match the submitted one
            if ($context->id==$modcontext->id && has_capability('mod/pcast:view', $modcontext)) {
                $uservalidated = true;
            }
        }

        if (!$uservalidated) {
            return null;
        }

        // OK the user can view the RSS feed

        $pcast = $DB->get_record('pcast', array('id' => $pcastid), '*', MUST_EXIST);

        // Check to se if RSS is enabled
        // NOTE: cannot use the rss_enabled_for_mod() function due to the functions internals and naming conflicts
        if (($pcast->rssepisodes == 0)||(empty($pcast->rssepisodes))) {
            return null;
        }

        $sql = pcast_rss_get_sql($pcast);

        //get the cache file info
        $filename = rss_get_file_name($pcast, $sql);
        $cachedfilepath = rss_get_file_full_name('mod_pcast', $filename);

        //Is the cache out of date?
        $cachedfilelastmodified = 0;
        if (file_exists($cachedfilepath)) {
            $cachedfilelastmodified = filemtime($cachedfilepath);
        }
        //if the cache is more than 60 seconds old and there's new stuff
        $dontrecheckcutoff = time()-60;
        if ( $dontrecheckcutoff > $cachedfilelastmodified && pcast_rss_newstuff($pcast, $cachedfilelastmodified)) {
            if (!$recs = $DB->get_records_sql($sql, array(), 0, $pcast->rssarticles)) {
                return null;
            }

            $items = array();

            $formatoptions = new stdClass();
            $formatoptions->trusttext = true;

            foreach ($recs as $rec) {
                $item = new stdClass();
                $user = new stdClass();
                $item->title = $rec->episodename;

                if ($pcast->rsstype == 1) {//With author
                    $user->firstname = $rec->userfirstname;
                    $user->lastname = $rec->userlastname;

                    $item->author = fullname($user);
                }

                $item->pubdate = $rec->episodetimecreated;
                $item->link = $CFG->wwwroot."/mod/pcast/showepisode.php?eid=".$rec->episodeid;
                $item->description = format_text($rec->episodesummary,'HTML',NULL,$pcast->course);
                $items[] = $item;
            }

            //First all rss feeds common headers
            $header = pcast_rss_header(format_string($pcast->name,true),
                                          $CFG->wwwroot."/mod/pcast/view.php?id=".$pcast->id,
                                          format_string($pcast->intro,true), $pcast);
            //Now all the rss items
            if (!empty($header)) {
                $episodes = pcast_rss_add_items($items);
            }
            //Now all rss feeds common footers
            if (!empty($header) && !empty($episodes)) {
                $footer = pcast_rss_footer();
            }
            //Now, if everything is ok, concatenate it
            if (!empty($header) && !empty($episodes) && !empty($footer)) {
                $rss = $header.$episodes.$footer;

                //Save the XML contents to file.
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
        //do we only want new items?
        if ($time) {
            $time = "AND e.timecreated > $time";
        } else {
            $time = "";
        }

        if ($pcast->rsssortorder == 0) { //Newest first
            $sort = "ORDER BY e.timecreated desc";

        } else { // Oldest first
            $sort = "ORDER BY e.timecreated asc";
        }
        if ($pcast->displayauthor == 1) {//With author
            $sql = "SELECT e.id AS episodeid,
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
                      u.firstname AS userfirstname,
                      u.lastname AS userlastname
                 FROM {pcast_episodes} e,
                      {user} u
                WHERE e.pcastid = {$pcast->id} AND
                      u.id = e.userid AND
                      e.approved = 1 $time $sort";

        } else {//Without author
            $sql = "SELECT e.id AS episodeid,
                      e.name AS episodename,
                      e.summary AS episodesummary,
                      e.mediafile AS mediafile,
                      e.duration AS duration,
                      e.subtitle AS subtitle,
                      e.keywords AS keywords,
                      e.topcategory AS topcategory,
                      e.nestedcategory AS nestedcategory,
                      e.timecreated AS episodetimecreated,
                      u.id AS userid
                 FROM {pcast_episodes} e,
                      {user} u
                WHERE e.pcastid = {$pcast->id} AND
                      u.id = e.userid AND
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

        $recs = $DB->get_records_sql($sql, null, 0, 1);//limit of 1. If we get even 1 back we have new stuff
        return ($recs && !empty($recs));
    }

    /**
     * Looks up the author information from the id
     * @global object $DB
     * @param int $userid
     * @return object
     */
    function pcast_rss_author_lookup($userid) {
        global $DB;
        $author = $DB->get_record('user', array("id"=>$userid), '*', true);
        return $author;
    }

//This function return all the headers for every pcast rss feed
function pcast_rss_header($title = NULL, $link = NULL, $description = NULL, $pcast = NULL) {

    global $CFG, $USER;

    static $pixpath = '';

    $status = true;
    $result = "";
    if(isset($pcast->enablerssitunes) && ($pcast->enablerssitunes == 1)) {
        $itunes = true;
    } else
        $itunes = false;
    if(isset($pcast->userid) && !empty($pcast->userid)) {
        $author = pcast_rss_author_lookup($pcast->userid);
    }

    if (!$site = get_site()) {
        $status = false;
    }

    if ($status) {

        //Calculate title, link and description
        if (empty($title)) {
            $title = format_string($site->fullname);
        }
        if (empty($link)) {
            $link = $CFG->wwwroot;
        }
        if (empty($description)) {
            $description = $site->summary;
        }

        //xml headers
        $result .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if($itunes) {
            $result .= "<rss xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\" version=\"2.0\">\n";
        } else {
            $result .= "<rss version=\"2.0\">\n";
        }

        //open the channel
        $result .= rss_start_tag('channel', 1, true);

        //write channel info
        $result .= rss_full_tag('title', 2, false, strip_tags($title));
        $result .= rss_full_tag('link', 2, false, $link);
        $result .= rss_full_tag('description', 2, false, $description);
        $result .= rss_full_tag('generator', 2, false, 'Moodle');
        if (!empty($USER->lang)) {
            $result .= rss_full_tag('language', 2, false, substr($USER->lang,0,2));
        }
        $today = getdate();
        $result .= rss_full_tag('copyright', 2, false, '&#169; '. $today['year'] .' '. format_string($site->fullname));
        $result .= rss_full_tag('lastBuildDate', 2, false, gmdate('D, d M Y H:i:s',$today[0]).' GMT');
        $result .= rss_full_tag('pubDate', 2, false, gmdate('D, d M Y H:i:s',$today[0]).' GMT');

        /*
       if (!empty($USER->email)) {
            $result .= rss_full_tag('managingEditor', 2, false, fullname($USER));
            $result .= rss_full_tag('webMaster', 2, false, fullname($USER));
        }
       */

        // itunes tags

        if($itunes) {
            if(isset($author)) {
                $result .= rss_full_tag('itunes:author', 2, false, fullname($author));
            }
            
            $result .= rss_full_tag('itunes:subtitle', 2, false, s($pcast->subtitle));
            $result .= rss_full_tag('itunes:summary', 2, false, s($pcast->keywords));

            if(isset($author)) {
                $result .= rss_start_tag('itunes:owner', 2, true);
                $result .= rss_full_tag('itunes:name',3, false,fullname($author));
                $result .= rss_full_tag('itunes:email',3,false,$author->email);
                $result .= rss_end_tag('itunes:owner', 2, true);
            }

            // Explicit
            if($pcast->explicit == 0) {
                $result .= rss_full_tag('itunes:explicit',2,false,'FALSE');
            } else if ($pcast->explicit == 1) {
                $result .= rss_full_tag('itunes:explicit',2,false,'TRUE');
            } else {
                $result .= rss_full_tag('itunes:explicit',2,false,'CLEAN');
            }
            
            //TODO: image, category
        }


        //TODO: Implement custom image handling
        //write image info
        $rsspix = $CFG->pixpath."/i/rsssitelogo.gif";

        //write the info
        $result .= rss_start_tag('image', 2, true);
        $result .= rss_full_tag('url', 3, false, $rsspix);
        $result .= rss_full_tag('title', 3, false, 'moodle');
        $result .= rss_full_tag('link', 3, false, $CFG->wwwroot);
        $result .= rss_full_tag('width', 3, false, '140');
        $result .= rss_full_tag('height', 3, false, '35');
        $result .= rss_end_tag('image', 2, true);
    }

    if (!$status) {
        return false;
    } else {
        return $result;
    }
}

//This function returns the rss XML code for every item passed in the array
//item->title: The title of the item
//item->author: The author of the item. Optional !!
//item->pubdate: The pubdate of the item
//item->link: The link url of the item
//item->description: The content of the item
function pcast_rss_add_items($items) {

    global $CFG;

    $result = '';

    if (!empty($items)) {
        foreach ($items as $item) {
            $result .= rss_start_tag('item',2,true);
            //Include the category if exists (some rss readers will use it to group items)
            if (isset($item->category)) {
                $result .= rss_full_tag('category',3,false,$item->category);
            }
            if (isset($item->tags)) {
                $attributes = array();
                if (isset($item->tagscheme)) {
                    $attributes['domain'] = s($item->tagscheme);
                }
                foreach ($item->tags as $tag) {
                    $result .= rss_full_tag('category', 3, false, $tag, $attributes);
                }
            }
            $result .= rss_full_tag('title',3,false,strip_tags($item->title));
            $result .= rss_full_tag('link',3,false,$item->link);
            $result .= rss_add_enclosures($item);
            $result .= rss_full_tag('pubDate',3,false,gmdate('D, d M Y H:i:s',$item->pubdate).' GMT');  # MDL-12563
            //Include the author if exists
            if (isset($item->author)) {
                //$result .= rss_full_tag('author',3,false,$item->author);
                //We put it in the description instead because it's more important
                //for moodle than most other feeds, and most rss software seems to ignore
                //the author field ...
                $item->description = get_string('byname','',$item->author).'. &nbsp;<p>'.$item->description.'</p>';
            }
            $result .= rss_full_tag('description',3,false,$item->description);
            $result .= rss_full_tag('guid',3,false,$item->link,array('isPermaLink' => 'true'));
            $result .= rss_end_tag('item',2,true);

        }
    } else {
        $result = false;
    }
    return $result;
}

//This function return all the common footers for every rss feed in the site
function pcast_rss_footer($title = NULL, $link = NULL, $description = NULL) {

    global $CFG, $USER;

    $status = true;
    $result = '';

    //Close the chanel
    $result .= rss_end_tag('channel', 1, true);
    ////Close the rss tag
    $result .= '</rss>';

    return $result;
}
