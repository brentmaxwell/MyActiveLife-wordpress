<?php

class MyActiveLife_Activities{
	
	public function __construct(){
		register_activation_hook( __FILE__, array($this,'activate' ));
		add_action('init', array( $this, 'register_taxonomy'));
		add_action('init', array( $this, 'register_post_type'));
		add_action('pre_get_posts', array( $this, 'pre_get_posts'));
		add_filter('getarchives_where', array($this,'getarchives_where'));
		add_filter('wp_tag_cloud',array($this,'tag_cloud_filter'));
	}
	
	public function activate(){
		flush_rewrite_rules();
	}
	
	public function register_taxonomy(){
		register_taxonomy(
			'activity_type',
			'activity',
			array(
				'label'		=> 'Activity Type',
				'labels'	=> array(
					'name'               => __('Activity Types'),
					'singular_name'      => __('Activity Type'),
					'menu_name'          => __('Activity Types'),
					'all_items'          => __('Activity Types'),
					'edit_item'          => __('Edit Activity Type'),
					'view_item'          => __('View Activity Type'),
					'update_item'        => __('Update Activity Type'),
					'add_new_item'       => __('Add New Activity Type'),
					'new_item_name'      => __('Activity Type'),
					//'parent_item'        => __(),
					'parent_item_colon'  => __(':'),
					'search_items'       => __('Search Activity Types'),
					'popular_items'      => __('Popular Activity Types'),
					//'separate_items_with_commas' => ('')
					//'add_or_remove_items' => __(),
					//'choose_from_most_used' => __(),
					'not_found'          => __('No Activity Types found'),
				),
				'public'                 => true,
				'show_ui'                => true,
				'show_in_nav_menus'      => true,
				'show_tagcloud'          => true,
				//'meta_box_cb'            =>,
				'show_admin_column'      => true,
				'hierarchical'           => false,
				'rewrite'                => array(
					'slug'               => 'activitytype',
					'with_front'         => true,
					'hierarchical'       => false,
					//'ep_mask'            =>
				),
				//'capabilities'           =>,
				'sort'                   => false, 
			)
		);
		
		register_taxonomy(
			'location',
			'location',
			array(
				'label'		=> 'Location',
				'labels'	=> array(
					'name'               => __('Locations'),
					'singular_name'      => __('Location'),
					'menu_name'          => __('Locations'),
					'all_items'          => __('Locations'),
					'edit_item'          => __('Edit Location'),
					'view_item'          => __('View Location'),
					'update_item'        => __('Update Location'),
					'add_new_item'       => __('Add New Location'),
					'new_item_name'      => __('Location'),
					//'parent_item'        => __(),
					'parent_item_colon'  => __(':'),
					'search_items'       => __('Search Locations'),
					'popular_items'      => __('Popular Locations'),
					//'separate_items_with_commas' => ('')
					//'add_or_remove_items' => __(),
					//'choose_from_most_used' => __(),
					'not_found'          => __('No Locations found'),
				),
				'public'                 => true,
				'show_ui'                => true,
				'show_in_nav_menus'      => true,
				'show_tagcloud'          => true,
				//'meta_box_cb'            =>,
				'show_admin_column'      => true,
				'hierarchical'           => false,
				'rewrite'                => array(
					'slug'               => 'location',
					'with_front'         => true,
					'hierarchical'       => false,
					//'ep_mask'            =>
				),
				//'capabilities'           =>,
				'sort'                   => false, 
			)
		);
	}
	
	public function register_post_type(){
		register_post_type(
			'activity',
			array(
				'label'		=> 'Activity',
				'labels'	=> array(
					'name'               => __('Activities'),
					'singular_name'      => __('Activity'),
					'menu_name'          => __('Activities'),
					'name_admin_bar'     => __('Activities'),
					'all_items'          => __('Activities'),
					'add_new'            => __('Add New Activity'),				
					'add_new_item'       => __('Add New Activity'),
					'edit_item'          => __('Edit Activity'),
					'new_item'           => __('New Activity'),
					'view_item'          => __('View Activity'),
					'search_items'       => __('Search Activities'),
					'not_found'          => __('No Activities found'),
					'not_found_in_trash' => __('No Activities found in trash'),
					'parent_item_colon'  => __(':'),
				),
				'description'            => __("Activities"),
				'public'                 => true,
				'exclude_from_search'    => false,
				'publicly_queryable'     => true,
				'show_ui'                => true,
				'show_in_nav_menus'      => true,
				'show_in_menu'           => true,
				'show_in_admin_bar'      => true,
				'menu_position'          => null,
				'menu_icon'              => 'dashicons-chart-line',
				'capability_type'        => 'post',
				//'capabilities'           => array(),
				'map_meta_cap'           => null,
				'hierarchical'           => false,
				'supports'               => array(
					'title',
					'editor',
					'author',
					'thumbnail',
					'excerpt',
					'trackbacks',
					'custom-fields',
					'comments',
					'revisions',
					//'page-attributes',
					//'post-formats'
				),
				//'register_meta_box_cb'   =>,
				'taxonomies'             => array(
					'activity_type',
					'location',
					'keyring_services'
				),
				'has_archive'            => true,
				'rewrite'                => array(
					'slug'               => 'activities',
					'with_front'         => true,
					'feeds'              => true,
					'pages'              => true,
					//'ep_mask'            => 
				),
				//'query_var'              =>,
				'can_export'             => true,
				
			)
		);
	}
	
	public function pre_get_posts($query){
		if ( !is_admin() && !$query->is_page()) {
        	$query->set( 'post_type', array('post','activity' ));
    	}
	}
	
	public function getarchives_where($where){
		$where = str_replace("post_type = 'post'", "post_type IN ('post','activity')",$where);
		return $where;
	}
	
	public function tag_cloud_filter($content){
		return str_replace('_',', ',$content);
	}
}



