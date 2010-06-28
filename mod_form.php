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
 * The main pcast configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_pcast_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE, $CFG, $DB;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('pcastname', 'pcast'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();
//-------------------------------------------------------------------------------
    /// General Podcast settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'posting', get_string('setupposting','pcast'));

    /// Allow comments
        $mform->addElement('selectyesno', 'userscancomment', get_string('userscancomment', 'pcast'));
        $mform->addHelpButton('userscancomment', 'userscancomment', 'pcast');
        $mform->setDefault('userscanpost', 0);

    /// Allow users to post episodes
        $mform->addElement('selectyesno', 'userscanpost', get_string('userscanpost', 'pcast'));
        $mform->addHelpButton('userscanpost', 'userscanpost', 'pcast');
        $mform->setDefault('userscanpost', 0);

    /// Require approval for posts
        $mform->addElement('selectyesno', 'requireapproval', get_string('requireapproval', 'pcast'));
        $mform->addHelpButton('requireapproval', 'requireapproval', 'pcast');
        $mform->setDefault('userscanpost', 0);

/// RSS Settings
//-------------------------------------------------------------------------------
        if ($CFG->enablerssfeeds && isset($CFG->pcast_enablerssfeeds) && $CFG->pcast_enablerssfeeds) {

            $mform->addElement('header', 'rss', get_string('rss'));

        /// RSS enabled
            $mform->addElement('selectyesno', 'enablerssfeed', get_string('enablerssfeed', 'pcast'));
            $mform->addHelpButton('enablerssfeed', 'enablerssfeed', 'pcast');

        /// RSS Entries per feed
            $choices = array();
            $choices[1] = '1';
            $choices[2] = '2';
            $choices[3] = '3';
            $choices[4] = '4';
            $choices[5] = '5';
            $choices[10] = '10';
            $choices[15] = '15';
            $choices[20] = '20';
            $choices[25] = '25';
            $choices[30] = '30';
            $choices[40] = '40';
            $choices[50] = '50';
            $mform->addElement('select', 'rssepisodes', get_string('rssepisodes','pcast'), $choices);
            $mform->addHelpButton('rssepisodes', 'rssepisodes', 'pcast');
            $mform->disabledIf('rssepisodes', 'enablerssfeed', 'eq', 0);
            $mform->setDefault('rssepisodes', 10);


        /// RSS Sort Order
            $sortorder = array();
            $sortorder[0] = get_string('createasc','pcast');
            $sortorder[1] = get_string('createdesc','pcast');
            $sortorder[2] = get_string('timeasc','pcast');;
            $sortorder[3] = get_string('timedesc','pcast');;
            $mform->addElement('select', 'rsssorting', get_string('rsssorting','pcast'), $sortorder);
            $mform->addHelpButton('rsssorting', 'rsssorting', 'pcast');
            $mform->setDefault('rsssorting', 2);
            $mform->disabledIf('rsssorting', 'enablerssfeed', 'eq', 0);

        }

//-------------------------------------------------------------------------------
        if (isset($CFG->pcast_enablerssitunes) && $CFG->pcast_enablerssitunes) {

            /// Itunes Tags
            $mform->addElement('header', 'itunes', get_string('itunes', 'pcast'));

            /// Enable Itunes Tags
            $mform->addElement('selectyesno', 'enablerssitunes', get_string('enablerssitunes', 'pcast'));
            $mform->addHelpButton('enablerssitunes', 'enablerssitunes', 'pcast');
            $mform->setDefault('enablerssitunes', 0);

            /// Subtitle
            $mform->addElement('text', 'subtitle', get_string('subtitle', 'pcast'), array('size'=>'64'));
            $mform->setType('subtitle', PARAM_NOTAGS);
            $mform->addHelpButton('subtitle', 'subtitle', 'pcast');
            $mform->disabledIf('subtitle', 'enablerssitunes', 'eq', 0);


            /// Owner
            ///TODO: Figure this out
//            $ownerlist = array();
//            $context = get_context_instance(CONTEXT_COURSE, $id);
//            if($owners = get_users_by_capability($context, 'mod/ipodcast:owner', 'u.*', 'u.lastaccess')) {
//                foreach ($owners as $owner) {
//                    $ownerlist[$owner->id] = $owner->firstname . ' ' . $owner->lastname;
//                }
//            }
//            $mform->addElement('select', 'userid', get_string('owner', 'ipodcast'), $ownerlist);
//            $mform->addHelpButton('userid', 'courseowner', 'ipodcast');
//            $mform->disabledIf('userid', 'enablerssitunes', 'eq', 0);


            /// Keywords
            $mform->addElement('text', 'keywords', get_string('keywords', 'pcast'), array('size'=>'64'));
            $mform->setType('keywords', PARAM_NOTAGS);
            $mform->addHelpButton('keywords', 'keywords', 'pcast');
            $mform->disabledIf('keywords', 'enablerssitunes', 'eq', 0);


            // Top Category
            $options=array();
            //TODO: convert to use MUST EXIST
            $topcatcount=0;
            if($topcategories = $DB->get_records("pcast_itunes_categories")) {
                foreach ($topcategories as $topcategory) {
                    $options[(int)$topcategory->id]= $topcategory->name;
                    $topcatcount = (int)$topcategory->id;
                }
            }

            // Secondary Category
            $options2=array();
            $options3=array();
            $options4=array();

            for($i=0; $i< $topcatcount; $i++) {
                $options4[$i] = 0;
            }

            //TODO: convert to use MUST EXIST
            if($nestedcategories = $DB->get_records("pcast_itunes_nested_cat")) {
                foreach ($nestedcategories as $nestedcategory) {

                    // Array format $options2[id] = name
                    $options2[(int)$nestedcategory->id]= $nestedcategory->name;
                    // Array format $options3[parentindex][id] = name
                    if(($prevnestedcategoryid != (int)$nestedcategory->topcategoryid) or !isset($prevnestedcategoryid)) {
                        $i =1;
                    }
                    // $options3[(int)$nestedcategory->topcategoryid][(int)$nestedcategory->id] = $nestedcategory->name;
                    $options3[(int)$nestedcategory->topcategoryid][$i++] = $nestedcategory->name;
                    // Array format $options4[id] = parentindexcount
                    $options4[(int)$nestedcategory->topcategoryid]++;
                    $prevnestedcategoryid =(int)$nestedcategory->topcategoryid;
                }
            }


            //Generate the select list options
            $k =0;
            $newoptions = array();
            for($i = 0; $i < $topcatcount; $i++) {
                if(isset($options[$i])) {
                    $newoptions[$k] = $options[$i];
                    $k++;
                }
                // Sub categories
                for( $j=0; $j <= $options4[$i]; $j++) {
                    if(isset($options3[$i][$j])) {
                        $newoptions[$k] = '&nbsp;&nbsp;' . $options3[$i][$j];
                        $k++;
                    }
                }
            }


            //$options = array_merge(array('all' => get_string('any')), $options);
            $mform->addElement('select', 'category', get_string('category', 'pcast'),
                    $newoptions, array('size' => '1'));
            //$mform->setDefault('subject', 'all');
            $mform->addHelpButton('category', 'category', 'pcast');
            $this->init_javascript_enhancement('category', 'smartselect',
                    array('selectablecategories' => true, 'mode' => 'compact'));



            // Merge Arrays 1, 2, and 4 into 1 array


            // Category List
//            $attribs = array('size' => '4');
//
//            //Category Selection -> STANDARD FORMS, NEEDS some sort of JS,
//            $categorylist=array();
//            $categorylist[] = &$mform->createElement('select', 'topcategory', get_string('category', 'pcast'), $options, $attribs);
//            $categorylist[] = &$mform->createElement('select', 'nestedcategory', '', $options2, $attribs);
//            $mform->addGroup($categorylist, 'categorylist', get_string('category', 'pcast'), array(' '), false);
//            $mform->setHelpButton('categorylist', array('coursecategory', 'topcategory', 'pcast'));
//            $mform->setDefault('topcategory', '1');
//            $mform->setDefault('nestedcategory', '1');
//            $mform->disabledIf('topcategory', 'enablerssitunes', 'eq', 0);
//            $mform->disabledIf('nestedcategory', 'enablerssitunes', 'eq', 0);

            // TODO: REMOVE THIS CODE ONCE THE ABOVE CODE WORKS:
/*
            //Uses HierSelect QFORM object (Make sure that the hierselect.php file is added to moodle/lib/form/
            //TODO: Fix Help File Icon for this mform object see MDL-20589
            $hier = &$mform->addElement('hierselect', 'category', get_string('category', 'ipodcast'), $attribs);
            $hier->setOptions(array($options,  $options3));
            $mform->addHelpButton('category', 'coursecategory', 'ipodcast');
            $mform->setDefaults(array('category'=>array(4,14)));
            $mform->disabledIf('category', 'enablerssitunes', 'eq', 0);
*/

            // Content
            $explicit=array();
            $explicit[0]  = get_string('yes');
            $explicit[1]  = get_string('no');
            $explicit[2]  = get_string('clean','pcast');
            $mform->addElement('select', 'explicit', get_string('explicit', 'pcast'),$explicit);
            $mform->addHelpButton('explicit', 'courseexplicit', 'pcast');
            $mform->disabledIf('explicit', 'enablerssitunes', 'eq', 0);
            $mform->setDefault('explicit', 2);
        }


//-------------------------------------------------------------------------------
    /// Adding the rest of pcast settings, spreading all them into this fieldset
    /// or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('static', 'label1', 'pcastsetting1', 'Your pcast fields go here. Replace me!');

        $mform->addElement('header', 'pcastfieldset', get_string('pcastfieldset', 'pcast'));
        $mform->addElement('static', 'label2', 'pcastsetting2', 'Your pcast fields go here. Replace me!');
        // Here is how you can add help (?) icons to your field labels
        $mform->setHelpButton('label2', array('helpfilename', 'pcastsetting2', 'pcast'));

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }
}
