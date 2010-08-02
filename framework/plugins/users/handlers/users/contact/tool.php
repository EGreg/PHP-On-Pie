<?php

function users_contact_tool($params)
{
	$defaults = array(
		'uri' => 'users/contact',
		'omit' => array(),
		'fields' => array(),
		'title' => "Contact Info",
		'collapsed' => false,
		'toggle' => false,
		'editing' => true,
		'complete' => true,
		'inProcess' => false,
		'prompt' => "In order for things to work, we must be able to reach you.",
		'button_content' => 'OK'
	);
	extract(array_merge($defaults, $params));
	$default_fields = array(
		'first_name' => array('type' => 'text', 'label' => 'First Name'),
		'last_name' => array('type' => 'text', 'label' => 'Last Name'),
		'email_address' => array('type' => 'text', 'label' => 'Email')
	);
	$fields = array_merge($default_fields, $fields);
	
	$user = Users::loggedInUser();
	if (!$user) {
		throw new Users_Exception_NotLoggedIn();
	}
	$email = null;
	$missing_fields = Users::accountStatus($email);
	if (isset($user->first_name)) {
		$fields['first_name']['value'] = $user->first_name;
	}
	if (isset($user->last_name)) {
		$fields['last_name']['value'] = $user->last_name;
	}
	if (isset($user->email_address)) {
		$fields['email_address']['value'] = $user->email_address;
	} else if ($email) {
		$link = Pie_Html::a('#resend', 
			array('class' => 'users_contact_tool_resend'), 
			"You can re-send the activation email"
		);
		switch ($email->state) {
		 case 'active':
			if ($email->user_id == $user->id) {
				$message = "Please confirm this email address.<br>$link";
			} else {
				$message = "This email seems to belong to another user";
			}
			break;
		 case 'suspended':
			$message = "This address has been suspended.";
			break;
		 case 'unsubscribed':
			$message = "The owner of this address has unsubscribed";
			break;
		 case 'unverified':
		 default:
			$message = "Not verified yet.<br>$link";
			break;
		}
		$fields['email_address']['value'] = $email->address;
		$fields['email_address']['message'] = $message;
	}
	
	$on_success = (isset($_REQUEST['_pie']['onSuccess']))
		? $_REQUEST['_pie']['onSuccess']
		: Pie_Request::url();
	
	Pie_Response::addScript('plugins/users/js/Users.js');
	$form = $static = compact('fields');
	return Pie::tool('pie/panel', compact(
		'uri', 'on_success', 'form', 'static', 'title',
		'collapsed', 'toggle', 'complete', 'editing', 'inProcess',
		'_form_static'
	));
}
