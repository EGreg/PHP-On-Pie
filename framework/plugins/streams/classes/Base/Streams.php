<?php

/**
 * Autogenerated base class for the streams model.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Streams.php file.
 *
 * @package streams
 */
abstract class Base_Streams
{
	static $table_classes = array (
  0 => 'Streams_Forum',
  1 => 'Streams_ForumRole',
  2 => 'Streams_Message',
  3 => 'Streams_Notification',
  4 => 'Streams_Sent',
  5 => 'Streams_Stream',
  6 => 'Streams_Subscription',
);

	/** @return Db_Mysql */
	static function db()
	{
		return Db::connect('streams');
	}

	static function connectionName()
	{
		return 'streams';
	}
};