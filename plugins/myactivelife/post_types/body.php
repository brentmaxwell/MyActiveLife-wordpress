<?php

class MyActiveLife_PostTypes_Body extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'body';
		$this->options = array(
			'label'		=> 'Body',
			'labels'	=> array(
				'name'               => __('Body'),
				'singular_name'      => __('Body'),
				'menu_name'          => __('Body'),
				'name_admin_bar'     => __('Body'),
				'all_items'          => __('Body'),
				'add_new'            => __('Add New Body'),				
				'add_new_item'       => __('Add New Body'),
				'edit_item'          => __('Edit Body'),
				'new_item'           => __('New Body'),
				'view_item'          => __('View Body'),
				'search_items'       => __('Search Body'),
				'not_found'          => __('No Body found'),
				'not_found_in_trash' => __('No Body found in trash'),
				'parent_item_colon'  => __(':'),
			),
			'description'            => __("Body Measurements"),
			'public'                 => true,
			'exclude_from_search'    => false,
			'publicly_queryable'     => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 11,
			'menu_icon'              => 'dashicons-universal-access',
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
			'taxonomies'             => array(
				'keyring_services',
				'measurement_type'
			),
			'has_archive'            => true,
			'rewrite'                => array(
				'slug'               => 'body-measurement',
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
				'id' => 'body',
				'title' => 'Measurements',
				'context' => 'normal',
				'priority' => 'core',
				'fields' => array(
					array(
						'id' => 'weight',
						'title' => 'Weight',
						'type' => 'number'
					),
					array(
						'id' => 'body_fat',
						'title' => 'Body Fat',
						'type' => 'number'
					),
					array(
						'id' => 'lean_mass',
						'title' => 'Lean Mass',
						'type' => 'number'
					),
					array(
						'id' => 'bmi',
						'title' => 'BMI',
						'type' => 'number'
					),
				)
			)
		);
		parent::__construct();
	}
}

$myActiveLife_PostTypes_Body = new MyActiveLife_PostTypes_Body();