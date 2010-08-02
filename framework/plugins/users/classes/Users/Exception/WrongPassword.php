<?php

/**
 * @package users
 */
class Users_Exception_WrongPassword extends Pie_Exception
{
	
};

Pie_Exception::add('Users_Exception_WrongPassword', 'Wrong password for $identifier');
