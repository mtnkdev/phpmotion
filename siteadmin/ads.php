<?php
error_reporting(E_ALL);

/**
* @author PHPmotion.com
* @copyright 2008
*/

include_once ('../classes/config.php');

include_once ('includes/inc.stats.php');
include_once ('includes/functions.php');
include_once ('includes/login_check.php');

$dashboard_header = $lang_ads;

$show_hide = 1;
$show_notification = 0;

if ($_POST['update_ads'] != '') {
	
	$ads_top 	= mysql_real_escape_string($_POST['ads_top']);
	$ads_bottom	= mysql_real_escape_string($_POST['ads_bottom']);
	$ads_left 	= mysql_real_escape_string($_POST['ads_left']);
	$ads_right 	= mysql_real_escape_string($_POST['ads_right']);
      
      $sql = "UPDATE adverts SET ads_top = '$ads_top', ads_bottom = '$ads_bottom', ads_left = '$ads_left', ads_right = '$ads_right' WHERE preloaded = 'yes'";
      mysql_query($sql);
      $show_notification = 1;
      $message = $config['error_25'];     
}

$sql = "SELECT * FROM adverts WHERE preloaded = 'yes'";
$query = mysql_query($sql);
while ($result = mysql_fetch_array($query)){
	$ads_top = $result['ads_top'];
	$ads_bottom = $result['ads_bottom'];
	$ads_left = $result['ads_left'];
	$ads_right = $result['ads_right'];
	$ads_home_right = $result['ads_home_right'];
}

include_once ('includes/menuloader.php');

$template = "templates/main.html";
$inner_template1 = "templates/inner_ads.html";
$TBS = new clsTinyButStrong;
$TBS->NoErr = true;
$TBS->LoadTemplate("$template");
$TBS->Render = TBS_OUTPUT;
$TBS->Show();
mysql_close();
die();

?>