<?php
$media_type = explode('/',$post->post_mime_type)[0];
$full_json = getPostJson('image', $post->ID);
$thumb_json = getPostJson('thumbnail', $post->ID);
$full = array(
	$full_json->url,
	$full_json->width,
	$full_json->height,
	$orientation = $full_json->height > $full_json->width ? 'portrait' : 'landscape'
);
$thumb = array(
	$thumb_json->url,
	(150/$thumb_json->height) * $thumb_json->width,
	150,
	$orientation = $thumb_json->height > $thumb_json->width ? 'portrait' : 'landscape'
);
if(!empty($thumb)):
?>
<figure class="gallery-item">
	<div class="gallery-icon <?php echo $thumb['3'];?>">
		<a href="<?php the_permalink();?>">
			<img width="<?php echo $thumb[1];?>" height="<?php echo $thumb[2];?>" src="<?php echo $thumb[0]; ?>"/>
		</a>
	</div>
	<figcaption class="wp-caption-text gallery-caption" id="gallery-<?php echo $post->parent_post;?>-<?php the_ID();?>">
		<?php
			echo '<span class="myactivelifeicon myactivelifeicon-type-'.$media_type.'"></span>';
		?>
		<?php the_title();?>
		<br/>
		<?php getTermsList('people','<span class="myactivelifeicon myactivelifeicon-person"></span>&nbsp;','');?>
		
	</figcaption>
</figure>
<?php endif;?>