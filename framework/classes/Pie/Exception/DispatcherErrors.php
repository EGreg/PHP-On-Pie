<?php

class Pie_Exception_DispatcherErrors extends Pie_Exception
{
	
};

Pie_Exception::add(
	'Pie_Exception_DispatcherErrors', 
	'Dispatcher is displaying errors',
	array('Pie_Dispatcher')
);
