<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php twentyfourteen_post_thumbnail(); ?>
	<header class="entry-header">
		<?php if ( in_array( 'category', get_object_taxonomies( get_post_type() ) ) && twentyfourteen_categorized_blog() ) : ?>
			<div class="entry-meta">
				<span class="cat-links"><?php echo get_the_category_list( _x( ', ', 'Used between list items, there is a space after the comma.', 'twentyfourteen' ) ); ?></span>
			</div>
		<?php endif; ?>
		<?php the_title('<h1 class="entry-title"><span class="myactivelifeicon myactivelifeicon-trip"></span>&nbsp;<a href="'.get_the_permalink().'">','</a></h1>');?>
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
			<?php getTermsList('location','<span class="myactivelifeicon myactivelifeicon-location"></span> <span class="tags-links">','</span>');?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->
	<?php if ( is_search() ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php else : ?>
	<div class="entry-content">
		<?php if(!is_singular('media')):?>
			<div class="media-gallery">
			<?php echo do_shortcode('[mediagallery]');?>
			</div>
		<?php endif;?>
	</div>
	<?php endif;?>
</article>
