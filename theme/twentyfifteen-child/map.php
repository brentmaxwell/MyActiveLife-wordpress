<?php
	if(get_post_meta($post->ID,'geo_public',true)==1){
		$lat = get_post_meta($post->ID,'geo_latitude',true);
		$long = get_post_meta($post->ID,'geo_longitude',true);
		if($lat != null && $long != null){
			echo do_shortcode('[map lat="'.$lat.' "long"='.$long.' size="100x100" class="footer-map"]');
		}
	}
?>