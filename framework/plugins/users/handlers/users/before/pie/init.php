<?php

function users_before_pie_init()
{
	$facebook_apps = Pie_Config::get('users', 'facebookApps', array());
	foreach ($facebook_apps as $app_id => $fb_info) {
		if (isset($fb_info['url'])) {
			$subpath = isset($fb_info['subpath']) ? $fb_info['subpath'] : '';
			Pie_Config::set('pie', 'proxies', Pie_Request::baseUrl(true).$subpath, $fb_info['url']);
		}
	}
}
