<?php

function pie_addScriptLines()
{
	$app = Pie_Config::expect('pie', 'app');
	$uri = Pie_Dispatcher::uri();
	
	$proxies_json = json_encode(Pie_Config::get('pie', 'proxies', array()));
	$uri_json = json_encode($uri->toArray());
	$url = Pie_Request::url();
	$url_json = json_encode($url);
	$proxy_url_json = json_encode(Pie_Uri::url($url));
	$base_url = json_encode(Pie_Request::baseUrl());
	Pie_Response::addScriptLine( <<<EOT
// pie {{
		Pie.info = {
			"proxies": $proxies_json,
			"uri": $uri_json,
			"url": $url_json,
			"proxyUrl": $proxy_url_json,
			"baseUrl": $base_url
		};
EOT
	);

	$uris = Pie_Config::get('pie', 'javascript', 'uris', array());
	$urls = array();
	foreach ($uris as $u) {
		$urls["$u"] = Pie_Uri::url("$u");
	}
	$urls_json = json_encode($urls);
	Pie_Response::addScriptLine("\t\tPie.urls = $urls_json;");

	// Export more variables to inline js
	$app = Pie_Config::expect('pie', 'app');
	$app_json = json_encode($app);
	Pie_Response::addScriptLine(
		"\t\tPie.app = $app_json;\n"
		."// }} pie"
	);
	$snf = Pie_Config::get('pie', 'session', 'nonceField', 'nonce');
	$nonce = isset($_SESSION[$snf]) ? $_SESSION[$snf] : null;
	if ($nonce) {
		$nonce_json = json_encode($nonce);
		Pie_Response::addScriptLine("\t\tPie.nonce = $nonce_json;");
	}
}