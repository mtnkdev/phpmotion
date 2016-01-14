<?php
error_reporting (E_ALL);
///////////////////////////////////////////////////////////////////////////////////////
// PHPmotion                                                http://www.phpmotion.com //
///////////////////////////////////////////////////////////////////////////////////////
// License: You are not to sell or distribute this software without permission       //
// Help and support please visit http://www.phpmotion.com                            //
// Copyright reserved                                                                //
///////////////////////////////////////////////////////////////////////////////////////

include_once ('classes/config.php');
include_once ('classes/sessions.php');

if ( $enable_forum == 1 ) header("Location: " . $smf_bridge_register);


$referer	= $_SERVER[HTTP_REFERER];
if ( !ereg ($base_url, $referer) ) $referer = $base_url;

$type 	= mysql_real_escape_string( $_GET['type'] );

if ( $_POST['submitted'] != 'yes' ) {

	$show_signup	= 0;
	$show_login		= 1;

	if ( !isset($form_submitted) || ($form_submitted == '') ) {

		if ( defined('SMF_INSTALLED') ) $show_login = 2;

		$template		= "themes/$user_theme/templates/main_1.htm";
     		$inner_template1 	= "themes/$user_theme/templates/inner_signup_form.htm";

     		$TBS			= new clsTinyButStrong;
     		$TBS->NoErr = true;
     		$TBS->LoadTemplate("$template");
     		$TBS->Render = TBS_OUTPUT;
     		$TBS->Show();
     		@mysql_close();
     		die();
    	}
}

if ( $_POST['submitted'] == 'yes' && !isset($_POST['user_name_login']) || ($_POST['user_name_login'] == '') || !isset($_POST['password_login']) || ($_POST['password_login'] == '')) {

	//display form with error message
	$error_message	= $config['incorrect_logins'];
    	$message_type	= $lang_error;
    	$blk_notification = 1;
    	$show_signup	= 0;
	$show_login		= 1;
    	$template		= "themes/$user_theme/templates/main_1.htm";
     	$inner_template1 	= "themes/$user_theme/templates/inner_signup_form.htm";

     	$TBS			= new clsTinyButStrong;
     	$TBS->NoErr = true;
     	$TBS->LoadTemplate("$template");
     	$TBS->Render = TBS_OUTPUT;
     	$TBS->Show();

     	@mysql_close();
     	die();
}

// GET LOGIN INFORMATION AND CHECK AGAINST DATABASE

$user_name_login	= mysql_real_escape_string($_POST['user_name_login']);
$password_login	= mysql_real_escape_string($_POST['password_login']);
$password_login	= md5($password_login);
$remember_me	= mysql_real_escape_string($_POST['remember_me']);
$cookie_time 	= mysql_real_escape_string($_POST['cookie_time']);
$referer_url	= mysql_real_escape_string($_POST['referer_url']);

$config_redirect	= 'yes';
$validated              = 'no';
$user_role              = 'none';

if ($cookie_time == '-1' ) $cookie_time = 43200;

// hook in the ldap integration here
if ($config['ldap_domain'] != '') {
	// authenticate/bind to ldap
       	$link = ldap_connect($config['ldap_domain']);
       	ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
       	if (ldap_bind($link, $_POST['user_name_login'].$config['ldap_domain'], $_POST['password_login'])) {
		$validated = 'ldap';
                if ($info['success'] == 'yes') {
		   // make sure the user exists in the local database and refresh the password with what they supplied
                   if ($user_is_new) {
                      $info = _get_ldap_properties($link, $config['ldap_domain'],$_POST['user_name_login']);
		   } else {
                      // just update the password
		   }
                }
	}


	ldap_unbind($link);
}




// case insensitive login and registration
$user_name_login			= strtolower($user_name_login);
$sql 					= "SELECT user_name, password FROM member_profile WHERE user_name = '$user_name_login' AND password = '$password_login'";
$query				= @mysql_query($sql);
$result				= @mysql_fetch_array($query);
$result_display_username	= $result['user_name'];
$result_username			= strtolower($result['user_name']);

if ( $result_username == $user_name_login ) {

	//success login - checkinng if user has confirmed email

    	$sql			= "SELECT user_name, user_id, password, passwordSalt, account_status FROM member_profile WHERE user_name = '$user_name_login' AND password = '$password_login'";
    	$query		= @mysql_query($sql);
    	$outcome		= @mysql_fetch_array($query);
    	$result		= $outcome['account_status'];

    	if ( $result == 'new' ) {

    		//email not confirmed
    		$notification_type	= $config['notification_error'];
        	$message			= $config['email_not_confirmed'];
        	$blk_notification 	= 1;

        	$template			= "themes/$user_theme/templates/main_1.htm";
        	$inner_template1 		= "themes/$user_theme/templates/inner_notification.htm";
        	$TBS 				= new clsTinyButStrong;
        	$TBS->NoErr 		= true;
        	$TBS->LoadTemplate("$template");
        	$TBS->Render 		= TBS_OUTPUT;
        	$TBS->Show();

        	@mysql_close();
        	die();
    	}

    	elseif( $result == 'suspended' ) {

      	//account suspended
      	$notification_type	= $config['notification_error'];
        	$error_message 		= $config['account_suspended'];
        	$blk_notification 	= 1;
        	$template 			= "themes/$user_theme/templates/main_1.htm";
        	$inner_template1		= "themes/$user_theme/templates/inner_notification.htm";
        	$TBS 				= new clsTinyButStrong;
        	$TBS->NoErr 		= true;
        	$TBS->LoadTemplate("$template");
        	$TBS->Render 		= TBS_OUTPUT;
        	$TBS->Show();

		@mysql_close();
        	die();
    	}
	elseif( $result == 'active' ) {

    		@session_start();
        	@session_register('user_id');
        	@session_register('user_name');
        	@session_register('user_group');
        	$_SESSION['user_id']		= $outcome['user_id'];
        	$_SESSION['user_name']		= $result_display_username;
        	$_SESSION['user_group']		= $outcome['user_group'];
        	$password				= $outcome['password'];
        	$passwordSalt			= $outcome['passwordSalt'];
        	$loggedin				= 1;

        	// remember me
        	if ( $remember_me == 'remember_me' ) {

        		$how_long		= (60 * $cookie_time);
        		$cookie_pass	= sha1( sha1($password) . sha1($passwordSalt) );
        		setcookie('user', $result_display_username, time()+$how_long);
        		setcookie('pass', $cookie_pass, time()+$how_long);
        	}

        	//header("Location: myaccount.php");

        	if ( $referer_url == '' ) $referer_url = "index.php";
        	if ($config_redirect == 'yes') {
        		header("Location: $referer_url");		//redirect to last page
        	} else {
        		header("Location: " . "myaccount.php");	//redirect to Myaccount page
        	}
	}

} else {

	//display form with error message
    	$error_message	= $config['incorrect_logins'];
    	$message_type	= $lang_error;
    	$blk_notification = 1;
    	$show_login		= 1;

    	$template 		= "themes/$user_theme/templates/main_1.htm";
    	$inner_template1 	= "themes/$user_theme/templates/inner_signup_form.htm";

    	$TBS 			= new clsTinyButStrong;
    	$TBS->NoErr 	= true;
    	$TBS->LoadTemplate("$template");
    	$TBS->Render 	= TBS_OUTPUT;
    	$TBS->Show();

    	@mysql_close();
    	die();
}


function _get_ldap_properties($link, $domain, $username) {

    $results= array ('success' => 'no',
                     'username' => $username
                    );

    // domain comes in as a domain name (example.com) 
    // we have to convert it to "DC=example,DC=com"
    $dn     = $domain;  
    $filter = "(sAMAccountName=$username)";

    $sr = ldap_search($link, $dn, $filter);
    $info = ldap_get_entries($link, $sr);
    
    $results['success'] = 'yes';
    $results['mail'] = $info[0]['mail'][0];
    $results['givenname'] = $info[0]['givenname'][0];
    $results['surname'] = $info[0]['sn'][0];
    return $results;
}

?>
