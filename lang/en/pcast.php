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
 * English strings for pcast
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget and Jillaine Beeckman
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['modulename'] = 'pcast';
$string['pluginname'] = 'pcast';
$string['modulenameplural'] = 'pcasts';
$string['pluginadministration'] = 'Podcast administration';
$string['pcastfieldset'] = 'Custom example fieldset';
$string['pcastname'] = 'pcast name';
$string['pcast'] = 'pcast';

//Settings.php
$string['configenablerssfeeds'] = 'This switch will enable the possibility of RSS feeds for all podcasts.  You will still need to turn feeds on manually in the settings for each podcast.';
$string['configenablerssitunes'] = 'This switch will enable the possibility of itunes compatible RSS feeds for all podcasts.  You will still need to set Enable iTunes RSS Tags to yes podcast course settings.';
$string['configusemediafilter'] = 'Use moodle media filters';
$string['configenablerssfeeds2'] = 'Enable RSS Feeds:';
$string['configenablerssitunes2'] = 'Enable iTunes RSS:';
$string['configusemediafilter2'] = 'Use Media Filter:';

//mod_form.php
$string['setupposting']='Posting options';
$string['userscancomment']='Allow user comments';
$string['userscanpost']='Allow users to post episodes';
$string['requireapproval']='Require approval for episodes';
$string['enablerssfeed']='Enable RSS';
$string['rssepisodes']='Number of episodes';
$string['rsssortorder']='RSS sort order';
$string['itunes']='iTunes';
$string['createasc']='Newest episode first';
$string['createdesc']='Oldest episode first';
$string['enablerssitunes']='Enable RSS for iTunes';
$string['subtitle']='Subtitle';
$string['keywords']='Keywords';
$string['category']='Category';
$string['clean']='Clean';
$string['explicit']='Explicit content';
$string['author']='Author';
$string['image']='Podcast image';
$string['imagefile']='Image';
$string['imageheight']='Height';
$string['imagewidth']='Width';
$string['noresize']='Do not resize';
$string['displayauthor']='Display author names';
$string['displayviews']='Display names of viewers';
$string['userscancategorize']='Allow episode categories';
$string['userscanrate']='Allow user ratings';




//mod_form.php help
$string['userscanpost_help']='Allow users to post episodes.';
$string['userscancomment_help']='Allow users to post comments';
$string['requireapproval_help']='Require episodes to be approved before posting';
$string['enablerssfeed_help']='Enable RSS for this podcast';
$string['rssepisodes_help']='This option limits the number of episodes displayed on the RSS feed';
$string['rsssortorder_help']='This is the sort order for the episodes.  They can be sorted by date';
$string['enablerssitunes_help']='This enables iTunes specific tags in the RSS file';
$string['subtitle_help']='Subtitle for podcast';
$string['keywords_help']='Keywords describing this podcast';
$string['category_help']='iTunes category';
$string['explicit_help']='This specifies if the podcast contains explicit content.';
$string['author_help']='Author of this podcast';
$string['displayauthor_help']='Display the name of the author for each episode to users';
$string['displayviews_help']='Display the names and number of views for each episode to users';
$string['imageheight_help']='Height of RSS channel logo';
$string['imagewidth_help']='Width of RSS channel logo';
$string['userscancategorize_help']='Allow users to select iTunes categories for each of their episodes';
$string['userscanrate_help']='Allow users to rate episodes';
$string['']='';

// Navigation Block
$string['pcastadministration']='Podcast administration';

//lib.php
$string['noviews']='No views';
$string['waitingapproval'] = 'Approve episodes';
$string['arealogo'] = 'Podcast RSS logo';
$string['areaepisode'] = 'Podcast episodes';
$string['newepisodes'] = 'New podcast episodes';
$string['rsslink']='RSS feed for this activity';

// Errors
$string['invalidcmorid']='Error: You must specify a course_module ID or an instance ID';
$string['invalidcourse'] = 'Error: Course ID is incorrect';
$string['invalidentry'] = 'Error: Invalid episode ID';
$string['noeditprivlidges']='Error: You do not have editing rights.';
$string['errcannoteditothers']='Error: you cannot edit other users episodes';
$string['erredittimeexpired']='Error: Editing time has expired';
$string['nopermissiontodelepisode']='Error: You do not have permission to delete this episode';
$string['errcannotedit']='Error: you cannot edit this episode';
$string['errorinvalidmode']='Error: you do not have access';
$string['commentsnotenabled']='Error: Commenting is not enabled';
$string['']='';

//edit_form.php
$string['name']='Title';
$string['summary']='Summary';
$string['attachment']='Attachment';
$string['pcastmediafile']='Media file';
$string['']='';
$string['']='';

// view.php
$string['view']='View';
$string['standardview']='Browse by alphabet';
$string['categoryview']='Browse by category';
$string['dateview']='Browse by date';
$string['authorview']='Browse by author';
$string['approvalview']='Approve entries';
$string['addnewepisode']='Add a new episode';
$string['viewpcast']='View podcast: {$a}';
$string['pcast_help']= 'This activity allows users to create and maintain video and audio podcasts. The podcast can easily be configured for iTunes compatiblity.';


// locallib.php
$string['explainaddentry'] = 'Add a new episode to the current podcast.<br />name, summary, and attachment are mandatory fields.';
$string['explainall'] = 'Shows ALL entries on one page';
$string['explainalphabet'] = 'Browse the podcast using this index';
$string['explainspecial'] = 'Shows entries that do not begin with a letter';
$string['special'] = 'Special';
$string['allentries'] = 'ALL';
$string['allcategories'] = 'All categories';
$string['notcategorised']='Not categorised';
$string['episodeswithoutcategory']='Episodes without a category';
$string['ascending']='Acsending';
$string['descending']='Descending';
$string['changeto']='Change to {$a}';
$string['sortbycreation']='Date created';
$string['sortbylastupdate']='Date updated';
$string['current']='current sort {$a}';
$string['sortby']='Sort by';

$string['duration'] = 'Duration';
$string['durationlength'] = '{$a->min} minutes {$a->sec} seconds';
$string['totalviews'] = 'Total views';
$string['totalcomments'] = 'Total comments';
$string['totalratings'] = 'Total ratings';
$string['created']='Created';
$string['updated']='Last updated';
$string['nopcastmediafile'] = 'No media file found';
$string['views']='Views';
$string['user']='Users';
$string['noviews']='No views';
$string['viewepisode']='view this episode';
$string['episodetitle']='Episode';
$string['']='';


// deleteepisode.php
$string['areyousuredelete']='Are you sure you want to delete this episode';
$string['episodedeleted']='episode {$a} was sucessfully deleted';


// Roles
$string['pcast:approve']='Approve unapproved episodes';
$string['pcast:manage']='Manage episodes (Edit / Delete)';
$string['pcast:view']='View episodes';
$string['pcast:write']='Create new episodes';
$string['pcast:rate']='Add ratings to episodes';
$string['pcast:viewrating']='View the total ratings you received';
$string['pcast:viewallratings']='View all raw ratings given by individuals';
$string['pcast:viewanyrating']='View total ratings that anyone received';
$string['']='';

//showepisode.php
$string['episodeview'] = 'Episode';
$string['episodeviews'] = 'Views';
$string['episodecommentview'] = 'Comment';
$string['episoderateview'] = 'Rate';
$string['episodecommentandrateview'] = 'Comment / Rate';
$string['viewthisepisode']='Viewing an episode from: {$a}';

// Reset
$string['resetpcastsall']='Delete episodes from all podcasts';
$string['deletenotenrolled']='Delete episodes by users not enrolled';
$string['deleteallviews']='Delete episode view history';
