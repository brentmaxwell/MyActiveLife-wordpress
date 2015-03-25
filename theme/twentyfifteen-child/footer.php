<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the "site-content" div and all content after.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */
?>

	</div><!-- .site-content -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		
		<?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) ) : ?>
			<div class="site-info">
				<?php if ( is_active_sidebar( 'footer-1' )): ?>
					<?php dynamic_sidebar( 'footer-1' ); ?>
				<?php endif;?>
				<?php if ( is_active_sidebar( 'footer-2' )): ?>
					<?php dynamic_sidebar( 'footer-2' ); ?>
				<?php endif;?>
			</div><!-- .site-info -->
		<?php endif; ?>
	</footer><!-- .site-footer -->

</div><!-- .site -->

<?php wp_footer(); ?>

</body>
</html>