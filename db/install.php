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
 * This file handles the installation of the modules.
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 */
function xmldb_pcast_install() {
    global $DB;

    // Install default categories.

    $dataobject = new stdClass();
    $dataobject->id = 1;
    $dataobject->name = 'Arts';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Business';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Comedy';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Education';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Games & Hobbies';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Government & Organizations';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Health';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Kids & Family';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Music';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'News & Politics';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Religion & Spirituality';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Science & Medicine';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Society & Culture';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Sports & Recreation';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Technology';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'TV & Film';
    $DB->insert_record('pcast_itunes_categories', $dataobject, false, false);

    unset($dataobject);
    // Itunes Nested Categories.

    $dataobject = new stdClass();
    $dataobject->id = 1;
    $dataobject->topcategoryid = 1;
    $dataobject->name = 'Design';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Fashion & Beauty';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Food';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Literature';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Performing Arts';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Visual Arts';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #2.
    $dataobject->topcategoryid = 2;

    $dataobject->id++;
    $dataobject->name = 'Business News';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Careers';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Investing';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Management & Marketing';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Shopping';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #4.
    $dataobject->topcategoryid = 4;

    $dataobject->id++;
    $dataobject->name = 'Education Technology';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Higher Education';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'K-12';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Language Courses';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Training';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #5.
    $dataobject->topcategoryid = 5;

    $dataobject->id++;
    $dataobject->name = 'Automotive';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Aviation';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Hobbies';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Other Games';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Video Games';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #6.
    $dataobject->topcategoryid = 6;

    $dataobject->id++;
    $dataobject->name = 'Local';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'National';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Non-Profit';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Regional';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #7.
    $dataobject->topcategoryid = 7;

    $dataobject->id++;
    $dataobject->name = 'Alternative Health';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Fitness & Nutrition';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Self-Help';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Sexuality';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #11.
    $dataobject->topcategoryid = 11;

    $dataobject->id++;
    $dataobject->name = 'Buddhism';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Christianity';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Hinduism';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Islam';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Judaism';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Other';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #12.
    $dataobject->topcategoryid = 12;

    $dataobject->id++;
    $dataobject->name = 'Medicine';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Natural Sciences';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Social Sciences';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #13.
    $dataobject->topcategoryid = 13;

    $dataobject->id++;
    $dataobject->name = 'History';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Personal Journals';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Philosophy';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Places & Travel';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #14.
    $dataobject->topcategoryid = 14;

    $dataobject->id++;
    $dataobject->name = 'Amateur';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'College & High School';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Outdoor';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Professional';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    // Category #15.
    $dataobject->topcategoryid = 15;

    $dataobject->id++;
    $dataobject->name = 'Gadgets';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Tech News';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Podcasting';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    $dataobject->id++;
    $dataobject->name = 'Software How-To';
    $DB->insert_record('pcast_itunes_nested_cat', $dataobject, false, false);

    unset($dataobject);

}
