<div class='items_addPhoto_tool_select pie_choice'>
	<h2>Select a photo from my albums</h2>
	
	<?php echo Pie_Html::hidden(compact('queried_facebook')) ?>
	<?php echo Pie_Html::label('albums') ?>Choose an album:</label>
	<?php echo Pie_Html::select('albums', array('id' => 'albums')) ?>
		<?php echo Pie_Html::options($albums) ?>
	</select>
	<?php echo Pie_Html::div('photos', 'items_addPhoto_tool_photos') ?>
		<?php echo Pie::view('items/tool/addPhotoList.php', compact('photos')) ?>
	</div>
	<?php if (Pie_Request::accepts('text/fbml')): ?>
		<fb:js-string var="fbml.<?php echo Pie_Html::getIdPrefix() ?>throbber">
			<div class="items_addPhoto_tool_throbber">
				<img src="<?php echo $throbber_url ?>" alt="loading..." />
			</div>
		</fb:js-string>
	<?php else: ?>
		<?php echo Pie_Html::div('throbber_html', '', 
			array('style' => 'display: none;')) 
		?>
			<div class="items_addPhoto_tool_throbber">
				<img src="<?php echo $throbber_url ?>" alt="loading..." />
			</div>
		</div>
	<?php endif ?>
</div>

<?php if ($upload): ?>
<?php echo Pie_Html::form($action_uri, 'post', array(
	'id' => 'form',
	'enctype' => 'multipart/form-data'
)) ?> 
<?php echo Pie_Html::formInfo($on_success) ?>
	<div class='items_addPhoto_tool_upload pie_choice'>
		<h2>Upload</h2>
		<div class="items_addPhoto_tool_certify">
			By uploading, you certify that you have the right to distribute the image and that it does not violate the Terms of Service.
		</div>
		<div class='items_addPhoto_tool_photo_uploader'>
			<!-- MAX_FILE_SIZE must precede the file input field -->
			<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
			<input type="hidden" name="uniqid" value="<?php echo $upload ?>" />
			<input name="upload" type="file" 
				id="<?php echo Pie_Html::getIdPrefix() ?>upload" 
				class="file" accept="image/gif,image/jpeg,image/png" />
		</div>
	</div>
</form>
<?php endif; ?>
