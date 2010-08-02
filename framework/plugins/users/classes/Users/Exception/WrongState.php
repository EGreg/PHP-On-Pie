<?php

/**
 * @package users
 */
class Users_Exception_WrongState extends Pie_Exception
{
	
};

Pie_Exception::add('Users_Exception_WrongState', '$key is $state');
