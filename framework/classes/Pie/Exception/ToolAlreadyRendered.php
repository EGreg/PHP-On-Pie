<?php

class Pie_Exception_ToolAlreadyRendered extends Pie_Exception
{
	
};

Pie_Exception::add('Pie_Exception_ToolAlreadyRendered', 'Tool named "$tool_name" with id "$id" already rendered');
