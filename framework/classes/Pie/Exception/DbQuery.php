<?php

class Pie_Exception_DbQuery extends Pie_Exception
{

};

Pie_Exception::add('Pie_Exception_DbQuery', 'DbQuery Exception: $message ... Query was: $sql');
