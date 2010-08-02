<?php

/// { aggregate handlers for production
/// pie/*.php
/// }

function pie_init()
{
	//Db::connect('users')->generateModels(PIE_DIR.DS.'plugins'.DS.'users'.DS.'classes');
	//Db::connect('games')->generateModels(PIE_DIR.DS.'plugins'.DS.'games'.DS.'classes');
	
	Pie::log('To stop logging database queries, change pie/init.php');
	Pie_Config::set('pie', 'handlersBeforeEvent', 'db/query/execute', 'temp_query');
}

function temp_query($params)
{
	$params['query']->getSql("Pie::log");
}
