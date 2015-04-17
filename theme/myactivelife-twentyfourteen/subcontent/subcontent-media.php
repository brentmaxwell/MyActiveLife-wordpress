<?php
	$child_query_args = array(
		'post_parent' => $post->ID,
		'post_type' => 'media',
		'numberposts' => -1
	);
	$children_query = new WP_Query($child_query_args);
	if($children_query->have_posts()):
?>
	<div id="gallery-<?php echo $post->ID;?>" class="gallery gallery-columns-6 gallery-size-thumbnail">
		<?php
			while($children_query->have_posts()){
				$children_query->the_post();
				get_template_part('content/content',$post->post_type);
			}
			wp_reset_query();
		?>
	</div>
	<?php endif;?>