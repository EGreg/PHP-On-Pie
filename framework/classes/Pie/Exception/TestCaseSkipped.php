<?php

class Pie_Exception_TestCaseSkipped extends Pie_Exception_TestCase
{
	
};

Pie_Exception::add('Pie_Exception_TestCaseSkipped', 'skipped. $message');
