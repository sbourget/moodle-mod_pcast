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
 * This file keeps track of upgrades to the pcast module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installtion to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in
 * lib/ddllib.php
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_pcast_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pcast_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    // RatingArea Upgrade.
    if ($oldversion < 2011080700) {

        // rating.component and rating.ratingarea have now been added as mandatory fields.
        // Presently you can only rate data entries so component = 'mod_pcast' and ratingarea = 'episode'.
        // for all ratings with a pcast context.
        // We want to update all ratings that belong to a pcast context and don't already have a component set.
        // This could take a while reset upgrade timeout to 5 min.

        upgrade_set_timeout(60 * 20);
        $sql = "UPDATE {rating}
                SET component = 'mod_pcast', ratingarea = 'episode'
                WHERE contextid IN (
                    SELECT ctx.id
                      FROM {context} ctx
                      JOIN {course_modules} cm ON cm.id = ctx.instanceid
                      JOIN {modules} m ON m.id = cm.module
                     WHERE ctx.contextlevel = 70 AND
                           m.name = 'pcast'
                ) AND component = 'unknown'";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2011080700, 'pcast');

    }

    if ($oldversion < 2012022900) {
        //Add support for file limit on attachments used with pcast episodes.

        // Define field maxbytes to be added to pcast.
        $table = new xmldb_table('pcast');
        $field = new xmldb_field('maxbytes', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userscanpost');

        // Conditionally launch add field maxbytes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pcast savepoint reached.
        upgrade_mod_savepoint(true, 2012022900, 'pcast');
    }

        if ($oldversion < 2012061400) {

        // Define field summaryformat to be added to pcast_episodes
        $table = new xmldb_table('pcast_episodes');
        $field = new xmldb_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'summary');

        // Conditionally launch add field summaryformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pcast savepoint reached
        upgrade_mod_savepoint(true, 2012061400, 'pcast');
    }

        if ($oldversion < 2012061401) {

        // Define field summarytrust to be added to pcast_episodes
        $table = new xmldb_table('pcast_episodes');
        $field = new xmldb_field('summarytrust', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'summaryformat');

        // Conditionally launch add field summarytrust
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pcast savepoint reached
        upgrade_mod_savepoint(true, 2012061401, 'pcast');
    }

    if ($oldversion < 2013062400) {

        // Changing type of field intro on table pcast to text
        $table = new xmldb_table('pcast');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');

        // Launch change of type for field intro
        $dbman->change_field_type($table, $field);

        // pcast savepoint reached
        upgrade_mod_savepoint(true, 2013062400, 'pcast');
    }


    // Final return of upgrade result (true/false) to Moodle. Must be always the last line in the script.
    return true;
}
