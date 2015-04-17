<?php

// This is a horrible hack, because WordPress doesn't support dependencies/load-order.
// We wrap our entire class definition in a function, and then only call that on a hook
// where we know that the class we're extending is available. *hangs head in shame*
function Keyring_TripIt_Importer() {

class Keyring_TripIt_Importer extends Keyring_Importer_Base {
	const SLUG              = 'tripit';    // e.g. 'twitter' (should match a service in Keyring)
	const LABEL             = 'TripIt';    // e.g. 'Twitter'
	const KEYRING_SERVICE   = 'Keyring_Service_TripIt';    // Full class name of the Keyring_Service this importer requires
	const REQUESTS_PER_LOAD = 1;     // How many remote requests should be made before reloading the page?
	const SCHEDULE = 'daily';
	var $auto_import = false;

	function handle_request_options() {
		// Validate options and store them so they can be used in auto-imports
		if ( empty( $_POST['category'] ) || !ctype_digit( $_POST['category'] ) )
			$this->error( __( "Make sure you select a valid category to import your activities into." ) );

		if ( empty( $_POST['author'] ) || !ctype_digit( $_POST['author'] ) )
			$this->error( __( "You must select an author to assign to all activities." ) );

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
		// Base request URL - http://tripit.github.com/api/doc/v1/index.html
		// Because we want to go for the AirObjects, it's actually easier to just do this request
		// and get all the data, every time. Internal de-duping will clear out anything we already have.
		$url = "https://api.tripit.com/v1/list/trip/past/true";
		if ( $this->auto_import ) {

			// Locate our most recently imported Tweet, and get ones since then
			$latest = get_posts( array(
				'post_type'   => 'trip',
				'numberposts' => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'author'      => $this->get_option( 'author' ),
				'tax_query'   => array( array(
					'taxonomy' => 'keyring_services',
					'field'    => 'slug',
					'terms'    => array( $this->taxonomy->slug ),
					'operator' => 'IN',
				) ),
			) );
			// If we have already imported some, then start since the most recent
			if ( $latest ) {
				$max = strtotime($latest[0]->post_modified_gmt);
				$max = new DateTime("@$max");
				$max =  $max->sub(new DateInterval('P30D'));
				$url = add_query_arg( 'modified_since', $max->getTimestamp(), $url );
			}
		} else {
			$page = $this->get_option( 'page', 1 );
			if($page > 0){
				$url.="/page_num/".$page;
			}
		}
		echo $url;
		return $url;
	}

	function extract_posts_from_data( $raw ) {
		$importdata = $raw;
		
		$this->set_option('max_page',$raw->max_page);
		$this->set_option('current_page',$raw->page_num);

		if ( null === $importdata ) {
			$this->finished = true;
			return new Keyring_Error( 'keyring-tripit-importer-failed-download', __( 'Failed to download your trips from TripIt. Please wait a few minutes and try again.' ) );
		}

		// Make sure we have some trips to parse
		if ( !is_object( $importdata ) || !count( $importdata->Trip ) ) {
			$this->finished = true;
			return;
		}

		// Parse/convert everything to WP post structs
		foreach ( $importdata->Trip as $trip ) {


			// If a segment occurs more than 24 hours after the previous
			// one, then we create a new post for it
			// Post title is an abbreviated version of post content
			$post_title = $trip->display_name;
			$start_date = $trip->start_date;
			$end_date = $trip->end_date;

			// Date of the post is the start of the first flight segment
			$post_date     = $trip->start_date;
			$post_modified_gmt = gmdate( 'Y-m-d H:i:s', $trip->timestamp );

			// Apply selected category
			$post_category = array( $this->get_option( 'category' ) );

			// Tags use the default ones, plus each airport code and city name
			$tags = $this->get_option( 'tags' );

			// Other bits
			$post_author       = $this->get_option( 'author' );
			$post_status       = 'publish';
			if($trip->is_private == 'true'){
				$post_status = 'private';
			}
			
			$post_type         = 'trip';
			$tripit_id         = $trip->id;

			$tripit_raw = $trip;

			$location = str_replace(', ',' ',$trip->primary_location);
			$geo = array(
				'lat' => $trip->PrimaryLocationAddress->latitude,
				'lon' => $trip->PrimaryLocationAddress->longitude
			);
			$this->posts[] = compact(
				'post_author',
				'post_date',
				'post_modified_gmt',
				'post_content',
				'post_title',
				'post_status',
				'post_category',
				'post_type',
				'start_date',
				'end_date',
				'tags',
				'location',
				'geo',
				'tripit_id',
				'tripit_raw'
			);
		}
	}

	function insert_posts() {
		global $wpdb;
		$imported = 0;
		$skipped  = 0;
		foreach ( $this->posts as $post ) {
			// See the end of extract_posts_from_data() for what is in here
			extract( $post );
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'tripit_id' AND meta_value = %s", $tripit_id ) );
			if($post_id){
				$post['ID'] = $post_id;
				unset($post['post_status']);
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
			wp_set_object_terms($post_id,$location,'location',true);

			// Mark it as a status
			set_post_format( $post_id, 'status' );

			// Update Category + Tags
			wp_set_post_categories( $post_id, $post_category );

			if ( count( $tags ) )
				wp_set_post_terms( $post_id, implode( ',', $tags ) );

			update_post_meta( $post_id, 'tripit_id', $tripit_id );
			update_post_meta($post_id,'start_date',$start_date);
			update_post_meta($post_id,'end_date',$end_date);

			// Store geodata if it's available
			if ( !empty( $geo ) ) {
				update_post_meta( $post_id, 'geo_latitude', $geo['lat'] );
				update_post_meta( $post_id, 'geo_longitude', $geo['lon'] );
				update_post_meta( $post_id, 'geo_public', 1 );
			}

			if ( $tripit_raw )
				update_post_meta( $post_id, 'tripit_raw', json_encode( $tripit_raw ) );

			$imported++;

			do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
		}
		$this->posts = array();

		$max_page = $this->get_option('max_page',0);
		$current_page = $this->get_option('current_page',0);
		if($max_page == $current_page){
			$this->finished = true;
			$this->set_option('max_page',0);
			$this->set_option('current_page',0);
		}
		// Return, so that the handler can output info (or update DB, or whatever)
		return array( 'imported' => $imported, 'skipped' => $skipped );
	}

	/**
	 * The parent class creates an hourly cron job to run auto import. That's unnecessarily aggressive for
	 * TripIt, so we're going to cut that downt to once every 12 hours by just skipping the job depending
	 * on the hour. If we want to run, then call the parent auto_import.
	 */
	function do_auto_import() {
		if ( 01 == date( 'H' ) || 12 == date( 'H' ) )
			parent::do_auto_import();
	}
}

} // end function Keyring_Instagram_Importer


add_action( 'init', function() {
	Keyring_TripIt_Importer(); // Load the class code from above
	keyring_register_importer(
		'tripit',
		'Keyring_TripIt_Importer',
		plugin_basename( __FILE__ ),
		__( 'Download your travel details from TripIt and auto-post maps of your flights. Each flight is saved as a Post containing a map, marked with the Status format.', 'keyring' )
	);
} );
