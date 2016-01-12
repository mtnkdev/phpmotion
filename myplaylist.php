<?php

///////////////////////////////////////////////////////////////////////////////////////
// PHPmotion                                                http://www.phpmotion.com //
///////////////////////////////////////////////////////////////////////////////////////
// License: You are not to sell or distribute this software without permission       //
// Help and support please visit http://www.phpmotion.com                            //
// Copyright reserved                                                                //
///////////////////////////////////////////////////////////////////////////////////////

include_once ('classes/config.php');
include_once ('classes/sessions.php');

if ($user_id == "") {
    echo '<p align="center"><font color="#FF4242" face="Arial" size="2"><b>'.$config["must_login"].
        '</b></font>';
    die();
}

$playlists_id = mysql_real_escape_string($_GET['id']);

$sql = "SELECT * FROM video_playlist WHERE list_id = $playlists_id AND user_id = $user_id";
if (mysql_num_rows(mysql_query($sql)) == 0){
	echo '<p align="center"><font color="#FF4242" face="Arial" size="2"><b>'.$config["error_26"].'</b></font>';	//error
	die();
}

header("Content-Type: text/xml; charset=UTF-8");
header("Expires: 0");
print "<?xml version=\"1.0\"?>\n";
print "<playlist>\n";


$sql 		= "SELECT * FROM video_playlist_lists WHERE list_id = $playlists_id AND user_id  = $user_id";
$query	= mysql_query($sql);
while ($result = mysql_fetch_array($query)) {
	$play_list_video_id = $result['video_id'];

	$sql2			= "SELECT * FROM videos WHERE approved = 'yes' AND video_type = 'uploaded' AND indexer = $play_list_video_id";
	$query2 		= mysql_query($sql2);
	$result2 		= mysql_fetch_array($query2);


	$title		= CleanTitle($result2['title']);
    	$title_seo 		= CleanTitle($result2['title_seo']);
    	$vid 			= $result2['indexer'];
    	$file_name 		= $result2['video_id'];
    	$duration		= $result2['video_length'];

    	$external_url 	= $config['site_base_url'].'/videos/'.$vid.'/'.$title_seo;
    	$source_url 	= $config['site_base_url'].'/uploads/'.$file_name.'.flv';
    	$thumb_path 	= $base_path.'/uploads/player_thumbs/'.$file_name.'.jpg';
    	$source_path 	= $base_path.'/uploads/'.$file_name.'.flv';

    	if(file_exists($thumb_path)) {

    		$thumb_url = $config['site_base_url'].'/uploads/player_thumbs/'.$file_name.'.jpg';

    	} else {

    		$thumb_url = $config['site_base_url'].'/uploads/thumbs/'.$file_name.'.jpg';
        	$thumb_path = $base_path.'/uploads/thumbs/'.$file_name.'.jpg';
    	}

   	if(file_exists($source_path) && file_exists($thumb_path)) {
      	print "<video>\n";
        	print "<title>$title</title>\n";
        	print "<source>$source_url</source>\n";
        	print "<thumb>$thumb_url</thumb>\n";
        	print "<external_url>$external_url</external_url>\n";
        	print "<duration>$duration</duration>\n";
        	print "</video>\n";
    	}
}

print "</playlist>";

function CleanTitle($txt = '') {
	$txt = trim($txt);
    	$txt = str_replace('"',"",$txt);
    	$txt = str_replace("'","",$txt);
    	$txt = str_replace('?','',$txt);
    	$txt = str_replace('&','',$txt);
    	return $txt;
}

?>