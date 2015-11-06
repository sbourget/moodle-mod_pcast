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
 * @see uninstall_plugin()
 *
 * @package    mod
 * @subpackage pcast
 * @copyright  2012 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Custom module uninstall code
 * 
 * @global stdClass $DB
 * @return bool
 */

function mod_pcast_uninstall() {
    global $DB;

    $fs = get_file_storage();
    // Remove all files stored in the pcast filepool area from the database.
    $files = $DB->get_records('files', array('component' => 'mod_pcast'), 'id');
    foreach ($files as $file) {
        $fs->get_file_instance($file)->delete();
    }
    return true;
}