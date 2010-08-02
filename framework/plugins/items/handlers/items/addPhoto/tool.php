<?php

/**
 * This is a tool for selecting photos (to possibly add)
 * @param $facebook
 *  Optional. You can provide instance of the Facebook class.
 * @param $upload
 *  Defaults to false. If true, shows an option to upload, as well.
 * @param $action_uri
 *  Defaults to 'items/addPhoto'. The URI to submit the form to.
 * @param $filter_visible
 *  Optional string. Set to 'everyone' to only display albums visible to everyone.
 * @param $on_success
 *  Optional string. The url to redirect to after a photo is added or uploaded.
 */
function items_addPhoto_tool($params)
{	
	if (isset(Users::$facebook)) {
		$facebook = Users::$facebook;
	} else {
		$app = Pie_Config::expect('pie', 'app');
		if (!isset(Users::$facebooks[$app])) {
			throw new Pie_Exception_MissingObject(array(
				'name' => 'Users::$facebooks[' . $app . ']'
			));
		}
		$facebook = Users::$facebooks[$app];
	}
	$defaults = array(
		'facebook' => $facebook,
		'upload' => false,
		'action_uri' => 'items/addPhoto',
		'on_success' => Pie_Request::url()
	);
	extract(array_merge($defaults, $params));	

	if (!($facebook instanceof Facebook)) {
		throw new Pie_Exception_WrongType(array(
			'field' => '$facebook', 'type' => 'Facebook'
		));
	}

	if (isset($_REQUEST['_pie']['onSuccess'])) {
		$on_success = $_REQUEST['_pie']['onSuccess'];
	}
	$sn = Pie_Session::name();
	$sid = Pie_Session::id();

	$photos = array();
	if (isset($aid)) {
		$photos = Items::facebookPhotos($facebook, $aid);
		return Pie::view('items/tool/addPhotoList.php', compact('photos'));
	}

	$facebook->require_login();
	$album_rows = Items::facebookAlbums($facebook);
	$albums = array();
	foreach ($album_rows as $ar) {
		if (isset($filter_visible) and $ar['visible'] != $filter_visible) {
			continue;
		}
		$albums[$ar['aid']] = $ar['name'];
	}
	$albums = $albums;
	if (count($album_rows)) {
		$row = reset($album_rows);
		$photos = Items::facebookPhotos($facebook, $row['aid']);
	}
	$throbber_url = Pie_Html::themedUrl('plugins/items/img/anim/throbber.gif');	
	$url_json = json_encode(Pie_Uri::url($action_uri));
	Pie_Response::addStylesheet('plugins/items/css/Items.css');
	if (Pie_Request::accepts('text/fbml')) {
		Pie_Response::addScript('plugins/items/fbjs/Items.fb.js');
	} else {
		Pie_Response::addScript('plugins/items/js/Items.js');
	}
	if (is_bool($upload)) {
		$upload = uniqid('up.', false);
	}
	$addPhoto_url_json = json_encode(Pie_Uri::url('items/addPhoto'));
	Pie_Response::addScriptLine(
		"\tPie.Items.urls['items/addPhoto'] = $addPhoto_url_json;"
	);
	return Pie::view('items/tool/addPhoto.php', compact(
		'action_uri', 'on_success', 'on_added',
		'albums', 'photos',
		'throbber_url', 'upload'
	));
}
