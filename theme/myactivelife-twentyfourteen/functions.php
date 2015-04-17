<?php
include('functions/template_functions.php');
include('functions/misc_functions.php');

class theBrentTwentyfourteenTheme {
	
	public function __construct(){
		add_action( 'after_setup_theme', array($this,'setup'));
		add_action( 'wp_enqueue_scripts', array($this, 'register_styles' ));
		add_action( 'wp_enqueue_scripts', array($this, 'register_scripts' ));
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts'));
		add_action( 'admin_init',array($this,'editor_style'));
		add_action( 'admin_enqueue_scripts',array($this,'admin_style') );
		//add_filter( 'getarchives_where', array($this,'getarchives_where'));
		//add_filter( 'nav_menu_css_class' , array($this,'list_group_item') , 10 , 2);
	}
	
	function setup(){
		load_theme_textdomain( 'myactivelife', get_template_directory() . '/languages' );
		$this->add_theme_support();
		//$this->register_nav_menus();
		$this->register_sidebars();
		//if (class_exists('WPLessPlugin')){
		//	add_filter('wp_less_compiler', array($this,'set_less_compiler'));
		//}
	}
	
	function register_sidebars(){
		register_sidebar( array(
			'name'          => __( 'Primary Sidebar Accordion', 'twentyfourteen' ),
			'id'            => 'sidebar-accordion',
			'description'   => __( 'Main sidebar that appears on the left in an accordion.', 'twentyfourteen' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		) );
	}
	
	function admin_style(){
        wp_enqueue_style('custom-admin-style',get_stylesheet_directory_uri(). '/css/admin-style.css' );
		wp_enqueue_style('myactivelifeicons',get_stylesheet_directory_uri(). '/fonts/myactivelifeicons/style.css' );
	}
	
	function add_theme_support(){
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 672, 372, true );
		add_image_size( 'twentyfourteen-full-width', 1038, 576, true );
		add_theme_support( 'custom-background', apply_filters( 'twentyfourteen_custom_background_args', array(
			'default-color' => 'f5f5f5',
		) ) );
		add_theme_support( 'featured-content', array(
			'featured_content_filter' => 'twentyfourteen_get_featured_posts',
			'max_posts' => 6,
		) );
		add_theme_support( 'html5', array(
			'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
		) );
		add_theme_support( 'post-formats', array(
			'aside', 'image', 'video', 'quote', 'link', 'gallery', 'status', 'audio', 'chat'
		) );
		add_theme_support( 'custom-header', apply_filters( 'twentyfourteen_custom_header_args', array(
			'default-text-color'     => 'fff',
			'width'                  => 0,
			'height'                 => 0,
			'flex-height'            => true,
			'flex-width'             => true,
			'wp-head-callback'       => 'twentyfourteen_header_style',
			'admin-head-callback'    => 'twentyfourteen_admin_header_style',
			'admin-preview-callback' => 'twentyfourteen_admin_header_image',
		) ) );
	}
	
	function register_styles(){
		wp_enqueue_style( 'parent-style' , get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'child-style' , get_stylesheet_directory_uri() . '/style.css' );
		//wp_enqueue_style('jquery-ui',get_stylesheet_directory_uri().'/css/jquery-ui.min.css');
		//wp_enqueue_style('jquery-ui',get_stylesheet_directory_uri().'/css/jquery-ui.structure.min.css');
		//wp_enqueue_style('jquery-ui',get_stylesheet_directory_uri().'/css/jquery-ui.theme.min.css');
		wp_enqueue_style( 'myactivelifeicons' , get_stylesheet_directory_uri() . '/fonts/myactivelifeicons/myactivelifeicons.css' );
	}
	
	function register_scripts(){
		wp_enqueue_script( 'jquery-ui', get_stylesheet_directory_uri() . '/js/jquery-ui.min.js', array( 'jquery' ), '1.11.4', true );
		
	}
	
	function pre_get_posts($query){
		if (
			!is_admin() &&
			!$query->is_page() &&
			$query->is_main_query() &&
			!$query->is_post_type_archive() &&
			!$query->is_single() &&
			!$query->is_attachment() &&
			!$query->is_tax()
			){
			$post_types = $query->get('post_type');
			if(is_array($post_types))
			{
				$post_types[] = 'post';
				$post_types[] = 'activity';
				$post_types[] = 'trip';
			}
			else
        		$query->set( 'post_type', array('post','activity','trip', $post_types));
    	}
		if(is_post_type_archive( 'media') || is_tax('people')){
			$query->set('posts_per_page',24);
		}
		if(is_tax('people')){
			$post_types = $query->get('post_type');
			if(is_array($post_types))
			{
				$post_types[] = 'media';
			}
			else
        		$query->set( 'post_type', array('media', $post_types));
		
		}
	}
	
	public function getarchives_where($where){
		$where = str_replace("post_type = 'post'", "post_type IN ('post','activity','trip')",$where);
		return $where;
	}
}

$wpThemeSetup = new theBrentTwentyfourteenTheme();
