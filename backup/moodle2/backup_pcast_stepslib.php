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
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package mod_pcast
 * @subpackage backup-moodle2
 * @copyright 2011 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete choice structure for backup, with file and id annotations
 *
 * @package mod_pcast
 * @subpackage backup-moodle2
 * @copyright 2011 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pcast_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines structure for data backup.
     * @return object
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $pcast = new backup_nested_element('pcast', array('id'), array(
            'userid', 'name', 'intro', 'introformat', 'userscancomment',
            'userscancategorize', 'userscanpost', 'maxbytes', 'requireapproval', 'displayauthor',
            'displayviews', 'image', 'imageheight', 'imagewidth', 'rssepisodes',
            'rsssortorder', 'enablerssfeed', 'enableitunes', 'visible', 'explicit',
            'subtitle', 'keywords', 'topcategory', 'nestedcategory', 'assessed',
            'assesstimestart', 'assesstimefinish', 'scale', 'timecreated', 'timemodified',
            'completionepisodes', 'allowedfiletypes'));

        $episodes = new backup_nested_element('episodes');

        $episode = new backup_nested_element('episode', array('id'), array(
            'userid', 'name', 'summary', 'summaryformat', 'summarytrust', 'mediafile', 'duration', 'explicit',
            'subtitle', 'keywords', 'topcategory', 'nestedcategory', 'timecreated', 'timemodified',
            'approved', 'sequencenumber'));

        $views = new backup_nested_element('views');

        $view = new backup_nested_element('view', array('id'), array(
            'episodeid', 'userid', 'views', 'lastview'));

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
            'component', 'ratingarea', 'scaleid', 'value', 'userid', 'timecreated', 'timemodified'));

        $tags = new backup_nested_element('tags');
        $tag = new backup_nested_element('tag', array('id'), array('itemid', 'rawname'));

        // Build the tree.

        $pcast->add_child($episodes);
        $episodes->add_child($episode);

        $episode->add_child($tags);
        $tags->add_child($tag);

        $episode->add_child($views);
        $views->add_child($view);

        $episode->add_child($ratings);
        $ratings->add_child($rating);

        // Define sources.

        $pcast->set_source_table('pcast', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {

            $episode->set_source_sql('
            SELECT *
              FROM {pcast_episodes}
             WHERE pcastid = ?',
            array(backup::VAR_PARENTID));

            $view->set_source_sql('
            SELECT *
              FROM {pcast_views}
             WHERE episodeid = ?',
            array(backup::VAR_PARENTID));

            $rating->set_source_table('rating', array('contextid'  => backup::VAR_CONTEXTID,
                                                      'itemid'     => backup::VAR_PARENTID,
                                                      'component'  => backup_helper::is_sqlparam('mod_pcast'),
                                                      'ratingarea' => backup_helper::is_sqlparam('episode')));
            $rating->set_source_alias('rating', 'value');

            if (core_tag_tag::is_enabled('mod_pcast', 'pcast_episodes')) {
                $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                       WHERE ti.itemtype = ?
                                         AND ti.component = ?
                                         AND ti.contextid = ?', array(
                    backup_helper::is_sqlparam('pcast_episodes'),
                    backup_helper::is_sqlparam('mod_pcast'),
                    backup::VAR_CONTEXTID));
            }

        }

        // Define id annotations.

        $pcast->annotate_ids('user', 'userid');
        $episode->annotate_ids('user', 'userid');
        $view->annotate_ids('user', 'userid');

        $rating->annotate_ids('scale', 'scaleid');
        $rating->annotate_ids('user', 'userid');

        // Define file annotations.

        $pcast->annotate_files('mod_pcast', 'intro', null); // This file area hasn't itemid.
        $pcast->annotate_files('mod_pcast', 'logo', null);

        $episode->annotate_files('mod_pcast', 'episode', 'id');

        // Return the root element (pcast), wrapped into standard activity structure.
        return $this->prepare_activity_structure($pcast);

    }
}