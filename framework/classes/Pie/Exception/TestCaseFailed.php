<?php

class Pie_Exception_TestCaseFailed extends Pie_Exception_TestCase
{
	
};

Pie_Exception::add('Pie_Exception_TestCaseFailed', 'failed. $message');
