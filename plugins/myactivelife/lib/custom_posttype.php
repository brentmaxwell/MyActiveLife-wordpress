<?php

class CustomPostType{
	public function __construct(){
		register_activation_hook( __FILE__, array($this,'activate' ));
		add_action('init', array( $this, 'register_post_type'));
		add_action( 'add_meta_boxes_'.$this->post_type, array( $this, 'add_meta_box' ) );
		add_filter('manage_edit-'.$this->post_type.'_columns', array($this,'add_columns'));
		add_action('manage_'.$this->post_type.'_posts_custom_column', array($this,'display_columns'), 10, 2);
		add_action( 'save_post', array( $this, 'save_metabox' ) );
	}
	
	public function activate(){
		flush_rewrite_rules();
	}
	
	public function register_post_type(){
		register_post_type(
			$this->post_type,
			$this->options
		);
	}
	
	public function add_meta_box(){
		if(isset($this->meta_boxes)){
			foreach($this->meta_boxes as $metabox)
			{
				add_meta_box(
					$metabox['id'],
					__($metabox['title']),
					array($this,'render_meta_box'),
					$this->post_type,
					$metabox['context'],
					$metabox['priority'],
					$metabox
				);
			}
		}
		if(isset($this->parent_type)){
			add_meta_box(
				'post_parent_metabox',
				__('Parent'),
				array($this,'render_parent_meta_box'),
				$this->post_type,
				'side',
				'high'
			);
		}
		
	}
	
	public function render_meta_box($post,$metabox){
		echo "<table>";
		echo "<tbody>";
		// Add an nonce field so we can check for it later.
		wp_nonce_field( $metabox['args']['id'].'_metabox', $metabox['args']['id'].'_metabox_nonce' );

		$meta = get_post_meta($post->ID);
		foreach($metabox['args']['fields'] as $field){
			echo $this->generate_metabox_field($field,$meta[$field['id']][0]);	
		}
		echo "</tbody>";
		echo "</table>";
	}
	
	public function generate_metabox_field($field,$meta_value){
		$output =  '<tr><td><label for="'.$field['id'].'">';
		$output .=  __( $field['title']);
		$output .= '</label></td><td>';
		$output .= '<input id="'.$field['id'].'" name="'.$field['id'].'"';
		switch($field['type'])
		{
			case 'checkbox':
				$output .= ' type="checkbox" value="1" '.checked( $meta_value, true, false );
				break;
			case 'time':
				$d1 = new DateTime();
				$d2 = new DateTime();
				$d2->add(new DateInterval('PT'.$meta_value.'S'));
				$elapsed_time = $d2->diff($d1);
				$output .= ' type="time" value="'. $elapsed_time->format('%H:%I:%S').'"';
				break;
			case 'datetime-local':
				$output .= ' type="datetime-local" value="'. date("Y-m-d\TH:i:s",strtotime($meta_value)).'"';
				break;
			default:
				$output .= ' type="text" value="'.esc_attr( $meta_value ).'"';
				break;
		}
		$output .= '/>';
		if(isset($field['label'])){
			$output .='('.$field['label'] .')';	
		}
		$output .= '</td></tr>';
        
		return $output;
	}
	
	public function render_parent_meta_box($post){
		$parents = get_posts(
	        array(
	            'post_type'   => $this->parent_type, 
	            'orderby'     => 'post_date', 
	            'order'       => 'DESC', 
	            'numberposts' => -1 
	        )
	    );
		echo '<script type="text/javascript">'."\n";
		echo 'function setParentID(selected){'."\n";
		echo 'document.getElementById("parent_id").value = selected.value;'."\n";
		echo '};'."\n";
		echo '</script>';
		echo "<table>";
		echo "<tbody>";
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'post_parent_metabox', 'post_parent_metabox_nonce' );
		echo $this->generate_metabox_field(array('id'=>'parent_id','title'=>'ID'),$post->post_parent,'ID');
	 	if ( !empty( $parents ) ) {
        	echo '<tr><td colspan="2"><select id="parent_id_select" class="widefat" onchange="setParentID(this);">';
	        foreach ( $parents as $parent ) {
				if($parent->post_type != $this->post_type){
    	        	printf( '<option value="%s"%s>%s</option>', esc_attr( $parent->ID ), selected( $parent->ID, $post->post_parent, false ), esc_html( '(' . get_the_date('Y/m/d',$parent->ID) .') ' . $parent->post_title ) );
				}
        	}
        	echo '</select></td></tr>';
    	} 
		if($post->post_parent != null || $post->post_parent != 0){
			echo '<tr><td colspan="2">';
			echo '<a href="'.get_edit_post_link($post->post_parent).'">';
			echo get_the_title($post->post_parent);
			echo '</a>';
			echo "</td></tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}
	
	public function save_metabox($post_id){
		foreach($this->meta_boxes as $metabox){
			if ( ! isset( $_POST[$metabox['id'].'_metabox_nonce'] ) ) {
			return;
			}
		
			if ( ! wp_verify_nonce( $_POST[$metabox['id'].'_metabox_nonce'], $metabox['id'].'_metabox' ) ) {
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
	
			foreach($metabox['fields'] as $field){
				switch($field['type']){
					case 'checkbox':
						$field_data = sanitize_text_field( $_POST[$field['id']] );
						break;
					case 'datetime-local':
						$field_data = date('c',strtotime(sanitize_text_field( $_POST[$field['id']] )));
						break;
					default:
						$field_data = sanitize_text_field( $_POST[$field['id']] );
						break;
				}
				
				update_post_meta( $post_id, $field['id'], $field_data );	
			}
		}
	}
	
	public function add_columns($columns){
		if(isset($this->columns)){
			$columns = array_merge($columns,$this->columns);
		}
		if(isset($this->geo) && $this->geo){
			$columns['geo'] = __('Location');	
		}
		if(isset($this->parent_type)){
			$columns['parent'] = __('Parent');
		}
		
		return $columns;
	}
	
	function display_columns($column_name, $id) {
		if(isset($this->columns)){
			if(array_key_exists($column_name,$this->columns)){
				echo get_post_meta($id,$column_name,true);
			}
		}
		if($column_name == 'parent' && isset($this->parent_type)){
			$parent_post = get_post_field('post_parent',$id,'raw'); 
			if($parent_post != null){
				echo '<a href="'.get_edit_post_link($parent_post).'">';
				echo get_post_field('post_title',$parent_post);
				echo '</a>';
			}
		}
		if($column_name == 'geo' && isset($this->geo) && $this->geo){
			$meta = get_post_meta($id);
			if( !is_null($meta['geo_latitude']) || !is_null($meta['geo_longitude']) ){
				echo '<div>'.$meta['geo_latitude'][0].', '.$meta['geo_longitude'][0].'</div>';
	 			$this->render_map_static(array(
					'lat' => $meta['geo_latitude'][0],
					'lng' => $meta['geo_longitude'][0]),
					array(
						'height' => 100,
						'width' => 100
					)
				);
			}
		}
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
}