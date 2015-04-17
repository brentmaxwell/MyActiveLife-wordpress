<?php
class MyActiveLife_Taxonomies_People extends CustomTaxonomy{
	public function __construct(){
		$this->taxonomy_type = 'people';
		$this->post_type = 'photo';
		$this->options = array(
			'label'		=> 'People',
			'labels'	=> array(
				'name'               => __('People'),
				'singular_name'      => __('Person'),
				'menu_name'          => __('People'),
				'all_items'          => __('People'),
				'edit_item'          => __('Edit Person'),
				'view_item'          => __('View Person'),
				'update_item'        => __('Update Person'),
				'add_new_item'       => __('Add New Person'),
				'new_item_name'      => __('Person'),
				//'parent_item'        => __(),
				'parent_item_colon'  => __(':'),
				'search_items'       => __('Search People'),
				'popular_items'      => __('Popular People'),
				//'separate_items_with_commas' => ('')
				//'add_or_remove_items' => __(),
				//'choose_from_most_used' => __(),
				'not_found'          => __('No People found'),
			),
			'public'                 => true,
			'show_ui'                => true,
			'show_in_nav_menus'      => true,
			'show_tagcloud'          => true,
			//'meta_box_cb'            =>,
			'show_admin_column'      => true,
			'hierarchical'           => false,
			'rewrite'                => array(
				'slug'               => 'people',
				'with_front'         => true,
				'hierarchical'       => false,
				//'ep_mask'            =>
			),
			//'capabilities'           =>,
			'sort'                   => false, 
		);
		parent::__construct();
		add_action('init',array($this,'add_rewrite_rule'));
	}
	
	public function add_rewrite_rule(){
		add_rewrite_rule("^people/([^/]+)/([^/]+)/?",'index.php?people=$matches[1]&post_type=$matches[2]','bottom');
	} 
}

$myActiveLife_Taxonomies_People = new MyActiveLife_Taxonomies_People();