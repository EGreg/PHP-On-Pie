<?php

class Items extends Base_Items
{
	const ATTRIBUTE_CATEGORY = 1;
	
	static function facebookAlbums($facebook, $uid = null)
	{
		if (!($facebook instanceof Facebook)) {
			throw new Pie_Exception_WrongType(array(
				'field' => '$facebook', 'type' => 'Facebook'
			));
		}
		if (!isset($uid)) {
			$uid = $facebook->user;
			if (!isset($uid)) {
				throw new Users_Exception_NotLoggedIn();
			}
		}
		$q = "SELECT aid, name, description, location, size, "
		    . "link, edit_link, visible, modified_major, object_id "
			. "FROM album WHERE owner = $uid";
		return Users::fql($facebook, $q);
	}
	
	static function facebookPhotos($facebook, $aid)
	{
		if (!($facebook instanceof Facebook)) {
			throw new Pie_Exception_WrongType(array(
				'field' => '$facebook', 'type' => 'Facebook'
			));
		}
		$q = "SELECT pid, aid, owner, "
		 	. "src_small, src_small_height, src_small_width, "
		    . "src_big, src_big_height, src_big_width, "
			. "src, src_height, src_width, "
			. "link, caption, object_id "
			. "FROM photo WHERE aid = \"$aid\"";
		return Users::fql($facebook, $q);
	}
	
	static function facebookPhotosTaggingUser($facebook, $uid = null)
	{
		// TODO: implement
	}
}
