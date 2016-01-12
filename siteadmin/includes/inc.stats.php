<?php

//get some basic site stats
///////////////////////////

$sql 			= "SELECT count(indexer) FROM videos WHERE approved = 'yes'";
$result 		= mysql_query($sql);
$video_total	= mysql_result($result,0);
$sql 			= "SELECT count(indexer) FROM audios WHERE approved = 'yes'";
$result 		= mysql_query($sql);
$audio_total	= mysql_result($result,0);
$sql 			= "SELECT count(indexer) FROM images WHERE approved = 'yes'";
$result 		= mysql_query($sql);
$image_total 	= mysql_result($result,0);
$sql 			= "SELECT count(indexer) FROM blogs WHERE approved = 'yes'";
$result 		= mysql_query($sql);
$blog_total 	= mysql_result($result,0);
$sql 			= "SELECT count(user_id) FROM member_profile WHERE account_status = 'active'";
$result 		= mysql_query($sql);
$members_total 	= mysql_result($result,0);

$sql 			= "SELECT indexer FROM views_tracker WHERE media_type ='videos'";
$result 		= mysql_query($sql);
$total_views_videos = mysql_result($result,0);

//views images
$sql = "SELECT count(indexer) FROM views_tracker WHERE media_type ='images'";
$result 		= mysql_query($sql);
$total_views_images = mysql_result($result,0);

//views blogs
$sql = "SELECT count(indexer) FROM views_tracker WHERE media_type ='blogs'";
$result 		= mysql_query($sql);
$total_views_blogs = mysql_result($result,0);

//views audio
$sql = "SELECT count(indexer) FROM views_tracker WHERE media_type ='audios'";
$result = @mysql_query($sql);
$total_views_audio = mysql_result($result,0);

//views profiles
$sql = "SELECT count(indexer) FROM views_tracker WHERE media_type ='member_profile'";
$result = @mysql_query($sql);
$total_views_profiles = mysql_result($result,0);

//total comments
$sql = "SELECT count(indexer) FROM videocomments";
$result = @mysql_query($sql);
$comments_total = mysql_result($result,0);

//latest member
$sql = "select user_name, user_id from member_profile order by user_id desc";
$result = @mysql_query($sql);
$row = @mysql_fetch_row($result);
$newest_user_name = $row[0];
$newest_userid = $row[1];



//recent videos
$recent = array();
$sql = "SELECT * FROM videos WHERE approved='yes' AND public_private = 'public' ORDER BY indexer DESC LIMIT 10";
$query = @mysql_query($sql);
while ($result1 = mysql_fetch_array($query)) $recent[] = $result1;

//Get Folder Usage (i.e folder sizez)
//Get Total Folder Sizes

function foldersize($path) {

	$total_size = 0;

	if (!function_exists('scandir')) {
    		function scandir($path) {
    			$dh  = opendir($path);
    			while (false !== ($filename = readdir($dh))) {
    				$files[] = $filename;
			}

			sort($files);
			//print_r($files);

			rsort($files);
			//print_r($files);

        		return($files);
    		}

    		$files = scandir($path);

    		foreach ($files as $t) {

        		if (is_dir($t)) {// In case of folder
            		if ($t <> "." && $t <> "..") {// Exclude self and parent folder
                			$size = foldersize($path."/".$t);
                			//print("Dir - $path/$t = $size<br>\n");
                			$total_size += $size;
            		}

        		} else {// In case of file
            		$size = @filesize($path."/".$t);
            		//print("File - $path/$t = $size<br>\n");
            		$total_size += $size;
        		}
    		}

    		$bytes = array('B','KB','MB','GB','TB');

    		foreach ($bytes as $val) {
      		if ($total_size > 1024) {
            		$total_size = $total_size / 1024;
        	     	} else {
            		break;
        		}
    		}

    		return @round($total_size,2)." ".$val;

	} else {

		$files = @scandir($path);

		foreach ($files as $t) {

			if (is_dir($t)) {// In case of folder
            		if ($t <> "." && $t <> "..") {// Exclude self and parent folder
                			$size = foldersize($path."/".$t);
                			$total_size += $size;
            		}

        		} else {
        			$size = @filesize($path."/".$t);
            		$total_size += $size;
        		}
    		}

    		$bytes = array('B','KB','MB','GB','TB');

    		foreach ($bytes as $val) {
        		if ($total_size > 1024) {
            		$total_size = $total_size / 1024;
        		} else {
            		break;
        		}
    		}
    		return @round($total_size,2)." ".$val;
	}
}

$videos_folder 	= foldersize($base_path . '/uploads');
if ($videos_folder == '') $videos_folder = 'None';

$mp3_folder		= foldersize($base_path . '/uploads/audio');
if ($mp3_folder == '') $mp3_folder = 'None';

$pictures_folder 	= foldersize($base_path . '/addons/albums/images');
if ($pictures_folder == '') $pictures_folder = 'None';

?>
