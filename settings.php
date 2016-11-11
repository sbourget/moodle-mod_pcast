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
 * The main module settings page (for the admin menu)
 *
 *
 * @package   mod-pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
    $str = get_string('configenablerssfeeds', 'pcast').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

} else {
    $options = array(0 => get_string('no'), 1 => get_string('yes'));
    $str = get_string('configenablerssfeeds', 'pcast');
}
$settings->add(new admin_setting_configselect('pcast_enablerssfeeds', get_string('configenablerssfeeds2', 'pcast'),
                   $str, 0, $options));

unset($options);
if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
} else {
    $options = array(0 => get_string('no'), 1 => get_string('yes'));
}
$settings->add(new admin_setting_configselect('pcast_enablerssitunes', get_string('configenablerssitunes2', 'pcast'),
                   get_string('configenablerssitunes', 'pcast'), 0, $options));

unset($options);
$options = array(0 => get_string('no'), 1 => get_string('yes'));
$settings->add(new admin_setting_configselect('pcast_allowhtmlinsummary', get_string('configallowhtmlinsummary2', 'pcast'),
                   get_string('configallowhtmlinsummary', 'pcast'), 0, $options));
