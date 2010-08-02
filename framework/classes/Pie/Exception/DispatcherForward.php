<?php

class Pie_Exception_DispatcherForward extends Pie_Exception
{
	
};

Pie_Exception::add(
	'Pie_Exception_DispatcherForward', 
	'Dispatcher is forwarding to $uri',
	array('Pie_Dispatcher')
);
