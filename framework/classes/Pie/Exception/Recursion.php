<?php

class Pie_Exception_Recursion extends Pie_Exception
{
	
};

Pie_Exception::add('Pie_Exception_Recursion', 'Seems we have runaway recursive calls to $function_name');
