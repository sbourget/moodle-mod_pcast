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
require_once(dirname(__FILE__).'/locallib.php');

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
class mod_pcast_mod_form extends moodleform_mod {

    /**
     * The form definition.
     */
    public function definition() {

        global $COURSE, $CFG, $DB;
        $mform =& $this->_form;
        $pcastconfig = get_config('mod_pcast');

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('pcastname', 'pcast'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Adding the max upload size of episodes.
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0);
        $mform->addElement('select', 'maxbytes', get_string('maxattachmentsize', 'pcast'), $choices);
        $mform->addHelpButton('maxbytes', 'maxattachmentsize', 'pcast');
        $mform->setDefault('maxbytes', $COURSE->maxbytes);

        $mform->addElement('filetypes', 'allowedfiletypes', get_string('allowedfiletypes', 'pcast'),
             ['onlytypes' => ['html_audio', 'web_audio', 'html_video', 'web_video'], 'allowunknown' => true]);
        $mform->addHelpButton('allowedfiletypes', 'allowedfiletypes', 'pcast');
        $mform->setDefault('allowedfiletypes', 'web_audio,web_video,html_video,html_audio');

        // RSS Settings.

        if ($CFG->enablerssfeeds && isset($pcastconfig->enablerssfeeds) && $pcastconfig->enablerssfeeds) {

            $mform->addElement('header', 'rss', get_string('rss'));

            // RSS enabled.
            $mform->addElement('selectyesno', 'enablerssfeed', get_string('enablerssfeed', 'pcast'));
            $mform->addHelpButton('enablerssfeed', 'enablerssfeed', 'pcast');

            // RSS Entries per feed.
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
            $mform->addElement('select', 'rssepisodes', get_string('rssepisodes', 'pcast'), $choices);
            $mform->addHelpButton('rssepisodes', 'rssepisodes', 'pcast');
            $mform->disabledIf('rssepisodes', 'enablerssfeed', 'eq', 0);
            $mform->setDefault('rssepisodes', 10);

            // RSS Sort Order.
            $sortorder = array();
            $sortorder[0] = get_string('createasc', 'pcast');
            $sortorder[1] = get_string('createdesc', 'pcast');
            $mform->addElement('select', 'rsssortorder', get_string('rsssortorder', 'pcast'), $sortorder);
            $mform->addHelpButton('rsssortorder', 'rsssortorder', 'pcast');
            $mform->setDefault('rsssortorder', 2);
            $mform->disabledIf('rsssortorder', 'enablerssfeed', 'eq', 0);

        }

        // Itunes.
        if ($CFG->enablerssfeeds && isset($pcastconfig->enablerssitunes) && $pcastconfig->enablerssitunes) {

            // Itunes Tags.
            $mform->addElement('header', 'itunes', get_string('itunes', 'pcast'));

            // Enable Itunes Tags.
            $mform->addElement('selectyesno', 'enablerssitunes', get_string('enablerssitunes', 'pcast'));
            $mform->addHelpButton('enablerssitunes', 'enablerssitunes', 'pcast');
            $mform->setDefault('enablerssitunes', 0);
            $mform->disabledIf('enablerssitunes', 'enablerssfeed', 'eq', 0);

            // Subtitle.
            $mform->addElement('text', 'subtitle', get_string('subtitle', 'pcast'), array('size' => '64'));
            $mform->setType('subtitle', PARAM_NOTAGS);
            $mform->addHelpButton('subtitle', 'subtitle', 'pcast');
            $mform->disabledIf('subtitle', 'enablerssitunes', 'eq', 0);
            $mform->addRule('subtitle', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

            // Owner.
            $ownerlist = array();
            $context = context_course::instance($COURSE->id);
            if ($owners = get_users_by_capability($context, 'mod/pcast:manage', 'u.*', 'u.lastaccess')) {
                foreach ($owners as $owner) {
                    $ownerlist[$owner->id] = $owner->firstname . ' ' . $owner->lastname;
                }
            }
            $mform->addElement('select', 'userid', get_string('author', 'pcast'), $ownerlist);
            $mform->addHelpButton('userid', 'author', 'pcast');
            $mform->disabledIf('userid', 'enablerssitunes', 'eq', 0);

            // Keywords.
            $mform->addElement('text', 'keywords', get_string('keywords', 'pcast'), array('size' => '64'));
            $mform->setType('keywords', PARAM_NOTAGS);
            $mform->addHelpButton('keywords', 'keywords', 'pcast');
            $mform->disabledIf('keywords', 'enablerssitunes', 'eq', 0);
            $mform->addRule('keywords', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

            // Category form element.
            $mform->addElement('selectgroups', 'category', get_string('category', 'pcast'), pcast_get_categories());
            $mform->addHelpButton('category', 'category', 'pcast');
            $mform->disabledIf('category', 'enablerssitunes', 'eq', 0);

            // Content.
            $explicit = array();
            $explicit[0]  = get_string('yes');
            $explicit[1]  = get_string('no');
            $explicit[2]  = get_string('clean', 'pcast');
            $mform->addElement('select', 'explicit', get_string('explicit', 'pcast'), $explicit);
            $mform->addHelpButton('explicit', 'explicit', 'pcast');
            $mform->disabledIf('explicit', 'enablerssitunes', 'eq', 0);
            $mform->setDefault('explicit', 2);
        }

        // General Podcast settings.

        $mform->addElement('header', 'posting', get_string('setupposting', 'pcast'));

        // Allow comments.
        if ($CFG->usecomments) {
            $mform->addElement('selectyesno', 'userscancomment', get_string('userscancomment', 'pcast'));
            $mform->addHelpButton('userscancomment', 'userscancomment', 'pcast');
            $mform->setDefault('userscancomment', 0);
        }

        // Allow users to post episodes.
        $mform->addElement('selectyesno', 'userscanpost', get_string('userscanpost', 'pcast'));
        $mform->addHelpButton('userscanpost', 'userscanpost', 'pcast');
        $mform->setDefault('userscanpost', 1);

        // Require approval for posts.
        $mform->addElement('selectyesno', 'requireapproval', get_string('requireapproval', 'pcast'));
        $mform->addHelpButton('requireapproval', 'requireapproval', 'pcast');
        $mform->setDefault('requireapproval', 1);

        // Allow Display authors names.
        $mform->addElement('selectyesno', 'displayauthor', get_string('displayauthor', 'pcast'));
        $mform->addHelpButton('displayauthor', 'displayauthor', 'pcast');
        $mform->setDefault('displayauthor', 0);

        // Allow Display of viewers names.
        $mform->addElement('selectyesno', 'displayviews', get_string('displayviews', 'pcast'));
        $mform->addHelpButton('displayviews', 'displayviews', 'pcast');
        $mform->setDefault('displayviews', 0);

        // Allow users to select categories.
        $mform->addElement('selectyesno', 'userscancategorize', get_string('userscancategorize', 'pcast'));
        $mform->addHelpButton('userscancategorize', 'userscancategorize', 'pcast');
        $mform->setDefault('userscancategorize', 0);
        $mform->disabledIf('userscancategorize', 'enablerssitunes', 'eq', 0);

        // Show X episodes per page.
        $mform->addElement('text', 'episodesperpage', get_string('episodesperpage', 'pcast'));
        $mform->addHelpButton('episodesperpage', 'episodesperpage', 'pcast');
        $mform->setDefault('episodesperpage', 10);
        $mform->addRule('episodesperpage', null, 'numeric', null, 'client');
        $mform->setType('episodesperpage', PARAM_INT);

        // Images.
        $mform->addElement('header', 'images', get_string('image', 'pcast'));

        $mform->addElement('filemanager', 'image', get_string('imagefile', 'pcast'), null,
            array('subdirs' => 0,
                'maxfiles' => 1,
                'filetypes' => array('jpeg', 'png'),
                'returnvalue' => 'ref_id')
            );

        // Image Size (Height).
        $size = array();
        $size[0] = get_string('noresize', 'pcast');
        $size[16] = "16";
        $size[32] = "32";
        $size[48] = "48";
        $size[64] = "64";
        $size[128] = "128";
        $size[144] = "144";
        $size[200] = "200";
        $size[400] = "400";

        $mform->addElement('select', 'imageheight', get_string('imageheight', 'pcast'), $size);
        $mform->addHelpButton('imageheight', 'imageheight', 'pcast');
        $mform->setDefault('imageheight', 144);

        // Image Size (Width).
        unset($size);
        $size = array();
        $size[0] = get_string('noresize', 'pcast');
        $size[16] = "16";
        $size[32] = "32";
        $size[48] = "48";
        $size[64] = "64";
        $size[128] = "128";
        $size[144] = "144";

        $mform->addElement('select', 'imagewidth', get_string('imagewidth', 'pcast'), $size);
        $mform->addHelpButton('imagewidth', 'imagewidth', 'pcast');
        $mform->setDefault('imagewidth', 144);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

    }

    /**
     * Function used to edit user data before save.
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            // Editing existing instance - copy existing files into draft area.
            $draftitemid = file_get_submitted_draft_itemid('id');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_pcast', 'logo', 0, array('subdirs' => false));
            $defaultvalues['image'] = $draftitemid;

            // Convert topcategory and nested to a single category.
            $defaultvalues['category'] = (int)$defaultvalues['topcategory'] * 1000 + (int)$defaultvalues['nestedcategory'];

        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        if (!empty($defaultvalues['completionepisodes'])) {
            $defaultvalues['completionepisodesenabled'] = 1;
        } else {
            $defaultvalues['completionepisodesenabled'] = 0;
        }
        if (empty($defaultvalues['completionepisodes'])) {
            $defaultvalues['completionepisodes'] = 1;
        }
    }

    /**
     * Add completion rules to form.
     * @return array
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completionepisodesenabled', '',
                get_string('completionepisodes', 'pcast'));
        $group[] =& $mform->createElement('text', 'completionepisodes', '', array('size' => 3));
        $mform->setType('completionepisodes', PARAM_INT);
        $mform->addGroup($group, 'completionepisodesgroup',
                get_string('completionepisodesgroup', 'pcast'), array(' '), false);
        $mform->disabledIf('completionepisodes', 'completionepisodesenabled', 'notchecked');
        return array('completionepisodesgroup');
    }

    /**
     * Enable completion rules.
     * @param stdcalss $data
     * @return array
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionepisodesenabled']) && $data['completionepisodes'] != 0);
    }

    /**
     * Used to load form data.
     * @return object | boolean
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionepisodesenabled) || !$autocompletion) {
                $data->completionepisodes = 0;
            }
        }
        return $data;
    }
}
