<?php

class MyActiveLife_PostTypes_Media extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'media';
		$this->options = array(
			'label'		=> 'Photos & Videos',
			'labels'	=> array(
				'name'               => __('Photos & Videos'),
				'singular_name'      => __('Photos & Videos'),
				'menu_name'          => __('Photos & Videos'),
				'name_admin_bar'     => __('Photos & Videos'),
				'all_items'          => __('All '),
				'add_new'            => __('Add New Photos & Videos'),				
				'add_new_item'       => __('Add New Photos & Videos'),
				'edit_item'          => __('Edit Photos & Videos'),
				'new_item'           => __('New Photos & Videos'),
				'view_item'          => __('View Photos & Videos'),
				'search_items'       => __('Search Photos & Videos'),
				'not_found'          => __('No Photos & Videos found'),
				'not_found_in_trash' => __('No Photos & Videos found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Linked Photos & Videos"),
			'public'                 => true,
			'exclude_from_search'    => false,
			'publicly_queryable'     => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 12,
			'menu_icon'              => 'dashicons-admin-media',
			'capability_type'        => 'post',
			//'capabilities'           => array(),
			'map_meta_cap'           => null,
			'hierarchical'           => false,
			'supports'               => array(
				'title',
			//	'editor',
				'author',
				'thumbnail',
				'excerpt',
				'trackbacks',
				'custom-fields',
				'comments',
				//'revisions',
				//'page-attributes',
				//'post-formats'
			),
			//'register_meta_box_cb'   =>,
			'taxonomies'             => array(
				'keyring_services',
     			'people',
     			'post_tag',
				'media_author'
			),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'media',
				'with_front'         => true,
				'feeds'              => true,
				'pages'              => true,
				//'ep_mask'            => 
			),
			//'query_var'              =>,
			'can_export'             => true,
			
		);
		
		$this->meta_boxes = array(
			array(
				'id' => 'media',
				'title' => 'Media',
				'context' => 'side',
				'priority' => 'high'
			),
			array(
				'id' => 'details',
				'title' => 'Details',
				'context' => 'side',
				'priority' => 'high',
				'fields' => array(
					array(
						'id' => 'height',
						'title' => 'Height',
						'type'=>'number'
					),
					array(
						'id' => 'width',
						'title' => 'Width',
						'type'=>'number'
					),
				)
			)
		);
		$this->parent_type = 'activity';
		parent::__construct();
		//add_action( 'save_post', array( $this, 'update_post' ) );
		add_action( 'restrict_manage_posts', array($this,'mime_type_filter'));
		add_filter( 'posts_where', array($this,'filter_posts'));
		add_action('admin_head-edit.php',array($this,'title_filter'));
		
	}
	
	public function render_meta_box($post,$metabox){
		switch($metabox['id']){
			case 'media':
				$thumb = json_decode(get_post_meta($post->ID,'thumbnail',true));
				$photo = json_decode(get_post_meta($post->ID,'full',true));
				echo '<a href="#" onclick="window.open(\''.$photo->url.'\', \'\', \'height='.$photo->height.',width='.$photo->width.',menubar=0,toolbar=0,location=0\')">';
				echo '<img src="'.$thumb->url.'"/>';
				echo '</a>';
				break;
			default:
				parent::render_meta_box($post,$metabox);
		}
		
	}
	
	public function add_columns($columns){
		$columns['media'] = __('Thumbnail');
		unset($columns['tags']);
		unset($columns['taxonomy-media_author']);
		unset($columns['taxonomy-media_type']);
		return parent::add_columns($columns);
	}
	
	function display_columns($column_name, $id) {
    	switch ($column_name) {
	    	case 'media':
				$thumb = json_decode(get_post_meta($id,'thumbnail',true));
				$photo = json_decode(get_post_meta($id,'full',true));
				$link = get_post_meta($id,'link',true);
				echo '<a href="#" onclick="window.open(\''.$photo->url.'\', \'\', \'height='.$photo->height.',width='.$photo->width.',menubar=0,toolbar=0,location=0\')">';
				echo '<img src="'.$thumb->url.'"/>';
				echo '</a>';
				echo '<br/>';
				echo '<a href="'. $link .'">Link</a>';
				break;
	    	default:
				parent::display_columns($column_name,$id);
		        break;
    	} // end switch
	}   
	
	function title_filter(){
		global $post_type;
		if($this->post_type == $post_type)
			add_filter('the_title',array($this,'title_column_filter'),100,2);
	}
	
	function title_column_filter($title,$id){
		$media_type = explode('/',get_post_field('post_mime_type',$id,'raw'))[0];
		$title = '<span class="dashicons dashicons-format-'.$media_type.'"></span>&nbsp;' . $title;
		return $title;
	}
	
	public function mime_type_filter(){
		$type = 'media';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}

		//only add filter to post type you want
		if ('media' == $type){
			//change this to the list of values you want to show
			//in 'label' => 'value' format
			?>
			<select name="mime_type">
				<option value="0">All</option>
			<?php
				global $wpdb;
				$mime_types = $wpdb->get_col("SELECT DISTINCT `post_mime_type` FROM {$wpdb->posts} WHERE `post_mime_type` != ''");
				$current_v = isset($_GET['mime_type'])? $_GET['mime_type']:'';
				foreach($mime_types as $key=>$value){
					$mime_types[$key] = explode('/',$value)[0];	
				}
				$mime_types = array_unique($mime_types);
				foreach ($mime_types as $type) {
					printf
						(
							'<option value="%s"%s>%s</option>',
							$type,
							$type == $current_v? ' selected="selected"':'',
							$type
						);
					}
			?>
			</select>
			<?php
		}
	}
	
	public function filter_posts( $query ){
		global $pagenow;
		$type = 'media';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}
		if ( 'media' == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['mime_type']) && $_GET['mime_type'] != '' && $_GET['mime_type'] != '0') {
				$query .= " AND `post_mime_type` LIKE '%".$_GET['mime_type']."%'"; 
		}
		return $query;
	}
	
	public function update_post($post_id){
		$post = get_post($post_id);
		/*
			$keyring_service = call_user_func(array( 'Keyring_Service_'.$imported_from, 'init' ));
			$token = Keyring::get_token_store()->get_token( array( 'service' => $imported_from, 'id' => (int) $_REQUEST[ $imported_from . '_token' ] ) );
			
			remove_action( 'save_post', array( $this, 'update_post' ) );

			// update the post, which calls save_post again
			wp_update_post(  );

			// re-hook this function
			add_action( 'save_post', array( $this, 'update_post' ) );
		*/
	}
	
	
 	
}
$myActiveLife_PostTypes_Media = new MyActiveLife_PostTypes_Media();