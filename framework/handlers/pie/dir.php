<?php

/**
 * Default pie/dir handler.
 * Just displays a simple directory listing,
 * and prevents further processing by returning true.
 */
function pie_dir()
{
	$filename = Pie_Request::filename();

	// TODO: show directory listing
	echo Pie::view('pie/dir.php', compact('filename'));
	
	return true;
}
