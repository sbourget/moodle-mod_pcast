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
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'pcast', 'action'=>'add', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'update', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'view', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'view all', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'add episode', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'update episode', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'approve episode', 'mtable'=>'pcast', 'field'=>'name'),
    array('module'=>'pcast', 'action'=>'view episode', 'mtable'=>'pcast_episodes', 'field'=>'name'),
);


