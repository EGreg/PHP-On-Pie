<?php

function users_after_pie_addScriptLines()
{
	$app = Pie_Config::expect('pie', 'app');
	$app_json = json_encode($app);
	$fb_app_info = Pie_Config::get('users', 'facebookApps', $app, array());
	if ($fb_app_info) {
		unset($fb_app_info['secret']);
		$fb_app_info_json = json_encode($fb_app_info);
		Pie_Response::addScriptLine(
			"// users {{ \n"
			."\t\tif (!Pie) Pie = {}; if (!Pie.Users) Pie.Users = {};\n"
			."\t\tif (!Pie.Users.facebookApps) Pie.Users.facebookApps = {};\n"
			."\t\tPie.Users.facebookApps[$app_json] = $fb_app_info_json\n"
			."// }} users \n"
		);
	}
}