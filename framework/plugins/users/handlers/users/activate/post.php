<?php

function users_activate_post()
{
	$email_address = Pie_Dispatcher::uri()->email_address;
	$mobile_number = Pie_Dispatcher::uri()->mobile_number;

	$email = null;
	$mobile = null;

	if ($email_address) {
		$email = new Users_Email();
		$email->address = $email_address; // NOTE: not sharded by user_id
		if (!$email->retrieve()) {
			throw new Pie_Exception_MissingRow(array(
				'table' => 'email',
				'criteria' => "address = $email_address"
			));
		}
		$user = new Users_User();
		$user->id = $email->user_id;
		if (!$user->retrieve()) {
                        throw new Pie_Exception_MissingRow(array(                       
                                'table'	=> 'user',
                                'criteria' => 'id = '.$user->id
                        ));
		}
                if ($email->activation_code != $_REQUEST['code']) {
                        throw new Pie_Exception("The activation code does not match.", 'code');
                }
		$user->setEmailAddress($email->address); // may throw exception
		$type = "email address";
	}

        if ($mobile_number) {
                $mobile = new Users_Mobile();
                $mobile->number = $mobile_number; // NOTE: not sharded by user_id
                if (!$mobile->retrieve()) {
                        throw new Pie_Exception_MissingRow(array(
                                'table' => 'mobile phone',
                                'criteria' => "number = $mobile_number"
                        ));
                }
                $user = new Users_User();
                $user->id = $mobile->user_id;
                if (!$user->retrieve()) {
                        throw new Pie_Exception_MissingRow(array(
				'table' => 'user',
				'criteria' => 'id = '.$user->id
			));
                }
                if ($mobile->activation_code != $_REQUEST['code']) {
                        throw new Pie_Exception("The activation code does not match.", 'code');
                }
                $user->setMobileNumber($mobile->number); // may throw exception
		$type = "mobile number";
        }

	if ($type) {
		Pie_Response::addNotice("users/activate", "Your $type has been activated.");
	}
	Users::$cache['user'] = $user;
}
