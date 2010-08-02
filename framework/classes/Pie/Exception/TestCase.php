<?php

abstract class Pie_Exception_TestCase extends Pie_Exception 
{
	
};

Pie_Exception::add('Pie_Exception_TestCase', 'test case exception: $message');
