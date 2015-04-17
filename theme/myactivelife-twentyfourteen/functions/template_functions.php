<?php

function getPostFormat(){
	$format = get_post_format();
	if ( current_theme_supports( 'post-formats', $format ) ) {
		printf( '<span class="entry-format">%1$s<a href="%2$s">%3$s</a></span>',
			sprintf( '<span class="screen-reader-text">%s </span>', _x( 'Format', 'Used before post format.', 'myactivelife' ) ),
			esc_url( get_post_format_link( $format ) ),
			get_post_format_string( $format )
		);
	}
}

function getPostDate(){
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';

		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( 'c' ) ),
			get_the_date()
		);

		printf( '<li><span class="glyphicon glyphicon-calendar"></span> <a href="%1$s" rel="bookmark">%2$s</a></span></li>',
			esc_url( get_permalink() ),
			$time_string
		);
}

function getPostDateTime(){
		getPostDate();
		getPostTime();
}

function getPostTime(){
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';

		$time_string = sprintf( $time_string,
			esc_attr( get_the_time( 'c' ) ),
			get_the_time()
		);

		printf( '<li><span class="glyphicon glyphicon-time"></span> <a href="%1$s" rel="bookmark">%2$s</a></span></li>',
			esc_url( get_permalink() ),
			$time_string
		);
}

function getAuthor(){
		if ( is_singular() || is_multi_author() ) {
			printf( '<li><span class="glyphicon glyphicon-user"></span> <span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span></li>',
				esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
				get_the_author()
			);
		}
}
function getCategories(){
		$categories_list = get_the_category_list( _x( ', ', 'Used between list items, there is a space after the comma.', 'myactivelife' ) );
		if ( $categories_list && is_categorized_blog()) {
			printf( '<li><span class="glyphicon glyphicon-folder-open"></span> %1$s</li>',
				$categories_list
			);
		}
}
function getTags(){
		$tags_list = get_the_tag_list( '<li><span class="glyphicon glyphicon-tags"></span> &nbsp;',', ','</li>' );
		if ( $tags_list ) {
			echo $tags_list;
		}
	}
	
function getTermsList($tax,$before,$after){
	$list = get_the_term_list($post->ID,$tax,$before,', ',$after);
	if ( $list ) {
		echo $list;
	}
}

function getAttachmentMeta(){
	if ( is_attachment() && wp_attachment_is_image() ) {
		// Retrieve attachment metadata.
		$metadata = wp_get_attachment_metadata();

		printf( '<li><span class="full-size-link"><span class="screen-reader-text">%1$s </span><a href="%2$s">%3$s &times; %4$s</a></span></li>',
			_x( 'Full size', 'Used before full size attachment link.', 'myactivelife' ),
			esc_url( wp_get_attachment_url() ),
			$metadata['width'],
			$metadata['height']
		);
	}
}

function getCommentsLink(){
	if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
		echo '<li><span class="comments-link">';
		comments_popup_link( __( 'Leave a comment', 'myactivelife' ), __( '1 Comment', 'myactivelife' ), __( '% Comments', 'myactivelife' ) );
		echo '</span></li>';
	}
}

function getEditPostLink(){
	edit_post_link( '<span class="genericon genericon-edit"></span> '.__( 'Edit', 'myactivelife' ),'<li>',"</li>");
}

function is_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'myactivelife_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,

			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'myactivelife_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so twentyfifteen_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so twentyfifteen_categorized_blog should return false.
		return false;
	}
}

function pagination(){
	$links = paginate_links(array(
		'type'=>'array',
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
	));
	if(!empty($links)){
	foreach($links as $key=>$value){
		$value = '<li>'.$value.'</li>';
		$value = str_replace("<li><span class='page-numbers current'>",'<li class="active"><a>',$value);
		$value = str_replace('</span>','</a>',$value);
		$links[$key] = $value;
	}
	$output = '<nav><ul class="pagination">'.implode('',$links).'</ul></nav>';
	echo $output;
	}
}
