<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo $title ?></title>
	<link rel="shortcut icon" href="<?php echo Pie_Request::baseUrl() ?>/favicon.ico" type="image/x-icon">
	
	<?php echo Pie_Response::stylesheets("\n\t") ?> 
	<?php echo Pie_Response::scripts("\n\t") ?> 
	<style type="text/css">
		<?php echo Pie_Response::stylesInline() ?> 
	</style>
	<!--[if lt IE 7]>
		<?php // echo Pie_Html::tag('script', array('src' => 'js/unitpngfix.js')) ?> 
		<style type="text/css">
			/* This is for fixed elements to work properly in IE6 and IE7
			html, body { height: 100%; overflow: auto; }
			body { behavior: url("csshover.htc"); }
			#example_fixed_element { position: absolute; }
			*/
		</style>
	<![endif]-->
</head>
<body>
	<script type='text/javascript' src='http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php'></script>
	<div id="dashboard_slot">
		<?php echo $dashboard ?> 
	</div>
	<?php if ($notices): ?>
		<div id="notices_slot">
			<?php echo $notices ?>
		</div>
	<?php endif; ?>
	<div id="content_slot">
		<?php echo $content; ?> 
	</div>
	<?php echo Pie_Html::script(Pie_Response::scriptLines()) ?>
	<?php echo Pie_html::script("
		if (typeof(jQuery) != 'undefined') {
			jQuery(document).ready(function() { Pie.ready(); });
		} else {
			if ('addEventListener' in document) {
				window.addEventListener('load', function() { Pie.ready(); }, false); 
			} else if ('attachEvent' in document) {
				document.attachEvent('onload', function() { Pie.ready(); });
			}
		}
	") ?>
</body>
</html>
