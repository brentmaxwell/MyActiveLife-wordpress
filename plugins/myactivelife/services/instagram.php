<?php
class MyActiveLife_Services_Instagram{
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
	}

	public function register_importer(){
		
		MyActiveLife_Services_Instagram_Keyring(); // Load the class code from above
		add_action( 'keyring_load_services', array('MyActiveLife_Services_Instagram_Keyring_Service','register_service') );
		keyring_register_importer(
			'instagram',
			'MyActiveLife_Services_Instagram_Keyring_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your photos from Instagram.', 'keyring' )
		);
	}
}

// This is a horrible hack, because WordPress doesn't support dependencies/load-order.
// We wrap our entire class definition in a function, and then only call that on a hook
// where we know that the class we're extending is available. *hangs head in shame*
function MyActiveLife_Services_Instagram_Keyring() {

class MyActiveLife_Services_Instagram_Keyring_Importer extends Keyring_Importer_Base {
	const SLUG              = 'instagram';    // e.g. 'twitter' (should match a service in Keyring)
	const LABEL             = 'Instagram';    // e.g. 'Twitter'
	const KEYRING_SERVICE   = 'Keyring_Service_Instagram';    // Full class name of the Keyring_Service this importer requires
	const REQUESTS_PER_LOAD = 3;     // How many remote requests should be made before reloading the page?
	const NUM_PER_REQUEST   = 25;     // Number of images per request to ask for
	const SCHEDULE = 'fifteenminutes';

	var $auto_import = false;

	function handle_request_options() {
		// Validate options and store them so they can be used in auto-imports
		if ( empty( $_POST['category'] ) || !ctype_digit( $_POST['category'] ) )
			$this->error( __( "Make sure you select a valid category to import your pictures into." ) );

		if ( empty( $_POST['author'] ) || !ctype_digit( $_POST['author'] ) )
			$this->error( __( "You must select an author to assign to all pictures." ) );

		if ( isset( $_POST['auto_import'] ) )
			$_POST['auto_import'] = true;
		else
			$_POST['auto_import'] = false;

		// If there were errors, output them, otherwise store options and start importing
		if ( count( $this->errors ) ) {
			$this->step = 'options';
		} else {
			$this->set_option( array(
				'category'    => (int) $_POST['category'],
				'tags'        => explode( ',', $_POST['tags'] ),
				'author'      => (int) $_POST['author'],
				'auto_import' => $_POST['auto_import'],
			) );

			$this->step = 'import';
		}
	}

	function build_request_url() {
		// Base request URL
		$url = "https://api.instagram.com/v1/users/self/media/recent/?count=" . self::NUM_PER_REQUEST;

		if ( $this->auto_import ) {
			// Get most recent image we've imported (if any), and its date so that we can get new ones since then
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		// First import starts from now and imports back to day-0.
		// Auto imports start from the most recently imported and go up to "now"
		$latest = get_posts( array(
			'numberposts' => 1,
			'post_type' => 'media',
			'author' => (int) $_POST['author'],
			'orderby'     => 'date',
			'order'       => $order,
			'tax_query'   => array( array(
				'taxonomy' => 'keyring_services',
				'field'    => 'slug',
				'terms'    => array( $this->taxonomy->slug ),
				'operator' => 'IN',
			) ),
		) );

		// If we have already imported some, then import around that
		if ( $latest ) {
			$id = get_post_meta( $latest[0]->ID, 'instagram_id', true );
			echo $id;
			if ( $this->auto_import )
				$url = add_query_arg( 'min_id', $id, $url );
			else
				$url = add_query_arg( 'max_id', $id, $url );
		}
		echo $url;
		return $url;
	}

	function extract_posts_from_data( $raw ) {
		global $wpdb;

		$importdata = $raw;

		if ( null === $importdata ) {
			$this->finished = true;
			return new Keyring_Error( 'keyring-instagram-importer-failed-download', __( 'Failed to download your images from Instagram. Please wait a few minutes and try again.', 'keyring' ) );
		}

		// Make sure we have some pictures to parse
		if ( !is_object( $importdata ) || !count( $importdata->data ) ) {
			$this->finished = true;
			return;
		}

		// Parse/convert everything to WP post structs
		
		foreach ( $importdata->data as $post ) {
			// Post title can be empty for Images, but it makes them easier to manage if they have *something*
			if ( !empty( $post->caption ) )
				$post_title = strip_tags( $post->caption->text );
			$post_type = 'media';
			// Parse/adjust dates
			$post_date_gmt = $post->created_time;
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = get_date_from_gmt( $post_date_gmt );

			// Include geo Data
			$geo = false;
			if ( !empty( $post->location ) ) {
				$geo = array(
					'lat'  => $post->location->latitude,
					'long' => $post->location->longitude,
				);
			}
			$thumbnail = json_encode($post->images->thumbnail);
			$full = json_encode($post->images->standard_resolution);
			$height = $post->images->standard_resolution->height;
			$width = $post->images->standard_resolution->width;
			// Tags
			$tags = $this->get_option( 'tags' );
			if ( !empty( $post->tags ) )
				$tags = array_merge( $tags, $post->tags );

			// Other bits
			$post_author      = $this->get_option( 'author' );
			$post_status      = 'pending';
			$post_mime_type = $post->type;
			$instagram_id     = $post->id;
			$link    = $post->link;
	
			$instagram_raw    = $post;

			// Build the post array, and hang onto it along with the others
			$this->posts[] = compact(
				'post_author',
				'post_date',
				'post_date_gmt',
				'post_content',
				'post_title',
				'post_status',
				'post_category',
				'post_type',
				'geo',
				'tags',
				'instagram_id',
				'link',
				'thumbnail',
				'full',
				'height',
				'width',
				'instagram_raw',
				'post_mime_type'
			);
		}
	}

	function insert_posts() {
		global $wpdb;
		$imported = 0;
		$skipped  = 0;
		do_action('allow_empty_post');
		foreach ( $this->posts as $post ) {
			// See the end of extract_posts_from_data() for what is in here
			extract( $post );
			$query_args = array(
				'post_type' => 'activity',
				'meta_query'=> array(
					'relation' => 'AND',
					array(
						'key'=>'start_timestamp',
						'value' => strtotime($post_date_gmt),
						'type' => 'numeric',
						'compare' => '<='
					),
					array(
						'key'=>'end_timestamp',
						'value' => strtotime($post_date_gmt),
						'type' => 'numeric',
						'compare' => '>='
					)
				)
			);
			$activity_query = new WP_Query($query_args);
			if($activity_query->post_count > 0){
				if(!array_key_exists('post_parent',$post)){
					$post['post_parent'] = $activity_query->post->ID;
					$post['post_status'] = $activity_query->post->post_status;
				}
			}
			else{
				$query_args = array(
					'post_type' => 'trip',
					'meta_query'=> array(
						'relation' => 'AND',
						array(
							'key'=>'start_date',
							'value' => $post_date,
							'type' => 'date',
							'compare' => '<='
						),
						array(
							'key'=>'end_date',
							'value' => $post_date,
							'type' => 'date',
							'compare' => '>='
						)
					)
				);
				$trip_query = new WP_Query($query_args);
				if($trip_query->post_count > 0){
					if(!array_key_exists('post_parent',$post)){
						$post['post_parent'] = $trip_query->post->ID;
						$post['post_status'] = $trip_query->post->post_status;
					}
				}
			}
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'instagram_id' AND meta_value = %s", $instagram_id ) );
			if ($post_id) {
				$post['ID'] = $post_id;
				unset($post['post_status']);
				// Looks like a duplicate
				wp_update_post($post);
				$skipped++;
			} else {
				$post_id = wp_insert_post( $post );
			}

			if ( is_wp_error( $post_id ) )
				return $post_id;

			if ( !$post_id )
				continue;

			// Track which Keyring service was used
			wp_set_object_terms( $post_id, self::LABEL, 'keyring_services' );

			update_post_meta( $post_id, 'instagram_id', $instagram_id );
			update_post_meta( $post_id, 'link', $link );

			if ( count( $tags ) )
				wp_set_post_terms( $post_id, implode( ',', $tags ) );

			// Store geodata if it's available
			if ( !empty( $geo ) ) {
				update_post_meta( $post_id, 'geo_latitude', $geo['lat'] );
				update_post_meta( $post_id, 'geo_longitude', $geo['long'] );
				update_post_meta( $post_id, 'geo_public', 1 );
			}
			
			if(!empty($full)){
				update_post_meta($post_id,'full',$full);
			}
			
			if(!empty($thumbnail)){
				update_post_meta($post_id,'thumbnail',$thumbnail);
			}
			
			update_post_meta($post_id,'height',$height);
			update_post_meta($post_id,'width',$width);

			update_post_meta( $post_id, 'instagram_raw', json_encode( $instagram_raw ) );

			//$this->sideload_media( $instagram_img, $post_id, $post, apply_filters( 'keyring_instagram_importer_image_embed_size', 'full' ) );

			$imported++;

			do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
		}
		$this->posts = array();
		do_action('disallow_empty_post');
		// If we're doing a normal import and the last request was all skipped, then we're at "now"
		if ( !$this->auto_import && self::NUM_PER_REQUEST == $skipped )
			$this->finished = true;

		// Return, so that the handler can output info (or update DB, or whatever)
		return array( 'imported' => $imported, 'skipped' => $skipped );
	}
}

} // end function Keyring_Instagram_Importer



$myActiveLife_Services_Instagram = new MyActiveLife_Services_Instagram();