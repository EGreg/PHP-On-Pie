<?php

function users_contact_post()
{
	Pie_Session::start();
	Pie_Valid::nonce(true);

	extract($_REQUEST);
	$user = Users::loggedInUser();
	if (!$user) {
			throw new Users_Exception_NotLoggedIn();
	}
	$app = Pie_Config::expect('pie', 'app');
	$subject = "Welcome! Activate your email.";
	$view = "$app/email/setEmail.php";
	$fields = array();

	$p = array();
	$p['subject'] =& $subject;
	$p['view'] =& $view;
	$p['fields'] =& $fields;
	Pie::event('users/setEmail', $p, 'before'); // may change the fields

	if (isset($first_name)) $user->first_name = $first_name;
	if (isset($last_name)) $user->last_name = $last_name;

	$user->addEmail(
			$_REQUEST['email_address'],
			$subject, $view, true,
			$fields
	);

	// If no exceptions were throw, save this user row
	if (isset($first_name) or isset($last_name)) {
		$user->save();
	}
}
