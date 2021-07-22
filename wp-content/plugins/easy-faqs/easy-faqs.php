<?php
/*
Plugin Name: Easy FAQs
Plugin URI: https://goldplugins.com/our-plugins/easy-faqs-details/
Description: Easy FAQs - Provides custom post type, shortcodes, widgets, and other functionality for Frequently Asked Questions (FAQs).
Author: Gold Plugins
Version: 3.2.1
Author URI: https://goldplugins.com
Text Domain: easy-faqs

This file is part of Easy FAQs.

Easy FAQs is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Easy FAQs is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Easy FAQs .  If not, see <http://www.gnu.org/licenses/>.
*/

global $easy_faqs;
global $easy_faqs_footer_css_output;

require_once('include/easy_faqs_config.php');	
require_once('include/lib/lib.php');
require_once('include/lib/BikeShed/bikeshed.php');	
require_once("include/lib/GP_Media_Button/gold-plugins-media-button.class.php");
require_once("include/lib/GP_Janus/gp-janus.class.php");
require_once("include/lib/GP_Aloha/gp_aloha.class.php");
require_once('include/lib/ik-custom-post-type.php');
require_once('include/easy_faq_options.php');
require_once('include/tgmpa/init.php');
require_once('include/Easy_FAQs_Update_Notices.php');
require_once('include/Easy_FAQs_Upgrade_Reminder.class.php');

/* Gutenburg blocks */
if ( function_exists('register_block_type') ) {
	require_once('blocks/single-faq.php');
	require_once('blocks/list-faqs.php');
	require_once('blocks/faqs-by-category.php');
}

class easyFAQs
{
	var $category_sort_order = array();
	var $SearchFAQs = false;
	var $textdomain = "easy-faqs";
	var $is_pro = false;
	
	function __construct()
	{					
		//set class variable for tracking pro
		if ( isValidFAQKey() ) {
			$this->is_pro = true;
		}		
		
		//instantiate options
		$easy_faqs_options = new easyFAQOptions($this);
		
		//load strings with translations
		$this->strings = include('include/lib/strings.php');
		
		// init upgrade to Pro notices
		$this->Update_Notices = new Easy_FAQs_Update_Notices();
		
		//create shortcodes
		add_shortcode( 'single_faq', array($this, 'outputSingleFAQ') );
		add_shortcode( 'faqs', array($this, 'outputFAQs') );
		add_shortcode( 'faqs-by-category', array($this, 'outputFAQsByCategory') );
		add_shortcode( 'faqs_by_category', array($this, 'outputFAQsByCategory') ); // i've heard it both ways
		
		// add blocks
		if ( function_exists ('register_block_type') ) {
			register_block_type( 'easy-faqs/single-faq', array(
				'editor_script' 	=> 'single-faq-block-editor',
				'editor_style'  	=> 'single-faq-block-editor',
				'style'         	=> 'single-faq-block',
				'render_callback' 	=> array($this, 'outputSingleFAQ'),
			) );
			
			register_block_type( 'easy-faqs/list-faqs', array(
				'editor_script' => 'list-faqs-block-editor',
				'editor_style'  => 'list-faqs-block-editor',
				'style'         => 'list-faqs-block',
				'render_callback' 	=> array($this, 'outputFAQs'),
			) );
			
			register_block_type( 'easy-faqs/faqs-by-category', array(
				'editor_script' => 'faqs-by-category-block-editor',
				'editor_style'  => 'faqs-by-category-block-editor',
				'style'         => 'faqs-by-category-block',
				'render_callback' 	=> array($this, 'outputFAQsByCategory'),
			) );
			
		}

		//add JS
		add_action( 'wp_enqueue_scripts', array($this, 'easy_faqs_setup_js') );
		add_action( 'admin_enqueue_scripts', array($this, 'easy_faqs_setup_js') );

		//add CSS
		add_action( 'wp_enqueue_scripts', array($this, 'easy_faqs_setup_css') );
		add_action( 'admin_enqueue_scripts', array($this, 'easy_faqs_setup_css') );

		//add Custom CSS
		add_action( 'wp_head', array($this, 'easy_faqs_setup_custom_css') );

		//register sidebar widgets
		add_action( 'widgets_init', array($this, 'easy_faqs_register_widgets') );
		
		// add Meta Boxes to FAQs post type
		add_action( 'admin_menu', array($this, 'add_meta_boxes')); // add our custom meta boxes

		//do stuff
		add_action( 'after_setup_theme', array($this, 'easy_faqs_setup_faqs') );

		//add example shortcode to list of faqs
		add_filter('manage_faq_posts_columns', array($this, 'easy_faqs_column_head'), 10);  
		add_action('manage_faq_posts_custom_column', array($this, 'easy_faqs_columns_content'), 10, 2); 
		
		//add example shortcode to faq categories list
		add_filter('manage_edit-easy-faq-category_columns', array($this, 'easy_faqs_cat_column_head'), 10);  
		add_action('manage_easy-faq-category_custom_column', array($this, 'easy_faqs_cat_columns_content'), 10, 3); 
		
		// run a hook for other plugins (i.e., Pro plugin) to add their own submenus
		add_action( 'admin_menu', array($this, 'run_admin_menu_hook') );
		
		// enqueue admin styles and scripts
		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		
		// add Google web fonts if needed
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_webfonts') );
		
		//add our custom links for Settings and Support to various places on the Plugins page
		$plugin = plugin_basename(__FILE__);
		add_filter( "plugin_action_links_{$plugin}", array($this, 'add_settings_link_to_plugin_action_links') );
		add_filter( 'plugin_row_meta', array($this, 'add_custom_links_to_plugin_description'), 10, 2 );	
		
		// add a hook for firing the displayFAQsFromQuery function (e.g., from Pro plugin)
		add_filter( 'easy_faqs_render_faqs_loop', array($this, 'displayFAQsFromQuery'), 10, 2 );
		
		// add a hook for get_str
		add_filter( 'easy_faqs_get_str', array($this, 'get_str'), 10, 2 );
		
		/* Look for Export requests */
		add_action( 'admin_init', array($this, 'process_export') );

		//flush rewrite rules - only do this once!
		//we do this to prevent 404s when viewing individual FAQs
		register_activation_hook( __FILE__, array($this, 'easy_faqs_activation_hook') );
		
		//add our function to customize the excerpt, if enabled
		add_filter( 'excerpt_more', array($this,'easy_faqs_excerpt_more'), 9999 );
		add_filter( 'excerpt_length', array($this, 'easy_faqs_excerpt_length'), 9999 );
	
		//override content filter on single faqs 
		//to load the proper HTML structure and content for displaying an faq
		add_filter( 'the_content', array($this, 'single_faq_content_filter') );
		
		// add hooks for displaying quicklinks
		add_filter( 'easy_faqs_before_faqs_loop', array($this, 'maybe_display_quicklinks'), 10, 2 );
		add_action( 'easy_faqs_before_faqs_by_category', array($this, 'maybe_display_quicklinks'), 10, 2 );
		
		// add upgrade link to plugin description in plugins list
		add_filter( 'plugin_row_meta', array($this, 'add_custom_links_to_plugin_description'), 10, 2 );
				
		// add media buttons to admin
		$cur_post_type = ( isset($_GET['post']) ? get_post_type(intval($_GET['post'])) : '' );
		if( is_admin() && ( empty($_REQUEST['post_type']) || $_REQUEST['post_type'] !== 'faq' ) && ($cur_post_type !== 'faq') )
		{
			global $EasyFAQs_MediaButton;
			$EasyFAQs_MediaButton = new Easy_FAQs_Gold_Plugins_Media_Button('FAQs', 'format-chat');
			$EasyFAQs_MediaButton->add_button('Single FAQ', 'single_faq', 'singlefaqwidget', 'format-chat');
			$EasyFAQs_MediaButton->add_button('List of FAQs',  'faqs', 'listfaqswidget', 'format-chat');
			$EasyFAQs_MediaButton->add_button('FAQs By Category',  'faqs_by_category', 'faqsbycategorywidget', 'format-chat');
			
			if( $this->is_pro ){
				$EasyFAQs_MediaButton->add_button('Search FAQs',  'search_faqs', 'searchfaqswidget', 'format-chat');
				$EasyFAQs_MediaButton->add_button('Submit Question Form',  'submit_faq', 'submitfaqswidget', 'format-chat');
			}
		}
		
		// add Gutenburg custom blocks category 
		add_filter( 'block_categories', array($this, 'add_gutenburg_block_category'), 10, 2 );
		
		// make the list of themes available in JS (admin only)
		add_action( 'admin_init', array($this, 'provide_config_data_to_admin') );
		
		// add AJAX hooks for feedback links
		add_action( 'wp_ajax_easy_faqs_record_vote', array($this, 'ajax_record_vote') );
		add_action( 'wp_ajax_nopriv_easy_faqs_record_vote', array($this, 'ajax_record_vote') );
		add_action( 'wp_ajax_easy_faqs_delete_user_comment', array($this, 'ajax_delete_user_comment') );

		// load Janus
		if (class_exists('GP_Janus')) {
			$easy_faqs_Janus = new GP_Janus();
		}

		if ( is_admin() ) {
			// load Aloha
			$config = array(
				'menu_label' => __('About Plugin'),
				'page_title' => __('Welcome To Easy FAQs'),
				'tagline' => __('Easy FAQs is the easiest way to add an FAQs page to your website.'),
				'top_level_menu' => 'easy-faqs-settings',
			);
			$this->Aloha = new GP_Aloha($config);
			add_filter( 'gp_aloha_welcome_page_content_easy-faqs-settings', array($this, 'get_welcome_template') );			
		
			if ( !$this->is_pro ) {
				$this->Upgrade_Reminder = new Easy_FAQs_Upgrade_Reminder();
			}
			
			// load Sajak now so that the needed CSS and JS are enqueued in time
			new GP_Sajak();
		}
		
	}
	
	function get_welcome_template()
	{
		$base_path = plugin_dir_path( __FILE__ );
		$template_path = $base_path . '/include/content/welcome.php';
		$is_pro = isValidFAQKey();
		$content = file_exists($template_path)
				   ? include($template_path)
				   : '';
		return $content;
	}

	//runs when viewing a single faq's page (ie, you clicked on the continue reading link from the excerpt)
	function single_faq_content_filter($content) {
		global $easy_faqs_in_widget;
		global $easy_faq_in_content_filter;
		
		//THE GEORGE FIX
		wp_reset_postdata();
		
		//not running in a widget, is running in a single view or archive view such as category, tag, date, the post type is an faq
		if ( empty($easy_faqs_in_widget) && ( is_single() || is_archive() ) && get_post_type() == 'faq' ) {				
			$easy_faq_in_content_filter = true;
			//load needed data
			$postid = get_the_ID();
			$template_content = $this->outputSingleFAQ( array('id' => $postid, 'show_question' => false) );
			$easy_faq_in_content_filter = false;

			// load user feedback options
			$feedback_enabled = get_option('easy_faqs_visitor_feedback_enabled', true);
			if ( $feedback_enabled) {
				$feedback_html = $this->get_user_feedback_html($postid);
				$template_content .= $feedback_html;
			}		
			return $template_content;
		}
		return $content;
	}
	
	//only do this once
	function easy_faqs_activation_hook() {		
		
		// flush permalinks
		$this->easy_faqs_setup_faqs();		
		flush_rewrite_rules();
		
		// make sure the welcome screen gets seen again
		if ( !empty($this->Aloha) ) {
			$this->Aloha->reset_welcome_screen();		
		}
	}
	
	function run_admin_menu_hook($hook = '')
	{
		do_action('easy_faqs_add_menus', 'easy-faqs-settings');		
	}
		
	function admin_enqueue_scripts($hook)
	{
		
		// only enqueue scripts and styles on our admin pages
		$screen = get_current_screen();
		
		if ( is_admin() ) {
			wp_register_style( 'easy_faqs_admin_stylesheet', plugins_url('include/css/admin_style.css', __FILE__) );
			wp_enqueue_style( 'easy_faqs_admin_stylesheet' );			
			wp_enqueue_script(
				'easy-faqs-admin',
				plugins_url('include/js/easy-faqs-admin.js', __FILE__)
			);
		}
		
		if ( strpos($hook,'easy-faqs')!==false || $screen->id === "widgets" || ( function_exists('is_customize_preview') && is_customize_preview() ) )
		{			
			wp_enqueue_script(
				'gp-shortcode-generator',
				plugins_url('include/js/gp-shortcode-generator.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);		
			wp_enqueue_script(
				'gp-admin_v2',
				plugins_url('include/js/gp-admin_v2.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);
		}
		
		if ( !isValidFAQKey() ) {
			wp_register_style( 'easy_faqs_admin_stylesheet_global', plugins_url('include/css/admin_style_global.css', __FILE__) );
			wp_enqueue_style( 'easy_faqs_admin_stylesheet_global' );
		}
	}

	//apply any excerpt settings
	/* add customized continue reading link to answers, if set */
	function easy_faqs_excerpt_more( $more ) {
		global $post;
		
		//if this is an faq, use our customization
		if ($post->post_type == 'faq') {
			if ( get_option('easy_faqs_link_excerpt_to_full', false) ) {
				return ' <a class="more-link" href="' . get_permalink( get_the_ID() ) . '">' . get_option('easy_faqs_excerpt_text') . '</a>';
			} else {
				return ' ' . get_option('easy_faqs_excerpt_text');
			}			
		} else {
		//otherwise, return the currently set $more value
			return $more;
		}
	}
	//checks to see if this is an faq
	//if it is, loads custom excerpt length and uses it
	//otherwise use current wordpress setting
	function easy_faqs_excerpt_length( $length ) {
		global $post;
		
		//if this is an faq, use our customization
		if ($post->post_type == 'faq') {
			return get_option('easy_faqs_excerpt_length',55);
		}
		
		return $length;
	}
	
	//add an inline link to the settings page, before the "deactivate" link
	function add_settings_link_to_plugin_action_links($links)
	{
		$settings_link = sprintf( '<a href="%s">%s</a>', 'admin.php?page=easy-faqs-settings', __('Settings') );
		array_unshift($links, $settings_link); 
		
		$docs_link = sprintf( '<a href="%s">%s</a>', 'https://goldplugins.com/documentation/easy-faqs-documentation/?utm_source=easy_faqs&utm_campaign=easy_faqs_docs', __('Documentation') );
		array_unshift($links, $docs_link); 

		if( !isValidFAQKey() ) {
			$upgrade_url = 'http://goldplugins.com/special-offers/upgrade-to-easy-faqs-pro/?utm_source=easy_faqs_free_plugin&utm_campaign=upgrade_to_pro';
			$upgrade_link = sprintf( '<a href="%s" target="_blank" class="gp_pro_link">%s</a>', $upgrade_url, __('Upgrade to Pro') );
			array_unshift($links, $upgrade_link); 
		}

		if ( isset($links['edit']) ) {
			unset($links['edit']);
		}
		return $links; 
	}

	//add inlines link to pur plugin listing on the Plugins page, in the description area
	function add_custom_links_to_plugin_description($links, $file) 
	{	
		/** Get the plugin file name for reference */
		$plugin_file = plugin_basename( __FILE__ );
	 
		/** Check if $plugin_file matches the passed $file name */
		if ( $file == $plugin_file )
		{		
			$new_links['settings_link'] = '<a href="admin.php?page=easy-faqs-settings">' . htmlentities( $this->get_str('FAQ_SETTINGS_TEXT') ) . '</a>';
			$new_links['support_link'] = '<a href="https://goldplugins.com/contact/?utm-source=plugin_menu&utm_campaign=support&utm_banner=easy-faqs" target="_blank">' . htmlentities( $this->get_str('FAQ_SUPPORT_TEXT') ) . '</a>';
			
			if ( !isValidFAQKey() ) {
				$new_links['upgrade_to_pro'] = '<a href="https://goldplugins.com/our-plugins/easy-faqs-details/upgrade-to-easy-faqs-pro/?utm_source=plugin_menu&utm_campaign=up
				grade" target="_blank">' . htmlentities( $this->get_str('FAQ_UPGRADE_TEXT') ) . '</a>';
			}
			
			$links = array_merge( $links, $new_links);
		}
		return $links; 
	}

	// enqueue JS for front end and admin
	function easy_faqs_setup_js()
	{		
		if ( is_admin() ) {
			wp_enqueue_script(
				'gp-easy_faqs_theme_selector',
				plugins_url('include/js/gp-easy_faqs_theme_selector.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);			
		} else {
			// front-end scripts
			wp_register_script( 'easy_faqs', 
								plugins_url('include/js/easy_faqs.js', __FILE__),
								array('jquery'), // dependencies
								'1.1', // version
								true // in footer?
							);
			wp_enqueue_script( 'easy_faqs' );

			$feedback_thank_you_message = get_option('easy_faqs_visitor_feedback_thank_you_message', '' );
			$feedback_thank_you_message = !empty($feedback_thank_you_message) ? $feedback_thank_you_message : __('Thank you!', 'easy-faqs');

			$easy_faqs_vars = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'feedback_thank_you_message' => $feedback_thank_you_message,
			);
			wp_localize_script( 'easy_faqs', 'easy_faqs_vars', $easy_faqs_vars);  
		}
		do_action('easy_faqs_enqueue_scripts');
	}

	//add FAQ CSS to header
	function easy_faqs_setup_css()
	{
		$current_theme = get_option('faqs_style');
		wp_register_style( 'easy_faqs_style', plugins_url('include/css/style.css', __FILE__) );				
		switch ( $current_theme ) {
			case 'no_style':
				break;
			case 'default_style':
			default:
				wp_enqueue_style( 'easy_faqs_style' );
				break;
		}
		
		do_action('easy_faqs_enqueue_styles', $current_theme);
	}	
	
	//add Custom CSS
	function easy_faqs_setup_custom_css() {
		//use this to track if css has been output
		global $easy_faqs_footer_css_output;
		
		if ($easy_faqs_footer_css_output) {
			return;
		} else {
			echo '<style type="text/css" media="screen">' . get_option('easy_faqs_custom_css') . "</style>";
			$easy_faqs_footer_css_output = true;
		}
	}

	function word_trim($string, $count, $ellipsis = FALSE)	{
		$words = explode(' ', $string);
		if (count($words) > $count)
		{
			array_splice($words, $count);
			$string = implode(' ', $words);
			// trim of punctionation
			$string = rtrim($string, ',;.');	

			// add ellipsis if needed
			if ( is_string($ellipsis) ) {
				$string .= $ellipsis;
			} elseif ($ellipsis) {
				$string .= '&hellip;';
			}			
		}
		return $string;
	}

	// converts a DateTime string (e.g., a MySQL timestamp) into a friendly time string, e.g. "10 minutes ago"	
	// source: http://stackoverflow.com/a/18602474
	function time_elapsed_string($datetime, $full = false) {
		$now = new DateTime;
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);
		
		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;
		
		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}
		
		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
	
	//setup custom post type for faqs
	function easy_faqs_setup_faqs() {				
		//setup post type for faqs
		$postType = array(
			'name' => 'FAQ',
			'display_singular' =>'FAQ',
			'display_plural' =>'FAQs',
			'plural' =>'faqs',
			'slug' => 'faq',
			'menu_icon' => 'dashicons-format-chat'
		);
		$fields = array(); 
		$myCustomType = new ikFAQsCustomPostType($postType, $fields, false, $this->textdomain);
		register_taxonomy( 'easy-faq-category', 'faq', array( 
			'hierarchical' => true,
			'label' => 'FAQ Category',
			'rewrite' => array('slug' => 'faq-category', 'with_front' => true),
			'show_in_rest' => true
		) ); 
		
		//load list of current posts that have featured images	
		$supportedTypes = get_theme_support( 'post-thumbnails' );
		
		//none set, add them just to our type
		if ( $supportedTypes === false ) {
			add_theme_support( 'post-thumbnails', array( 'faq' ) );       
			//for the faq thumb images    
		}
		//specifics set, add our to the array
		elseif ( is_array( $supportedTypes ) ) {
			$supportedTypes[0][] = 'faq';
			add_theme_support( 'post-thumbnails', $supportedTypes[0] );
			//for the faq thumb images
		}
		//if neither of the above hit, the theme in general supports them for everything.  that includes us!
		
		add_image_size( 'easy_faqs_thumb', 50, 50, true );
	}
	 
	//this is the heading of the new column we're adding to the faq posts list
	function easy_faqs_column_head($defaults) {  
		$defaults = array_slice($defaults, 0, 2, true) +
		array(
			"single_shortcode" => "Shortcode",
			"feedback_score" => __("Feedback Score")
		) +
		array_slice($defaults, 2, count($defaults)-2, true);
		return $defaults;  
	}  

	//this content is displayed in the faq post list
	function easy_faqs_columns_content($column_name, $post_id) {  
		if ($column_name == 'single_shortcode') {  
			echo "<code>[single_faq id={$post_id}]</code>";
		}  
		else if ($column_name == 'feedback_score') {  
			$score = get_post_meta($post_id, 'feedback_score', true);
			$yes_votes = get_post_meta($post_id, 'feedback_yes_votes', true);
			$no_votes = get_post_meta($post_id, 'feedback_no_votes', true);
			$total_votes = get_post_meta($post_id, 'feedback_total_votes', true);
			if ($total_votes > 0) {
				$maybe_s = $total_votes > 1	? 's' : '';
				printf("%d%% Positive<br>(%d vote%s)", $score, $total_votes, $maybe_s);
			} else{
				printf( '<em>%s</em>', __('No feedback yet.', 'easy-faqs') );
			}
		}  
	} 

	//this is the heading of the new column we're adding to the faq category list
	function easy_faqs_cat_column_head($defaults) {  
		$defaults = array_slice($defaults, 0, 2, true) +
		array("single_shortcode" => "Shortcode") +
		array_slice($defaults, 2, count($defaults)-2, true);
		return $defaults;  
	}  

	//this content is displayed in the faq category list
	function easy_faqs_cat_columns_content($value, $column_name, $tax_id) {  

		$category = get_term_by('id', $tax_id, 'easy-faq-category');
		
		return "<code>[faqs category='{$category->slug}']</code>"; 
	} 

	//return an array of random numbers within a given range
	//credit: http://stackoverflow.com/questions/5612656/generating-unique-random-numbers-within-a-range-php
	function UniqueRandomNumbersWithinRange($min, $max, $quantity) {
		$numbers = range($min, $max);
		shuffle($numbers);
		return array_slice($numbers, 0, $quantity);
	}

	//output specific faq
	function outputSingleFAQ($atts)
	{		
		// go ahead and extract category and ID because we need them to generate the loop
		extract( shortcode_atts( array(
			'id' => NULL,
			'category' => '',
			'class' => '',
			'theme' => ''			
		), $atts ) );
		
		$loop = new WP_Query(
			array( 
				'post_type' => 'faq',
				'p' => $id,
				'easy-faq-category' => $category
			)
		);
		
		$faqs_list_html = $this->displayFAQsFromQuery($loop, $atts);
		return $faqs_list_html;
	}
	
	// Generic function to display the results of a WP_Query ($loop)
	function displayFAQsFromQuery( $loop, $atts = array() )
	{
		// load default shortcode attributes into an array
		// and merge with anything specified
		extract( shortcode_atts( array(
			'read_more_link' => get_option('faqs_link'),
			'id' => NULL,
			'category' => '',
			'show_thumbs' => get_option('faqs_image'),
			'style' => '',
			'accordion_style' => '',
			'quicklinks' => false,
			'scroll_offset' => 0,
			'read_more_link_text' =>  get_option('faqs_read_more_text', 'Read More'),
			'highlight_word' => '',
			'class' => '',
			'theme' => '',
			'use_excerpt' => false,
			'show_question' => true
		), $atts ) );

		// start building the HTML now
		$output = '';

		// run action before displaying FAQs (e.g., for displaying quicklinks)
		$output .= apply_filters('easy_faqs_before_faqs_loop', '', $atts);
		
		/*
		 * Build the wrapper class
		 */
		 
		$wrapper_classes = array('easy-faqs-wrapper');
		if ( !empty($class) ) {
			$wrapper_classes[] = $class;
		}
		
		$faqs_theme = !empty($theme) ? $theme : get_option('faqs_style');
		if ( $faqs_theme !== 'no_style' ) {
			// TODO: verify that its a valid theme name
			$wrapper_classes[] = sprintf('easy-faqs-theme-%s', $faqs_theme);
			$spot = strpos($faqs_theme, '-');
			if ($spot !== FALSE) {
				$faqs_theme_basename = substr($faqs_theme, 0, $spot);
				$wrapper_classes[] = sprintf('easy-faqs-theme-%s', $faqs_theme_basename);
			}
		}
		
		$wrapper_classes = apply_filters('easy_faqs_display_faqs_wrapper_classes', $wrapper_classes, $atts);
		$output .= sprintf( '<div class="%s">', implode(' ', $wrapper_classes) );
		
		// build a single FAQ HTML block for each item, adding it to $output
		while( $loop->have_posts() )
		{
			// load up the current post data with this FAQ's information
			// this lets us use get_the_content, get_the_title, etc
			$loop->the_post();
			
			// load content for this FAQ
			$postid = get_the_ID();
			$faq['content'] = get_post_meta($postid, '_ikcf_short_content', true); 		
			
			// if nothing is set for the short content, use the long content instead
			// RWG: I don't recall what the short content is, but I'm leaving it untouched WRT the excerpt
			if (strlen($faq['content']) < 2) {
				//if the excerpt attribute is set, (use_excerpt=1), use the excerpt instead of long content
				if ($use_excerpt) {
					$faq['content'] = get_the_excerpt();
				} else {
					$faq['content'] = get_the_content(); 
				}
			}
			
			// add an image, if requested
			if ($show_thumbs) {
				$faq_image_size = apply_filters('easy_faqs_featured_image_size', 'fullsize', $postid);
				$faq['image'] = get_the_post_thumbnail($postid, $faq_image_size);
				$image_html = $faq['image'];
			} else {
				$image_html = '';				
			}

			// generate the question and answer HTML
			$question_html = $this->build_the_question($postid);
			$answer_html = $this->build_the_answer($faq, $read_more_link, $read_more_link_text, $image_html);
			
			// highlight the query in the question & answer
			if ( strlen( trim($highlight_word) ) > 0 ) {
				$highlight_tag = '<span class="search_highlight">\1</span>';				
				$highlight_tag = apply_filters('easy_faqs_search_highlight_tag', $highlight_tag);
				$question_html = gp_str_highlight($question_html, $highlight_word, null, $highlight_tag);
				$answer_html = gp_str_highlight($answer_html, $highlight_word, null, $highlight_tag);
			}
			
			// put it all together into the single FAQ template
			if ( !isset($atts['show_question']) || !empty($atts['show_question']) ) {
				$faq_template = '<div class="easy-faq" id="easy-faq-%d">%s %s</div>';
				$faq_html = sprintf($faq_template, $postid, $question_html, $answer_html);
			}
			else {
				$faq_template = '<div class="easy-faq" id="easy-faq-%d">%s</div>';
				$faq_html = sprintf($faq_template, $postid, $answer_html);				
			}

			// add the completed FAQ to the output we are building
			$output .= $faq_html;
		} //endwhile;	
		
		// close the wrapper div
		$output .= '</div>';
		
		wp_reset_postdata();
		
		return $output;
	}
	
	function build_the_question($postid)
	{
		$h3 = '<h3 class="easy-faq-title" style="%s"><span class="easy-faqs-title-before"></span><span class="easy-faqs-title-text">%s</span><span class="easy-faqs-title-after"></span></h3>';
		$style_str = $this->build_typography_css('easy_faqs_question_');
		$output = sprintf( $h3, $style_str, get_the_title($postid) );
		return apply_filters( 'easy_faqs_question', $output);
	}
	
	function build_the_answer($faq, $read_more_link = '', $read_more_link_text = '', $image_html = '')
	{
		//track whether or not we're inside the_content filter
		global $easy_faq_in_content_filter;
		
		$template = '<div class="easy-faq-body" style="%s">%s %s</div>';		
		$content_str = '';
		
		// add featured image if present
		if ( !empty($image_html) ) {
			$featured_image_div = sprintf('<div class="easy-faq-featured-image">%s</div>', $image_html);
			$content_str .= apply_filters('easy_faqs_featured_image', $featured_image_div, $faq);
		}
		
		//if we're currently in the content filter, don't try and apply it again :-)
		if ($easy_faq_in_content_filter) {
			$content_str .= $faq['content'];
		} else {
			$content_str .= apply_filters('the_content', $faq['content']);
		}
		
		$style_str = $this->build_typography_css('easy_faqs_answer_');
		
		// add the read more link (if the user's options say to do so)
		if ( !empty($read_more_link) ) {
			// build the read more link to be inserted
			$link_template = '<a class="easy-faq-read-more-link" style="%s" href="%s">%s</a>';
			$link_style_str = $this->build_typography_css('easy_faqs_read_more_link_');
			$link_str = sprintf($link_template, $link_style_str, $read_more_link, $read_more_link_text);
		} else {
			// do not output a read more link
			$link_str = '';
		}
		$link_str = apply_filters( 'easy_faqs_read_more_link', $link_str);		
		
		// return the formatted answer text
		$output =  sprintf($template, $style_str, $content_str, $link_str);
		return apply_filters( 'easy_faqs_answer', $output);		
	}
	
	//output all faqs
	function outputFAQs($atts)
	{
		// go ahead and extract category and ID because we need them to generate the loop
		extract( shortcode_atts( array(
			'count' => -1,
			'category' => '',
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC', //'DESC',
			'quicklinks' => false,
			'scroll_offset' => 0,
			'class' => '',
			'theme' => ''
		), $atts ) );
		
		$args = array( 
			'post_type' => 'faq',
			'posts_per_page' => $count,
			'orderby' => $orderby,
			'order' => $order,
			'easy-faq-category' => $category,
		);
		
		$loop = new WP_Query($args);

		$faqs_list_html = $this->displayFAQsFromQuery($loop, $atts);
		return $faqs_list_html;
	}
	
	//passed the atts for the shortcode of faqs this is displayed above
	//loads faq data into a loop object
	//loops through that object and outputs quicklinks for those FAQs
	function maybe_display_quicklinks($content = '', $atts)
	{
		if ( !empty($atts['quicklinks']) ) {
			ob_start();
			$this->outputQuickLinks($atts, $by_category = false);
			$content = ob_get_contents();
			ob_end_clean();
		}
		return $content;		
	}		
	
	function outputQuickLinks($atts, $by_category = false)
	{		
		//load shortcode attributes into an array
		extract( shortcode_atts( array(
			'count' => -1,
			'category' => '',
			'category_id' => '',
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC',//'DESC'
			'colcount' => false,
			'scroll_offset' => 0,
			'class' => '',
			'theme' => ''			
		), $atts ) );
		
		$scroll_offset = intval($scroll_offset);
		
		if ($by_category) {
			//load list of FAQ categories
			$categories = array();
			$args = array();
			
			/* If a custom category order was specified, apply it now */
			if ( !empty($category_id) ) {
				// we may have many categorys, delimited by commas, so explode 
				// the ID string into an array and then trim any whitespace
				$cats = explode(',', $category_id);
				$trimmed_cats = array_map('trim', $cats);
				$args['include'] = $trimmed_cats;
				
				// get only the categories which were specified
				$categories = get_terms('easy-faq-category', $args);		
				
				// resort the category's by the custom order
				$this->category_sort_order = $trimmed_cats;
				usort( $categories, array($this, "order_faqs_by_category_id") );
			} else {
				// no custom ordering specified, so proceed normally
				$categories = get_terms('easy-faq-category', $args);		
			}

			$quick_links_title = '<h3 class="quick-links" id="quick-links-top">' . htmlentities( $this->get_str('FAQ_QUICK_LINKS_LABEL') ) . '</h3>';
			echo apply_filters( 'easy_faqs_quick_links_title', $quick_links_title);
			
			//loop through categories, outputting a heading for the category and the list of faqs in that category
			foreach ($categories as $category)
			{
				//output title of category as a heading
				$category_name = apply_filters( 'easy_faqs_category_name', $category->name);
				$category_heading = sprintf('<h4 class="easy-testimonial-category-heading">%s</h4>', $category_name);
				echo apply_filters( 'easy_faqs_quick_links_category_heading', $category_heading);

				//load faqs into an array
				$loop = new WP_Query( 
					array( 
						'post_type' => 'faq',
						'posts_per_page' => $count,
						'orderby' => $orderby,
						'order' => $order,
						'easy-faq-category' => $category->slug
					)
				);
			
				$i = 0;
				$r = $loop->post_count;
				
				if ( !$colcount ) {
					$divCount = intval($r/5);
					//if there are trailing testimonials, make sure we take into account the final div
					if ( ($r % 5) != 0 ) {
						$divCount ++;
					}
				}
				else {
					$divCount = intval($colcount);
				}
				
				//trying CSS3 instead...
				printf ('<div class="faq-questions" data-scroll_offset="%d">', $scroll_offset);
				echo "<ol style=\"-webkit-column-count: {$divCount}; -moz-column-count: {$divCount}; column-count: {$divCount};\">";
				
				while( $loop->have_posts() ) : $loop->the_post();

					$postid = get_the_ID();
					
					$list_item = '<li class="faq_scroll" id="'.$postid.'"><a href="#easy-faq-' . $postid . '">' . get_the_title($postid) . '</a></li>';
					echo apply_filters( 'easy_faqs_quick_links_list_item', $list_item);

					$i ++;
					
				endwhile;
				
				
				echo "</ol>";
				echo "</div>";
			} 
		} else {
			//load faqs into an array
			$loop = new WP_Query(
				array( 
					'post_type' => 'faq',
					'posts_per_page' => $count,
					'orderby' => $orderby,
					'order' => $order,
					'easy-faq-category' => $category
				)
			);
		
			$i = 0;
			$r = $loop->post_count;
			
			if ( !$colcount ) {
				$divCount = intval( $r/5 );
				//if there are trailing testimonials, make sure we take into account the final div
				if ( ($r % 5) != 0 ) {
					$divCount ++;
				}		
			} else {
				$divCount = intval($colcount);
			}
			
			//trying CSS3 instead...
			$quick_links_title = '<h3 class="quick-links" id="quick-links-top">Quick Links</h3>';			
			echo apply_filters( 'easy_faqs_quick_links_title', $quick_links_title);			
			printf ('<div class="faq-questions" data-scroll_offset="%d">', $scroll_offset);
			echo "<ol style=\"-webkit-column-count: {$divCount}; -moz-column-count: {$divCount}; column-count: {$divCount};\">";
			
			while( $loop->have_posts() ) : $loop->the_post();

				$postid = get_the_ID();
				
				echo '<li class="faq_scroll" id="'.$postid.'"><a href="#easy-faq-' . $postid . '">' . get_the_title($postid) . '</a></li>';

				$i ++;
				
			endwhile;
			
			
			echo "</ol>";
			echo "</div>";
		}
	}
	
	//output all faqs grouped by category
	function outputFAQsByCategory($atts) { 
		
		//load shortcode attributes into an array
		extract( shortcode_atts( array(
			'category_id' => '',
			'category_ids' => '',
			'category_order' => 'ASC',
			'category_orderby' => 'name',
			'read_more_link' => get_option('faqs_link'),
			'count' => -1,
			//'category' => '',
			'show_thumbs' => get_option('faqs_image'),
			'read_more_link_text' =>  get_option('faqs_read_more_text', 'Read More'),
			'style' => '',
			'accordion_style' => '',
			'quicklinks' => false,
			'scroll_offset' => 0,
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC', //'DESC'
			'class' => '',
			'theme' => '',
			'categories_accordion' => '', // none (off), 'accordion', 'accordion-collapsed'			
		), $atts ) );
				
		if ( !is_numeric($count) ) {
			$count = -1;
		}		

		// handle possible pluralization of category_id(s)
		if ( empty($category_id) && !empty($category_ids) ) {
			// note: $atts gets passed through to several other functions later,
			// so we need to update it too
			$category_id = $category_ids;
			$atts['category_id'] = $category_ids; 
		}

		ob_start();
		
		//load list of FAQ categories
		$categories = array();
		$args = array(
			'order' => $category_order,
			'orderby' => $category_orderby		
		);
		
		/* If a custom category order was specified, apply it now */
		if ( !empty($category_id) ) {
			// we may have many categorys, delimited by commas, so explode 
			// the ID string into an array and then trim any whitespace
			$cats = explode(',', $category_id);
			$trimmed_cats = array_map('trim', $cats);
			$args['include'] = $trimmed_cats;
			
			// get only the categories which were specified
			$categories = get_terms('easy-faq-category', $args);		
			
			// resort the category's by the custom order
			$this->category_sort_order = $trimmed_cats;
			usort( $categories, array($this, "order_faqs_by_category_id") );
		} else {
			// no custom ordering specified, so proceed normally
			$categories = get_terms('easy-faq-category', $args);		
		}
		
		// run action before displaying FAQs (e.g., for displaying quicklinks)
		$before_category = apply_filters('easy_faqs_before_faqs_by_category', '', $atts);
		if ( !empty($before_category) ) {
			echo $before_category;
		}
		
		// starting here, we force quicklinks to false so that we don't 
		// output another set of quicklinks for every category
		$atts['quicklinks'] = false;

		//loop through categories, outputting a heading for the category and the list of faqs in that category
		$category_classes = array('easy_faqs_category_wrapper');
		$category_classes = apply_filters( 'easy_faqs_category_classes', $category_classes, $atts );
		
		foreach ($categories as $category)
		{	
			// apply filter seperately for each category
			$filter_key = sprintf('easy_faqs_category_classes_%s', $category->name);
			$my_category_classes = apply_filters( $filter_key, $category_classes, $category );
			printf ( '<div class="%s">', implode(' ', $my_category_classes) );

			//output title of category as a heading
			$category_name = apply_filters( 'easy_faqs_category_name', $category->name);
			$category_heading = sprintf('<h2 class="easy-faqs-category-heading">%s</h2>', $category_name);
			echo apply_filters( 'easy_faqs_category_heading', $category_heading);
		
			//load faqs into an array and then output them as a list
			$loop = new WP_Query(
				array( 
					'post_type' => 'faq',
					'posts_per_page' => $count,
					'orderby' => $orderby,
					'order' => $order,
					'easy-faq-category' => $category->slug
				)
			);
			echo $this->displayFAQsFromQuery($loop, $atts);

			echo '</div>';//end easy_faqs_category_heading
			
		}//endforeach categories
		
		$content = ob_get_contents();
		ob_end_clean();	
		
		return $content;
	}
	
	function order_faqs_by_category_id($cat_1, $cat_2)
	{
		// first find their term's positions in our order array		
		// this is the key on which we will actually sort
		$c1_pos = array_search( $cat_1->term_id, $this->category_sort_order );
		$c2_pos = array_search( $cat_2->term_id, $this->category_sort_order );
		
		// now, handle cases where one of the keys wasn't found
		// in this case, whichever one was found "wins"		
		if ($c1_pos === FALSE && $c2_pos === FALSE) {
			return 0;
		}
		else if ($c1_pos >= 0 && $c2_pos == FALSE) {
			return 1;
		}
		else if ($c1_pos === FALSE && $c2_pos >= 0) {
			return -1;
		}		
				
		// both keys found; return the one which is first in our custom order
		if ($c1_pos === $c2_pos) {
			// this should only happen if a category id was duplicated
			return 0;
		}
		else if ($c1_pos > $c2_pos) {
			// first term appears first
			return 1;
		} else if ($c1_pos < $c2_pos) {
			// second term appears first
			return -1;
		}
		
	}
	
/*
	 * Builds a CSS string corresponding to the values of a typography setting
	 *
	 * @param	$prefix		The prefix for the settings. We'll append font_name,
	 *						font_size, etc to this prefix to get the actual keys
	 *
	 * @returns	string		The completed CSS string, with the values inlined
	 */
	function build_typography_css($prefix)
	{
		$css_rule_template = ' %s: %s;';
		$output = '';
		
		/* 
		 * Font Family
		 */
		 
		$option_val = get_option($prefix . 'font_family', '');
		if ( !empty($option_val) ) {
			// strip off 'google:' prefix if needed
			$option_val = str_replace('google:', '', $option_val);

		
			// wrap font family name in quotes
			$option_val = '\'' . $option_val . '\'';
			$output .= sprintf($css_rule_template, 'font-family', $option_val);
		}
		
		/* 
		 * Font Size
		 */
		$option_val = get_option($prefix . 'font_size', '');
		if ( !empty($option_val) ) {
			// append 'px' if needed
			if ( is_numeric($option_val) ) {
				$option_val .= 'px';
			}
			$output .= sprintf($css_rule_template, 'font-size', $option_val);
		}		
		
		/* 
		 * Font Color
		 */
		$option_val = get_option($prefix . 'font_color', '');
		if ( !empty($option_val) ) {
			$output .= sprintf($css_rule_template, 'color', $option_val);
		}

		/* 
		 * Font Style - add font-style and font-weight rules
		 * NOTE: in this special case, we are adding 2 rules!
		 */
		$option_val = get_option($prefix . 'font_style', '');

		// Convert the value to 2 CSS rules, font-style and font-weight
		// NOTE: we lowercase the value before comparison, for simplification
		switch ( strtolower($option_val) )
		{
			case 'regular':
				// not bold not italic
				$output .= sprintf($css_rule_template, 'font-style', 'normal');
				$output .= sprintf($css_rule_template, 'font-weight', 'normal');
			break;
		
			case 'bold':
				// bold, but not italic
				$output .= sprintf($css_rule_template, 'font-style', 'normal');
				$output .= sprintf($css_rule_template, 'font-weight', 'bold');
			break;

			case 'italic':
				// italic, but not bold
				$output .= sprintf($css_rule_template, 'font-style', 'italic');
				$output .= sprintf($css_rule_template, 'font-weight', 'normal');
			break;
		
			case 'bold italic':
				// bold and italic
				$output .= sprintf($css_rule_template, 'font-style', 'italic');
				$output .= sprintf($css_rule_template, 'font-weight', 'bold');
			break;
			
			default:
				// empty string or other invalid value, ignore and move on
			break;			
		}			

		// return the completed CSS string
		return trim($output);		
	}
	
	// Enqueue any needed Google Web Fonts
	function enqueue_webfonts()
	{
		$font_list = $this->list_required_google_fonts();
		$font_list_encoded = array_map( 'urlencode', $this->list_required_google_fonts() );
		$font_str = implode('|', $font_list_encoded);
		
		//don't register this unless a font is set to register
		if (strlen($font_str)>2) {
			$protocol = is_ssl() ? 'https:' : 'http:';
			$font_url = $protocol . '//fonts.googleapis.com/css?family=' . $font_str;
			wp_register_style( 'easy_faqs_webfonts', $font_url);
			wp_enqueue_style( 'easy_faqs_webfonts' );
		}
	}

	function list_required_google_fonts()
	{
		// check each typography setting for google fonts, and build a list
		$option_keys = array(
			'easy_faqs_question_font_family',
			'easy_faqs_answer_font_family',
			'easy_faqs_read_more_link_font_family',
		);
		$fonts = array();
		foreach ($option_keys as $option_key) {
			$option_value = get_option($option_key);
			if (strpos($option_value, 'google:') !== FALSE) {
				$option_value = str_replace('google:', '', $option_value);
				
				//only add the font to the array if it was in fact a google font
				$fonts[$option_value] = $option_value;				
			}
		}
		return $fonts;
	}
	
	//register any widgets here
	function easy_faqs_register_widgets() {
		include('include/widgets/single_faq_widget.php');
		include('include/widgets/search_faqs_widget.php');
		include('include/widgets/list_faqs_widget.php');
		include('include/widgets/faqs_by_category_widget.php');

		register_widget( 'singleFAQWidget' );
		register_widget( 'searchFAQsWidget' );
		register_widget( 'listFAQsWidget' );
		register_widget( 'FAQsByCategoryWidget' );
		
		do_action('easy_faqs_register_widgets');
	}
	
	/* Looks for a special POST value, and if its found, outputs a CSV of FAQs */
	function process_export()
	{
		// look for an Export command first
		if (isset($_POST['_gp_do_export']) && $_POST['_gp_do_export'] == '_gp_do_export') {
			$exporter = new FAQsPlugin_Exporter();
			$exporter->process_export();
			exit();
		}
	}
	
	function get_str($key = '', $default_value = '')
	{
		if ( !empty($this->strings) && !empty($this->strings[$key]) ) {
			return $this->strings[$key];
		} else {
			return $default_value;
		}
	}
	
	function add_gutenburg_block_category ( $categories, $post ) 
	{
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'easy-faqs',
					'title' => 'Easy FAQs',
				),
			)
		);
	}

	function provide_config_data_to_admin()
	{
		// Localize the script with new data
		$translation_array = array(
			'themes' => EasyFAQs_Config::all_themes(),
			'is_pro' => $this->is_pro,
			'theme_group_labels' => array(
				'standard_themes' => __('Free Themes', 'easy-faqs'),
				'pro_themes' => __('Pro Themes', 'easy-faqs'),
			),
		);
		wp_localize_script( 'single-faq-block-editor', 'easy_faqs_admin_single_faq', $translation_array );
		
		// Localize the script with new data
		$translation_array = array(
			'themes' => EasyFAQs_Config::all_themes(),
			'is_pro' => $this->is_pro,
			'theme_group_labels' => array(
				'standard_themes' => __('Free Themes', 'easy-faqs'),
				'pro_themes' => __('Pro Themes', 'easy-faqs'),
			),
		);
		wp_localize_script( 'list-faqs-block-editor', 'easy_faqs_admin_list_faqs', $translation_array );
	}	
	
	function get_user_feedback_html($postid)
	{
		$feedback_message = get_option('easy_faqs_visitor_feedback_message', '');
		$feedback_message = !empty($feedback_message) ? $feedback_message : __('Did this answer your question?', 'easy-faqs');

		$feedback_more_feeback_message = get_option('easy_faqs_visitor_feedback_more_feedback_message', '');
		$feedback_more_feeback_message = !empty($feedback_more_feeback_message) ? $feedback_more_feeback_message : __('Additional Comments', 'easy-faqs');

		$feedback_thank_you_message = get_option('easy_faqs_visitor_feedback_thank_you_message', '' );
		$feedback_thank_you_message = !empty($feedback_thank_you_message) ? $feedback_thank_you_message : __('Thank you!', 'easy-faqs');

		$feedback_yes_button_label = get_option('easy_faqs_visitor_feedback_yes_button_label', '' );
		$feedback_yes_button_label = !empty($feedback_yes_button_label) ? $feedback_yes_button_label : __('Yes', 'easy-faqs');

		$feedback_no_button_label = get_option('easy_faqs_visitor_feedback_no_button_label', '' );
		$feedback_no_button_label = !empty($feedback_no_button_label) ? $feedback_no_button_label : __('No', 'easy-faqs');

		$feedback_submit_button_label = get_option('easy_faqs_visitor_feedback_submit_button_label', '' );
		$feedback_submit_button_label = !empty($feedback_submit_button_label) ? $feedback_submit_button_label : __('Send Feedback', 'easy-faqs');
		
		// add "Did this answer your question?" HTML
		$vote_nonce = wp_create_nonce( 'easy_faqs_inline_feedback_' . $postid );
		$yes_link = sprintf( '<button class="easy_faqs_vote_link easy_faqs_vote_link_yes" href="#" data-vote="yes" data-post-id="%d" data-nonce="%s">%s</button>',
							 $postid,
							 $vote_nonce,
							 $feedback_yes_button_label );
							
		$no_link  = sprintf( '<button class="easy_faqs_vote_link easy_faqs_vote_link_no" href="#" data-vote="no" data-post-id="%d" data-nonce="%s">%s</button>',
							 $postid,
							 $vote_nonce,
							 $feedback_no_button_label );
							
		$submit_btn = sprintf( '<button type="button" class="easy_faqs_vote_link easy_faqs_feedback_submit_button" data-vote="no" data-post-id="%d" data-nonce="%s">%s</button>',
							   $postid,
							   $vote_nonce,
							   $feedback_submit_button_label );
							
		$inline_form  = sprintf( '<div class="easy_faqs_vote_text" style="display:none"><p>%s</p><textarea autocomplete="false" class="easy_faqs_vote_text_input"></textarea><br>%s</div>', 
								 $feedback_more_feeback_message,
								 $submit_btn );
								
								
		$feedback_html = sprintf( '<div class="easy-faqs-inline-feedback"><p class="easy_faqs_feedback_message">%s</p><div class="easy_faqs_voting">%s</div>%s</div>',
								  $feedback_message,
								  $yes_link . ' &nbsp; ' . $no_link,
								  $inline_form );
		return $feedback_html;
	}
	
	function add_meta_boxes()
	{
		add_meta_box( 'faqs_feedback', 'Visitor Feedback', array($this, 'display_feedback_meta_box'), 'faq', 'normal', 'default' );
	}
	
	function display_feedback_meta_box()
	{
		global $post;		
		$score = get_post_meta($post->ID, 'feedback_score', true);
		$yes_votes = get_post_meta($post->ID, 'feedback_yes_votes', true);
		$no_votes = get_post_meta($post->ID, 'feedback_no_votes', true);
		$total_votes = get_post_meta($post->ID, 'feedback_total_votes', true);
		$comments = get_post_meta($post->ID, 'feedback_comments', true);
		$comments = !empty($comments)
					? maybe_unserialize($comments)
					: '';
		echo '<div class="easy_faqs_user_feedback_meta_box">';
		if ($total_votes > 0) {			
			printf( '<h2 class="easy_faqs_user_feedback_heading">%s</h2>', __('Feedback Score', 'easy-faqs') );				
			echo '<table class="easy_faqs_user_feedback_table" cellpadding="0" cellspacing="0">';
			printf("<tr><td>Score:</td><td>%d%%</td></tr>", $score);
			printf("<tr><td>Yes Votes:</td><td>%d</td></tr>", $yes_votes);
			printf("<tr><td>No Votes:</td><td>%d</td></tr>", $no_votes);
			printf("<tr><td>Total:</td><td>%d</td></tr>", $total_votes);
			echo '</table>';
			
			if ( !empty($comments) ) {
				printf( '<h2 class="easy_faqs_user_feedback_heading">%s</h2>', __('Visitor Comments', 'easy-faqs') );
				echo '<table class="easy_faqs_user_feedback_table easy_faqs_user_comments_table" cellpadding="0" cellspacing="0">';				
				foreach($comments as $comment) {
					$delete_link = sprintf( '<a href="#" class="easy_faqs_feedback_delete_user_comment" data-post-id="%d" data-user-hash="%s">%s</a>',
										    $post->ID,
										    md5($comment['ip']),
										    __('Delete Comment') );
										   
					printf( '<tr><td class="easy_faqs_user_feedback_ts" width="18%%">%s</td><td width="70%%" class="easy_faqs_user_feedback_comment"><p><em>%s</em></p></td><td>%s</td></tr>',
						    date('F j, Y, g:i a', $comment['ts']),
						    htmlentities($comment['text']),
						    $delete_link );
				}
				echo '</table>';
			}
		}
		else{
			echo '<em>No feedback yet.</em>';
		}
		echo '</div>'; // end .easy_faqs_user_feedback_meta_box
	}
	
	function ajax_delete_user_comment()
	{
		// gather required fields
		$post_id = intval($_POST['post_id']);
		$user_hash = !empty($_POST['user_hash'])
					 ? sanitize_text_field($_POST['user_hash'])
					 : '';
				
		if ( empty($post_id) || empty($user_hash) ) {
			wp_die('x');
		}
		
		// see if this user has voted before
		$user_vote_hash = 'vote_' . $user_hash;
		$meta_val = get_post_meta($post_id, $user_vote_hash, true);
		if ( !empty($meta_val) ) {
			// found vote / comment. delete it and recalc
			delete_post_meta($post_id, $user_vote_hash);
			$this->recalc_feedback_score($post_id);
			wp_die('deleted');
		}
		wp_die('ty');
	}
	
	function ajax_record_vote()
	{
		// gather required fields
		$post_id = intval($_POST['post_id']);
		$nonce = !empty($_POST['nonce'])
				 ? $_POST['nonce']
				 : '';
		$vote = !empty($_POST['vote'])
				? $_POST['vote']
				: '';
		$text = !empty($_POST['text']) && !empty($vote) && ('no' == $vote)
				? sanitize_text_field($_POST['text'])
				: '';
				
		if ( empty($post_id) || empty($post_id) || empty($vote) ) {
			wp_die('x');
		}

		// verify nonce
		$correct_nonce = wp_verify_nonce( $nonce, 'easy_faqs_inline_feedback_' . $post_id);
		if ( !$correct_nonce ) {
			wp_die('n');
		}
		
		// see if this user has voted before
		$user_vote_hash = 'vote_' . md5($_SERVER['REMOTE_ADDR']);
		$meta_val = get_post_meta($post_id, $user_vote_hash, true);
		if ( !empty($meta_val) ) {
			// user has already voted, so disregard
			wp_die('av');
		}
		else {
			// record the vote
			$this->record_user_vote($post_id, $vote, $text);
		}

		wp_die('ty');
	}
	
	
	function record_user_vote($post_id, $vote, $text = '')
	{
		// ensure $vote is a valid value ('yes' or 'no')
		$vote = strtolower($vote);
		if ( !in_array($vote, array('yes', 'no') ) ) {
			return false;
		}
		
		// save the vote
		$user_vote_hash = 'vote_' . md5($_SERVER['REMOTE_ADDR']);
		$meta_value = serialize( array(
			'vote' => $vote,
			'text' => $text,
			'ts'   => date('U'),
			'ip'   => $_SERVER['REMOTE_ADDR']
		) );
		update_post_meta($post_id, $user_vote_hash, $meta_value);
		
		// recalc totals
		$this->recalc_feedback_score($post_id);
	}
	
	function recalc_feedback_score($post_id)
	{
		$all_meta = get_post_meta($post_id);
		$total_votes = 0;
		$total_yes = 0;
		$comments = array();
		
		foreach($all_meta as $meta_key => $meta_value) {
			if ( strpos($meta_key, 'vote_') !== 0 ) {
				continue;
			}
			
			$val = unserialize($meta_value[0]);
			$val = maybe_unserialize($val);

			if ('yes' == $val['vote']) {
				$total_yes++;
			}
			$total_votes++;
			
			if ( !empty($val['text']) ) {
				$comments[] = array(
					'text' => $val['text'],
					'ts' => $val['ts'],
					'ip' => $val['ip'],
				);
			}
		}
		
		if ( $total_votes > 0 ) {
			$avg_yes = round( ($total_yes / $total_votes) * 100 );
			update_post_meta($post_id, 'feedback_score', $avg_yes);
			update_post_meta($post_id, 'feedback_total_votes', $total_votes);
			update_post_meta($post_id, 'feedback_yes_votes', $total_yes);
			update_post_meta($post_id, 'feedback_no_votes', ($total_votes - $total_yes));
			update_post_meta($post_id, 'feedback_comments', serialize($comments));
		} else {
			update_post_meta($post_id, 'feedback_score', 0);
			update_post_meta($post_id, 'feedback_total_votes', 0);
			update_post_meta($post_id, 'feedback_yes_votes', 0);
			update_post_meta($post_id, 'feedback_no_votes', 0);
		}
	}
	
	
}//end easyFAQs

if ( empty($easy_faqs) ) {
	$easy_faqs = new easyFAQs();
	do_action('easy_faqs_bootstrap');
}
