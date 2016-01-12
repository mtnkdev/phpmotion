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

$sql = mysql_query("SELECT profile_comments FROM features_settings");
$row = mysql_fetch_array($sql);
$enabled = $row['profile_comments'];

if($_POST['submitted'] == 'yes' && $enabled == 'yes'){

	$member_id = $_POST['member_id'];
	$profile_comment = $_POST['FCKeditor1'];

	if ($profile_comment == "") {
		echo '<p align="center"><font color="#FF4242" face="Arial"><b>'.$config['video_comments_error_comment'].'</b></font>';
		die();
	}

	if ($user_id == "") {
		echo '<p align="center"><font color="#FF4242" face="Arial"><b>'.$config['video_comments_login'].'</b></font>';
		die();
	}

	$user_id = mysql_real_escape_string($user_id);
	$member_id = mysql_real_escape_string($member_id);
	$profile_comment = mysql_real_escape_string($profile_comment);
	$comment_table = 'profilecomments';
	$id_name = 'members_id';

	$proceed = flood_check ( $user_id, $comment_table, $id_name, $member_id );
	if ( $proceed[0] == 'false' ) {
		echo $proceed[1];
		die();
	}

	$sql1 = "SELECT * FROM privacy WHERE user_id = $member_id AND profilecomments = 'no'";
	$result1 = @mysql_query($sql1);
	if(@mysql_num_rows($result1) != 0){
		echo '<p align="center"><font color="#FF4242" face="Arial"><b>'.$config['video_comments_not_allowed'].'</b></font>';
		die();
	}

	$sql = "INSERT into profilecomments (by_id, by_username, members_id, comments, todays_date) VALUES
                                  ($user_id, '$user_name', $member_id, '$profile_comment', NOW())";

	$query = @mysql_query($sql);

	if(!$query) {
		die("Error while during sql_query. Error Output: <br/>". mysql_errno() . ": " . mysql_error(). "<br/>"."Query follows:<br/>".$query);
		@mysql_close();
		die();
	}

	echo '<p align="center"><font color="#009933" face="Arial"><b>'.$config['video_comments_success'].'</b></font>';
	$comments = "";
	die();

} else {

	if ($enabled != 'no') {
		if ($user_id == "") {
			echo '<div style="margin-left:12px; text-align:left;"><font color="#FF0000" face="Arial"><b>'.$config['video_comments_login'].'</b></font></div>';
			die();
		} else {

			$member_id = $_GET['uid'];
		
			include('fckeditor/fckeditor.php');
    			$sBasePath 						= "$base_url/fckeditor/";
    			$oFCKeditor 					= new FCKeditor('FCKeditor1');
    			$oFCKeditor->BasePath 				= $sBasePath;
    			$oFCKeditor->CustomConfigurationsPath 	= "fckconfig.js";
    			$oFCKeditor->ToolbarSet 			= 'Basic';
    			$oFCKeditor->SkinPath 				= $sBasePath + 'editor/skins/silver/';
	    		$oFCKeditor->Value  				= "$profile_comment";
    			$oFCKeditor->Width  				= '690';
    			$oFCKeditor->Height 				= '220';
    			$FCKeditor1 					= $oFCKeditor->CreateHtml();
    			$my_edit 						= $FCKeditor1;
		
			echo '<br /><div align="center">
		        <form id="comments_reply" action="javascript:ahahscript.likeSubmit(\''.$base_url.'/memberprofile_comments_ajax.php\', \'post\', \'comments_reply\', \'MP_comments_axaj\');">
		          '.$my_edit.'
		          <input type="hidden" value="'.$member_id.'" name="member_id" />
		          <input type="hidden" value="yes" name="submitted" />
		          <br />
		          <input type="submit" value="Post Reply" name="B3" />
		        </form></div>
			<br />';
		}
	} else {
		echo '<div style="margin-left:12px; text-align:left;"><font color="#FF0000" face="Arial"><b>Disabled</b></font></div>';
		die();
	}
}


?>