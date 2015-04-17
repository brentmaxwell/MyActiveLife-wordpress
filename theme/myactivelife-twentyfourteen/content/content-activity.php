<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php twentyfourteen_post_thumbnail(); ?>
	<header class="entry-header">
		<?php if ( in_array( 'category', get_object_taxonomies( get_post_type() ) ) && twentyfourteen_categorized_blog() ) : ?>
			<div class="entry-meta">
				<span class="cat-links"><?php echo get_the_category_list( _x( ', ', 'Used between list items, there is a space after the comma.', 'twentyfourteen' ) ); ?></span>
			</div>
		<?php endif; ?>
		<?php
			$activity_types = wp_get_post_terms( $post->ID, 'activity_type');
			if($activity_types){
				foreach($activity_types as $activity_type){
					$activity_type_title .= '<span class="myactivelifeicon myactivelifeicon-activitytype-'.$activity_type->slug.'"></span>';
				}
			}
			
		?>
	
		<?php the_title('<h1 class="entry-title">'.$activity_type_title.'&nbsp;<a href="'.get_the_permalink().'">','</a></h1>');?>
		<div class="entry-meta">
			<?php
				//if ( 'post' == get_post_type() )
					twentyfourteen_posted_on();

				if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
			?>
				<span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'twentyfourteen' ), __( '1 Comment', 'twentyfourteen' ), __( '% Comments', 'twentyfourteen' ) ); ?></span>
			<?php
				endif;

				edit_post_link( __( 'Edit', 'twentyfourteen' ), '<span class="edit-link">', '</span>' );
			?>
			<?php
			$imported_from = wp_get_post_terms( $post->ID, 'keyring_services');
			$source = "";
			if($imported_from){
				foreach($imported_from as $service){
					$sources .= '<span class="myactivelifeicon myactivelifeicon-source-'.$service->slug.'"></span>';
				}
			}
			echo $source;
		?>
			<?php getTermsList('location','<span class="myactivelifeicon myactivelifeicon-location"></span><span class="tags-links">','</span>');?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->
	<?php if ( is_search() ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php else : ?>
	<div class="entry-content">
		<?php $data = get_post_custom($post->ID); ?>
		<?php if(has_term('strava','keyring_services') && array_key_exists('strava_raw',$data)):
			$raw = getPostJson('strava_raw');
			?>
			<div class="map">
				<a href="https://www.strava.com/activities/<?php echo $data['strava_id'][0];?>" target="_parent">
					<?php
					$start = implode(',',$raw->start_latlng);
					$end = implode(',',$raw->end_latlng);
					$shortcode = '[staticmap markers="color:green|'.$start.' color:red|'.$end.'" class="img-responsive center-block thumbnail" height="240" width="480" polyline="color:0xFF0000BF|weight:2|enc:'.urlencode($raw->map->summary_polyline).'"]';
					echo do_shortcode($shortcode);
					?>
				</a>
			</div>
			<table class="stats">
				<tbody>
					<?php if(array_key_exists('elapsed_time',$data)):?>
					<tr>
						<th>Elapsed Time</th>
						<td><?php echo formatElapsedTime($data['elapsed_time'][0]);?></td>
					</tr>
					<?php endif;?>					
					<?php if(array_key_exists('active_time',$data)):?>
					<tr>
						<th>Active Time</th>
						<td><?php echo formatElapsedTime($data['active_time'][0]);?></td>
					</tr>
					<?php endif;?>
					<?php if(array_key_exists('distance',$data)):?>
					<tr>
						<th>Distance</th>
						<td><?php echo formatDistance($data['distance'][0]);?> mi</td>
					</tr>
					<?php endif;?>
					<?php if(array_key_exists('total_elevation_gain',$data)):?>
					<tr>
						<th>Total Elevation Gain</th>
						<td><?php echo formatElevation($data['total_elevation_gain'][0]);?> ft</td>
					</tr>
					<?php endif;?>
					<?php if(array_key_exists('average_speed',$data)):?>
					<tr>
						<th>Average Speed</th>
						<td><?php echo formatSpeed($data['average_speed'][0]);?> mph</td>
					</tr>
					<?php endif;?>
					<?php if(array_key_exists('max_speed',$data)):?>
					<tr>
						<th>Max Speed</th>
						<td><?php echo formatSpeed($data['max_speed'][0]);?> mph</td>
					</tr>
					<?php endif;?>
				</tbody>
			</table>
		<?php endif;?>
	<?php if(!is_singular('media')):?>
		<?php echo do_shortcode('[mediagallery]');?>
	<?php endif;?>
	</div>
	<?php endif;?>
</article>
