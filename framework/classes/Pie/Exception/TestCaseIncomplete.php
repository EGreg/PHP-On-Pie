<?php

class Pie_Exception_TestCaseIncomplete extends Pie_Exception_TestCase 
{
	
};

Pie_Exception::add('Pie_Exception_TestCaseIncomplete', 'incomplete. $message');
