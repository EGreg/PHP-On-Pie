<?php

function users_user_validate()
{
	if (!isset($_REQUEST['email_address'])) {
		throw new Pie_Exception('email address is missing', array('email_address'));
	}
	if (!Pie_Valid::email($_REQUEST['email_address'])) {
		throw new Pie_Exception('a valid email address is required', array('email_address'));
	}
}