<?php

class MyActiveLife_Services_Meetup{
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
	}

	public function register_importer(){
		
		MyActiveLife_Services_Meetup_Keyring(); // Load the class code from above
		add_action( 'keyring_load_services', array('MyActiveLife_Services_Meetup_Keyring_Service','register_service') );
		keyring_register_importer(
			'meetup',
			'MyActiveLife_Services_Meetup_Keyring_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your events from Meetup.', 'keyring' )
		);
	}
}

function MyActiveLife_Services_Meetup_Keyring() {

	class MyActiveLife_Services_Meetup_Keyring_Importer extends Keyring_Importer_Base {
		const SLUG              = 'meetup';    
		const LABEL             = 'MeetUp';    
		const KEYRING_SERVICE   = 'Keyring_Service_Meetup';    // Full class name of the Keyring_Service this importer requires
		const REQUESTS_PER_LOAD = 1;     // How many remote requests should be made before reloading the page?
	
		var $auto_import = false;
	
		function __construct() {
			parent::__construct();
			add_action( 'keyring_importer_meetup_custom_options', array( $this, 'custom_options' ) );
			add_action('import_start',array($this->service,'maybe_refresh_token'));
		}
	
		function custom_options() {
			?>
			<?php
		}
	
	 	function handle_request_options() {
			// Validate options and store them so they can be used in auto-imports
			if ( empty( $_POST['author'] ) || !ctype_digit( $_POST['author'] ) )
				$this->error( __( "You must select an author to assign to all checkins." ) );
	
			if ( isset( $_POST['auto_import'] ) )
				$_POST['auto_import'] = true;
			else
				$_POST['auto_import'] = false;
	
			// If there were errors, output them, otherwise store options and start importing
			if ( count( $this->errors ) ) {
				$this->step = 'greet';
			} else {
				$this->set_option( array(
					'author'          => (int) $_POST['author'],
					'auto_import'     => (bool) $_POST['auto_import'],
					'user_id'         => $this->service->get_token()->get_meta( 'user_id' ),
				) );
	
				$this->step = 'import';
			}
		}
	
		function build_request_url() {
			// Base request URL
			$url = "https://api.meetup.com/2/events?rsvp=yes&status=past&fields=photo_count&desc=true";
			$params = array();
			$url = $url . http_build_query( $params );
			
			if ( $this->auto_import ) {

				// Locate our most recently imported Tweet, and get ones since then
				$latest = get_posts( array(
					'post_type'   => 'event',
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
					$max = strtotime(get_post_meta( $latest[0]->ID, 'start_date', true ));
					$max = new DateTime("@$max");
					$max =  $max->sub(new DateInterval('P30D'));
					$url = add_query_arg( 'time', $max->getTimestamp() . ','.time(), $url );
				}
			} else {
				// Handle page offsets (only for non-auto-import requests)
				$url = add_query_arg( 'offset', $this->get_option( 'page', 0 ), $url );
				$url = add_query_arg( 'page', 10, $url);
			}
			return $url;
		}
	
		function extract_posts_from_data( $raw ) {
			global $wpdb;
	
			$importdata = $raw->results;
	
			if ( null === $importdata ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-meetup-importer-failed-download', __( 'Failed to download your activities from Meetup. Please wait a few minutes and try again.', 'keyring' ) );
			}
	
			// Check for API overage/errors
			if ( !empty( $importdata->error ) ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-meetup-importer-throttled', __( 'You have made too many requests to Meetup and have been temporarily blocked. Please try again in 1 hour (duplicate activities will be skipped).', 'keyring' ) );
			}
	
			// Make sure we have some tweets to parse
			if ( !is_array( $importdata ) || !count( $importdata ) ) {
				$this->finished = true;
				return;
			}
	
			// Get the total number of tweets we're importing
			$this->set_option( 'total', count($importdata) );
	
			$event_ids = array();
			
			// Parse/convert everything to WP post structs
			foreach ( $importdata as $post ) {
				$post_type = 'event';
				
				$meetup_id = $post->id;

				$start_timestamp = $post->time / 1000;
				$start_date_local = $start_timestamp + ($post->utc_offset / 1000);
				
				$end_timestamp = ($post->time / 1000) + ($post->duration / 1000);
				$end_date_local = $end_timestamp + ($post->utc_offset / 1000);
				
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $start_timestamp );
				$post_date = date('Y-m-d H:i:s',$start_date_local);
				
				$location = array();
				if(!empty($post->venue->city)){$location[] = $post->venue->city;}
				if(!empty($post->venue->state)){ $location[] = $post->venue->state;}
				if(!empty($post->venue->country)){$location[] = strtoupper($post->venue->country);}
				$location = implode(' ',$location);
				
				$post_title = strip_tags( $post->name );
				$post_excerpt = esc_sql( html_entity_decode( trim( $post->description ) ) );
				
				$post_author = $this->get_option( 'author' );
				$post_status  = 'pending';	
				
				if ( !empty( $post->venue ) )
					$geo = array(
						'lat' => $post->venue->lat,
						'long' => $post->venue->lon
					);
				else $geo = array();

				$meetup_id = $post->id;
				$meetup_raw              = json_encode($post);
				
				// Build the post array, and hang onto it along with the others
				$this->posts[] = compact(
					'post_author',
					'post_date',
					'post_date_gmt',
					'post_excerpt',
					'post_title',
					'post_status',
					'post_type',
					'start_date_local',
					'end_date_local',
					'start_timestamp',
					'end_timestamp',
					'location',
					'meetup_id',
					'meetup_raw',
					'geo'
				);
				
				if(property_exists($post,'photo_count'))
				{
					$this->posts = array_merge($this->posts,$this->get_photos($meetup_id,$post));
				}
			}
		}
	
		function insert_posts() {
			global $wpdb;
			$imported = 0;
			$skipped  = 0;
			do_action('allow_empty_post');
			foreach ( $this->posts as $post ) {
				extract( $post );
				$query_args = array(
					'post_type' => $post_type,
					'numberposts' => 1,
					'meta_query'=> array(
						'relation' => 'AND',
						array(
							'key' => 'meetup_id',
							'value' => $meetup_id,
							'compare' => '='
						),
					)
				); 
				$query = new WP_Query($query_args);
				if ($query->post) {
					$post_id = $query->post->ID;
					$post['ID'] = $post_id;
					wp_update_post($post);
					$skipped++;
				} else {
					$post_id = wp_insert_post( $post );
					$post['ID'] = $post_id;
					$imported++;
				}
				if ( is_wp_error( $post_id ) )
					return $post_id;
	
				if ( !$post_id )
					continue;
					
				wp_set_object_terms( $post_id, self::LABEL, 'keyring_services',true );
				
				update_post_meta ( $post_id, 'meetup_id', $meetup_id );
				update_post_meta( $post_id, 'meetup_raw',$meetup_raw);
				
				switch($post_type){
					case 'event':
						$this->insert_event($post_id,$post);
						break;
					case 'media':
						$this->insert_media($post_id,$post);
						break;
				}
				do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
			}
			
			$this->posts = array();
			do_action('disallow_empty_post');
			// Return, so that the handler can output info (or update DB, or whatever)
			return array( 'imported' => $imported, 'skipped' => $skipped );
		}
		
		function get_photos($meetup_id,$post){
			$photo_url = 'https://api.meetup.com/2/photos?event_id='.$meetup_id.'&page='.$post->photo_count;
			$photo_response = $this->service->request($photo_url);
			$posts = array();
			if($photo_response != null){
				$photos = $photo_response->results;
				foreach($photos as $photo)
				{
					$photo_post = array();
					$photo_post['post_type'] = 'media';
					$photo_post['post_status'] = 'pending';
					$photo_post['post_author'] = $this->get_option( 'author' );
					$photo_post['post_date_gmt'] = gmdate('Y-m-d H:i:s',$photo->created / 1000);
					$photo_post['post_date'] = ($photo->created + $post->utc_offset)/1000;
					$photo_post['post_title'] = $photo->caption;
					$photo_post['thumbnail_small'] = array(
						'url' => $photo->thumb_link
					);
					$photo_post['thumbnail_large'] = array(
						'url' => $photo->photo_link
					);
					$photo_post['thumbnail_highres'] = array(
						'url' => $photo->highres_link
					);
					$photo_post['media_author'] = $photo->member->name;
					$photo_post['meetup_id'] = $photo->photo_id;
					$photo_post['meetup_raw'] = json_encode($photo);
					$photo_post['parent_meetup_id'] = $meetup_id;
					$posts[] = $photo_post;
				}
			}
			return $posts;
		}
		
		public function insert_event($post_id,$post){
			extract( $post );
			// Track which Keyring service was used

			wp_set_object_terms( $post_id, $location, 'location');

			update_post_meta ( $post_id, 'start_date', date('c',$start_timestamp));
			update_post_meta ( $post_id, 'start_timestamp', $start_timestamp);
			update_post_meta ( $post_id, 'end_date', date('c',$end_timestamp ));
			update_post_meta ( $post_id, 'end_timestamp', $end_timestamp);
			update_post_meta ( $post_id, 'start_date_local', date('c',$start_date_local ));
			update_post_meta ( $post_id, 'end_date_local', date('c',$end_date_local ));		
			update_post_meta ( $post_id, 'geo_latitude', $geo['lat'] );
			update_post_meta ( $post_id, 'geo_longitude', $geo['long'] );
			update_post_meta ( $post_id, 'geo_public', 1 );

			
		}
		
		public function insert_media($post_id,$photo){
			extract( $photo );
			$query_args = array(
				'post_type' => 'event',
				'numberposts' => 1,
				'meta_query'=> array(
					'relation' => 'AND',
					array(
						'key' => 'meetup_id',
						'value' => $parent_meetup_id,
						'compare' => '='
					),
				)
			); 
			$query = new WP_Query($query_args);
			if ($query->post) {
				$photo['post_parent'] = $query->post->ID;
				wp_update_post($photo);
			}
			
			// Track which Keyring service was used
			wp_set_object_terms( $post_id, $media_author,'media_author',true);
			wp_set_object_terms( $post_id, 'photo','media_type',true);

			
			update_post_meta ( $post_id, 'thumbnail_small',json_encode($thumbnail_small));
			update_post_meta ( $post_id, 'thumbnail_large',json_encode($thumbnail_large));
			update_post_meta ( $post_id, 'thumbnail_highres',json_encode($thumbnail_highres));
		}
	}
}

$myActiveLife_Services_Meetup = new MyActiveLife_Services_Meetup();