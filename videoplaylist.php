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

if ($user_id == '') {
	echo '<p align="center"><font color="#FF4242" face="Arial" size="2"><b>'.$config["must_login"].'</b></font>';
	die();
}

$blk_notification = '';
$show_create_new = 1;
$show_main_list_actions = 1;

$sql = "SELECT * FROM video_playlist WHERE user_id = $user_id ORDER BY list_name ASC";
if (mysql_num_rows(mysql_query($sql)) == 0) {
	$show_main_list_actions = '';
	$show_main_list_table = '';
	$blk_notification = 1;
	$message_type = $config['word_notice'];
	$error_message = $config['Playlists_none'];
} else {
	$query = mysql_query($sql);
	while ($result = mysql_fetch_array($query)) {
    		$shortened_name = ShortenText($result['list_name']);
     		$pulldown_list = $pulldown_list.'<option value="'.$result['list_id'].'">'.$shortened_name.'</option>';
     	}
}

if ($_POST['submitted_new_list'] == 'yes') {
	$blk_notification = 0;
	$list_name = mysql_real_escape_string($_POST['list_name']);

	if ($list_name == '') {
		$blk_notification = 1;
        	$message_type = $config['notification_error'];
        	$error_message = $config['fill_all_fields'];
        	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
		$TBS = new clsTinyButStrong;
		$TBS->NoErr = true;
		$TBS->LoadTemplate("$template");
		$TBS->Render = TBS_OUTPUT;
		$TBS->tbs_show();
		die();
	}

	$sql = "SELECT * FROM video_playlist WHERE user_id = $user_id AND list_name = '$list_name'";
    	if (mysql_num_rows(mysql_query($sql)) > 0) {
      	$blk_notification = 1;
        	$message_type = $config['notification_error'];
        	$error_message = $config['Playlists_duplicate'];
        	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
		$TBS = new clsTinyButStrong;
		$TBS->NoErr = true;
		$TBS->LoadTemplate("$template");
		$TBS->Render = TBS_OUTPUT;
		$TBS->tbs_show();
		die();
	}

    	$sql = "INSERT INTO video_playlist (list_name, user_id) VALUES ('$list_name', $user_id)";
    	mysql_query($sql);

    	$sql = "SELECT * FROM video_playlist WHERE user_id = $user_id AND list_name = '$list_name'";
    	if (mysql_num_rows(mysql_query($sql)) == 0) {
      	$blk_notification = 1;
        	$message_type = $config['notification_error'];
        	$error_message = $config['error_26'];
        	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
		$TBS = new clsTinyButStrong;
		$TBS->NoErr = true;
		$TBS->LoadTemplate("$template");
		$TBS->Render = TBS_OUTPUT;
		$TBS->tbs_show();
		die();
	} else {
      	$blk_notification = 1;
        	$message_type = $config['notification_success'];
        	$error_message = $config['error_25'];
        	$show_main_list_actions = 1;

        	$pulldown_list ='';
        	$sql = "SELECT * FROM video_playlist WHERE user_id = $user_id ORDER BY list_name ASC";
        	$query = mysql_query($sql);
    		while ($result = mysql_fetch_array($query)) {
      		$shortened_name = ShortenText($result['list_name']);
        		$pulldown_list = $pulldown_list.'<option value="'.$result['list_id'].'">'.$shortened_name.'</option>';
        	}
        	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
		$TBS = new clsTinyButStrong;
		$TBS->NoErr = true;
		$TBS->LoadTemplate("$template");
		$TBS->Render = TBS_OUTPUT;
		$TBS->tbs_show();
		die();
        }
}

//POST ACTIONS ON PLAY LISTS
if ($_POST['submitted_playlist_action'] == 'yes') {
	$playlists_id = mysql_real_escape_string($_POST['my_playlists']);

	if ($_POST['selected_box'] == 'delete') {
		$sql = "DELETE FROM video_playlist WHERE list_id = $playlists_id AND user_id  = $user_id";
		mysql_query($sql);
		$sql = "DELETE FROM video_playlist_lists WHERE list_id = $playlists_id AND user_id  = $user_id";
		mysql_query($sql);
		$blk_notification = 1;
        	$message_type = $config['notification_success'];
        	$error_message = $config['error_25'];
        	$show_main_list_actions = 1;

        	$pulldown_list ='';
        	$sql = "SELECT * FROM video_playlist WHERE user_id = $user_id ORDER BY list_name ASC";
        	$query = mysql_query($sql);
    		while ($result = mysql_fetch_array($query)) {
      		$shortened_name = ShortenText($result['list_name']);
        		$pulldown_list = $pulldown_list.'<option value="'.$result['list_id'].'">'.$shortened_name.'</option>';
        	}
	}

	//display all videos
	if ($_POST['selected_box'] == 'show' || $_POST['selected_box'] == 'delete'){
		$show_main_list_table = 1;
		$result = array();
		$sql = "SELECT * FROM video_playlist_lists WHERE list_id = $playlists_id AND user_id  = $user_id";
		$query = mysql_query($sql);

		while ($result1 = mysql_fetch_array($query)){
			$play_list_video_id = $result1['video_id'];
			$sql2 = "SELECT * FROM videos WHERE indexer = $play_list_video_id";
			$query2 = mysql_query($sql2);
			$results2 = mysql_fetch_array($query2);
			$merged_array = array_merge($results2, $result1);
			$result[] = $merged_array;
		}

		//get list title
		$sql = "SELECT * FROM video_playlist WHERE list_id = $playlists_id";
		$query = mysql_query($sql);
		$result3 = mysql_fetch_array($query);
		$shortened_name = ShortenText($result3['list_name']);

		if (empty($result))
			$show = 2;
		else
			$show = 1;

		$show_title = 1;

		if ($_POST['selected_box'] == 'delete'){
			$show_title = '';
			$show = '';
		}
		$template = "themes/$user_theme/templates/inner_playlist_main.htm";
		$TBS = new clsTinyButStrong;
    		$TBS->NoErr = true;
    		$TBS->LoadTemplate("$template");
    		$TBS->MergeBlock('blk1',$result);
    		$TBS->Render = TBS_OUTPUT;
    		$TBS->tbs_show();
    		die();
	}
	if ($_POST['selected_box'] == 'play') {
		$sql = "SELECT * FROM video_playlist_lists WHERE list_id = $playlists_id AND user_id  = $user_id";
		$query = mysql_query($sql);
		if(mysql_num_rows($query) < 0) {
			$show = 2;
			$show_player = '';
		} else {
			$count_videos = mysql_num_rows($query);
			$show_player = 1;
			$show = 0;
			$show_title = 2;
			$show_create_new = 0;
		}
	}
}

//GET ACTIONS ON PLAY LISTS
if ($_GET['act']=='del' && $_GET['vid'] != '' && $_GET['id'] != ''){
	$playlists_id = mysql_real_escape_string($_GET['id']);
	$del_vid = mysql_real_escape_string($_GET['vid']);
	$sql = "DELETE FROM video_playlist_lists WHERE list_id = $playlists_id AND video_id = $del_vid AND user_id = $user_id";
	mysql_query($sql);
      $blk_notification = 1;
      $message_type = $config['notification_success'];
      $error_message = $config['error_25'];
      $show_main_list_actions = 1;
	$show_main_list_table = 1;
	$result =array();
	$sql = "SELECT * FROM video_playlist_lists WHERE list_id = $playlists_id AND user_id  = $user_id";
	$query = mysql_query($sql);
	while ($result1 = mysql_fetch_array($query)){
		$play_list_video_id = $result1['video_id'];
		$sql2 = "SELECT * FROM videos WHERE indexer = $play_list_video_id";
		$query2 = mysql_query($sql2);
		$results2 = mysql_fetch_array($query2);
		$merged_array = array_merge($results2, $result1);
		$result[] = $merged_array;
	}
	if (empty($result)){
		$show = 2;
	}else{
	$show = 1;
	}
	$show_title = 1;
	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
	$TBS = new clsTinyButStrong;
	$TBS->NoErr = true;
	$TBS->LoadTemplate("$template");
	$TBS->MergeBlock('blk1',$result);
	$TBS->Render = TBS_OUTPUT;
	$TBS->tbs_show();
	die();
}

//default page
if ( !isset($_POST['submit']) ) {
	$template = "themes/$user_theme/templates/inner_playlist_main.htm";
	$TBS = new clsTinyButStrong;
	$TBS->NoErr = true;
	$TBS->LoadTemplate("$template");
	$TBS->Render = TBS_OUTPUT;
	$TBS->tbs_show();
	die();
}

function ShortenText($text) {
	$chars = 60;
	if (strlen($text) > $chars){
		$dot_dot = '...';
	}else{
		$dot_dot = '';
	}
	$text = $text." ";
	$text = substr($text,0,$chars);
	$text = substr($text,0,strrpos($text,' '));
	$text = $text.$dot_dot;
	return $text;
}

?>