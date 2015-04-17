<?php

class MyActiveLife_PostTypes_Apikey extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'apikey';
		$this->options = array(
			'label'		=> 'API Key',
			'labels'	=> array(
				'name'                => _x( 'API Keys', 'Post Type General Name'),
				'singular_name'       => _x( 'API Key', 'Post Type Singular Name'),
				'menu_name'           => __( 'API Keys'),
				'name_admin_bar'      => __( 'API Keys'),
				'parent_item_colon'   => __( ':'),
				'all_items'           => __( 'All Items'),
				'add_new_item'        => __( 'Add New Item'),
				'add_new'             => __( 'Add New'),
				'new_item'            => __( 'New Item'),
				'edit_item'           => __( 'Edit Item'),
				'update_item'         => __( 'Update Item'),
				'view_item'           => __( 'View Item'),
				'search_items'        => __( 'Search Item'),
				'not_found'           => __( 'Not found'),
				'not_found_in_trash'  => __( 'Not found in Trash'),
			),
			'description'            => __("API Key"),
			'public'                 => false,
			'exclude_from_search'    => true,
			'publicly_queryable'     => false,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_in_menu'           => true,
			'show_in_admin_bar'      => true,
			'menu_position'          => 80,
			'menu_icon'              => 'dashicons-admin-network',
			'capability_type'        => 'post',
			'capabilities'           => array(
				'edit_post'           => 'activate_plugins',
				'read_post'           => 'activate_plugins',
				'delete_post'         => 'activate_plugins',
				'edit_posts'          => 'activate_plugins',
				'edit_others_posts'   => 'activate_plugins',
				'publish_posts'       => 'activate_plugins',
				'read_private_posts'  => 'activate_plugins',
			),
			'map_meta_cap'           => null,
			'hierarchical'           => false,
			'supports'               => array(
				'title',
				'custom-fields',
			),
			'taxonomies'             => array(),
			'has_archive'            => false,
			'can_export'             => true,
			
		);
		$this->meta_boxes = array(
			array(
				'id' => 'api_key',
				'title' => 'Api Key',
				'context' => 'normal',
				'priority' => 'core',
				'fields' => array(
					array(
						'id' => 'client_id',
						'title' => 'Client ID'
					),
					array(
						'id' => 'client_secret',
						'title' => 'Client Secret'
					),
					array(
						'id' => 'base_endpoint',
						'title' => 'Base Endpoint'
					),
					array(
						'id' => 'authorize_endpoint',
						'title' => 'Authorize Endpoint'
					),
					array(
						'id' => 'access_token_endpoint',
						'title' => 'Access Token Endpoint'
					),
					array(
						'id' => 'scope',
						'title' => 'Scope'
					)
				)
			),
		);
		parent::__construct();
	}
}

//$myActiveLife_PostTypes_Apikey = new MyActiveLife_PostTypes_Apikey();