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
 * Internal library of functions for module pcast
 *
 * All the pcast specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod_pcast
 * @copyright 2010 Stephen Bourget and Jillaine Beeckman
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 */
//function pcast_do_something_useful(array $things) {
//    return new stdClass();
//}


/*function set_id3($filename, $title = "", $author = "", $album = "", $year = "", $comment = "", $genre_id = 0) {
            $this->error = false;
            $this->wfh = fopen($this->file,"a");
            fseek($this->wfh, -128, SEEK_END);
            fwrite($this->wfh, pack("a3a30a30a30a4a30C1", "TAG", $title, $author, $album, $year, $comment, $genre_id), 128);
            fclose($this->wfh);
        }*/

/////////////////////////////////////////////////
//Get all id3 information and decode to an array
//Takes a single parameter a full local path to file as $filename
//returns array $id3
/////////////////////////////////////////////////
function pcast_get_id3($filename) {
    $filehandle = NULL;

    $id3_genres_array = array(
        'Blues', 'Classic Rock', 'Country', 'Dance', 'Disco', 'Funk', 'Grunge', 'Hip-Hop', 'Jazz', 'Metal', 'New Age', 'Oldies', 'Other', 'Pop', 'R&B', 'Rap', 'Reggae', 'Rock', 'Techno', 'Industrial',
        'Alternative', 'Ska', 'Death Metal', 'Pranks', 'Soundtrack', 'Euro-Techno', 'Ambient', 'Trip-Hop', 'Vocal', 'Jazz+Funk', 'Fusion', 'Trance', 'Classical', 'Instrumental', 'Acid', 'House',
        'Game', 'Sound Clip', 'Gospel', 'Noise', 'AlternRock', 'Bass', 'Soul', 'Punk', 'Space', 'Meditative', 'Instrumental Pop', 'Instrumental Rock', 'Ethnic', 'Gothic', 'Darkwave',
        'Techno-Industrial', 'Electronic', 'Pop-Folk', 'Eurodance', 'Dream', 'Southern Rock', 'Comedy', 'Cult', 'Gangsta', 'Top 40', 'Christian Rap', 'Pop/Funk', 'Jungle', 'Native American', 'Cabaret',
        'New Wave', 'Psychadelic', 'Rave', 'Showtunes', 'Trailer', 'Lo-Fi', 'Tribal', 'Acid Punk', 'Acid Jazz', 'Polka', 'Retro', 'Musical', 'Rock & Roll', 'Hard Rock', 'Folk', 'Folk/Rock', 'National Folk',
        'Swing', 'Fast Fusion', 'Bebob', 'Latin', 'Revival', 'Celtic', 'Bluegrass', 'Avantgarde', 'Gothic Rock', 'Progressive Rock', 'Psychedelic Rock', 'Symphonic Rock', 'Slow Rock', 'Big Band',
        'Chorus', 'Easy Listening', 'Acoustic', 'Humour', 'Speech', 'Chanson', 'Opera', 'Chamber Music', 'Sonata', 'Symphony', 'Booty Bass', 'Primus', 'Porn Groove', 'Satire', 'Slow Jam', 'Club', 'Tango', 'Samba',
        'Folklore', 'Ballad', 'Power Ballad', 'Rhythmic Soul', 'Freestyle', 'Duet', 'Punk Rock', 'Drum Solo', 'Acapella', 'Euro-house', 'Dance Hall'
        );

    $id3 = array();

    if (file_exists($filename)) {
        $filehandle = fopen($filename,"r");
    } else {
        return false;
    }
    fseek($filehandle, -128, SEEK_END);
    $line = fread($filehandle, 10000);
    if (preg_match("/^TAG/", $line)) {
        $id3 = unpack("a3tag/a30title/a30author/a30album/a4year/a30comment/C1genre_id", $line);
        $id3["genre"] = $id3_genres_array[$id3["genre_id"]];
        return $id3;
    } else {
        return false;
    }
    fclose($filehandle);
}

/////////////////////////////////////////////////
//Calculate mp3 file lendth in play time
//Used for <itunes:duration> tag
/////////////////////////////////////////////////
function pcast_calculate_length($size, $bitrate, $id3v2_tagsize = 0) {
    $length = floor(($size - $id3v2_tagsize) / $bitrate * 0.008);
    //Need to add hours here
    $min = floor($length / 60);
    $min = strlen($min) == 1 ? "0$min" : $min;
    $sec = $length % 60;
    $sec = strlen($sec) == 1 ? "0$sec" : $sec;
    return("$min:$sec");
}

/////////////////////////////////////////////////
//Get all mpeg audio header information and decode to an array
//Takes a single parameter a full local path to file as $filename
//returns array
/////////////////////////////////////////////////
function pcast_get_mp3_info($filename) {
    $filehandle = NULL;
    $info = array();
    $info = NULL;

    $info_bitrates = array(
        1    =>    array(
            1    =>    array( 0 => 0, 16 => 32, 32 => 64, 48 => 96, 64 => 128, 80 => 160, 96 => 192, 112 => 224, 128 => 256, 144 => 288, 160 => 320, 176 => 352, 192 => 384, 208 => 416, 224 => 448, 240 => false),
            2    =>    array( 0 => 0, 16 => 32, 32 => 48, 48 => 56, 64 =>  64, 80 =>  80, 96 =>  96, 112 => 112, 128 => 128, 144 => 160, 160 => 192, 176 => 224, 192 => 256, 208 => 320, 224 => 384, 240 => false),
            3    =>    array( 0 => 0, 16 => 32, 32 => 40, 48 => 48, 64 =>  56, 80 =>  64, 96 =>  80, 112 =>  96, 128 => 112, 144 => 128, 160 => 160, 176 => 192, 192 => 224, 208 => 256, 224 => 320, 240 => false)
            ),
        2    =>    array(
            1    =>    array( 0 => 0, 16 => 32, 32 => 48, 48 => 56, 64 =>  64, 80 => 80, 96 => 96, 112 => 112, 128 => 128, 144 => 144, 160 => 160, 176 => 176, 192 => 192, 208 => 224, 224 => 256, 240 => false),
            2    =>    array( 0 => 0, 16 =>  8, 32 => 16, 48 => 24, 64 =>  32, 80 => 40, 96 => 48, 112 =>  56, 128 =>  64, 144 =>  80, 160 =>  96, 176 => 112, 192 => 128, 208 => 144, 224 => 160, 240 => false),
            3    =>    array( 0 => 0, 16 =>  8, 32 => 16, 48 => 24, 64 =>  32, 80 => 40, 96 => 48, 112 =>  56, 128 =>  64, 144 =>  80, 160 =>  96, 176 => 112, 192 => 128, 208 => 144, 224 => 160, 240 => false)
            )
    );

    $info_versions = array(0 => "reserved", 1 => "MPEG Version 1", 2 => "MPEG Version 2", 2.5 => "MPEG Version 2.5");
    $info_layers = array("reserved", "Layer I", "Layer II", "Layer III");
    $info_sampling_rates = array(
        0        =>    array(0 => false, 4 => false, 8 => false, 12 => false),
        1        =>    array(0 => "44100 Hz", 4 => "48000 Hz", 8 => "32000 Hz", 12 => false),
        2        =>    array(0 => "22050 Hz", 4 => "24000 Hz", 8 => "16000 Hz", 12 => false),
        2.5    =>    array(0 => "11025 Hz", 4 => "12000 Hz", 8 => "8000 Hz", 12 => false)
        );
    $info_channel_modes = array(0 => "stereo", 64 => "joint stereo", 128 => "dual channel", 192 => "single channel");

    if (file_exists($filename)) {
        $filehandle = fopen($filename,"r");
    } else {
        return false;
    }

    $finished = false;
    rewind($filehandle);
    while (!$finished) {
        $skip = ord(fread($filehandle, 1));
        while ($skip != 255 && !feof($filehandle)) {
            $skip = ord(fread($filehandle, 1));
        }
        if (feof($filehandle)) {
            echo "no info header found";
        }
        $second = ord(fread($filehandle, 1));
      if ($second >= 225) {
          $finished = true;
        } else if (feof($filehandle)) {
            echo"no info header found";
        }
    }

   $third = ord(fread($filehandle, 1));
   $fourth = ord(fread($filehandle, 1));
   $info->version_id = ($second & 16) > 0 ? ( ($second & 8) > 0 ? 1 : 2 ) : ( ($second & 8) > 0 ? 0 : 2.5 );
   $info->version = $info_versions[ $info->version_id ];
   $info->layer_id = ($second & 4) > 0 ? ( ($second & 2) > 0 ? 1 : 2 ) : ( ($second & 2) > 0 ? 3 : 0 );     ;
   $info->layer = $info_layers[ $info->layer_id ];
   $info->protection = ($second & 1) > 0 ? "no CRC" : "CRC";
   $info->bitrate = $info_bitrates[ $info->version_id ][ $info->layer_id ][ ($third & 240) ];
   $info->sampling_rate = $info_sampling_rates[ $info->version_id ][ ($third & 12)];
   $info->padding = ($third & 2) > 0 ? "on" : "off";
   $info->private = ($third & 1) > 0 ? "on" : "off";
   $info->channel_mode = $info_channel_modes[$fourth & 192];
   $info->copyright = ($fourth & 8) > 0 ? "on" : "off";
   $info->original = ($fourth & 4) > 0 ? "on" : "off";
   $info->size = filesize($filename);
   $info->length = pcast_calculate_length($info->size,$info->bitrate, 0);
    fclose($filehandle);
    return $info;
}


/**
 * Get the complete file path based on the SHA1 hash
 *
 * @global object $CFG
 * @param object $filehash (This is the content hash)
 * @return path to file in dataroot, false on error
**/
function pcast_file_path_lookup ($filehash) {
    global $CFG;
    if (!empty($filehash)){
        $hash1 = substr($filehash, 0, 2);
        $hash2 = substr($filehash, 2, 2);
        $filepath = $CFG->dataroot . '/filedir/' . $hash1 .'/' .$hash2 . '/' . $filehash;
        return $filepath;

    } else {
        return false;
    }
}

/**
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_approval_menu($cm, $pcast,$mode, $hook, $sortkey = '', $sortorder = '') {

    echo '<div class="pcastexplain">' . get_string("explainalphabet","pcast") . '</div><br />';
    
    pcast_print_special_links($cm, $pcast, $mode, $hook);

    pcast_print_alphabet_links($cm, $pcast, $mode, $hook,$sortkey, $sortorder);

    pcast_print_all_links($cm, $pcast, $mode, $hook);

    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
}

/**
 * @param object $cm
 * @param object $pcast
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_alphabet_menu($cm, $pcast, $mode, $hook, $sortkey='', $sortorder = '') {

    echo '<div class="pcastexplain">' . get_string("explainalphabet","pcast") . '</div><br />';
    pcast_print_special_links($cm, $pcast, $mode, $hook);
    pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
    pcast_print_all_links($cm, $pcast, $mode, $hook);

}

function pcast_print_date_menu($cm, $pcast, $mode, $hook, $sortkey='', $sortorder = '') {
    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
}

/**
 * @param object $cm
 * @param object $pcast
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_author_menu($cm, $pcast,$mode, $hook, $sortkey = '', $sortorder = '') {

    echo '<div class="pcastexplain">' . get_string("explainalphabet","pcast") . '</div><br />';
    
    if(empty($sortkey)) {
        $sortkey = PCAST_AUTHOR_LNAME;
    }
    if(empty($sortorder)) {
        $sortkey = 'asc';
    }
    pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder);
    pcast_print_all_links($cm, $pcast, $mode, $hook);
    pcast_print_sorting_links($cm, $mode, $sortkey, $sortorder, $hook);
}

/**
 * @global object
 * @global object
 * @param object $cm
 * @param object $pcast
 * @param string $hook
 * @param object $category
 */
function pcast_print_categories_menu($cm, $pcast, $hook=PCAST_SHOW_ALL_CATEGORIES) {
     global $CFG, $DB, $OUTPUT;

     $context = get_context_instance(CONTEXT_MODULE, $cm->id);

     echo '<table border="0" width="100%">';
     echo '<tr>';

     echo '<td align="center" style="width:20%">';

     echo '</td>';

     echo '<td align="center" style="width:60%">';
     echo '<b>';

     $menu = array();
     $menu[PCAST_SHOW_ALL_CATEGORIES] = get_string("allcategories","pcast");
     $menu[PCAST_SHOW_NOT_CATEGORISED] = get_string("notcategorised","pcast");

    // Generate Top Categorys;
    if($topcategories = $DB->get_records("pcast_itunes_categories")) {
        foreach ($topcategories as $topcategory) {
            $value = (int)$topcategory->id * 1000;
            $menu[(int)$value] = $topcategory->name;
        }
    }

    // Generate Secondary Category
    if($nestedcategories = $DB->get_records("pcast_itunes_nested_cat")) {
        foreach ($nestedcategories as $nestedcategory) {
            $value = (int)$nestedcategory->topcategoryid * 1000;
            $value = $value + (int)$nestedcategory->id;
            $menu[(int)$value] = '&nbsp;&nbsp;' .$nestedcategory->name;
        }
    }
    ksort($menu);

    // Print the category name
    if ( $hook == PCAST_SHOW_NOT_CATEGORISED ) {
        echo get_string("episodeswithoutcategory","pcast");

    } else if ( $hook == PCAST_SHOW_ALL_CATEGORIES ) {
        echo get_string("allcategories","pcast");
    } else {
        // Lookup the category name by 4 digit ID
        $category->category = $hook;
        $category = pcast_get_itunes_categories($category, $pcast);
        
        // Print the category names in the format top: nested
        if($category->nestedcategory == 0) {
            echo $menu[(int)$hook];
        } else {
            //Todo: convert to lang file later
            echo $menu[(int)$category->topcategory*1000].': '.$menu[(int)$hook];
        }
    }
     
     echo '</b></td>';
     echo '<td align="center" style="width:20%">';

     $select = new single_select(new moodle_url("/mod/pcast/view.php", array('id'=>$cm->id, 'mode'=>PCAST_CATEGORY_VIEW)), 'hook', $menu, $hook, null, "catmenu");
     echo $OUTPUT->render($select);

     echo '</td>';
     echo '</tr>';

     echo '</table>';
}

/**
 * @global object
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 */
function pcast_print_all_links($cm, $pcast, $mode, $hook) {

    global $CFG;
    $strallentries       = get_string("allentries", "pcast");
    if ( $hook == 'ALL' ) {
      echo "<b>$strallentries</b>";
    } else {
      $strexplainall = strip_tags(get_string("explainall","pcast"));
      echo "<a title=\"$strexplainall\" href=\"$CFG->wwwroot/mod/pcast/view.php?id=$cm->id&amp;mode=$mode&amp;hook=ALL\">$strallentries</a>";
    }
     
}

/**
 * @global object
 * @param object $cm
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 */
function pcast_print_special_links($cm, $pcast, $mode, $hook) {
    
    global $CFG;

    $strspecial          = get_string("special", "pcast");
    if ( $hook == 'SPECIAL' ) {
      echo "<b>$strspecial</b> | ";
    } else {
      $strexplainspecial = strip_tags(get_string("explainspecial","pcast"));
      echo "<a title=\"$strexplainspecial\" href=\"$CFG->wwwroot/mod/pcast/view.php?id=$cm->id&amp;mode=$mode&amp;hook=SPECIAL\">$strspecial</a> | ";
    }
     
}

/**
 * @global object
 * @param object $pcast
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_alphabet_links($cm, $pcast, $mode, $hook, $sortkey, $sortorder) {
global $CFG;

      $alphabet = explode(",", get_string('alphabet', 'langconfig'));
      $letters_by_line = 26;
      for ($i = 0; $i < count($alphabet); $i++) {
          if ( $hook == $alphabet[$i] and $hook) {
               echo "<b>$alphabet[$i]</b>";
          } else {
               echo "<a href=\"$CFG->wwwroot/mod/pcast/view.php?id=$cm->id&amp;mode=$mode&amp;hook=".urlencode($alphabet[$i])."&amp;sortkey=$sortkey&amp;sortorder=$sortorder\">$alphabet[$i]</a>";
          }
          if ((int) ($i % $letters_by_line) != 0 or $i == 0) {
               echo ' | ';
          } else {
               echo '<br />';
          }
      }
     
}

/**
 * @global object
 * @param object $cm
 * @param string $mode
 * @param string $sortkey
 * @param string $sortorder
 */
function pcast_print_sorting_links($cm, $mode, $sortkey = '',$sortorder = '', $hook='') {
    global $CFG, $OUTPUT;
    
    //Get our strings
    $asc    = get_string("ascending","pcast");
    $desc   = get_string("descending","pcast");
    $strsortcreation = get_string("sortbycreation", "pcast");
    $strsortlastupdate = get_string("sortbylastupdate", "pcast");
    $strsortfname = get_string("firstname");;
    $strsortlname = get_string("lastname");
    $strsortby = get_string("sortby", "pcast");
    $strsep = get_string('labelsep', 'langconfig');


    // Determine our sort order ASC or DESC
    switch ($sortorder) {
        case 'desc':
            $currentorder = $desc;
            $neworder = '&amp;sortorder=asc';
            $strchangeto = get_string('changeto', 'pcast', $asc);
            $icon = " <img src=\"".$OUTPUT->pix_url($sortorder, 'pcast')."\" class=\"icon\" alt=\"$strchangeto\" />";

            break;

        case 'asc':
            $currentorder = $asc;
            $neworder = '&amp;sortorder=desc';
            $strchangeto = get_string('changeto', 'pcast', $desc);
            $icon = " <img src=\"".$OUTPUT->pix_url($sortorder, 'pcast')."\" class=\"icon\" alt=\"$strchangeto\" />";

            break;
        default:
            // Pick some reasonable defaults if sort order is not specified
            switch ($sortkey) {
                case PCAST_DATE_UPDATED:
                case PCAST_DATE_CREATED:
                case PCAST_AUTHOR_FNAME:
                case PCAST_AUTHOR_LNAME:                    
                    $strchangeto = get_string('changeto', 'pcast', $asc);
                    $neworder = '&amp;sortorder=asc';
                    $icon = ' <img src="'.$OUTPUT->pix_url('asc', 'pcast').'" class="icon" alt="'.$strchangeto.'" />';
                    $currentorder = '';
                    break;

                default:
                    $icon = "";
                    $neworder = '';
                    $currentorder = '';
                    $strchangeto = get_string('changeto', 'pcast', $asc);

                    break;
            }

        }        
        
    switch ($sortkey) {
        case PCAST_DATE_UPDATED:

            $html = '<span class="accesshide">';
            $html .= get_string('current', 'pcast', $strsortlastupdate .' ' . $currentorder).'</span>';
            $html .= $strsortby.$strsep;

            $url1 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_DATE_UPDATED.$neworder;
            $url2 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_DATE_CREATED;

            $link1 = '<a title = "'.$strsortlastupdate.' '.$strchangeto.'" href = "'.$url1.'" >'.$strsortlastupdate.$icon.' </a>';
            $link2 = '<a title = "'.$strsortcreation.' '.$asc.'" href = "'.$url2.'" >'.$strsortcreation.' </a>';
            $html .= $link1 . ' |<span class="pcast-bold">' . $link2. '</span> ';

            break;

        case PCAST_DATE_CREATED:
            
            $html = '<span class="accesshide">';
            $html .= get_string('current', 'pcast', $strsortcreation .' ' . $currentorder).'</span>';
            $html .= $strsortby.$strsep;

            $url1 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_DATE_UPDATED;
            $url2 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_DATE_CREATED.$neworder;

            $link1 = '<a title = "'.$strsortlastupdate.' '.$asc.'" href = "'.$url1.'" >'.$strsortlastupdate.' </a>';
            $link2 = '<a title = "'.$strsortcreation.' '.$strchangeto.'" href = "'.$url2.'" >'.$strsortcreation.$icon.' </a>';
            $html .= '<span class="pcast-bold">'. $link1 . '</span>  |' . $link2. '';

            break;

        case PCAST_AUTHOR_FNAME:

            $html = '<span class="accesshide">';
            $html .= get_string('current', 'pcast', $strsortlname .' ' . $currentorder).'</span>';
            $html .= $strsortby.$strsep;

            $url1 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_AUTHOR_LNAME;
            $url2 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_AUTHOR_FNAME.$neworder;

            $link1 = '<a title = "'.$strsortlname.' '.$asc.'" href = "'.$url1.'" >'.$strsortlname.' </a>';
            $link2 = '<a title = "'.$strsortfname.' '.$strchangeto.'" href = "'.$url2.'" >'.$strsortfname.$icon.' </a>';
            $html .= '<span class="pcast-bold">'. $link1 . '</span>  |' . $link2. '';


            break;

        case PCAST_AUTHOR_LNAME:

            $html = '<span class="accesshide">';
            $html .= get_string('current', 'pcast', $strsortfname .' ' . $currentorder).'</span>';
            $html .= $strsortby.$strsep;

            $url1 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_AUTHOR_LNAME.$neworder;
            $url2 = $CFG->wwwroot.'/mod/pcast/view.php?id='.$cm->id.'&amp;mode='.$mode.'&amp;hook='.$hook.'&amp;sortkey='.PCAST_AUTHOR_FNAME;

            $link1 = '<a title = "'.$strsortlname.' '.$strchangeto.'" href = "'.$url1.'" >'.$strsortlname.$icon.' </a>';
            $link2 = '<a title = "'.$strsortfname.' '.$asc.'" href = "'.$url2.'" >'.$strsortfname.' </a>';
            $html .= ''. $link1 . '  |<span class="pcast-bold">' . $link2. '</span>';
            break;
            
        default:

            $html ='';

    }

    // Display the links
    echo '<br />'. $html .'<br />';

}

/**
 *
 * @param object $entry0
 * @param object $entry1
 * @return int [-1 | 0 | 1]
 */
function pcast_sort_entries ( $entry0, $entry1 ) {

    if ( moodle_strtolower(ltrim($entry0->concept)) < moodle_strtolower(ltrim($entry1->concept)) ) {
        return -1;
    } elseif ( moodle_strtolower(ltrim($entry0->concept)) > moodle_strtolower(ltrim($entry1->concept)) ) {
        return 1;
    } else {
        return 0;
    }
}


/**
 *
 * @global object
 * @global object
 * @global object
 * @param int $courseid
 * @param array $entries
 * @param int $displayformat
 */
function pcast_print_dynaentry($courseid, $entries, $displayformat = -1) {
    global $USER,$CFG, $DB;

    echo '<div class="boxaligncenter">';
    echo '<table class="pcastpopup" cellspacing="0"><tr>';
    echo '<td>';
    if ( $entries ) {
        foreach ( $entries as $entry ) {
            if (! $pcast = $DB->get_record('pcast', array('id'=>$entry->pcastid))) {
                print_error('invalidid', 'pcast');
            }
            if (! $course = $DB->get_record('course', array('id'=>$pcast->course))) {
                print_error('coursemisconf');
            }
            if (!$cm = get_coursemodule_from_instance('pcast', $entry->pcastid, $pcast->course) ) {
                print_error('invalidid', 'pcast');
            }

            //If displayformat is present, override pcast->displayformat
            if ($displayformat < 0) {
                $dp = $pcast->displayformat;
            } else {
                $dp = $displayformat;
            }

            //Get popupformatname
            $format = $DB->get_record('pcast_formats', array('name'=>$dp));
            $displayformat = $format->popupformatname;

            //Check displayformat variable and set to default if necessary
            if (!$displayformat) {
                $displayformat = 'dictionary';
            }

            $formatfile = $CFG->dirroot.'/mod/pcast/formats/'.$displayformat.'/'.$displayformat.'_format.php';
            $functionname = 'pcast_show_entry_'.$displayformat;

            if (file_exists($formatfile)) {
                include_once($formatfile);
                if (function_exists($functionname)) {
                    $functionname($course, $cm, $pcast, $entry,'','','','');
                }
            }
        }
    }
    echo '</td>';
    echo '</tr></table></div>';
}

function pcast_display_standard_episodes($pcast, $cm, $hook='', $sortkey='', $sortorder='asc') {
    global $CFG, $DB, $USER;

    // Get the episodes for this pcast
    if(!empty($sortorder)) {
        $sort = 'p.name '. $sortorder;
    } else {
        $sort = 'p.name ASC';
    }
    $sql = pcast_get_episode_sql();
    $sql .=    " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";
        
    if(empty($hook) or ($hook == 'ALL')) {

        $sql .= " ORDER BY ". $sort;
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id));
    } else if($hook == 'SPECIAL') {
        // Match Other Characters
        $sql .= " AND (". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 )
                ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id,'1%','2%','3%','4%','5%','6%','7%','8%','9%','0%'));
    } else {
        $sql .= " and ". $DB->sql_like('p.name', '?',false)." ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id, $hook.'%'));
    }
    
    
    foreach ($episodes as $episode) {
        pcast_display_episode_brief($episode, $cm);
    }

    return true;
}

function pcast_display_category_episodes($pcast, $cm, $hook=PCAST_SHOW_ALL_CATEGORIES) {
    global $CFG, $DB;

    // Get the episodes for this pcast
    $sql = pcast_get_episode_sql();
    $sql .=    " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";
    
    if($hook == PCAST_SHOW_ALL_CATEGORIES) {
        $sql .= " ORDER BY cat.name, ncat.name, p.name ASC";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id));

    } else if ($hook == PCAST_SHOW_NOT_CATEGORISED) {
        $sql .= " AND
                p.topcategory = ?
                ORDER BY p.name ASC";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id,'0'));

    } else {
        $category->category = $hook;
        $category = pcast_get_itunes_categories($category, $pcast);
        if($category->nestedcategory == 0) {
            $sql .= " AND
                    p.topcategory = ?
                    ORDER BY cat.name, ncat.name, p.name ASC";
            $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id, $category->topcategory));

        } else {
            $sql .= " AND
                    p.nestedcategory = ?
                    ORDER BY cat.name, ncat.name, p.name ASC";
            $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id, $category->nestedcategory));

        }

    }

    // Print the episodes
    foreach ($episodes as $episode) {
        pcast_display_episode_brief($episode, $cm);
    }
}


function pcast_display_date_episodes($pcast, $cm, $hook, $sortkey=PCAST_DATE_CREATED, $sortorder='desc') {
        global $CFG, $DB, $USER;

    // Get the episodes for this pcast
   $sql = pcast_get_episode_sql();
   $sql .=    " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";


    switch ($sortkey) {
        case PCAST_DATE_UPDATED:
            $sql .= " ORDER BY p.timemodified";
            break;

        case PCAST_DATE_CREATED:
        default:
            $sql .= " ORDER BY p.timecreated";
            break;
    }

    switch ($sortorder) {
        case 'asc':
            $sql .= " ASC , p.name ASC";
            break;
        case 'desc':
        default:
            $sql .= " DESC, p.name ASC";
            break;
    }

    $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id));

    // Print the episodes
    foreach ($episodes as $episode) {
        pcast_display_episode_brief($episode, $cm);
    }
}

function pcast_display_author_episodes($pcast, $cm, $hook='', $sortkey='', $sortorder='asc') {
        global $CFG, $DB, $USER;

    // Get the episodes for this pcast
   $sql = pcast_get_episode_sql();
   $sql .=    " WHERE p.pcastid = ? AND (p.approved =? OR p.userid =? )";

   // Setup for ASC or DESC sorting
   switch ($sortorder) {
        case 'asc':
            $sort= "ASC";
            break;
        case 'desc':
        default:
            $sort= "DESC";
            break;
    }

    // Construct lookups based on FNAME / LNAME and HOOK
    switch ($sortkey) {
        case PCAST_AUTHOR_LNAME:
            // Order is constant for all LNAME sorts
            $order = " ORDER BY u.lastname " .$sort .", u.firstname " . $sort. ", p.name ASC" ;

            // Handle cases where you lookup by first letter of name (last / first)
            if(empty($hook) or ($hook == 'ALL')) {
                $sql .= $order;
                $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id));

            } else {
                $sql .= " and ". $DB->sql_like('u.lastname', '?',false) . $order;
                $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id, $hook.'%'));
            }
        
            break;

        case PCAST_AUTHOR_FNAME:
        default:
            // Order is constant for all FNAME sorts
            $order = " ORDER BY u.firstname " .$sort .", u.lastname " . $sort. ", p.name ASC" ;

            // Handle cases where you lookup by first letter of name (last / first)
            if(empty($hook) or ($hook == 'ALL')) {
                $sql .= $order;
                $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id));

            } else {
                $sql .= " and ". $DB->sql_like('u.firstname', '?',false) . $order;
                $episodes = $DB->get_records_sql($sql,array($pcast->id, '1', $USER->id, $hook.'%'));
            }

            break;
    }

    // Print the episodes
    foreach ($episodes as $episode) {
        pcast_display_episode_brief($episode, $cm);
    }
}


function pcast_display_approval_episodes($pcast, $cm, $hook='', $sortkey='', $sortorder='asc') {
    global $CFG, $DB, $USER;

    // Get the episodes for this pcast
    $sql = pcast_get_episode_sql();
    $sql .=    " WHERE p.pcastid = ? AND p.approved =?";

    if(!empty($sortorder)) {
        $sort = 'p.name '. $sortorder;
    } else {
        $sort = 'p.name ASC';
    }

    if(empty($hook) or ($hook == 'ALL')) {

        $sql .= " ORDER BY ". $sort;
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '0'));
    } else if($hook == 'SPECIAL') {
        // Match Other Characters
        $sql .= " AND (". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 OR ". $DB->sql_like('p.name', '?',false)."
                 )
                ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '0','1%','2%','3%','4%','5%','6%','7%','8%','9%','0%'));
    } else {
        $like = $DB->sql_ilike();
        $sql .= " and ". $DB->sql_like('p.name', '?',false)." ORDER BY $sort";
        $episodes = $DB->get_records_sql($sql,array($pcast->id, '0', $hook.'%'));
    }


    foreach ($episodes as $episode) {
        pcast_display_episode_brief($episode, $cm, $hook);
    }

    return true;
}


function pcast_get_episode_sql() {
       $sql = "SELECT p.id AS id,
                p.pcastid AS pcastid,
                p.course AS course,
                p.userid AS user,
                p.name AS name,
                p.summary AS summary,
                p.mediafile AS mediafile,
                p.duration AS duration,
                p.explicit AS explicit,
                p.subtitle AS subtitle,
                p.keywords AS keywords,
                p.topcategory as topcatid,
                p.nestedcategory as nestedcatid,
                p.timecreated as timecreated,
                p.timemodified as timemodified,
                p.approved as approved,
                p.sequencenumber as sequencenumber,
                pcast.userscancomment as userscancomment,
                pcast.userscancategorize as userscancategorize,
                pcast.userscanpost as userscanpost,
                pcast.requireapproval as requireapproval,
                pcast.displayauthor as displayauthor,
                pcast.displayviews as displayviews,
                pcast.assessed as assessed,
                pcast.assesstimestart as assesstimestart,
                pcast.assesstimefinish as assesstimefinish,
                pcast.scale as scale,
                cat.name as topcategory,
                ncat.name as nestedcategory,
                u.firstname as firstname,
                u.lastname as lastname
            FROM {pcast_episodes} AS p
            LEFT JOIN
                {pcast} AS pcast ON
                p.pcastid = pcast.id
            LEFT JOIN
                {user} AS u ON
                p.userid = u.id
            LEFT JOIN
                {pcast_itunes_categories} AS cat ON
                p.topcategory = cat.id
            LEFT JOIN
                {pcast_itunes_nested_cat} AS ncat ON
                p.nestedcategory = ncat.id";

    return $sql;
}

/**
 * Function to print overview of the episode
 * @global object $CFG
 * @param object $episode
 * @param object $cm
 * @param string $hook
 */
function pcast_display_episode_brief($episode, $cm, $hook ='ALL'){
    global $CFG, $DB;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $strsep = get_string('labelsep', 'langconfig');
    $html = '<div class="pcast-episode">'."\n";
    $html .= ''."\n";

    $table = new html_table();
    //$table->set_classes('views');
    $table->style = 'views';
    $table->cellpadding = '5';
    $table->colclasses = array('pcast-header','pcast-data');
    $table->width = '100%';
    $table->align = array ("RIGHT", "LEFT");
    // Name of episode
    $table->data[] = array (get_string("name","pcast"), $episode->name);
    // Description
    $table->data[] = array (get_string("summary","pcast"), $episode->summary);

    // Category -Display only if enabled
    if((isset($episode->userscancategorize))and ($episode->userscancategorize != '0')) {
        if((isset($episode->topcategory))and ($episode->topcategory != '0')) {
            $episode->category = $episode->topcategory;

            if((isset($episode->nestedcategory))and ($episode->nestedcategory != '0')) {
                $episode->category .= $strsep. ' '.$episode->nestedcategory;

            }
        }
        if(isset($episode->category)) {
            $table->data[] = array (get_string("category","pcast"), $episode->category);
        }
    }

    // Attachment
    $table->data[] = array (get_string("pcastmediafile","pcast"), pcast_display_mediafile_link($episode, $cm, true));
    
    // Author
    // TODO: Revisit this, Performance!
    // Only print author if allowed or has manage rights.
    if(((isset($episode->displayauthor))and ($episode->displayauthor != '0')) or (has_capability('mod/pcast:manage', $context))) {
        $user = $DB->get_record("user", array("id" => $episode->user));
        $table->data[] = array (get_string("author","pcast"), fullname($user));
//        $table->data[] = array (get_string("author","pcast"), $episode->lastname.', '. $episode->firstname);
    }
    
    // Created
    $table->data[] = array (get_string("created","pcast"), userdate($episode->timecreated));

    // Updated
    $table->data[] = array (get_string("updated","pcast"), userdate($episode->timemodified));

    //Calculate editing period
    $ineditingperiod = ((time() - $episode->timecreated <  $CFG->maxeditingtime));
    $link = '';

        // Management Links:
    if ((has_capability('mod/pcast:manage', $context)) or ($ineditingperiod)) {

        // Edit Link
        $link .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/edit.php?cmid='.$cm->id.'&id='.$episode->id.'">'.get_string('edit').'</a>';
        $link .= ' | '."\n";

        // Delete link
        $link .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/deleteepisode.php?id='.$cm->id.'&amp;episode='.$episode->id.'&amp;prevmode=0">'.get_string('delete').'</a>';
        $link .= ' | '."\n";

    }
        // View Link
        $link .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$episode->id.'">'.get_string('view').'</a>';


    // Approve Link
    if ((has_capability('mod/pcast:approve', $context)) and ($episode->requireapproval) and (!$episode->approved)) {
        $link .= ' | '."\n";
        $link .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/approveepisode.php?eid='.$episode->id.'&amp;mode='.PCAST_APPROVAL_VIEW.'&amp;hook='.$hook.'&amp;sesskey='.sesskey().'">'.get_string('approve').'</a>';
    }

    // Construct links
    $table->data[] = array ('',$link);


    
    echo $html;
    echo html_writer::table($table);
    echo '</div>'."\n";

}

function pcast_display_episode_full($episode, $cm, $course){
    global $CFG, $DB, $USER;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);


    $strsep = get_string('labelsep', 'langconfig');
    $html = '<div class="pcast-episode">'."\n";
    $html .= ''."\n";

    $table = new html_table();
    //$table->set_classes('views');
    $table->style = 'views';
    $table->cellpadding = '5';
    $table->colclasses = array('pcast-header','pcast-data');
    $table->width = '100%';
    $table->align = array ("RIGHT", "LEFT");
    // Name of episode
    $table->data[] = array (get_string("name","pcast"), $episode->name);
    // Description
    $table->data[] = array (get_string("summary","pcast"), $episode->summary);

    // Category -Display only if enabled
    if((isset($episode->userscancategorize))and ($episode->userscancategorize != '0')) {
        if((isset($episode->topcategory))and ($episode->topcategory != '0')) {
            $episode->category = $episode->topcategory;

            if((isset($episode->nestedcategory))and ($episode->nestedcategory != '0')) {
                $episode->category .= $strsep. ' '.$episode->nestedcategory;

            }
        }
        if(isset($episode->category)) {
            $table->data[] = array (get_string("category","pcast"), $episode->category);
        }
    }

    // Attachment
    $table->data[] = array (get_string("pcastmediafile","pcast"), pcast_display_mediafile_link($episode, $cm, false));

    // Duration
    if(!empty($episode->duration)) {
        // Split up duration for printing
        $length = explode(":", $episode->duration);
        $length2->min = $length[0];
        $length2->sec = $length[1];
    } else {
        $length2->min = 0;
        $length2->sec = 0;
    }

    $table->data[] = array (get_string("duration","pcast"), get_string("durationlength","pcast",$length2));


    // Author
    // TODO: Revisit this, Performance!
    // Only print author if allowed or has manage rights.
    if(((isset($episode->displayauthor))and ($episode->displayauthor != '0')) or (has_capability('mod/pcast:manage', $context))) {
        $user = $DB->get_record("user", array("id" => $episode->user));
        $table->data[] = array (get_string("author","pcast"), fullname($user));
//        $table->data[] = array (get_string("author","pcast"), $episode->lastname.', '. $episode->firstname);
    }

    // Created
    $table->data[] = array (get_string("created","pcast"), userdate($episode->timecreated));

    // Updated
    $table->data[] = array (get_string("updated","pcast"), userdate($episode->timemodified));

    // Total views
    $table->data[] = array (get_string("totalviews","pcast"), pcast_get_episode_view_count($episode));

    // Total comments
    if(($CFG->usecomments) and ($episode->userscancomment) and (has_capability('moodle/comment:view', $context))) {
        $table->data[] = array (get_string("totalcomments","pcast"), pcast_get_episode_comment_count($episode, $cm));
    }

    // Total Ratings
    if(($episode->assessed) and ((has_capability('moodle/rating:view', $context)) and ($episode->user == $USER->id)) or (has_capability('moodle/rating:viewany', $context))) {
        $table->data[] = array (get_string("totalratings","pcast"), pcast_get_episode_rating_count($episode, $cm));
    }

    //Calculate editing period
    $ineditingperiod = ((time() - $episode->timecreated <  $CFG->maxeditingtime));
    $manage = '';
    $approve = '';

        // Management Links:
    if ((has_capability('mod/pcast:manage', $context)) or ($ineditingperiod)) {

        // Edit Link
        $manage .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/edit.php?cmid='.$cm->id.'&id='.$episode->id.'">'.get_string('edit').'</a>';
        $manage .= ' | '."\n";

        // Delete link
        $manage .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/deleteepisode.php?id='.$cm->id.'&amp;episode='.$episode->id.'&amp;prevmode=0">'.get_string('delete').'</a>';

    }


    // Approve Link
    if ((has_capability('mod/pcast:approve', $context)) and ($episode->requireapproval) and (!$episode->approved)) {

        $approve .= '<a href = "'.$CFG->wwwroot.'/mod/pcast/approveepisode.php?eid='.$episode->id.'&amp;mode='.PCAST_APPROVAL_VIEW.'&amp;sesskey='.sesskey().'">'.get_string('approve').'</a>';
    }

    // Construct links
    if((!empty($manage)) and (!empty($approve))) {
        $link = $manage . ' | '."\n" . $approve;
    } else {
        $link = $manage . $approve;
    }
    $table->data[] = array ('',$link);

    echo $html;
    echo html_writer::table($table);
    echo '</div>'."\n";

}

function pcast_display_episode_views($episode){

    global $DB;

    if (!$views = $DB->get_records("pcast_views", array( "episodeid" => $episode->id))) {
        echo get_string('noviews','pcast',get_string('modulename','pcast'));
    } else {
        $timenow = time();
        $strviews  = get_string("views","pcast");
        $struser = get_string("user","pcast");
        $strdate = get_string("date");

        $table = new html_table();
        $table->attributes['class'] = 'views';
        $table->head  = array ($struser, $strdate, $strviews);
        $table->align = array ("CENTER", "LEFT", "CENTER");
        $table->width = '100%';

        foreach ($views as $view) {
            $user = $DB->get_record("user", array("id" => $view->userid));
            $linedata = array (fullname($user), userdate($view->lastview), $view->views);
            $table->data[] = $linedata;
        }
        echo "<br />";
        echo html_writer::table($table);
    }
}

function pcast_display_episode_comments($episode, $cm, $course) {

    global $CFG;
    $html = '';

    if($episode->userscancomment) {
        //Get episode comments and display the comment box
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $output = true;

        // Generate comment box using API
        if (!empty($CFG->usecomments)) {
            require_once($CFG->dirroot . '/comment/lib.php');
            $cmt = new stdClass();
            $cmt->component = 'pcast';
            $cmt->context  = $context;
            $cmt->course   = $course;
            $cmt->cm       = $cm;
            $cmt->area     = 'pcast_episode';
            $cmt->itemid   = $episode->id;
            $cmt->showcount = true;
            $comment = new comment($cmt);
            $html = html_writer::tag('div',$comment->output(true), array('class'=> 'pcast-comments'));

        }
    }
    
    echo $html;

}

function pcast_display_episode_ratings($episode, $cm, $course) {

    global $CFG, $USER, $DB, $OUTPUT;

    //TODO: Rating API is currently broken in MDL 2.0 Preview 4 (MDL-23187)
    $sql = pcast_get_episode_sql();
    $sql .=  " WHERE p.id = ?";
    $episodes = $DB->get_records_sql($sql,array('id'=>$episode->id));

    // load ratings
    require_once($CFG->dirroot.'/rating/lib.php');
    if ($episode->assessed!=RATING_AGGREGATE_NONE) {
        
        $ratingoptions = new stdClass();
        $ratingoptions->plugintype = 'mod';
        $ratingoptions->pluginname = 'pcast';
        $ratingoptions->context = $cm->context;
        $ratingoptions->items = $episodes;
        $ratingoptions->aggregate = $episode->assessed;//the aggregation method
        $ratingoptions->scaleid = $episode->scale;
        $ratingoptions->userid = $USER->id;
        $ratingoptions->returnurl = $CFG->wwwroot.'/mod/pcast/showepisode.php?eid='.$episode->id.'&amp;mode='.PCAST_EPISODE_COMMENT_AND_RATE;
        $ratingoptions->assesstimestart = $episode->assesstimestart;
        $ratingoptions->assesstimefinish = $episode->assesstimefinish;

        $rm = new rating_manager();
        $allepisodes = $rm->get_ratings($ratingoptions);
    }
    foreach ($allepisodes as $thisepisode)
    {
        if(!empty($thisepisode->rating)) {
            echo html_writer::tag('div',$OUTPUT->render($thisepisode->rating), array('class'=> 'pcast-episode-rating'));
        }
    }

}

/**
 * @global object
 * @global object
 * @global object
 * @param object $course
 * @param object $episode
 * @return bool
 */
function  pcast_print_episode_ratings($course, $episode) {
    global $OUTPUT;
    pcast_debug_object($episode);
    if( !empty($episode->rating) ){
        echo $OUTPUT->render($episode->rating);
    }
}


function pcast_get_episode_view_count($episode) {
    global $DB;
    $count = 0;
    // Get all views
    if (!$views = $DB->get_records("pcast_views", array( "episodeid" => $episode->id))) {
        // No views
        return $count;
    } else {
        foreach ($views as $view) {
            // Total up the views
            $count += (int)$view->views;
        }
    }
    return $count;
}

/**
 * Get the total number of comments for a specific episode
 * @global object $CFG
 * @global object $DB
 * @param object $episode
 * @param object $cm
 * @return string
 */
function pcast_get_episode_comment_count($episode, $cm) {
    global $CFG, $DB;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if($count = $DB->count_records('comments',array('itemid'=>$episode->id, 'commentarea'=>'pcast_episode', 'contextid'=>$context->id))) {
        return $count;
    } else {
        return 0;
    }
}

/**
 * Get the total number of ratings for a specific episode
 * @global object $CFG
 * @global object $DB
 * @param object $episode
 * @param object $cm
 * @return string
 */
function pcast_get_episode_rating_count($episode, $cm) {

    global $CFG, $DB;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if($count = $DB->count_records('rating',array('itemid'=>$episode->id, 'scaleid'=>$episode->scale, 'contextid'=>$context->id))) {
        return $count;
    } else {
        return 0;
    }
}

function pcast_debug_object($object, $color='red') {
    echo '<pre><font color="'.$color.'">';
    print_r($object);
    echo '</font></pre>';
}

/**
 * Display the small Moodle Media Filter for MP3 File
 * @global global $CFG
 * @global global $OUTPUT
 * @global global $PAGE
 * @param string $fullurl
 * @param string $mimetype
 * @return string
 */
function pcast_mediaplugin_simple($fullurl, $mimetype) {
    global $CFG, $OUTPUT, $PAGE;
    $code = '';
    if ($mimetype == 'audio/mp3') {

    $c = $OUTPUT->resource_mp3player_colors();   // You can set this up in your theme/xxx/config.php
    $colors = explode('&', $c);
    $playercolors = array();
    foreach ($colors as $color) {
        $color = explode('=', $color);
        $playercolors[$color[0]] = $color[1];
    }

    $id = 'filter_mp3_'.time(); //we need something unique because it might be stored in text cache

    $playerpath = $CFG->wwwroot .'/filter/mediaplugin/mp3player.swf';
    $audioplayerpath = $CFG->wwwroot .'/filter/mediaplugin/flowplayer.audio.swf';

    $code = <<<OET
<div class="pcast_mp3">
  <span class="pcastmediaplugin pcastplugin_mp3" id="$id"></span>
  <div>
    <object width="251" height="25" id="nonjsmp3plugin" name="undefined" data="$playerpath" type="application/x-shockwave-flash">
    <param name="movie" value="$playerpath" />
    <param name="allowfullscreen" value="false" />
    <param name="allowscriptaccess" value="always" />
    <param name="flashvars" value='config={"plugins": {"controls": {
                                                            "fullscreen": false,
                                                            "height": 25,
                                                            "autoHide": false
                                                            }
                                                      },
                                           "clip":{"url":"$fullurl",
                                                   "autoPlay": false},
                                           "content":{"url":"$playerpath"}}}' />
    </object>
  </div>
</div>
OET;
    }
    return $code;
}

/**
 * Display the Moodle Media Filter for MP3 / Video File
 *
 * @global object $CFG
 * @param object $url
 * @param object $type (mime type)
 * @return nothing
**/

function pcast_mediaplugin_filter($fullurl, $mimetype, $audioonly=false) {
    global $CFG;
    require_once("$CFG->libdir/resourcelib.php");

    $title=get_string('episodetitle','pcast',get_string('modulename','pcast'));
    $clicktoopen=get_string('viewepisode','pcast',get_string('modulename','pcast'));
    $code = '';

    if($audioonly) {
        // For some reason the regular JS media player will only render once
        $code = pcast_mediaplugin_simple($fullurl, $mimetype);
    } else {
        //Display full player since there will only be one player instance
        if ($mimetype == 'audio/mp3') {
            // MP3 audio file
             $code = resourcelib_embed_mp3($fullurl, $title, $clicktoopen);
        }

        if ($mimetype == 'video/x-flv') {
            // Flash video file
            $code = resourcelib_embed_flashvideo($fullurl, $title, $clicktoopen);

        } else if (substr($mimetype, 0, 10) == 'video/x-ms') {
            // Windows Media Player file
            $code = resourcelib_embed_mediaplayer($fullurl, $title, $clicktoopen);

        } else if ($mimetype == 'video/quicktime') {
            // Quicktime file
            $code = resourcelib_embed_quicktime($fullurl, $title, $clicktoopen);

        } else if ($mimetype == 'video/mpeg') {
            // Mpeg file
            $code = resourcelib_embed_mpeg($fullurl, $title, $clicktoopen);

        } else if ($mimetype == 'audio/x-pn-realaudio') {
            // RealMedia file
            $code = resourcelib_embed_real($fullurl, $title, $clicktoopen);

        }
    }

    return $code;
}

/**
 * Print the podcast attachment and the media player if appropriate
 *
 * @global object $CFG
 * @global object $DB
 * @global object $OUTPUT
 * @param object $episode
 * @param object $cm
 * @return string image string or nothing depending on $type param
 */

function pcast_display_mediafile_link($episode, $cm,$audioonly=false) {

    global $CFG, $DB, $OUTPUT;


    if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
        return '';
    }

    $fs = get_file_storage();

    $imagereturn = '';

    if ($files = $fs->get_area_files($context->id, 'mod_pcast','episode', $episode->id, "timemodified", false)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
            $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_pcast/episode/'.$episode->id.'/'.$filename);
        }
    }
    
    $templink = get_string('nopcastmediafile','pcast');
    // Make sure there is actually an attachment before trying to render the file link and player
    if(!empty($filename)) {

        //Add Media player, uses the one from filter/mediafilter
        if(($CFG->pcast_usemediafilter)) {
            $templink = '<a href="'.$path.'">'.$iconimage.$filename.'</a>';
            $templink .= "</br />\n";
            //TODO: Replace this code with the embed code used in resourcelib
            // (See mod/resource, function resource_display_embed()
            $templink .= pcast_mediaplugin_filter($path,$mimetype,$audioonly);
        } else {
            $templink = '<a href="'.$path.'">'.$iconimage.$filename.'</a>';
        }
    }
    return $templink;
}


?>