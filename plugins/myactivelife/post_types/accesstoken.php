<?php

class MyActiveLife_PostTypes_AccessToken extends CustomPostType{
	
	public function __construct(){
		$this->post_type = 'accesstoken';
		$this->options = array(
			'label'		=> 'Access Token',
			'labels'	=> array(
				'name'                => _x( 'Access Tokens', 'Post Type General Name'),
				'singular_name'       => _x( 'Access Token', 'Post Type Singular Name'),
				'menu_name'           => __( 'Access Tokens'),
				'name_admin_bar'      => __( 'Access Tokens'),
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
			'description'            => __("Access Tokens"),
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
				'author',
				'custom-fields',
			),
			'taxonomies'             => array(),
			'has_archive'            => false,
			'can_export'             => true,
			
		);
		$this->meta_boxes = array(
			array(
				'id' => 'access_token',
				'title' => 'Access Token',
				'context' => 'normal',
				'priority' => 'core',
				'fields' => array(
					array(
						'id' => 'access_token',
						'title' => 'Access Token'
					),
					array(
						'id' => 'expires',
						'title' => 'Expires'
					),
					array(
						'id' => 'refresh_token',
						'title' => 'Refresh Token'
					),
					array(
						'id' => 'user_id',
						'title' => 'User ID'
					)
				)
			),
		);
		parent::__construct();
	}
}

//$myActiveLife_PostTypes_AccessToken = new MyActiveLife_PostTypes_AccessToken();