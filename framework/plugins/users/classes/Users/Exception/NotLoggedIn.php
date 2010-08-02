<?php

/**
 * @package users
 */
class Users_Exception_NotLoggedIn extends Pie_Exception
{
	
};

Pie_Exception::add('Users_Exception_NotLoggedIn', 'You are not logged in.');
