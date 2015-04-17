<?php

class MyActiveLife_AdminMeta_GeoMetaBox{
	public function __construct(){
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );

	}
	
	public function add_metabox(){
		/*if(get_option('geo_metabox_use_dynamic_maps') == '1'){
			$api_key = get_option('geo_metabox_use_dynamic_maps');
			if($api_key != null){
				wp_enqueue_script('google_maps','https://maps.googleapis.com/maps/api/js?key='.$api_key,array(),'v3',false);
					
			}
		}*/
		add_meta_box(
			'geo',
			__('Geo'),
			array($this,'render_metabox'),
			null,
			'side'
		);
	}
	
	public function render_metabox( $post, $metabox ){
		echo "<table>";
		echo "<tbody>";
		
		wp_nonce_field( 'geo_metabox', 'geo_metabox_nonce' );

		$meta = get_post_meta($post->ID);
		echo "<tr>";
		echo $this->generate_metabox_field('geo_public',$meta['geo_public'][0],'Public','checkbox');
		echo '</tr>';
		echo "<tr>";
		echo $this->generate_metabox_field('geo_latitude',$meta['geo_latitude'][0],'Latitude');
		echo '</tr>';
		echo "<tr>";
		echo $this->generate_metabox_field('geo_longitude',$meta['geo_longitude'][0],'Longitude');
		echo '</tr>';
		echo "</tbody>";
		echo "</table>";
		if( !is_null($meta['geo_latitude']) || !is_null($meta['geo_longitude']) ){
			if(get_option('geo_metabox_use_dynamic_maps') == '1'){
				$this->render_map_dynamic(array(
					'lat' => $meta['geo_latitude'][0],
					'lng' => $meta['geo_longitude'][0]),
					array(
						'height' => 254,
						'width' => 254
					)
				);	
			}else{
				$this->render_map_static(array(
					'lat' => $meta['geo_latitude'][0],
					'lng' => $meta['geo_longitude'][0]),
					array(
						'height' => 254,
						'width' => 254
					)
				);	
			}
			
		}
	}
	
	public function generate_metabox_field($meta_id,$meta_value,$label,$type="text"){
		$output =  '<td><label for="'.$meta_id.'">';
		$output .=  __( $label);
		$output .= '</label></td><td>';
		$output .= '<input type="'.$type.'" id="'.$meta_id.'" name="'.$meta_id.'"';
		switch($type)
		{
			case 'checkbox':
				$output .= ' value="1" '.checked( $meta_value, true, false );
				break;
			default:
				$output .= ' value="'.esc_attr( $meta_value ).'"';
				break;
		}
		$output .= '"/></td>';
        
		return $output;
	}
	
	public function render_map_static($location,$size){
		$base_url = 'https://maps.google.com/maps/api/staticmap?';
		$params = array(
			'maptype' =>'terrain',
			'sensor' => 'false',
			'size' => $size['width'].'x'.$size['height'],
			'markers' => 'color:red|'.$location['lat'].','.$location['lng']
		);
		$base_url .= http_build_query($params);
		echo '<img src="'.$base_url.'"/>';
	}
	
	public function mapsUrl($location,$size){
		
	}
	
	public function render_map_dynamic($location,$size){
		echo '<img src="'.$this->mapsUrl($location,$size).'"/>';
	}
	
	public function save_metabox($post_id){
		if ( ! isset( $_POST['geo_metabox_nonce'] ) ) {
			return;
		}
	
		if ( ! wp_verify_nonce( $_POST['geo_metabox_nonce'], 'geo_metabox' ) ) {
			return;
		}
	
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		$geo_latitude = sanitize_text_field( $_POST['geo_latitude'] );
		$geo_longitude = sanitize_text_field( $_POST['geo_longitude'] );
		$geo_public = $_POST['geo_public'];
		
		update_post_meta( $post_id, 'geo_latitude', $geo_latitude );
		update_post_meta( $post_id, 'geo_longitude', $geo_longitude );
		update_post_meta( $post_id, 'geo_public', $geo_public );	
	}
	
	
	function settings_init() {
		add_settings_section(
			'geo_metabox_settings',
			__( 'Geo MetaBox'),
			array( $this, 'settings_section_callback' ),
			'writing'
		);

		/*add_settings_field(
			'geo_metabox_use_dynamic_maps',
			__( 'Use Dynamic Maps'),
			array( $this, 'setting_option_callback_use_dynamic_maps' ),
			'writing',
			'geo_metabox_settings'
		);*/
		
		add_settings_field(
			'geo_metabox_google_api_key',
			__( 'Google Maps API Key'),
			array( $this, 'setting_option_callback_apikey' ),
			'writing',
			'geo_metabox_settings'
		);
		
		register_setting(
			'writing',
			'geo_metabox_use_dynamic_maps'
		);
		
		register_setting(
			'writing',
			'geo_metabox_google_api_key'
		);
	}
	
	function settings_section_callback() {
		?><p><?php _e( 'Geo Metabox Options'); ?></p><?php
	}
	
	function setting_option_callback_use_dynamic_maps() {
		?>
			<input name="geo_metabox_use_dynamic_maps" id="geo_metabox_use_dynamic_maps" <?php echo checked( get_option( 'geo_metabox_use_dynamic_maps', '0' ), true, false ); ?> type="checkbox" value="1" />
			<label>
				<?php _e('Use dynamic maps instead of static maps');?>
			</label>
		<?
	}
	
	function setting_option_callback_apikey() {
		?>
			<input name="geo_metabox_google_api_key" id="geo_metabox_google_api_key" type="text" value="<?php echo get_option( 'geo_metabox_google_api_key'); ?>" />
		<?
	}
}
$myActiveLife_AdminMeta_GeoMetaBox = new MyActiveLife_AdminMeta_GeoMetaBox();