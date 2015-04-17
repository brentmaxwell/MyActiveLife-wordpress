<?php
class MyActiveLife_Services_Jawbone{
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
	}

	public function register_importer(){
		
		MyActiveLife_Services_Jawbone_Keyring(); // Load the class code from above
		add_action( 'keyring_load_services', array('MyActiveLife_Services_Jawbone_Keyring_Service','register_service') );
		keyring_register_importer(
			'jawbone',
			'MyActiveLife_Services_Jawbone_Keyring_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your body measurements and activities from Jawbone.', 'keyring' )
		);
	}
}



// This is a horrible hack, because WordPress doesn't support dependencies/load-order.
// We wrap our entire class definition in a function, and then only call that on a hook
// where we know that the class we're extending is available. *hangs head in shame*

function MyActiveLife_Services_Jawbone_Keyring() {
	class MyActiveLife_Services_Jawbone_Keyring_Importer extends Keyring_Importer_Base {
		const SLUG              = 'jawbone';    
		const LABEL             = 'Jawbone';    
		const KEYRING_SERVICE   = 'Keyring_Service_Jawbone';    // Full class name of the Keyring_Service this importer requires
		const REQUESTS_PER_LOAD = 1;     // How many remote requests should be made before reloading the page?
		const NUM_PER_REQUEST = 25;
		const BASE_URL = "https://jawbone.com";
		const SCHEDULE = 'fifteenminutes';
		var $auto_import = false;
	
		function __construct() {
			parent::__construct();
			add_action( 'keyring_importer_jawbone_custom_options', array( $this, 'custom_options' ) );
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
			$urls = $this->get_option('urls');
			if(empty($urls))
			{
				$urls = array(
					'body' => "/nudge/api/v.1.1/users/@me/body_events/?limit=".self::NUM_PER_REQUEST,
					//'move' => "/nudge/api/v.1.1/users/@me/moves/?limit=".self::NUM_PER_REQUEST,
					//'sleep'=>"/nudge/api/v.1.1/users/@me/sleeps/?limit=".self::NUM_PER_REQUEST,
					'activity'=>"/nudge/api/v.1.1/users/@me/workouts/?limit=".self::NUM_PER_REQUEST
				);
				$this->set_option('urls',$urls);
			}
			$current_section = array_keys($urls)[0];
			$url = self::BASE_URL . $urls[$current_section];
			$params = array();
			if ( $this->auto_import ) {
				$latest = get_posts( array(
					'post_type'   => $current_section,
					'numberposts' => 1,
					'orderby'     => 'date',
					'order'       => 'DESC',
					'author'      => $this->get_option( 'author' ),
					'tax_query'   => array( array(
						'taxonomy' => 'keyring_services',
						'field'    => 'slug',
						'terms'    => array( self::SLUG ),
						'operator' => 'IN',
					) ),
				) );
				// If we have already imported some, then start since the most recent
				if ( $latest ) {
					$max = strtotime($latest[0]->post_modified);
					$max = new DateTime("@$max");
					$max =  $max->sub(new DateInterval('P30D'));
					$url = add_query_arg( 'updated_after', $max->getTimestamp(), $url );
				}
			} else {
				// Handle page offsets (only for non-auto-import requests)
				$nextLink = $this->get_option('nextLink');
				if($nextLink != null)
				{
					$url = self::BASE_URL . $this->get_option( 'nextLink');
				}
			}
			echo $url;
			return $url;
		}
	
		function extract_posts_from_data( $raw ) {
			global $wpdb;
	
			if(property_exists($raw->data,'items'))
			{
				$importdata = $raw->data->items;
			}
			
	
			if ( null === $importdata ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-jawbone-importer-failed-download', __( 'Failed to download your activities from Jawbone. Please wait a few minutes and try again.', 'keyring' ) );
			}
	
			// Check for API overage/errors
			if ( !empty( $importdata->error ) ) {
				$this->finished = true;
				return new Keyring_Error( 'keyring-jawbone-importer-throttled', __( 'You have made too many requests to Jawbone and have been temporarily blocked. Please try again in 1 hour (duplicate activities will be skipped).', 'keyring' ) );
			}
	
			// Make sure we have some tweets to parse
			if ( !is_array( $importdata ) || !count( $importdata ) ) {
				$this->finished = true;
				return;
			}
			
			if(property_exists($raw->data,'links'))
			{
				if(property_exists($raw->data->links,'next')){
					$this->set_option('nextLink',$raw->data->links->next);
				}
				else{
					$this->set_option('nextLink',null);
				}
			}else{
				$this->set_option('nextLink',null);
			}
			
			// Get the total number of tweets we're importing
			$this->set_option( 'total', count($importdata) );
	
			// Parse/convert everything to WP post structs
			foreach ( $importdata as $post ) {
				$jawbone_id = $post->xid;
				$jawbone_time_created = $post->time_created;
				
				$jawbone_time_updated = $post->time_updated;
				if($post->title != null){ 
					$post_title = strip_tags( $post->title );
				}
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $jawbone_time_created );
				$post_date     = get_date_from_gmt( $post_date_gmt );
				$post_modified_gmt = gmdate( 'Y-m-d H:i:s', $jawbone_time_updated );
				$post_modified     = get_date_from_gmt( $post_modified_gmt );
				$post_title = $post_date;
				$post_content = $post->note;
				if ( !empty( $post->place_lat ) && !empty($post->place_lon) )
					$geo = array(
						'lat' => $post->place_lat,
						'long' => $post->place_lon
					);
				else $geo = array();
				
				$post_author             = $this->get_option( 'author' );
				$post_status             = 'publish';
					
				$jawbone_raw              = json_encode($post);
				$urls = $this->get_option('urls');
				$current_section = array_keys($urls)[0];
				switch($current_section)
				{
					case "body":
						$post_type = 'body';
						$extData = $this->getBody($post);
						break;
					case "activity":
						$post_type = 'activity';
						$extData = $this->getWorkout($post);
						break;
				}
	
				// Build the post array, and hang onto it along with the others
				$newPost = compact( 
					'post_author',
					'post_date',
					'post_date_gmt',
					'post_title',
					'post_status',
					'post_type',
					'post_content',
					'post_modified_gmt',
					'post_modified',
					'geo',
					'jawbone_id',
					'jawbone_raw'
				);
				
				$newPost = array_merge($newPost,$extData);
				$this->posts[] = $newPost;
			}
		}
	
		function insert_posts() {
			global $wpdb;
			$imported = 0;
			$skipped  = 0;
			foreach ( $this->posts as $post ) {
				extract( $post );
				$query_args = array(
					'post_type' => $post_type,
					'numberposts' => 1,
					'post_status' => 'any',
					'author'      => $this->get_option( 'author' ),
					'meta_query'=> array(
						'relation' => 'OR',
						array(
							'key' => 'jawbone_id',
							'value' => $jawbone_id,
							'compare' => '='
						)
					)
				);
				switch($post_type){
					case 'activity':
						$query_args['meta_query'][] = array('relation' => 'AND',
							array(
								'key'=> 'start_timestamp',
								'value' => strtotime($post_date_gmt),
								'type' => 'numeric',
								'compare' => '='
							),
							array(
								'key'=> 'end_timestamp',
								'value' => $jawbone_time_completed,
								'type' => 'numeric',
								'compare' => '='
							)
						);
				}
				$body_query = new WP_Query($query_args);
				if($body_query->post_count > 0){
					$post_id = $body_query->post->ID;
					$post['ID'] = $post_id;
					if($post_type == 'body'){
						wp_update_post($post);
					}
					else
					{
						$oldPost = $body_query->post;
						$oldPost->post_modified_gmt = $post_modified_gmt;
						$oldPost->post_modified = $post_modified;
						wp_update_post($oldPost);
					}
					$skipped++;
				} else {
					// find applicable post if exists
					$post_id = wp_insert_post( $post );
					$imported++;
				}
				if ( is_wp_error( $post_id ) )
					return $post_id;
	
				if ( !$post_id )
					continue;
	
				// Track which Keyring service was used
				wp_set_object_terms( $post_id, self::LABEL, 'keyring_services',true );
				
	
				update_post_meta($post_id,'jawbone_id',$jawbone_id);
				update_post_meta($post_id,'jawbone_raw',$jawbone_raw);
				
				switch($post_type){
					case "body":
						$this->setBody($post_id,$post);
						break;
					case "workout":
						$this->setWorkout($post_id,$post);
						break;
				}
				
				do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
			}
			$this->posts = array();
	
			if($this->get_option('nextLink') == null){
				$urls = $this->get_option('urls');
				array_shift($urls);
				if(empty($urls)){
					$this->finished = true;	
				}
				$this->set_option('urls',$urls);
			}
			// Return, so that the handler can output info (or update DB, or whatever)
			return array( 'imported' => $imported, 'skipped' => $skipped );
		}
		
		function getBody($post){
			$jawbone_weight = $post->weight;
			$jawbone_body_fat = $post->body_fat;
			$jawbone_lean_mass = $post->lean_mass;
			$jawbone_bmi = $post->bmi;
			return compact(
				'jawbone_weight',
				'jawbone_body_fat',
				'jawbone_lean_mass',
				'jawbone_bmi'
			);
		}
		
		function getWorkout($post){
			$jawbone_image = $post->image;
			$jawbone_snapshot_image = $post->snapshot_image;
			$jawbone_time_completed = $post->time_completed;
			
			return compact(
				'jawbone_time_completed'
			);
		}
		
		function setBody($post_id,$post){
			extract( $post );
			
			if ( !empty($jawbone_weight) ) {
				wp_set_object_terms( $post_id, 'weight','measurement_type',true);
				update_post_meta($post_id,'weight',$jawbone_weight);
			}
			
			if ( !empty($jawbone_body_fat) ) {
				wp_set_object_terms( $post_id, 'body-fat','measurement_type',true);
				update_post_meta($post_id,'body_fat',$jawbone_body_fat);
			}
			
			if ( !empty($jawbone_lean_mass) ) {
				wp_set_object_terms( $post_id, 'lean-mass','measurement_type',true);
				update_post_meta($post_id,'lean_mass',$jawbone_lean_mass);
			}
			
			if ( !empty($jawbone_lean_mass) ) {
				wp_set_object_terms( $post_id, 'bmi','measurement_type',true);
				update_post_meta($post_id,'bmi',$jawbone_bmi);
			}
		}
		
		function setWorkout($post_id,$post){
		}
	}
} 

$myActiveLife_Services_Jawbone = new MyActiveLife_Services_Jawbone();