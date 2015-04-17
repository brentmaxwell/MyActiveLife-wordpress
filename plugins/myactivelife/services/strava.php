<?php

class MyActiveLife_Services_Strava{
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
	}

	public function register_importer(){
		
		MyActiveLife_Services_Strava_Keyring(); // Load the class code from above
		add_action( 'keyring_load_services', array('MyActiveLife_Services_Strava_Keyring_Service','register_service') );
		keyring_register_importer(
			'strava',
			'MyActiveLife_Services_Strava_Keyring_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your activities from Strava.', 'keyring' )
		);
	}
}

function MyActiveLife_Services_Strava_Keyring() {

	class MyActiveLife_Services_Strava_Keyring_Importer extends Keyring_Importer_Base {
		const SLUG              = 'strava';    
		const LABEL             = 'Strava';    
		const KEYRING_SERVICE   = 'Keyring_Service_Strava';    // Full class name of the Keyring_Service this importer requires
		const REQUESTS_PER_LOAD = 1;     // How many remote requests should be made before reloading the page?
		const SCHEDULE = 'fifteenminutes';
		var $auto_import = false;
	
		function __construct() {
			parent::__construct();
			add_action( 'keyring_importer_strava_custom_options', array( $this, 'custom_options' ) );
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
			$url = "https://www.strava.com/api/v3/athlete/activities?";
			$params = array();
			$url = $url . http_build_query( $params );
			
			if ( $this->auto_import ) {

				// Locate our most recently imported Tweet, and get ones since then
				$latest = get_posts( array(
					'post_type'   => 'activity',
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
					$url = add_query_arg( 'after', $max->getTimestamp(), $url );
				}
			} else {
				// Handle page offsets (only for non-auto-import requests)
				$url = add_query_arg( 'page', $this->get_option( 'page', 1 ), $url );
				$url = add_query_arg( 'per_page', 25, $url);
			}
			return $url;
		}
	
		function extract_posts_from_data( $raw ) {
			global $wpdb;
	
			$importdata = $raw;
	
			if ( null === $importdata ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-strava-importer-failed-download', __( 'Failed to download your activities from Strava. Please wait a few minutes and try again.', 'keyring' ) );
			}
	
			// Check for API overage/errors
			if ( !empty( $importdata->error ) ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-strava-importer-throttled', __( 'You have made too many requests to Strava and have been temporarily blocked. Please try again in 1 hour (duplicate activities will be skipped).', 'keyring' ) );
			}
	
			// Make sure we have some tweets to parse
			if ( !is_array( $importdata ) || !count( $importdata ) ) {
				$this->finished = true;
				return;
			}
	
			// Get the total number of tweets we're importing
			$this->set_option( 'total', count($importdata) );
	
			// Parse/convert everything to WP post structs
			foreach ( $importdata as $post ) {
				$post_type = 'activity';
				
				$strava_id = $post->id;
				
				$start_date = strtotime($post->start_date );
				$start_date_local = strtotime($post->start_date_local);
				
				$end_date = $start_date + $post->elapsed_time;
				$end_date_local = $start_date_local + $post->elapsed_time;
				
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $start_date );
				$post_date     = get_date_from_gmt( $post_date_gmt );
				
				$activity_type = $post->type;
				$distance = $post->distance;
				$elapsed_time = $post->elapsed_time;
				$active_time = $post->moving_time;
				$average_speed = $post->average_speed;
				$max_speed = $post->max_speed;
				$total_elevation_gain = $post->total_elevation_gain;
				
				$location = array();
				if(!empty($post->location_city)){$location[] = $post->location_city;}
				if(!empty($post->location_state)){
					if(array_key_exists($post->location_state,$this->states)){
						$location[] = $this->states[$post->location_state];	
					}
					else
					{
						$location[] = $post->location_state;
					}
				}
				if(!empty($post->location_country)){
					if(array_key_exists($post->location_country,$this->countries)){
						$location[] = $this->countries[$post->location_country];	
					}
					else
					{
						$location[] = $post->location_country;
					}
				}
				$location = implode(' ',$location);
				
				$post_title = strip_tags( $post->name );
				$post_excerpt = esc_sql( html_entity_decode( trim( $post->description ) ) );
				
				$post_author = $this->get_option( 'author' );
				if($post->{'private'}){
					$post_status = 'private';
				}
				else{
					$post_status  = 'publish';	
				}
				
				if ( !empty( $post->start_latlng ) )
					$geo = array(
						'lat' => $post->start_latlng[0],
						'long' => $post->start_latlng[1]
					);
				else $geo = array();
				if(!empty($post->map->summary_polyline)){
					$geo['polyline'] = $post->map->summary_polyline;
				}

				$strava_raw              = json_encode($post);
				
				// Build the post array, and hang onto it along with the others
				$this->posts[] = compact(
					'post_author',
					'post_date',
					'post_date_gmt',
					'post_excerpt',
					'post_title',
					'post_status',
					'post_type',
					'activity_type',
					'start_date',
					'end_date',
					'start_date_local',
					'end_date_local',
					'location',
					'strava_id',
					'strava_raw',
					'distance',
					'elapsed_time',
					'active_time',
					'average_speed',
					'max_speed',
					'total_elevation_gain',
					'geo'
				);
			}
		}
	
		function insert_posts() {
			global $wpdb;
			$imported = 0;
			$skipped  = 0;
			foreach ( $this->posts as $post ) {
				extract( $post );
				$query_args = array(
					'post_type' => 'activity',
					'numberposts' => 1,
					'meta_query'=> array(
						'relation' => 'OR',
						array(
							'key' => 'strava_id',
							'value' => $strava_id,
							'compare' => '='
						),
						array('relation' => 'AND',
							array(
								'key'=> 'start_timestamp',
								'value' => $start_date,
								'type' => 'numeric',
								'compare' => '='
							),
							array(
								'key'=> 'end_timestamp',
								'value' => $end_date,
								'type' => 'numeric',
								'compare' => '='
							)
						)
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
					$imported++;
				}
				if ( is_wp_error( $post_id ) )
					return $post_id;
	
				if ( !$post_id )
					continue;
	
				// Track which Keyring service was used
				wp_set_object_terms( $post_id, $activity_type, 'activity_type' );
				wp_set_object_terms( $post_id, self::LABEL, 'keyring_services',true );
				wp_set_object_terms( $post_id, $location, 'location');
	
				update_post_meta ( $post_id, 'strava_id', $strava_id );
				update_post_meta ( $post_id, 'start_date', date('c',$start_date ));
				update_post_meta ( $post_id, 'start_timestamp', $start_date);
				update_post_meta ( $post_id, 'end_date', date('c',$end_date ));
				update_post_meta ( $post_id, 'end_timestamp', $end_date);
				update_post_meta ( $post_id, 'start_date_local', date('c',$start_date_local ));
				update_post_meta ( $post_id, 'end_date_local', date('c',$end_date_local ));
				update_post_meta ( $post_id, 'distance',$distance);
				update_post_meta ( $post_id, 'elapsed_time',$elapsed_time);
				update_post_meta ( $post_id, 'active_time',$active_time);
				update_post_meta ( $post_id, 'average_speed',$average_speed);
				update_post_meta ( $post_id, 'max_speed',$max_speed);
				update_post_meta ( $post_id, 'total_elevation_gain',$total_elevation_gain);
				
				update_post_meta ( $post_id, 'geo_latitude', $geo['lat'] );
				update_post_meta ( $post_id, 'geo_longitude', $geo['long'] );
				update_post_meta ( $post_id, 'geo_public', 1 );
				update_post_meta ( $post_id, 'geo_polyline', $geo['polyline'] );
	
				update_post_meta( $post_id, 'strava_raw',$strava_raw);
	
				do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
			}
			
			$this->posts = array();
	
			// Return, so that the handler can output info (or update DB, or whatever)
			return array( 'imported' => $imported, 'skipped' => $skipped );
		}
		
		var $states = array(
			'Alabama'=>'AL',
			'Alaska'=>'AK',
			'Arizona'=>'AZ',
			'Arkansas'=>'AR',
			'California'=>'CA',
			'Colorado'=>'CO',
			'Connecticut'=>'CT',
			'Delaware'=>'DE',
			'District of Columbia'=>'DC',
			'Florida'=>'FL',
			'Georgia'=>'GA',
			'Hawaii'=>'HI',
			'Idaho'=>'ID',
			'Illinois'=>'IL',
			'Indiana'=>'IN',
			'Iowa'=>'IA',
			'Kansas'=>'KS',
			'Kentucky'=>'KY',
			'Louisiana'=>'LA',
			'Maine'=>'ME',
			'Maryland'=>'MD',
			'Massachusetts'=>'MA',
			'Michigan'=>'MI',
			'Minnesota'=>'MN',
			'Mississippi'=>'MS',
			'Missouri'=>'MO',
			'Montana'=>'MT',
			'Nebraska'=>'NE',
			'Nevada'=>'NV',
			'New Hampshire'=>'NH',
			'New Jersey'=>'NJ',
			'New Mexico'=>'NM',
			'New York'=>'NY',
			'North Carolina'=>'NC',
			'North Dakota'=>'ND',
			'Ohio'=>'OH',
			'Oklahoma'=>'OK',
			'Oregon'=>'OR',
			'Pennsylvania'=>'PA',
			'Rhode Island'=>'RI',
			'South Carolina'=>'SC',
			'South Dakota'=>'SD',
			'Tennessee'=>'TN',
			'Texas'=>'TX',
			'Utah'=>'UT',
			'Vermont'=>'VT',
			'Virginia'=>'VA',
			'Washington'=>'WA',
			'West Virginia'=>'WV',
			'Wisconsin'=>'WI',
			'Wyoming'=>'WY',
		);
		
		var $countries = array(
			'United States' => 'US'
		);
	}
}

$myActiveLife_Services_Strava = new MyActiveLife_Services_Strava();