<?php

class Pie_Exception_WrongType extends Pie_Exception
{
	
};

Pie_Exception::add('Pie_Exception_WrongType', '$field is the wrong type, expecting $type.');
