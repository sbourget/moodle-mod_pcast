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
 * The pcast episode configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package   mod-pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/lib/formslib.php');


class mod_pcast_entry_form extends moodleform {

    function definition() {
        global $DB, $CFG, $USER, $COURSE;

        $mform =& $this->_form;
        $cm = $this->_customdata['cm'];
        $currententry = $this->_customdata['current'];


//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name', 'pcast'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //$mform->addElement('textarea', 'summary', get_string("summary", "pcast"), 'wrap="virtual" rows="20" cols="50"');
        $mform->addElement('editor', 'summary', get_string('summary', 'pcast'), null);
        $mform->setType('summary', PARAM_RAW);
        $mform->addRule('summary', get_string('required'), 'required', null, 'client');

//-------------------------------------------------------------------------------

    /// iTunes Settings
        $mform->addElement('header', 'itunes', get_string('itunes', 'pcast'));

        // Subtitle
        $mform->addElement('text', 'subtitle', get_string('subtitle', 'pcast'), array('size'=>'64'));
        $mform->setType('subtitle', PARAM_NOTAGS);
        $mform->addHelpButton('subtitle', 'subtitle', 'pcast');

        // Keywords
        $mform->addElement('text', 'keywords', get_string('keywords', 'pcast'), array('size'=>'64'));
        $mform->setType('keywords', PARAM_NOTAGS);
        $mform->addHelpButton('keywords', 'keywords', 'pcast');

        //TODO: Disable if turned off on module settins page
        // Generate Top Categorys;
        $newoptions = array();
        if($topcategories = $DB->get_records("pcast_itunes_categories")) {
            foreach ($topcategories as $topcategory) {
                $value = (int)$topcategory->id * 1000;
                $newoptions[(int)$value] = $topcategory->name;
            }
        }

        // Generate Secondary Category
        if($nestedcategories = $DB->get_records("pcast_itunes_nested_cat")) {
            foreach ($nestedcategories as $nestedcategory) {
                $value = (int)$nestedcategory->topcategoryid * 1000;
                $value = $value + (int)$nestedcategory->id;
                $newoptions[(int)$value] = '&nbsp;&nbsp;' .$nestedcategory->name;
            }
        }
        ksort($newoptions);

        // Category form element
        $mform->addElement('select', 'category', get_string('category', 'pcast'),
                $newoptions, array('size' => '1'));
        $mform->addHelpButton('category', 'category', 'pcast');
        $mform->disabledIf('category', 'enablerssitunes', 'eq', 0);

        $this->init_javascript_enhancement('category', 'smartselect',
                array('selectablecategories' => false, 'mode' => 'compact'));


        // Content
        $explicit=array();
        $explicit[0]  = get_string('yes');
        $explicit[1]  = get_string('no');
        $explicit[2]  = get_string('clean','pcast');
        $mform->addElement('select', 'explicit', get_string('explicit', 'pcast'),$explicit);
        $mform->addHelpButton('explicit', 'explicit', 'pcast');
        $mform->setDefault('explicit', 2);

        // Attachment
        $mform->addElement('header', 'attachments', get_string('attachment', 'pcast'));
        $mform->addElement('filemanager', 'mediafile', get_string('pcastmediafile', 'pcast'), null,
            array('subdirs'=>0,
                'maxfiles'=>1,
                'maxbytes'=>$COURSE->maxbytes,
                'filetypes' => array('audio','video'),
                'returnvalue'=>'ref_id'
            ));
//-------------------------------------------------------------------------------

    /// hidden
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);


//-------------------------------------------------------------------------------
    // add standard buttons, common to all modules
        $this->add_action_buttons();

//-------------------------------------------------------------------------------
        $this->set_data($currententry);


    }

//    function data_preprocessing(&$default_values) {
//        if ($this->current->instance) {
//            // editing existing instance - copy existing files into draft area
//            $draftitemid = file_get_submitted_draft_itemid('mediafile');
//            file_prepare_draft_area($draftitemid, $this->context->id, 'pcast_episode', $this->current->mediafile, array('subdirs'=>false));
//            $default_values['mediafile'] = $draftitemid;
//        }
//    }
/*
    function validation($data, $files) {
        global $CFG, $USER, $DB;
        $errors = parent::validation($data, $files);

        $glossary = $this->_customdata['glossary'];
        $cm       = $this->_customdata['cm'];
        $context  = get_context_instance(CONTEXT_MODULE, $cm->id);

        $id = (int)$data['id'];
        $data['concept'] = trim($data['concept']);

        if ($id) {
            //We are updating an entry, so we compare current session user with
            //existing entry user to avoid some potential problems if secureforms=off
            //Perhaps too much security? Anyway thanks to skodak (Bug 1823)
            $old = $DB->get_record('glossary_entries', array('id'=>$id));
            $ineditperiod = ((time() - $old->timecreated <  $CFG->maxeditingtime) || $glossary->editalways);
            if ((!$ineditperiod || $USER->id != $old->userid) and !has_capability('mod/glossary:manageentries', $context)) {
                if ($USER->id != $old->userid) {
                    $errors['concept'] = get_string('errcannoteditothers', 'glossary');
                } elseif (!$ineditperiod) {
                    $errors['concept'] = get_string('erredittimeexpired', 'glossary');
                }
            }
            if (!$glossary->allowduplicatedentries) {
                if ($dupentries = $DB->get_records('glossary_entries', array('LOWER(concept)'=>moodle_strtolower($data['concept'])))) {
                    foreach ($dupentries as $curentry) {
                        if ($glossary->id == $curentry->glossaryid) {
                           if ($curentry->id != $id) {
                               $errors['concept'] = get_string('errconceptalreadyexists', 'glossary');
                               break;
                           }
                        }
                    }
                }
            }

        } else {
            if (!$glossary->allowduplicatedentries) {
                if ($dupentries = $DB->get_record('glossary_entries', array('LOWER(concept)'=>moodle_strtolower($data['concept']), 'glossaryid'=>$glossary->id))) {
                    $errors['concept'] = get_string('errconceptalreadyexists', 'glossary');
                }
            }
        }

        return $errors;
    }
*/


}
