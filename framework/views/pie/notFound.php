<?php echo '<?xml version="1.0" encoding="UTF-8" ?>' ?> 
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>Minimal XHTML 1.0 Document</title>
  </head>
  <body>
	<h1>Default PIE 404</h1>
	<h2>
		The url <span class='url'><?php echo $url ?></span> doesn't point to anything.
	</h2>
	<div>
		You should implement your own handlers for "pie/noModule" and "pie/notFound". A simple one will suffice, such as
<pre>

<?php echo "&lt;?php" ?>

/**
 * My custom pie/noModule handler.
 */
function pie_noModule($params)
{
        header("HTTP/1.0 404 Not Found");
        $url = Pie_Request::url();
        Pie_Dispatcher::uri()->module = 'myModule';
        Pie::event('pie/response', array());
}
</pre>
		
	</div>
	<!-- this webpage needs to be bigger than 512 bytes, for Chrome to not use its own 404 page -->
  </body>
</html>
