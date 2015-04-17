<?php
/**
 * The template for displaying image attachments
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

// Retrieve attachment metadata.

$metadata = wp_get_attachment_metadata();
$media_type = explode('/',$post->post_mime_type)[0];
get_header();
?>
	<section id="primary" class="content-area image-attachment">
		<div id="content" class="site-content" role="main">

	<?php
		// Start the Loop.
		while ( have_posts() ) : the_post();
		$full_json = getPostJson('full', $post->ID);
		$full = array(
			$full_json->url,
			$full_json->width,
			$full_json->height,
			$orientation = $full_json->height > $full_json->width ? 'portrait' : 'landscape'
		);
	?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title"><span class="myactivelifeicon myactivelifeicon-type-'.$media_type.'"></span>&nbsp;', '</h1>' ); ?>

					<div class="entry-meta">
						<span class="entry-date"><time class="entry-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
						<?php
				  			$terms = get_the_terms($post->ID,'keyring_services');
							foreach($terms as $term){
								echo '<span class="myactivelifeicon myactivelifeicon-source-'.$term->slug.'"></span>';
							}
						?>
						<span class="parent-post-link"><a href="<?php echo esc_url( get_permalink( $post->post_parent ) ); ?>" rel="gallery"><?php echo get_the_title( $post->post_parent ); ?></a></span>
						<?php getTermsList('people','<span class="myactivelifeicon myactivelifeicon-person"></span><span class="people-tags">&nbsp;','</span>');?>
						
						<?php edit_post_link( __( 'Edit', 'twentyfourteen' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .entry-meta -->
				</header><!-- .entry-header -->

				<div class="entry-content">
					<div class="entry-attachment">
						<div class="attachment">
							<?php if($media_type == 'image'):?>
								<img width="<?php echo $full[1];?>" height="<?php echo $full[2];?>" src="<?php echo $full[0]; ?>"/>
							<?php endif;?>
							<?php if($media_type == 'video'):?>
							<?php
								$embed_url = get_post_meta($post->ID,'link',true);
								$oembed = wp_oembed_get($embed_url);
								if($oembed != false){
									echo $oembed;
								}	
								else{
								?>
								<a href="<?php echo $embed_url;?>" target="_blank">
									<img width="<?php echo $full[1];?>" height="<?php echo $full[2];?>" src="<?php echo $full[0]; ?>"/>
								</a>
								<?php
								} 
							?>
							<?php endif;?>
						</div><!-- .attachment -->

						<?php if ( has_excerpt() ) : ?>
						<div class="entry-caption">
							<?php the_excerpt(); ?>
						</div><!-- .entry-caption -->
						<?php endif; ?>
					</div><!-- .entry-attachment -->

					<?php
						the_content();
						wp_link_pages( array(
							'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentyfourteen' ) . '</span>',
							'after'       => '</div>',
							'link_before' => '<span>',
							'link_after'  => '</span>',
						) );
					?>
				</div><!-- .entry-content -->
			</article><!-- #post-## -->

			<nav id="image-navigation" class="navigation image-navigation">
				<div class="nav-links">
				<?php previous_image_link( false, '<div class="previous-image">' . __( 'Previous Image', 'twentyfourteen' ) . '</div>' ); ?>
				<?php next_image_link( false, '<div class="next-image">' . __( 'Next Image', 'twentyfourteen' ) . '</div>' ); ?>
				</div><!-- .nav-links -->
			</nav><!-- #image-navigation -->

			<?php comments_template(); ?>

		<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php
get_sidebar();
get_footer();
