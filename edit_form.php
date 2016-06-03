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
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');

class mod_pcast_entry_form extends moodleform {

    public function definition() {
        global $DB, $CFG, $pcast;

        $mform =& $this->_form;
        $cm = $this->_customdata['cm'];
        $currententry = $this->_customdata['current'];
        $context = $this->_customdata['context'];

        // Adding the "general" fieldset, where all the common settings are showed.

        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'pcast'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('editor', 'summary', get_string('summary', 'pcast'), null,
                           array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context));
        $mform->setType('summary', PARAM_RAW);
        $mform->addRule('summary', get_string('required'), 'required', null, 'client');

        // Attachment.

        $mform->addElement('header', 'attachments', get_string('attachment', 'pcast'));
        $mform->addElement('filemanager', 'mediafile', get_string('pcastmediafile', 'pcast'), null,
            array('subdirs' => 0,
                'maxfiles' => 1,
                'maxbytes' => $pcast->maxbytes,
                'filetypes' => array('audio', 'video'),
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
                'returnvalue' => 'ref_id')
            );

        // Media File.
        $mform->addRule('mediafile', get_string('required'), 'required', null, 'client');

        // ITunes Settings.

        $mform->addElement('header', 'itunes', get_string('itunes', 'pcast'));

        // Subtitle.
        $mform->addElement('text', 'subtitle', get_string('subtitle', 'pcast'), array('size' => '64'));
        $mform->addRule('subtitle', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('subtitle', PARAM_NOTAGS);
        $mform->addHelpButton('subtitle', 'subtitle', 'pcast');

        // Keywords.
        $mform->addElement('text', 'keywords', get_string('keywords', 'pcast'), array('size' => '64'));
        $mform->addRule('keywords', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('keywords', PARAM_NOTAGS);
        $mform->addHelpButton('keywords', 'keywords', 'pcast');

        // Disable if turned off on module settings page.
        if ($pcast->userscancategorize) {
            // Generate Top Categorys.
            $newoptions = array();
            if ($topcategories = $DB->get_records("pcast_itunes_categories")) {
                foreach ($topcategories as $topcategory) {
                    $value = (int)$topcategory->id * 1000;
                    $newoptions[(int)$value] = $topcategory->name;
                }
            }

            // Generate Secondary Category.
            if ($nestedcategories = $DB->get_records("pcast_itunes_nested_cat")) {
                foreach ($nestedcategories as $nestedcategory) {
                    $value = (int)$nestedcategory->topcategoryid * 1000;
                    $value = $value + (int)$nestedcategory->id;
                    $newoptions[(int)$value] = '&nbsp; &nbsp; ' .$nestedcategory->name;
                }
            }
            ksort($newoptions);

            // Category form element.
            $mform->addElement('select', 'category', get_string('category', 'pcast'),
                    $newoptions, array('size' => '1'));
            $mform->addHelpButton('category', 'category', 'pcast');
            $mform->disabledIf('category', 'enablerssitunes', 'eq', 0);

            $this->init_javascript_enhancement('category', 'smartselect',
                    array('selectablecategories' => false, 'mode' => 'compact'));
        }

        // Content.
        $explicit = array();
        $explicit[0]  = get_string('yes');
        $explicit[1]  = get_string('no');
        $explicit[2]  = get_string('clean', 'pcast');
        $mform->addElement('select', 'explicit', get_string('explicit', 'pcast'), $explicit);
        $mform->addHelpButton('explicit', 'explicit', 'pcast');
        $mform->setDefault('explicit', 2);

        // Hidden.

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        // Add standard buttons, common to all modules.

        $this->add_action_buttons();
        $this->set_data($currententry);

    }
}
