<?php

function users_register_post()
{	
	$u = new Users_User();
	$u->email_address = $_REQUEST['email_address'];
	if ($u->retrieve()) {
		$key = 'this email';
		throw new Users_Exception_AlreadyVerified(compact('key'));
	}
	
	// Insert a new user into the database
	$user = new Users_User();
	$user->username = $_REQUEST['username'];
	if ($user->retrieve()) {
		throw new Users_Exception_UsernameExists(array(), array('username'));
	}
	$user->icon = 'default';
	$user->password_hash = '';
	$user->save(); // sets the user's id
	
	// Import the user's icon
	if (isset($_REQUEST['icon'])) {
		$folder = 'user_id_'.$user->id;
		users_register_post_download($_REQUEST['icon'], $folder, 80);
		users_register_post_download($_REQUEST['icon'], $folder, 40);
		$user->icon = $folder;
		$user->save();
	}
	
	// Add an email to the user, that they'll have to verify
	$user->addEmail($_REQUEST['email_address']);
	
	Users::setLoggedInUser($user);
	Users::$cache['user'] = $user;
}

function users_register_post_download($url, $folder, $size = 80)
{
	$url_parts = parse_url($url);
	if (substr($url_parts['host'], -12) != 'gravatar.com') {
		return false;
	}
	$dir = Pie_Config::get('users', 'paths', 'icons', 'files/users/icons');
	$ch = curl_init(Pie_Uri::url($_REQUEST['icon'].'?s='.$size));
	$dir2 = Pie::realPath($dir).DS.$folder;
	if (!file_exists($dir2)) {
		mkdir($dir2, 0777);
		chmod($dir2, 0777);
	}
	$fp = fopen($dir2.DS."$size.png", 'wb');
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	return true;
}