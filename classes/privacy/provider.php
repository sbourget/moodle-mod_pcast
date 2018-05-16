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
 * Privacy Subsystem implementation for mod_pcast.
 *
 * @package   mod_pcast
 * @copyright 2018 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pcast\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/comment/lib.php');

/**
 * Implementation of the privacy subsystem plugin provider for the pcast activity module.
 *
 * @copyright 2018 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,
    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'pcast_episodes',
            [
                'pcastid'    => 'privacy:metadata:pcast_episodes:pcastid',
                'userid'        => 'privacy:metadata:pcast_episodes:userid',
                'name'       => 'privacy:metadata:pcast_episodes:name',
                'summary'    => 'privacy:metadata:pcast_episodes:summary',
                'mediafile'    => 'privacy:metadata:pcast_episodes:mediafile',
                'subtitle'    => 'privacy:metadata:pcast_episodes:subtitle',
                'keywords'    => 'privacy:metadata:pcast_episodes:keywords',
                'timemodified'  => 'privacy:metadata:pcast_episodes:timemodified',
            ],
            'privacy:metadata:pcast_episodes'
        );

        $items->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $items->add_subsystem_link('core_comment', [], 'privacy:metadata:core_comments');
        $items->add_subsystem_link('core_tag', [], 'privacy:metadata:core_tag');
        $items->add_subsystem_link('core_rating', [], 'privacy:metadata:core_rating');
        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $ratingquery = \core_rating\privacy\provider::get_sql_join('r', 'mod_pcast', 'episode', 'pe.id', $userid);

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {pcast} p ON p.id = cm.instance
            INNER JOIN {pcast_episodes} pe ON pe.pcastid = p.id
             LEFT JOIN {comments} com ON com.commentarea =:commentarea AND com.itemid = pe.id
            {$ratingquery->join}
                 WHERE pe.userid = :pcastepisodeuserid OR com.userid = :commentuserid OR {$ratingquery->userwhere}";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'pcast',
            'commentarea' => 'pcast_episode',
            'pcastepisodeuserid' => $userid,
            'commentuserid' => $userid,
        ] + $ratingquery->params;

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT pe.id as episodeid,
                       cm.id AS cmid,
                       pe.userid,
                       pe.name,
                       pe.summary,
                       pe.summaryformat,
                       pe.mediafile,
                       pe.duration,
                       pe.explicit,
                       pe.subtitle,
                       pe.keywords,
                       pe.timecreated,
                       pe.timemodified
                  FROM {pcast_episodes} pe
                  JOIN {pcast} p ON pe.pcastid = p.id
                  JOIN {course_modules} cm ON p.id = cm.instance
                  JOIN {context} c ON cm.id = c.instanceid
                 WHERE c.id {$contextsql}
                   AND pe.userid = :userid
             OR EXISTS (SELECT 1 FROM {comments} com WHERE com.commentarea = :commentarea AND com.itemid = pe.id
                        AND com.userid = :commentuserid)
             OR EXISTS (SELECT 1 FROM {rating} r WHERE r.contextid = c.id AND r.itemid  = pe.id
                        AND r.component = :ratingcomponent
                   AND r.ratingarea = :ratingarea
                   AND r.userid = :ratinguserid)
               ORDER BY pe.id, cm.id";
        $params = [
            'userid' => $user->id,
            'commentarea' => 'pcast_episode',
            'commentuserid' => $user->id,
            'ratingcomponent' => 'mod_pcast',
            'ratingarea' => 'episode',
            'ratinguserid' => $user->id
        ] + $contextparams;
        $pcastepisodes = $DB->get_recordset_sql($sql, $params);

        // Reference to the pcast activity seen in the last iteration of the loop. By comparing this with the
        // current record, and because we know the results are ordered, we know when we've moved to the episodes
        // for a new pcast activity and therefore when we can export the complete data for the last activity.
        $lastcmid = null;

        $pcastdata = [];
        foreach ($pcastepisodes as $record) {
            $name = format_string($record->name);
            $path = array_merge([get_string('podcastepisodes', 'mod_pcast'), $name . " ({$record->episodeid})"]);

            // If we've moved to a new pcast, then write the last pcast data and reinit the pcast data array.
            if (!is_null($lastcmid)) {
                if ($lastcmid != $record->cmid) {
                    if (!empty($pcastdata)) {
                        $context = \context_module::instance($lastcmid);
                        self::export_pcast_data_for_user($pcastdata, $context, [], $user);
                        $pcastdata = [];
                    }
                }
            }
            $lastcmid = $record->cmid;
            $context = \context_module::instance($lastcmid);

            // Export files added on the pcast episode definition field.
            $summary = format_text(writer::with_context($context)->rewrite_pluginfile_urls($path, 'mod_pcast',
                'episode',  $record->episodeid, $record->summary), $record->summaryformat);

            // Export just the files attached to this user episode.
            if ($record->userid == $user->id) {
                // Get all files attached to the pcast attachment.
                writer::with_context($context)->export_area_files($path, 'mod_pcast', 'episode', $record->episodeid);

                // Get all files attached to the pcast attachment.
                writer::with_context($context)->export_area_files($path, 'mod_pcast', 'mediafile', $record->episodeid);
            }

            // Export associated comments.
            \core_comment\privacy\provider::export_comments($context, 'mod_pcast', 'pcast_episode',
                    $record->episodeid, $path, $record->userid != $user->id);

            // Export associated tags.
            \core_tag\privacy\provider::export_item_tags($user->id, $context, $path, 'mod_pcast', 'pcast_episodes',
                    $record->episodeid, $record->userid != $user->id);

            // Export associated ratings.
            \core_rating\privacy\provider::export_area_ratings($user->id, $context, $path, 'mod_pcast', 'episode',
                    $record->episodeid, $record->userid != $user->id);

            $pcastdata['episodes'][] = [
                'name'       => $record->name,
                'summary'    => $summary,
                'timecreated'   => \core_privacy\local\request\transform::datetime($record->timecreated),
                'timemodified'  => \core_privacy\local\request\transform::datetime($record->timemodified)
            ];
        }
        $pcastepisodes->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($pcastdata)) {
            $context = \context_module::instance($lastcmid);
            self::export_pcast_data_for_user($pcastdata, $context, [], $user);
        }
    }

    /**
     * Export the supplied personal data for a single pcast activity, along with any generic data or area files.
     *
     * @param array $pcastdata The personal data to export for the pcast.
     * @param \context_module $context The context of the pcast.
     * @param array $subcontext The location within the current context that this data belongs.
     * @param \stdClass $user the user record
     */
    protected static function export_pcast_data_for_user(array $pcastdata, \context_module $context,
                                                            array $subcontext, \stdClass $user) {
        // Fetch the generic module data for the pcast.
        $contextdata = helper::get_context_data($context, $user);
        // Merge with pcast data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $pcastdata);
        writer::with_context($context)->export_data($subcontext, $contextdata);
        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (empty($context)) {
            return;
        }

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
        $DB->record_exists('pcast', ['id' => $context->instanceid]);
        $DB->delete_records('pcast_episodes', ['pcastid' => $instanceid]);

        if ($context->contextlevel == CONTEXT_MODULE) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->record_exists('pcast', ['id' => $context->instanceid]);

            $episodes = $DB->get_records('pcast_episodes', ['pcastid' => $instanceid]);
            foreach ($episodes as $episode) {

                // Delete related episode views.
                $DB->delete_records('pcast_views', ['episodeid' => $episode->id]);
            }

            // Delete episode and attachment files.
            get_file_storage()->delete_area_files($context->id, 'mod_pcast', 'episode');
            get_file_storage()->delete_area_files($context->id, 'mod_pcast', 'mediafile');

            // Delete related ratings.
            \core_rating\privacy\provider::delete_ratings($context, 'mod_pcast', 'episode');

            // Delete comments.
            \core_comment\privacy\provider::delete_comments_for_all_users($context, 'mod_pcast', 'pcast_episode');

            // Delete tags.
            \core_tag\privacy\provider::delete_item_tags($context, 'mod_pcast', 'pcast_episodes');

            // Now delete all user related episodes.
            $DB->delete_records('pcast_episodes', ['pcastid' => $instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {

                $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
                $DB->record_exists('pcast', ['id' => $context->instanceid]);

                $episodes = $DB->get_records('pcast_episodes', ['pcastid' => $instanceid, 'userid' => $userid]);
                foreach ($episodes as $episode) {

                    // Delete related episode views.
                    $DB->delete_records('pcast_views', ['episodeid' => $episode->id]);

                    // Delete tags.
                    \core_tag\privacy\provider::delete_item_tags($context, 'mod_pcast', 'pcast_episodes', $episode->id);

                    // Delete episode and attachment files.
                    get_file_storage()->delete_area_files($context->id, 'mod_pcast', 'episode', $episode->id);
                    get_file_storage()->delete_area_files($context->id, 'mod_pcast', 'mediafile', $episode->id);

                    // Delete related ratings.
                    \core_rating\privacy\provider::delete_ratings($context, 'mod_pcast', 'episode', $episode->id);

                }
                // Delete comments.
                \core_comment\privacy\provider::delete_comments_for_user($contextlist, 'mod_pcast', 'pcast_episode');

                // Now delete all user related episodes.
                $DB->delete_records('pcast_episodes', ['pcastid' => $instanceid, 'userid' => $userid]);
            }
        }
    }
}
