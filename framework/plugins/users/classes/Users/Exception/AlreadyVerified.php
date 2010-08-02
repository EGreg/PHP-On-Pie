<?php

/**
 * @package users
 */
class Users_Exception_AlreadyVerified extends Pie_Exception
{
	
};

Pie_Exception::add('Users_Exception_AlreadyVerified', '$key has already been verified for another user');
