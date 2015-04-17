<pre>
<?php
/*
$count=0;
$query_args = array(
	'post_type' => 'media',
	'nopaging' => true,
	'post_status' => 'pending'
);

$query = new WP_Query($query_args);
foreach($query->posts as $post){
	if($post->post_parent != 0){
		$post->post_status = get_post_field('post_status',$post->post_parent);
		wp_update_post($post);
		$count++;
	}
}
echo $count;
?*
?>
</pre>