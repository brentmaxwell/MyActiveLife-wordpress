<?php

class MyActiveLife_Taxonomies_Author extends CustomTaxonomy{
	public function __construct(){
		$this->taxonomy_type = 'media_author';
		$this->post_type = 'media';
		$this->options = array(
			'label'		=> 'Author',
			'labels'	=> array(
				'name'               => __('Authors'),
				'singular_name'      => __('Author'),
				'menu_name'          => __('Authors'),
				'all_items'          => __('Authors'),
				'edit_item'          => __('Edit Author'),
				'view_item'          => __('View Author'),
				'update_item'        => __('Update Author'),
				'add_new_item'       => __('Add New Author'),
				'new_item_name'      => __('Author'),
				//'parent_item'        => __(),
				'parent_item_colon'  => __(':'),
				'search_items'       => __('Search Authors'),
				'popular_items'      => __('Popular Authors'),
				//'separate_items_with_commas' => ('')
				//'add_or_remove_items' => __(),
				//'choose_from_most_used' => __(),
				'not_found'          => __('No Authors found'),
			),
			'public'                 => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_tagcloud'          => true,
			//'meta_box_cb'            =>,
			'show_admin_column'      => true,
			'hierarchical'           => false,
			'rewrite'                => array(
				'slug'               => 'author',
				'with_front'         => true,
				'hierarchical'       => false,
				//'ep_mask'            =>
			),
			//'capabilities'           =>,
			'sort'                   => false, 
		);
		parent::__construct();
	}
}

$myActiveLife_Taxonomies_Author = new MyActiveLife_Taxonomies_Author();