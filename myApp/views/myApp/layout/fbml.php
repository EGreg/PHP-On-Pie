<fb:fbml version="1.1">
<?php Pie_Response::addStylesheet("css/fbml.css")?>

<?php echo Pie_Response::stylesheetsInline() ?> 
<?php echo Pie_Response::scriptsInline()?> 

<div id="body">
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
	<br style="clear: both;">
</div>

<?php echo Pie_Html::script(Pie_Response::scriptLines()) ?>
<?php echo Pie_html::script("Pie.ready();"); ?>
	
</fb:fbml>
