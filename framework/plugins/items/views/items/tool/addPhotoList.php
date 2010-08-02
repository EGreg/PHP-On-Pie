
<div class="items_addPhoto_tool_photos_list">
	<?php if ($photos) : ?>
		<?php foreach ($photos as $photo) {
			$pid = $photo['pid'];
			echo 
			  Pie_Html::form('items/addPhoto', 'post', array(
				'id' => 'facebook'.$pid,
				'style' => 'display: inline'
			  )),
			  Pie_Html::img($photo['src'], $photo['pid']),
			  Pie_Html::hidden(array(
				"src[$pid]" => $photo['src'],
				"src_width[$pid]" => $photo['src_width'],
				"src_height[$pid]" => $photo['src_height'],
				"src_small[$pid]" => $photo['src_small'],
				"src_small_width[$pid]" => $photo['src_small_width'],
				"src_small_height[$pid]" => $photo['src_small_height'],
				"src_big[$pid]" => $photo['src_big'],
				"src_big_width[$pid]" => $photo['src_big_width'],
				"src_big_height[$pid]" => $photo['src_big_height'],
				"link[$pid]" => $photo['link'],
				"caption[$pid]" => $photo['caption'],
				"object_id[$pid]" => $photo['object_id']
			  )),
			  "</form>";
		}?>
	<?php else: ?>
		<span class='pie_missing'>No photos in this album</span>
	<?php endif; ?>
</div>
