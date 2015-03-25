<?php

class MyActiveLife_Strava{
	
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
		add_filter('the_content', array($this, 'post_filter'));
	}
	
	public function register_importer(){
		MyActiveLife_Keyring_Strava_Importer(); // Load the class code from above
		keyring_register_importer(
			'strava',
			'MyActiveLife_Keyring_Strava_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your activities from Strava as Activities.', 'keyring' )
		);
	}
	
	public function post_filter($content) {
		$output = '';
		
		if($GLOBALS['post']->post_type == 'activity'){
			$terms = wp_get_post_terms( $GLOBALS['post']->ID, 'keyring_services');
			if(is_array($terms)) {
				if($terms[0]->slug == 'strava')
				{
					wp_enqueue_style('strava',plugins_url('css/styles.css',__FILE__));
					$data = get_post_custom_values('raw_import_data', $GLOBALS['post']->ID);
					$data = utf8_encode($data[0]);
					$data = str_replace("\\","\\\\",$data); 
					$data = json_decode($data);
					ob_start();
					include(__DIR__.'/../templates/strava.php');
					$output = ob_get_clean();
				}
			}
		}
		$output .= $content;
		return $output;
		
	}
}


// This is a horrible hack, because WordPress doesn't support dependencies/load-order.
// We wrap our entire class definition in a function, and then only call that on a hook
// where we know that the class we're extending is available. *hangs head in shame*

function MyActiveLife_Keyring_Strava_Importer() {
	class MyActiveLife_Keyring_Strava_Importer extends Keyring_Importer_Base {
		const SLUG              = 'strava';    
		const LABEL             = 'Strava';    
		const KEYRING_SERVICE   = 'Keyring_Service_Strava';    // Full class name of the Keyring_Service this importer requires
		const REQUESTS_PER_LOAD = 3;     // How many remote requests should be made before reloading the page?
	
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
					'tax_query'   => array( array(
						'taxonomy' => 'keyring_services',
						'field'    => 'slug',
						'terms'    => array( $this->taxonomy->slug ),
						'operator' => 'IN',
					) ),
				) );
	
				// If we have already imported some, then start since the most recent
				if ( $latest ) {
					$max = get_post_meta( $latest[0]->ID, 'strava_start_date', true );
					$max = new DateTime($max);
					$max =  $max->sub(new DateInterval('P30D'));
					$url = add_query_arg( 'after', $max->getTimestamp(), $url );
				}
			} else {
				// Handle page offsets (only for non-auto-import requests)
				$url = add_query_arg( 'page', $this->get_option( 'page', 1 ), $url );
				$url = add_query_arg( 'per_page', 50, $url);
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
				
			
				$strava_id = $post->id;
				/*$strava_resource_state = $post->resource_state;
				$strava_external_id = $post->external_id;
				$strava_upload_id = $post->upload_id;
				$strava_name = $post->name;
				$strava_description = $post->description;
				$strava_distance = $post->distance;
				$strava_moving_time = $post->moving_time;
				$strava_elapsed_time = $post->elapsed_time;
				$strava_total_elevation_gain = $post->total_elevation_gain;*/
				$strava_type = $post->type;
				$strava_start_date = gmdate( 'Y-m-d H:i:s', strtotime($post->start_date ) );
				//$strava_start_date_local = date( 'Y-m-d H:i:s', strtotime($post->start_date_local ));
				/*$strava_timezone = $post->timezone;
				$strava_start_latlng = $post->start_latlng;
				$strava_end_latlng = $post->end_latlng;*/
				$strava_location = array();
				if(!empty($data->location_city)){$strava_location[] = $data->location_city;}
				if(!empty($data->location_state)){
					if(array_key_exists($data->location_state,$this->states)){
						$strava_location[] = $this->states[$data->location_state];	
					}
					else
					{
						$strava_location[] = $data->location_state;
					}
				}
				if(!empty($data->location_country)){$strava_location[] = $data->location_country;}
				$strava_location = implode('_',$strava_location); 
				/*$strava_location_city = $post->location_city;
				$strava_location_state = $post->location_state;
				$strava_location_country = $post->location_country;
				$strava_map = $post->map;
				$strava_trainer = $post->trainer;
				$strava_commute = $post->commute;
				$strava_manual = $post->manual;
				//$strava_private = $post->'private';
				$strava_flagged = $post->flagged;
				$strava_workout_type = $post->workout_type;
				$strava_gear_id = $post->gear_id;
				$strava_gear = $post->gear;
				$strava_average_speed = $post->average_speed;
				$strava_max_speed = $post->max_speed;
				$strava_average_cadence = $post->average_cadence;
				$strava_average_temp = $post->average_temp;
				$strava_average_watts = $post->average_watts;
				$strava_weighted_average_watts = $post->weighted_average_watts;
				$strava_kilojoules = $post->kilojoules;
				$strava_device_watts = $post->device_watts;
				$strava_average_heartrate = $post->average_heartrate;
				$strava_max_heartrate = $post->max_heartrate;
				$strava_calories = $post->calories;
				$strava_truncated = $post->truncated;
				$strava_has_kudoed = $post->has_kudoed;
				$strava_segment_efforts = $post->segment_efforts;
				$strava_splits_metric = $post->splits_metric;
				$strava_splits_standard = $post->splits_standard;
				$strava_best_efforts = $post->best_efforts;*/
		
				$post_title = strip_tags( $post->name );
				$post_excerpt = esc_sql( html_entity_decode( trim( $post->description ) ) );
				
				$start_date = strtotime( $post->start_date );
				$end_date = $start_date + $post->elapsed_time;
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $end_date );
				$post_date     = get_date_from_gmt( $post_date_gmt );
				$post_type = 'activity';
				
				if ( !empty( $post->start_latlng ) )
					$geo = array(
						'lat' => $post->start_latlng[0],
						'long' => $post->start_latlng[1]
					);
				else
					$geo = array();
					
				
				$post_author             = $this->get_option( 'author' );
				$post_status             = 'publish';
				$raw_import_data              = json_encode($post);
	
				// Build the post array, and hang onto it along with the others
				$this->posts[] = compact(
					'post_author',
					'post_date',
					'post_date_gmt',
					'post_excerpt',
					'post_title',
					'post_status',
					'post_type',
					'strava_id',
					/*'strava_resource_state',
					'strava_external_id',
					'strava_upload_id',
					'strava_name',
					'strava_description',
					'strava_distance',
					'strava_moving_time',
					'strava_elapsed_time',
					'strava_total_elevation_gain',*/
					'strava_type',
					'strava_start_date',
					'strava_location',
					/*'strava_start_date_local',
					'strava_timezone',
					'strava_start_latlng',
					'strava_end_latlng',
					'strava_location_city',
					'strava_location_state',
					'strava_location_country',
					'strava_map',
					'strava_trainer',
					'strava_commute',
					'strava_manual',
	//				'strava_private',
					'strava_flagged',
					'strava_workout_type',
					'strava_gear_id',
					'strava_gear',
					'strava_average_speed',
					'strava_max_speed',
					'strava_average_cadence',
					'strava_average_temp',
					'strava_average_watts',
					'strava_weighted_average_watts',
					'strava_kilojoules',
					'strava_device_watts',
					'strava_average_heartrate',
					'strava_max_heartrate',
					'strava_calories',
					'strava_truncated',
					'strava_has_kudoed',
					'strava_segment_efforts',
					'strava_splits_metric',
					'strava_splits_standard',
					'strava_best_efforts',*/
					'geo',
					'raw_import_data'
				);
			}
		}
	
		function insert_posts() {
			global $wpdb;
			$imported = 0;
			$skipped  = 0;
			foreach ( $this->posts as $post ) {
				extract( $post );
				$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'strava_id' AND meta_value = %s", $strava_id ) ); 
				if ($post_id) {
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
				wp_set_object_terms( $post_id, $strava_type, 'activity_type' );
				wp_set_object_terms( $post_id, self::LABEL, 'keyring_services' );
				wp_set_object_terms( $post_id, $strava_location, 'location');
	
				delete_post_meta($post_id,'strava_id');
				/*delete_post_meta($post_id,'strava_resource_state');
				delete_post_meta($post_id,'strava_external_id');
				delete_post_meta($post_id,'strava_upload_id');
				delete_post_meta($post_id,'strava_name');
				delete_post_meta($post_id,'strava_description');
				delete_post_meta($post_id,'strava_distance');
				delete_post_meta($post_id,'strava_moving_time');
				delete_post_meta($post_id,'strava_elapsed_time');
				delete_post_meta($post_id,'strava_total_elevation_gain');
				delete_post_meta($post_id,'strava_type');*/
				delete_post_meta($post_id,'strava_start_date');
				/*delete_post_meta($post_id,'strava_start_date_local');
				delete_post_meta($post_id,'strava_timezone');
				delete_post_meta($post_id,'strava_start_latlng');
				delete_post_meta($post_id,'strava_end_latlng');
				delete_post_meta($post_id,'strava_location_city');
				delete_post_meta($post_id,'strava_location_state');
				delete_post_meta($post_id,'strava_location_country');
				delete_post_meta($post_id,'strava_map');
				delete_post_meta($post_id,'strava_trainer');
				delete_post_meta($post_id,'strava_commute');
				delete_post_meta($post_id,'strava_manual');
				delete_post_meta($post_id,'strava_private');
				delete_post_meta($post_id,'strava_flagged');
				delete_post_meta($post_id,'strava_workout_type');
				delete_post_meta($post_id,'strava_gear_id');
				delete_post_meta($post_id,'strava_gear');
				delete_post_meta($post_id,'strava_average_speed');
				delete_post_meta($post_id,'strava_max_speed');
				delete_post_meta($post_id,'strava_average_cadence');
				delete_post_meta($post_id,'strava_average_temp');
				delete_post_meta($post_id,'strava_average_watts');
				delete_post_meta($post_id,'strava_weighted_average_watts');
				delete_post_meta($post_id,'strava_kilojoules');
				delete_post_meta($post_id,'strava_device_watts');
				delete_post_meta($post_id,'strava_average_heartrate');
				delete_post_meta($post_id,'strava_max_heartrate');
				delete_post_meta($post_id,'strava_calories');
				delete_post_meta($post_id,'strava_truncated');
				delete_post_meta($post_id,'strava_has_kudoed');
				delete_post_meta($post_id,'strava_segment_efforts');
				delete_post_meta($post_id,'strava_splits_metric');
				delete_post_meta($post_id,'strava_splits_standard');
				delete_post_meta($post_id,'strava_best_efforts');*/
				delete_post_meta($post_id,'geo_latitude');
				delete_post_meta($post_id,'geo_longitude');
				delete_post_meta($post_id,'geo_public');
				delete_post_meta($post_id,'raw_import_data');
				
				add_post_meta( $post_id, 'strava_id', $strava_id );
				/*if ( !empty($strava_resource_state ) ) { add_post_meta ( $post_id, 'strava_resource_state', $strava_resource_state );}
				if ( !empty($strava_external_id ) ) { add_post_meta ( $post_id, 'strava_external_id', $strava_external_id );}
				if ( !empty($strava_upload_id ) ) { add_post_meta ( $post_id, 'strava_upload_id', $strava_upload_id );}
				if ( !empty($strava_name ) ) { add_post_meta ( $post_id, 'strava_name', $strava_name );}
				if ( !empty($strava_description ) ) { add_post_meta ( $post_id, 'strava_description', $strava_description );}
				if ( !empty($strava_distance ) ) { add_post_meta ( $post_id, 'strava_distance', $strava_distance );}
				if ( !empty($strava_moving_time ) ) { add_post_meta ( $post_id, 'strava_moving_time', $strava_moving_time );}
				if ( !empty($strava_elapsed_time ) ) { add_post_meta ( $post_id, 'strava_elapsed_time', $strava_elapsed_time );}
				if ( !empty($strava_total_elevation_gain ) ) { add_post_meta ( $post_id, 'strava_total_elevation_gain', $strava_total_elevation_gain );}
				if ( !empty($strava_type ) ) { add_post_meta ( $post_id, 'strava_type', $strava_type );}*/
				if ( !empty($strava_start_date ) ) { add_post_meta ( $post_id, 'strava_start_date', $strava_start_date );}
				/*if ( !empty($strava_start_date_local ) ) { add_post_meta ( $post_id, 'strava_start_date_local', $strava_start_date_local );}
				if ( !empty($strava_timezone ) ) { add_post_meta ( $post_id, 'strava_timezone', $strava_timezone );}
				if ( !empty($strava_start_latlng ) ) { add_post_meta ( $post_id, 'strava_start_latlng', $strava_start_latlng );}
				if ( !empty($strava_end_latlng ) ) { add_post_meta ( $post_id, 'strava_end_latlng', $strava_end_latlng );}
				if ( !empty($strava_location_city ) ) { add_post_meta ( $post_id, 'strava_location_city', $strava_location_city );}
				if ( !empty($strava_location_state ) ) { add_post_meta ( $post_id, 'strava_location_state', $strava_location_state );}
				if ( !empty($strava_location_country ) ) { add_post_meta ( $post_id, 'strava_location_country', $strava_location_country );}
				if ( !empty($strava_map ) ) { add_post_meta ( $post_id, 'strava_map', $strava_map );}
				if ( !empty($strava_trainer ) ) { add_post_meta ( $post_id, 'strava_trainer', $strava_trainer );}
				if ( !empty($strava_commute ) ) { add_post_meta ( $post_id, 'strava_commute', $strava_commute );}
				if ( !empty($strava_manual ) ) { add_post_meta ( $post_id, 'strava_manual', $strava_manual );}
	//			if ( !empty($strava_private ) ) { add_post_meta ( $post_id, 'strava_private', $strava_private );}
				if ( !empty($strava_flagged ) ) { add_post_meta ( $post_id, 'strava_flagged', $strava_flagged );}
				if ( !empty($strava_workout_type ) ) { add_post_meta ( $post_id, 'strava_workout_type', $strava_workout_type );}
				if ( !empty($strava_gear_id ) ) { add_post_meta ( $post_id, 'strava_gear_id', $strava_gear_id );}
				if ( !empty($strava_gear ) ) { add_post_meta ( $post_id, 'strava_gear', $strava_gear );}
				if ( !empty($strava_average_speed ) ) { add_post_meta ( $post_id, 'strava_average_speed', $strava_average_speed );}
				if ( !empty($strava_max_speed ) ) { add_post_meta ( $post_id, 'strava_max_speed', $strava_max_speed );}
				if ( !empty($strava_average_cadence ) ) { add_post_meta ( $post_id, 'strava_average_cadence', $strava_average_cadence );}
				if ( !empty($strava_average_temp ) ) { add_post_meta ( $post_id, 'strava_average_temp', $strava_average_temp );}
				if ( !empty($strava_average_watts ) ) { add_post_meta ( $post_id, 'strava_average_watts', $strava_average_watts );}
				if ( !empty($strava_weighted_average_watts ) ) { add_post_meta ( $post_id, 'strava_weighted_average_watts', $strava_weighted_average_watts );}
				if ( !empty($strava_kilojoules ) ) { add_post_meta ( $post_id, 'strava_kilojoules', $strava_kilojoules );}
				if ( !empty($strava_device_watts ) ) { add_post_meta ( $post_id, 'strava_device_watts', $strava_device_watts );}
				if ( !empty($strava_average_heartrate ) ) { add_post_meta ( $post_id, 'strava_average_heartrate', $strava_average_heartrate );}
				if ( !empty($strava_max_heartrate ) ) { add_post_meta ( $post_id, 'strava_max_heartrate', $strava_max_heartrate );}
				if ( !empty($strava_calories ) ) { add_post_meta ( $post_id, 'strava_calories', $strava_calories );}
				if ( !empty($strava_truncated ) ) { add_post_meta ( $post_id, 'strava_truncated', $strava_truncated );}
				if ( !empty($strava_has_kudoed ) ) { add_post_meta ( $post_id, 'strava_has_kudoed', $strava_has_kudoed );}
				if ( !empty($strava_segment_efforts ) ) { add_post_meta ( $post_id, 'strava_segment_efforts', $strava_segment_efforts );}
				if ( !empty($strava_splits_metric ) ) { add_post_meta ( $post_id, 'strava_splits_metric', $strava_splits_metric );}
				if ( !empty($strava_splits_standard ) ) { add_post_meta ( $post_id, 'strava_splits_standard', $strava_splits_standard );}
				if ( !empty($strava_best_efforts ) ) { add_post_meta ( $post_id, 'strava_best_efforts', $strava_best_efforts );}
	*/
				// Store geodata if it's available
				if ( !empty( $geo ) ) {
					add_post_meta( $post_id, 'geo_latitude', $geo['lat'] );
					add_post_meta( $post_id, 'geo_longitude', $geo['long'] );
					add_post_meta( $post_id, 'geo_public', 1 );
				}
	
				add_post_meta( $post_id, 'raw_import_data', $raw_import_data );
	
				do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
			}
			$this->posts = array();
	
			// Return, so that the handler can output info (or update DB, or whatever)
			return array( 'imported' => $imported, 'skipped' => $skipped );
		}
	}
} 

