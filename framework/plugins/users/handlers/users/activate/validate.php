<?php

function users_activate_validate()
{
	$email_address = Pie_Dispatcher::uri()->email_address;
	$mobile_number = Pie_Dispatcher::uri()->mobile_number;
	if ($email_address && !Pie_Valid::email($email_address)) {
		throw new Pie_Exception_WrongValue(array(
			'field' => 'email',
			'range' => 'a valid email address'
		), 'email_address');
	}
	if ($mobile_number && !Pie_Valid::phone($mobile_number)) {
                throw new Pie_Exception_WrongValue(array(
                        'field' => 'mobile phone',
                        'range'	=> 'a valid phone number'
                ), 'mobile_number');
	}
	if ($email_address or $mobile_number) {
                if (empty($_REQUEST['code'])) {
                        throw new Pie_Exception("The activation code is missing");
                }
	}
	// This is one of the few places where we cheat,
	// and fill the $_POST array even though it probably wasn't filled.
	if ($email_address) {
		$_POST['email_address'] = $email_address;
	} else if ($mobile_number) {
		$_POST['mobile_number'] = $mobile_number;
	}
}
