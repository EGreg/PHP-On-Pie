<?php

class Pie_Exception_BadUrl extends Pie_Exception
{
	
};

Pie_Exception::add('Pie_Exception_BadUrl', 'Bad url $url (the base url is $base_url)');
