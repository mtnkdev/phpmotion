<?php

//Revised Aug 5, 2010
///////////////////////////////////////////////////////////////////////////////////////
// PHPmotion                                                http://www.phpmotion.com //
///////////////////////////////////////////////////////////////////////////////////////
// License: You are not to sell or distribute this software without permission       //
// Help and support please visit http://www.phpmotion.com                            //
// Copyright All Rights Reserved                                                     //
///////////////////////////////////////////////////////////////////////////////////////

//______PHPmotion Admin Debug_______________________________________________
//An email is used here if we (PHPmotion staff) need to debug for you
//If you are using v3.0, you can also add your email here to receive notifications
//of any failed video conversions
$temp_sys_admin_email = '';

//______Various Includes___________________________
include_once ('classes/config.php');
include_once ('classes/sessions.php');

//______Various Includes___________________________
$convert_date        = date("D - M d, Y @ h:i A");
$log_file            = 'logs/convertor_log.txt';
$base_path           = installation_paths();

//______load dynamic convertor settings___________
$aspect              = $config['convertor_aspect_ratio'];
$frame_rate          = $config['convertor_frame_rate'];
$watermark           = $config['video_watermark'];
$watermark_image     = $config['video_watermark_image'];
$watermark_location  = $config['video_watermark_place'];
$resize              = $config['video_resize'];
$num_pass            = $config['video_convert_pass'];
$ffmpeg_size         = $config['video_ffmpeg_size'];
$bit_rate            = $config['video_ffmpeg_bit_rate'];
$ffmpeg_audio_rate   = $config['video_ffmpeg_audio_rate'];
$qmax                = $config['video_ffmpeg_qmax'];
$hq_on               = $config['video_ffmpeg_high_quality'];
$hq_setting          = $config['video_ffmpeg_hq'];
$hq_size             = $config['video_ffmpeg_hq_size'];
$vbitrate            = $config['video_mencoder_vbitrate'];
$scale               = $config['video_mencoder_scale'];
$srate               = $config['video_mencoder_srate'];
$mencoder_audio_rate = $config['video_mencoder_audio_rate'];



//__________Admins email address for report________________
$sql			= "SELECT email_address FROM member_profile WHERE user_group = 'admin' LIMIT 1";
$query 		= mysql_query($sql);
$result 		= mysql_fetch_array($query);
$admin_email	= $result[0];


//__________backward compatability (v3.0)________________
if($phpmotion_main_version == 3) {
	$frame_rate = 29.97;
	$aspect = '4:3';
}

//______________________________________________________________________________________________________________
//____________________________________Process Each Video (the big loop)_________________________________________

//___Loop through pending____________
$sql = "SELECT video_id FROM videos where approved = 'pending_conversion' LIMIT 1";
$query = @mysql_query($sql);

while($result = @mysql_fetch_array($query)) {

	$raw_video = $result['video_id'];
    	$raw_video_path = $base_path.'/uploads/avi/'.$raw_video;
    	list($file_name_no_extension,$extension) = @split('\.',$raw_video);
    	$extension = strtolower($extension);
    	$new_flv = $base_path.'/uploads/'.$file_name_no_extension.'.mp4';

    	//___Set video to converting_________
    	@mysql_query("UPDATE videos SET approved = 'converting' WHERE video_id = '$raw_video'");

    	//___Log start of conversion___________
      $output = array('0'=>"\n"."Converting ($raw_video) started - $convert_date"."\n");
      capture_output($output);

	//________________Set converting params for video type_______________________
        $ffmpeg_convert_opts        = "-y -i $raw_video_path -vcodec libx264 -ab 64k -ar $ffmpeg_audio_rate -b $bit_rate -r $frame_rate -nr 1000 -g 500 -qmax $qmax";
        $ffmpeg_convert_opts        = "-y -i $raw_video_path -vcodec libx264 ";
        if ($resize=='yes') {
               $ffmpeg_convert_opts        = "$ffmpeg_convert_opts -vf scale=\"$ffmpeg_size:trunc(ow/a/2)*2\" ";
        }

    	switch($extension) {

            //flv videos
            case 'flv':
            	$flv = true;
                break;

            //all other videos
            default:
            	$default_type 	= true;
       		$cmd = "$config[path_to_ffmpeg] $ffmpeg_convert_opts $new_flv";

            break;

	}// end switch extension check for now


	//______________________________________________________________________
    	//___CHECK IF VIDEO IS FLV______________________________________________

	if($flv) {
                print_r("Got an flv");
	// con't if not an flv
	} else {

		//_______________________________________________________________________________________________
        	//_______________________________Convert The videos______________________________________________

        	//___run the set cmd_____________
		$output = '';

        	// $cmd from above switch => without b_frames
        	@exec("$cmd 2>&1",$output);

        	$cmd_1 = $cmd;
        	$log_1 = $output;
        	capture_output($output,$cmd);


        	//___Conversion failed totally___________________
        	if(!file_exists($new_flv) || filesize($new_flv) < 10000) {
            	@unlink($new_flv);
            	die_with_msg("Failed to convert video");
        	}

        	//___Possible hack attempt___________________
        	if(file_exists($new_flv) && filesize($new_flv) < 10000) {
            	@unlink($new_flv);
            	die_with_msg('Output file size was too small. Possible hack attempt');
        	}
	} // end conversions

	//______________________________________________________________________________________________
    	//_____________________________FLVTOOL2 - (Meta Data Injection)_________________________________

	#$cmd = "$config[path_to_flvtool2] -UP $new_flv | grep duration";
    	#$output = '';
    	#@exec("$cmd 2>&1",$output);
    	#capture_output($output,$cmd);

        $video_attributes = _get_video_attributes($new_flv, $config[path_to_ffmpeg]);
        $vid_duration = $video_attributes['hours']*3600;
        $vid_duration = $vid_duration+($video_attributes['mins']*60);
        $vid_duration = $vid_duration+($video_attributes['secs']);

   	$duration_hours = $video_attributes['hours'];
    	$duration_mins = $video_attributes['mins'];
    	$duration_secs = $video_attributes['secs'];
    	$duration_time = sprintf("%02d:%02d:%02d",$duration_hours,$duration_mins,$duration_secs);
    	$duration = $duration_time;

    	//___middle of movie___________
    	$thumb_pos = (int)$vid_duration / 2;

    	//___change total seconds to ffmpeg time e.g. 121 seconds == 00:02:01
    	$ffmpeg_hours = floor($thumb_pos / 3600);
   	$ffmpeg_mins = floor($thumb_pos % 3600 / 60);
   	$ffmpeg_secs = floor($thumb_pos % 60);
    	$ffmpeg_time = sprintf("%02d:%02d:%02d",$ffmpeg_hours,$ffmpeg_mins,$ffmpeg_secs);
    	$thumb_position = $ffmpeg_time;

	//______________________________________________________________________________________________
    	//_____________________________CREATE THUMBNAIL IMAGE___________________________________________

	// TODO ADD MULTIPULE THUMB CODE HERE

    	$output_file = $base_path.'/uploads/thumbs/'.$file_name_no_extension.'.jpg';
    	$player_output_file = $base_path.'/uploads/player_thumbs/'.$file_name_no_extension.'.jpg';

	//__create standard thumb_______
    	$cmd = "$config[path_to_ffmpeg] -y -i $new_flv -ss $thumb_position -vframes 1 -vf scale=\"120:trunc(ow/a/2)*2\" -r 1 -f mjpeg $output_file";
    	$output = '';
    	@exec("$cmd 2>&1",$output);
   	capture_output($output,$cmd);

	//__create large thumb for better player image_______
    	$cmd = "$config[path_to_ffmpeg] -y -i $new_flv -ss $thumb_position -vframes 1 -vf scale=\"560:trunc(ow/a/2)*2\"  -r 1 -f mjpeg $player_output_file";
    	$output = '';
    	@exec("$cmd 2>&1",$output);
    	capture_output($output,$cmd);

	//__check if thumbnail was created_______
    	if(!file_exists($output_file)) {

      	$cmd = "$config[path_to_ffmpeg] -y -i $new_flv -ss $thumb_position -vframes 1 -vf scale=\"120:trunc(ow/a/2)*2\" -r 1 -f image2 $output_file";
        	$output = '';
       	@exec("$cmd 2>&1",$output);
        	capture_output($output,$cmd);
    	}

	//__check if thumbnail is 0 bytes_________
    	if(filesize($output_file) == 0) {
      	$second = '00:00:04';
        	$cmd = "$config[path_to_ffmpeg] -y -i $new_flv -deinterlace -an -ss $second -vframes 1 -vf scale=\"120:trunc(ow/a/2)*2\"  -r 1 -y -vcodec mjpeg -f mjpeg $output_file";
        	$output = '';
        	@exec("$cmd 2>&1",$output);
        	capture_output($output,$cmd);
    	}

	//____________________________________________________________________
    	//___JUMP START DATABASE______________________________________________
   	$sql_test = 'SELECT * FROM videos LIMIT 1';

    	if(!mysql_query($sql_test)) {
      	include_once ('classes/mysql.inc.php');
        	@mysql_close();
        	@mysql_connect($config["hostname"],$config["dbusername"],$config["dbpassword"],true);
        	@mysql_select_db($config["dbname"]);
    	}

	//___________________________________________________________________
    	//___UPDATE THE DATABASE_____________________________________________

    	//__set to just pending______
    	$sql = "UPDATE videos SET approved='pending' WHERE video_id = '$raw_video'";
    	@mysql_query($sql);

	//__reset video id - remove extension______
    	$sql = "UPDATE videos SET video_id='$file_name_no_extension' WHERE video_id = '$raw_video'";
    	@mysql_query($sql);

	//__set video duration_______
    	$sql = "UPDATE videos SET video_length='$duration' WHERE video_id = '$file_name_no_extension'";
    	@mysql_query($sql);

	//__auto approve________
    	if($config["auto_approve_videos"] == "yes") {
      	$sql = "UPDATE videos SET approved='yes' WHERE video_id = '$file_name_no_extension'";
       	@mysql_query($sql);
    	}

	//___________________________________________________________________
    	//___DELETE ORIGICAL FILE_____________________________________________

    	$original_file = $raw_video_path;

    	if($config['delete_original'] == 'yes') {
    		if(@file_exists("$new_flv") && @file_exists("$raw_video_path")) {
            	if($new_flv != $raw_video_path) {
                		@unlink($raw_video_path);
            	}
        	}
    	}

    	if($config['delete_avi'] == 'yes') if(@file_exists("$new_flv") && @file_exists("$avi_file")) @unlink($avi_file);

    	@mysql_close();
}

// end while


//_______________Write to logfile - and die______________________________________
function die_with_msg($error = '') {

	global $admin_email, $config, $cmd_1, $cmd_2, $cmd_3, $log_1, $log_2, $log_3, $raw_video_path, $new_flv, $temp_sys_admin_email, $raw_video, $massuploader_debug_emails;

	//____________________________________________________________________
    	//___JUMP START DATABASE______________________________________________
    	$sql_test = 'SELECT * FROM videos LIMIT 1';

    	if(!mysql_query($sql_test)) {
      	@mysql_close();
        	@mysql_connect($config["hostname"],$config["dbusername"],$config["dbpassword"],true);
        	@mysql_select_db($config["dbname"]);
    	}

	$sql = "DELETE FROM videos WHERE video_id = '$raw_video'";
    	$query = @mysql_query($sql);

	//Get any cmd output
    	foreach($log_1 as $output1) $output_1 .= $output1."\n";

    	foreach($log_2 as $output2) $output_2 .= $output2."\n";

    	foreach($log_3 as $output3) $output_3 .= $output3."\n";

    	$timestamp = date("F j, Y, g:i a");

	//Admin notification email
	$message =
		"\nAn error occurred while trying to convert a video. Commands Executed & Output (if any) are shown below\n
		You can disable future notices via your config file '/classes/config.inc.php'\n
		Conversion errors are produced by server side modules installed on your server (FFmpeg/Mencoder/FlvTool2).
		This is NOT a PHPmotion error. Consult with your webhost to resolve this.\n
		---------------------------------------------------------------------------------------------------------------------\n
		$error\n
		-->Command Executed<--\n
		$cmd_1\n
		-->Output Produced<--\n
		$output_1\n
		---------------------------------------------------------------------------------------------------------------------\n\n\n
		---------------------------------------------------------------------------------------------------------------------\n
		-->Command Executed<--\n
		$cmd_2\n
		-->Output Produced<--\n
		$output_2\n
		----------------------------------------------------------------------------------------------------------------------\n\n\n
		----------------------------------------------------------------------------------------------------------------------\n
		-->Command Executed<--\n
		$cmd_3\n
		-->Output Produced<--\n
		$output_3\n
		----------------------------------------------------------------------------------------------------------------------\n\n";

	//Send the email to admin
	$to = ($temp_sys_admin_email == '')? $admin_email : $temp_sys_admin_email;
	$subject = 'PHPmotion - Video Conversion (Error Notification)';
	$from = $config['site_name'].'<'.$config['notifications_from_email'].'>';

	//check if set to notify
	if($config['notify_failed_conversions'] == 'yes' || $temp_sys_admin_email != '' || $massuploader_debug_emails){

		if($massuploader_debug_emails == 'no'){ //double check massuploader settings

		} else {
			mail($to, $subject, $message, "From: $from");
		}
	}

	//delete raw video
	//@unlink($raw_video_path);

	@unlink($new_flv);

	//purge log vars
	$message = '';
	$error = '';
	$cmd_1 = '';
	$output_1 = '';
	$cmd_2 = '';
	$output_2 = '';
	$cmd_3 = '';
	$output_3 = '';
	$output = '';

	die();
}

//_______________Log To file______________________________________
function capture_output($output,$cmd = '') {

	global $log_file;

    	$file_contents = $cmd."\n";
    	foreach($output as $outputline) $file_contents .= $outputline."\n";

    	$file_write = "\n\n"."---------------------------------------------------------------------"."\n\n".$file_contents;

    	if(@file_exists($log_file)) {
      	$fo = @fopen($log_file,'a');
        	@fwrite($fo,$file_write);
       	@fclose($fo);
	} else {
        	$fo = @fopen($log_file,'w');
        	@fwrite($fo,$file_write);
        	@fclose($fo);
    	}

    	//echo $file_contents;

    	return true;
}


function _get_video_attributes($video, $ffmpeg) {

    $command = $ffmpeg . ' -i ' . $video . ' -vstats 2>&1';
    $output = shell_exec($command);

    $regex_sizes = "/Video: ([^,]*), ([^,]*), ([0-9]{1,4})x([0-9]{1,4})/";
    if (preg_match($regex_sizes, $output, $regs)) {
        $codec = $regs [1] ? $regs [1] : null;
        $width = $regs [3] ? $regs [3] : null;
        $height = $regs [4] ? $regs [4] : null;
     }

    $regex_duration = "/Duration: ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}).([0-9]{1,2})/";
    if (preg_match($regex_duration, $output, $regs)) {
        $hours = $regs [1] ? $regs [1] : null;
        $mins = $regs [2] ? $regs [2] : null;
        $secs = $regs [3] ? $regs [3] : null;
        $ms = $regs [4] ? $regs [4] : null;
    }

    return array ('codec' => $codec,
            'width' => $width,
            'height' => $height,
            'hours' => $hours,
            'mins' => $mins,
            'secs' => $secs,
            'ms' => $ms
    );

}

?>
