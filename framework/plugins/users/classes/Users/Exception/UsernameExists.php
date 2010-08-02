<?php

/**
 * @package users
 */
class Users_Exception_UsernameExists extends Pie_Exception
{
	
};

Pie_Exception::add('Users_Exception_UsernameExists', 'Someone else has that username');
