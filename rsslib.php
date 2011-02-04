<?php
    //This file adds support to rss feeds generation

    //This function is the main entry point to pcast
    //rss feeds generation.
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
        // NOTE: cannot use the rss_enabled_for_mod() function due to naming conflicts
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
            $header = rss_standard_header(format_string($pcast->name,true),
                                          $CFG->wwwroot."/mod/pcast/view.php?id=".$pcast->id,
                                          format_string($pcast->intro,true));
            //Now all the rss items
            if (!empty($header)) {
                $episodes = rss_add_items($items);
            }
            //Now all rss feeds common footers
            if (!empty($header) && !empty($episodes)) {
                $footer = rss_standard_footer();
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
            $sql = "SELECT e.id AS entryid,
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


