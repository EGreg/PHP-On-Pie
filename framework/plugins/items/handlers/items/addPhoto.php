<?php

function items_addPhoto()
{
	
}

function items_addPhoto_post()
{
	if (Pie_Dispatcher::uri()->facebook) {
		return;
	}
	if (isset($_POST['fb_sig_app_id'])) {
		$app_id = $_POST['fb_sig_app_id'];
	} else {
		$app = Pie_Config::expect('pie', 'app');
		$app_id = Pie_Config::expect('users', 'facebookApps', $app, 'appId');
	}
	Users::authenticate('facebook', $app_id);
	
	/*
	if (!isset($_REQUEST['content'])) {
		Pie_Response::addError(new Pie_Exception_RequiredField(array(
			'field' => 'content'
		)));
		Pie_Dispatcher::showErrors();
		return;
	}
	*/

	$user = Users::loggedInUser();
	if (!$user) {
		throw new Users_Exception_NotLoggedIn();
	}
	
	// TODO: download a backup copy into a special place for facebook photos
	// TODO: handle uploads
	
	// Facebook photo
	if (!empty($_POST['src_big'])) {
		if (!is_array($_POST['src_big'])) {
			throw new Exception("src_big must be an array");
		}
		// First, we download the photo to store on our site
		foreach ($_POST['src_big'] as $pid => $src_big) {
			$src_small = Pie::ifset($_POST['src_small'][$pid], $src_big);
			$parts = explode('/', $src_big);
			$parts = explode('.', end($parts));
			$ext = end($parts);
			$filename = 'photos'.DS.'facebook'.DS."pid$pid.$ext";
			$abs_filename = ITEMS_PLUGIN_FILES_DIR.DS.$filename;
			if (file_exists($abs_filename)) {
				// A photo was already copied to this filename
				Pie_Config::set('items', 'addPhoto', 'result', 'exists');
				$photo = new Items_Photo();
				$photo->filename = $filename;
				if ($photo = $photo->retrieve()) {
					$item = new Items_Item();
					$item->id = $photo->item_id;
					$item = $item->retrieve(); // relies on DB consistency
					Pie_Config::set('items', 'addPhoto', 'item_id', $item->id);
					Pie_Config::set('items', 'addPhoto', 'state', $item->state);
				}
				return;
			}
			copy($src_big, $abs_filename);
			$item = new Items_Item();
			$item->by_user_id = $user->id;
			$item->thumb_url = $src_small;
			$item->share_count = 0;
			$item->state = 'pending';
			Pie::event('items/addPhoto/saveItem', compact('item'), 'before');
			$item->save();
			$photo = new Items_Photo();
			$photo->src_url = $src_big;
			$photo->filename = $filename;
			$photo->item_id = $item->id;
			Pie::event('items/addPhoto/savePhoto', compact('photo'), 'before');
			$photo->save();
		}
		
	} else if (isset($_FILES['upload'])) {
		// TODO: maybe add checks for size, mime type, etc.
		if ($errcode = $_FILES['upload']['error']) {
			$code = $_FILES['upload']['error'];
			throw new Pie_Exception_UploadError(compact('code'));
		}
		$parts = explode('.', $_FILES['upload']['name']);
		$ext = end($parts);
		$uniqid = isset($_POST['uniqid']) 
			? $_POST['uniqid']
			: uniqid('up.', false);
		$md5 = md5($_FILES['upload']['name']);
		$dirname = 'photos'.DS.'user'.$user->id;
		$abs_dirname = ITEMS_PLUGIN_FILES_DIR.DS.$dirname;
		if (!file_exists($abs_dirname)) {
			mkdir($abs_dirname, 0777, true);
		}
		$filename = $dirname.DS."$uniqid.$md5.$ext";
		$abs_filename = ITEMS_PLUGIN_FILES_DIR.DS.$filename;
		if (file_exists($abs_filename)) {
			// A file was already uploaded via this uniqid
			Pie_Config::set('items', 'addPhoto', 'result', 'exists');
			$photo = new Items_Photo();
			$photo->filename = $filename;
			if ($photo = $photo->retrieve()) {
				$item = new Items_Item();
				$item->id = $photo->item_id;
				$item = $item->retrieve(); // relies on DB consistency
				Pie_Config::set('items', 'addPhoto', 'item_id', $item->id);
				Pie_Config::set('items', 'addPhoto', 'state', $item->state);
			}
			return;
		}
		move_uploaded_file($_FILES['upload']['tmp_name'], $abs_filename);
		$src_big = 'plugins/items/photos/user'.$user->id."/$uniqid.$md5.$ext";
		$src_small = $src_big;
		// TODO: make small version!!!! AND PUT INTO thumb_url
		// Try different functions if they exist, from graphics libs
		$item = new Items_Item();
		$item->by_user_id = $user->id;
		$item->thumb_url = $src_small; 
		$item->share_count = 0;
		$item->state = 'pending';
		Pie::event('items/addPhoto/saveItem', compact('item'), 'before');
		$item->save();
		$photo = new Items_Photo();
		$photo->src_url = $src_big;
		$photo->filename = $filename;
		$photo->item_id = $item->id;
		Pie::event('items/addPhoto/savePhoto', compact('photo'), 'before');
		$photo->save();
	}
	
	// Report as added
	if (!empty($item)) {
		Pie_Config::set('items', 'addPhoto', 'result', 'added');
		Pie_Config::set('items', 'addPhoto', 'item_id', $item->id);
		Pie_Config::set('items', 'addPhoto', 'state', $item->state);
	}
}

function items_addPhoto_response_content()
{
	if (isset($_POST['fb_sig_app_id'])) {
		$app_id = $_POST['fb_sig_app_id'];
	} else {
		$app = Pie_Config::expect('pie', 'app');
		$app_id = Pie_Config::expect('users', 'facebookApps', $app, 'appId');
	}
	Users::authenticate('facebook', $app_id);
	return Pie::tool('items/addPhoto', array());
}

function items_addPhoto_response_replace()
{
	return Pie::tool('items/addPhoto', array(), array('inner' => true));
}


function items_addPhoto_response_fbml_photo_list()
{
	return items_addPhoto_response_photo_list();
}

function items_addPhoto_response_photo_list()
{
	if (!isset($_REQUEST['aid'])) {
		throw new Pie_Exception_RequiredField(array(
			'field' => 'aid'
		));
	}
	return Pie::tool(
		'items/addPhoto', 
		array('aid' => $_REQUEST['aid'], 'tool_part' => 'photo_list'),
		array('inner' => true)
	);
}

function items_addPhoto_response_result()
{
	return Pie_Config::get('items', 'addPhoto', 'result', 'added')
		. ' ' . Pie_Config::get('items', 'addPhoto', 'item_id', null) 
		. ' ' . Pie_Config::get('items', 'addPhoto', 'state', null);
}
