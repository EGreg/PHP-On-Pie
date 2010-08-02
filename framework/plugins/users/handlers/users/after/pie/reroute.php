<?php

function users_after_pie_reroute($params, &$stop_dispatch)
{
	$uri = Pie_Dispatcher::uri();
	$app = Pie_Config::expect('pie', 'app');
	$ma = $uri->module.'/'.$uri->action;
	$requireComplete = Pie_Config::get('users', 'requireComplete', array());
	if (isset($requireComplete[$ma])) {
		$redirect_action = is_string($requireComplete[$ma])
			? $requireComplete[$ma]
			: "$app/login";
		$test_complete = true;
	} else {
		$requireLogin = Pie_Config::get('users', 'requireLogin', array());
		if (!isset($requireLogin[$ma])) {
			// We don't have to require complete or login here
			return;
		}
		$redirect_action = is_string($requireLogin[$ma])
			? $requireLogin[$ma]
			: "$app/login";
	}

	// First, try to get the user 
	$user = Users::loggedInUser();
	if (!$user) {
		// Try authenticating with facebook
		$module = Pie_Dispatcher::uri()->module;
		$app_id = Pie_Config::expect('users', 'facebookApps', $module, 'appId');
		$user = Users::authenticate('facebook', $app_id);
	}
	if (!$user) {
		$uri->onSuccess = $uri->module.'/'.$uri->action;
		$uri->onCancel = "$app/welcome";
		if ($uri->onSuccess === $redirect_action) {
			// avoid a redirect loop
			$uri->onSuccess = "$app/home";
		}
		$parts = explode('/', $redirect_action);
		$uri->action = $parts[0];
		$uri->action = $parts[1];
	}
	
	// If have requireLogin but not requireComplete, then
	// simply change the underlying URI without redirecting
	if (empty($test_complete)) {
		return;
	}

	// If we are here, we should check if the user account is complete
	$complete = Pie::event('users/account/complete');
	if ($complete) {
		// good, nothing else to complete
		return;
	}
	// redirect to account page
	$account_action = Pie_Config::expect('users', 'accountAction', $uri->module);
	if ($ma != $account_action) {
		// Make the user launch into setting up their account.
		// If they want to return to this URL later, they can do it on their own.
		Pie_Response::redirect($account_action);
		$stop_dispatch = true;
		return;
	}
}
