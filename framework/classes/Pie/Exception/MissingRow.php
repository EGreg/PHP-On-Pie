<?php

class Pie_Exception_MissingRow extends Pie_Exception
{
	
};

Pie_Exception::add('Pie_Exception_MissingRow', 'Missing $table with $criteria');
