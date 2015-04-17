<?php
class MediaGallery{
	function __construct(){
		add_shortcode('mediagallery', array($this, 'do_shortcode'));
	}
	
	function do_shortcode( $attr ) {
		$post = get_post();
		static $instance = 0;
		$instance++;
	
		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) ) {
				$attr['orderby'] = 'post__in';
			}
			$attr['include'] = $attr['ids'];
		}
	
		$output = apply_filters( 'post_gallery', '', $attr );
		if ( $output != '' ) {
			return $output;
		}
	
		$html5 = current_theme_supports( 'html5', 'gallery' );
		$atts = shortcode_atts( array(
			'order'      => 'DESC',
			'orderby'    => 'post_date',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => $html5 ? 'figure'     : 'dl',
			'icontag'    => $html5 ? 'div'        : 'dt',
			'captiontag' => $html5 ? 'figcaption' : 'dd',
			'columns'    => 0,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => '',
			'post_type'  => 'media',
			'post_status' => 'inherit,publish,unattached',
			'title'      => 'Photos & Videos'
		), $attr, 'gallery' );
	
		$id = intval( $atts['id'] );
	
		if ( ! empty( $atts['include'] ) ) {
			$_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => $atts['post_status'], 'post_type' => $atts['post_type'], 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( ! empty( $atts['exclude'] ) ) {
			$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => $atts['post_status'], 'post_type' => $atts['post_type'], 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
		} else {
			$attachments = get_children( array( 'post_parent' => $id, 'post_type' => $atts['post_type'],'post_status' => $atts['post_status'], 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
		}
		
		if ( empty( $attachments ) ) {
			return '';
		}
	
		$itemtag = tag_escape( $atts['itemtag'] );
		$captiontag = tag_escape( $atts['captiontag'] );
		$icontag = tag_escape( $atts['icontag'] );
		$valid_tags = wp_kses_allowed_html( 'post' );
		if ( ! isset( $valid_tags[ $itemtag ] ) ) {
			$itemtag = 'dl';
		}
		if ( ! isset( $valid_tags[ $captiontag ] ) ) {
			$captiontag = 'dd';
		}
		if ( ! isset( $valid_tags[ $icontag ] ) ) {
			$icontag = 'dt';
		}
	
		$columns = intval( $atts['columns'] );
		if($columns == 0){
			$columns = count($attachments) < 6 ? count($attachments) : 6;	
		}
		 
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		$float = is_rtl() ? 'right' : 'left';
	
		$selector = "gallery-{$instance}";
	
		$gallery_style = '';
	
		/**
		 * Filter whether to print default gallery styles.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $print Whether to print default gallery styles.
		 *                    Defaults to false if the theme supports HTML5 galleries.
		 *                    Otherwise, defaults to true.
		 */
		if ( apply_filters( 'use_default_gallery_style', ! $html5 ) ) {
			$gallery_style = "
			<style type='text/css'>
				#{$selector} {
					margin: auto;
				}
				#{$selector} .gallery-item {
					float: {$float};
					margin-top: 10px;
					text-align: center;
					width: {$itemwidth}%;
				}
				#{$selector}.gallery-size-thumbnail .gallery-item img{
					height:150px;
				}
				#{$selector} img {
					border: 2px solid #cfcfcf;
				}
				#{$selector} .gallery-caption {
					margin-left: 0;
				}
				/* see gallery_shortcode() in wp-includes/media.php */
			</style>\n\t\t";
			
		}
	
		$size_class = sanitize_html_class( $atts['size'] );
		$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
	
		/**
		 * Filter the default gallery shortcode CSS styles.
		 *
		 * @since 2.5.0
		 *
		 * @param string $gallery_style Default CSS styles and opening HTML div container
		 *                              for the gallery shortcode output.
		 */
		 
		$output = "";
		$output .= apply_filters( 'gallery_style', $gallery_style . $gallery_div );
		if(!empty($atts['title'])){
			 $output .= '<h2 class="gallery-title">'.esc_html($atts['title']).'</h2>';
		}
		$i = 0;
		foreach ( $attachments as $id => $attachment ) {
			$meta = get_post_meta($id); 
			$thumb = getPostJson($atts['size'],$id);
			$attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "$selector-$id" ) : '';
			if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
				$image_output = '<a href="'.$meta['link'][0].'"><img src="'.$thumb->url.'"/></a>';
			} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
				$image_output = '<img src="'.$thumb->url.'"/>';
			} else {
				$image_output = '<a href="'.get_permalink($id).'"><img src="'.$thumb->url.'"/></a>';
			}
	
			$orientation = '';
			if ( isset( $thumb->height, $thumb->width ) ) {
				$orientation = ( $thumb->height > $thumb->width ) ? 'portrait' : 'landscape';
			}
			$output .= "<{$itemtag} class='gallery-item'>";
			$output .= "
				<{$icontag} class='gallery-icon {$orientation}'>
					$image_output
				</{$icontag}>";
			if ( $captiontag) {
				$output .= "<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>";
				$media_type = explode('/',$attachment->post_mime_type)[0];
				$output .= '<span class="genericon genericon-'.$media_type.'"></span>';
				$output .= get_the_title($attachment->ID);

				$list = get_the_term_list($attachment->ID,'people','<span class="genericon genericon-user"></span>&nbsp;',', ','');
				if ( $list ) {
					$output .= '<br/>'.$list;
				}
		
				$output .= "</{$captiontag}>";
			}
			$output .= "</{$itemtag}>";
			if ( ! $html5 && $columns > 0 && ++$i % $columns == 0 ) {
				$output .= '<br style="clear: both" />';
			}
		}
	
		if ( ! $html5 && $columns > 0 && $i % $columns !== 0 ) {
			$output .= "
				<br style='clear: both' />";
		}
	
		$output .= "
			</div>\n";
	
		return $output;
	}
}

$mediaGallery = new MediaGallery();