<?php
class MyActiveLife_Services_Onedrive{
	public function __construct(){
		add_action('init', array( $this, 'register_importer'));
	}

	public function register_importer(){
		
		MyActiveLife_Services_Onedrive_Keyring(); // Load the class code from above
		add_action( 'keyring_load_services', array('MyActiveLife_Services_Onedrive_Keyring_Service','register_service') );
		keyring_register_importer(
			'onedrive',
			'MyActiveLife_Services_Onedrive_Keyring_Importer',
			plugin_basename( __FILE__ ),
			__( 'Import all of your photos from Onedrive.', 'keyring' )
		);
	}
}
// This is a horrible hack, because WordPress doesn't support dependencies/load-order.
// We wrap our entire class definition in a function, and then only call that on a hook
// where we know that the class we're extending is available. *hangs head in shame*

function MyActiveLife_Services_Onedrive_Keyring() {
	
class MyActiveLife_Services_Onedrive_Keyring_Importer extends Keyring_Importer_Base {
	const SLUG              = 'onedrive';    
	const LABEL             = 'Onedrive';    
	const KEYRING_SERVICE   = 'Keyring_Service_OneDrive';    // Full class name of the Keyring_Service this importer requires
	const REQUESTS_PER_LOAD = 1;     // How many remote requests should be made before reloading the page?
	const OBJECT_LIMIT = 20;
	const BASE_URL = "https://api.onedrive.com/v1.0/drive/special/cameraroll/view.changes?orderby=lastModifiedDateTime%20desc&top=";
	const THUMBNAIL_GET_URL = 'https://api.onedrive.com/v1.0/drive/items/{0}/thumbnails/0/';

	var $auto_import = false;

	function __construct() {
		parent::__construct();
		add_action( 'keyring_importer_onedrive_custom_options', array( $this, 'custom_options' ) );
		add_action('import_start',array($this->service,'maybe_refresh_token'));
	}

	function custom_options() {
		?>
		<?php
	}

 	function handle_request_options() {
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
		$url = self::BASE_URL.self::OBJECT_LIMIT;
		$changeToken = $this->get_option('changeToken');
		if($changeToken != null)
		{
			$url .= '&token='.$changeToken;
		}
		return $url;
	}

	function extract_posts_from_data( $raw ) {
		global $wpdb;
		if(property_exists($raw,'value'))
		{
			$importdata = $raw->value;
		}
		$changeToken = $raw->{'@changes.token'};
		$this->set_option('changeToken',$changeToken);

		if ( null === $importdata ) {
			$this->finished = true;
			return new Keyring_Error( 'keyring-onedrive-importer-failed-download', __( 'Failed to download your photos from Onedrive. Please wait a few minutes and try again.', 'keyring' ) );
		}

		// Check for API overage/errors
		if ( !empty( $importdata->error ) ) {
			$this->finished = true;
			return new Keyring_Error( 'keyring-onedrive-importer-throttled', __( 'You have made too many requests to Onedrive and have been temporarily blocked. Please try again in 1 hour (duplicate activities will be skipped).', 'keyring' ) );
		}

		// Make sure we have some tweets to parse
		if ( !is_array( $importdata ) || !count( $importdata) ) {
			$this->finished = true;
			return;
		}

		// Get the total number of tweets we're importing
		$this->set_option( 'total', count($importdata) );

		// Parse/convert everything to WP post structs
		foreach ( $importdata as $post ) {
			if(property_exists($post,'image')){
				// Post title can be empty for Asides, but it makes them easier to manage if they have *something*
				//$post_title = $post->name;
				if(property_exists($post->photo,'takenDateTime')){
					$onedrive_takenDateTime = $post->photo->takenDateTime;
				}
				else{
					$onedrive_takenDateTime = $post->createdDateTime;
				}
				
					
				if(property_exists($post,'thumbnails')){
					$onedrive_thumbnails = $post->thumbnails;
				}
				$onedrive_takenDateTime = rtrim($onedrive_takenDateTime,'Z');
				$dt = new DateTime($onedrive_takenDateTime,new DateTimeZone('America/New_York'));
				$onedrive_takenDateTime = $dt->getTimestamp();
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $onedrive_takenDateTime );
				$post_date     = get_date_from_gmt( $post_date_gmt );
				$post_status = 'pending';
				$onedrive_id = $post->id;
				$onedrive_link = $post->webUrl;
				$onedrive_raw = json_encode($post);
				$height = $post->image->height;
				$width = $post->image->width;
				$post_mime_type = $post->file->mimeType;
				if(property_exists($post,'location')){
					$geo = array('lat'=>$post->location->latitude,'long' =>$post->location->longitude);
				}
				$post_type = 'media';
				$this->posts[] = compact(
					'post_title',
					'post_date',
					'post_date_gmt',
					'post_type',
					'post_status',
					'post_mime_type',
					'onedrive_id',
					'onedrive_thumbnails',
					'onedrive_link',
					'onedrive_takenDateTime',
					'geo',
					'onedrive_raw',
					'height',
					'width'
				);
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
				'post_type' => 'activity',
				'meta_query'=> array(
					'relation' => 'AND',
					array(
						'key'=>'start_timestamp',
						'value' => $onedrive_takenDateTime,
						'type' => 'numeric',
						'compare' => '<='
					),
					array(
						'key'=>'end_timestamp',
						'value' => $onedrive_takenDateTime,
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
			//if(count($activities) > 0){
				$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'onedrive_id' AND meta_value = %s", $onedrive_id ) );
 
				if ($post_id) {
					$post['ID'] = $post_id;
					unset($post['post_status']);
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
				$thumbnail_response = $this->service->request(str_replace('{0}',$onedrive_id,self::THUMBNAIL_GET_URL));
				if($thumbnail_response != null){
					$thumbnail = json_encode($thumbnail_response->medium);
					$full = json_encode($thumbnail_response->large);
					$height = $thumbnail_response->large->height;
					$width = $thumbnail_response->large->width;
				}
				
				// Track which Keyring service was used
				wp_set_object_terms( $post_id, self::LABEL, 'keyring_services' );
				
				update_post_meta( $post_id, 'onedrive_id', $onedrive_id );

				if(!empty($full)){
					update_post_meta($post_id,'full',$full);
					update_post_meta($post_id,'height',$height);
					update_post_meta($post_id,'width',$width);
				}
				
				if(!empty($thumbnail)){
					update_post_meta($post_id,'thumbnail',$thumbnail);
				}
				
				update_post_meta($post_id,'link',$onedrive_link);
				
				update_post_meta($post_id,'onedrive_raw',$onedrive_raw);
				
				if ( !empty( $geo ) ) {
						update_post_meta( $post_id, 'geo_latitude', $geo['lat'] );
						update_post_meta( $post_id, 'geo_longitude', $geo['long'] );
						update_post_meta( $post_id, 'geo_public', 1 );
					}
	
				do_action( 'keyring_post_imported', $post_id, static::SLUG, $post );
			//}
			//else
			//{
//				$skipped++;
			//}
		}
		$this->posts = array();
		do_action('disallow_empty_post');
		// Return, so that the handler can output info (or update DB, or whatever)
		return array( 'imported' => $imported, 'skipped' => $skipped );
	}
}
} 

$myActiveLife_Services_Onedrive = new MyActiveLife_Services_Onedrive();