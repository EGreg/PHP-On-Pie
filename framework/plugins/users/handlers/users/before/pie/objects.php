<?php

function users_before_pie_objects()
{
	$fb_prefix = 'fb_sig_';

	// Get the facebook object from POST, if any
	if (isset($_POST[$fb_prefix.'app_id'])) {
		$app_id = $_POST[$fb_prefix.'app_id'];
		$fb_apps = Pie_Config::get('users', 'facebookApps', array());
		$fb_info = null;
		$fb_key = null;
		foreach ($fb_apps as $key => $a) {
			if (isset($a['appId']) and $a['appId'] == $app_id) {
				$fb_info = $a;
				$fb_key = $key;
				break;
			}
		}
		if (isset($fb_info['apiKey']) && isset($fb_info['secret'])) {
			Pie_Config::set('users', 'facebook', 'new', false);
			$facebook = new Facebook($fb_info['apiKey'], $fb_info['secret']);
			Users::$facebook = $facebook;
			Users::$facebooks[$app_id] = $facebook;
			Users::$facebooks[$key] = $facebook;
		}
	}
	
	// Get the other facebook objects from cookies
	/*
	Pie_Session::start();
	if (isset($_SESSION['users']['facebooks']))
		Users::$facebooks = $_SESSION['users']['facebooks'];
	*/
		
	$fb_apps = Pie_Config::get('users', 'facebookApps', array());
	foreach ($fb_apps as $key => $fb_info) {
		if (isset($_COOKIE[$fb_info['apiKey'].'_user'])
		and isset($_COOKIE[$fb_info['apiKey'].'_session_key'])) {
			Pie_Config::set('users', 'facebook', 'new', false);
			$facebook = new Facebook(
				$fb_info['apiKey'],
				$fb_info['secret']
			);
			$facebook->set_user(
				$_COOKIE[$fb_info['apiKey'].'_user'],
				$_COOKIE[$fb_info['apiKey'].'_session_key']
			);
			Users::$facebooks[$fb_info['appId']] = $facebook;
			Users::$facebooks[$key] = $facebook;
		}
	}
	
	// And now, the new facebook js can set fbs_$appId	
	$fb_apps = Pie_Config::get('users', 'facebookApps', array());
	foreach ($fb_apps as $key => $fb_info) {
		if (isset($_COOKIE['fbs_'.$fb_info['appId']])) {
			Pie_Config::set('users', 'facebook', 'new', true);
			$facebook = new Facebook(
				$fb_info['appId'],
				$fb_info['secret']
			); // will set user and session from the cookie
			Users::$facebooks[$fb_info['appId']] = $facebook;
			Users::$facebooks[$key] = $facebook;
		}
	}
	
	/*
	$_SESSION['users']['facebooks'] = Users::$facebooks;
	*/
	
	// Fire an event for hooking into, if necessary
	Pie::event('users/objects', array(), 'after');
}
