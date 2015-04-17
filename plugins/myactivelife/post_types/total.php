<?php
class MyActiveLife_PostTypes_Total extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'total';
		$this->options = array(
			'label'		=> 'Total',
			'labels'	=> array(
				'name'               => __('Totals'),
				'singular_name'      => __('Total'),
				'menu_name'          => __('Totals'),
				'name_admin_bar'     => __('Totals'),
				'all_items'          => __('Totals'),
				'add_new'            => __('Add New Total'),				
				'add_new_item'       => __('Add New Total'),
				'edit_item'          => __('Edit Total'),
				'new_item'           => __('New Total'),
				'view_item'          => __('View Total'),
				'search_items'       => __('Search Totals'),
				'not_found'          => __('No Totals found'),
				'not_found_in_trash' => __('No Totals found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Totals"),
			'public'                 => true,
			'exclude_from_search'    => false,
			'publicly_queryable'     => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 9,
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
				//'revisions',
				//'page-attributes',
				//'post-formats'
			),
			//'register_meta_box_cb'   =>,
			'taxonomies'             => array(),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'totals',
				'with_front'         => true,
				'feeds'              => true,
				'pages'              => true,
				//'ep_mask'            => 
			),
			//'query_var'              =>,
			'can_export'             => true,
			
		);
		parent::__construct();
	}
}

//$myActiveLife_PostTypes_Total = new MyActiveLife_PostTypes_Total();