<?php

function users_before_pie_response_notices()
{
	if ($user = Users::loggedInUser()) {
		if (empty($user->email_address)) {
			$email = new Users_Email();
			$email->user_id = $user->id;
			if ($email->retrieve()) {
				$resend_button = "<button id='notices_set_email'>try again</button>";
				Pie_Response::addNotice('email', "Please check your email to activate your account. Any problems, $resend_button");
			} else {
				$set_email_button = "<button id='notices_set_email'>set an email address</button> for your account.";
				Pie_Response::addNotice('email', "You need to $set_email_button");
			}
			Pie_Response::addScriptLine("jQuery(function() {
				$('#notices_set_email').click(function() { Pie.Users.setEmail(); });
			}); ");
		}
	}
}
