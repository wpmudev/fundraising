<?php
/*
Plugin Name: Fundraising
Plugin URI: http://premium.wpmudev.org/project/fundraising/
Description: Create a fundraising page for any purpose or project.
Version: 2.1.1
Text Domain: wdf
Author: Cole (Incsub)
Author URI: http://premium.wpmudev.org/
WDP ID: 259

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );
	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
/* --------------------------------------------------------------------- */

define ('WDF_PLUGIN_SELF_DIRNAME', basename(dirname(__FILE__)), true);

//Setup proper paths/URLs and load text domains
if (is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'mu-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WPMU_PLUGIN_DIR, true);
	define ('WDF_PLUGIN_URL', WPMU_PLUGIN_URL, true);
	$textdomain_handler = 'load_muplugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'subfolder-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	define ('WDF_PLUGIN_URL', WP_PLUGIN_URL . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR, true);
	define ('WDF_PLUGIN_URL', WP_PLUGIN_URL, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else {
	// No textdomain is loaded because we can't determine the plugin location.
	// No point in trying to add textdomain to string and/or localizing it.
	wp_die(__('There was an issue determining where the Fundraising plugin is installed. Please reinstall.'));
}
$textdomain_handler('wdf', false, WDF_PLUGIN_SELF_DIRNAME . '/languages/');

class WDF {
	function WDF() {
		$this->_vars();
		$this->_construct();
	}
	function _vars() {
		$this->version = '2.1.1';
		$this->defaults = array(
			'currency' => 'USD',
			'dir_slug' => __('fundraisers','wdf'),
			'checkout_slug' => __('pledge','wdf'),
			'confirm_slug' => __('thank-you','wdf'),
			'activity_slug' => __('activity','wdf'),
			'first_time' => 1,
			'default_style' => 'wdf-basic',
			'panel_in_sidebar' => 'no',
			'payment_types' => array('simple'),
			'curr_symbol_position' => 1,
			'curr_decimal' => 1,
			'default_email' => 'Thank you for your pledge. Your donation of %DONATIONTOTAL% has been recieved and is greatly appreciated. Thanks for your support.',
			'current_version' => $this->version,
			'checkout_type' => '1',
			'funder_labels' => array(
				'menu_name' => __('Fundraising','wdf'),
				'singular_name' => __('Fundraiser','wdf'),
				'plural_name' => __('Fundraisers','wdf'),
				'singular_level' => __('Reward','wdf'),
				'plural_level' => __('Rewards','wdf')
			),
			'donation_labels' => array(
				'backer_single' => __('Backer','wdf'),
				'backer_plural' => __('Backers','wdf'),
				'singular_name' => __('Pledge','wdf'),
				'plural_name' => __('Pledges','wdf'),
				'action_name' => __('Back This Project','wdf')
			)
		);
		
		// Setup Additional Data Structure
		require_once(WDF_PLUGIN_BASE_DIR . '/lib/wdf_data.php');
	}
	function _construct() {
		
		if($_POST['wdf_reset']) {
			$wdf_posts = get_posts(array(
				'post_type' => array('funder','donation'),
				'numberposts' => -1
			));
			if($wdf_posts) {
				foreach($wdf_posts as $wdf_post) {
					wp_delete_post( $wdf_post->ID, true );
				}
			}
			delete_option('wdf_settings');
		}
		
		$settings = get_option('wdf_settings');
		if(!is_array($settings) || !$settings || empty($settings) ) {
			update_option('wdf_settings',$this->defaults);
			$settings = $this->defaults;
		}
		if(!isset($settings['current_version'])) {
			$this->upgrade_settings();
		}
		
		if(version_compare($this->version, $settings['current_version']) == 1) {
			$settings['current_version'] = $this->version;
			$settings = array_merge($this->defaults, $settings);
			update_option('wdf_settings',$settings);
		}
		
		//load APIs and plugins
			add_action( 'plugins_loaded', array(&$this, 'load_plugins') );

		// Initialize our post types and rewrite structures
			add_action( 'init', array(&$this,'_init'),1);
			add_action( 'init', array(&$this, 'flush_rewrite'), 999 );
			add_filter( 'rewrite_rules_array', array(&$this, 'add_rewrite_rules') );
			add_filter( 'query_vars', array(&$this, 'add_queryvars') );
		
		// Include Widgets
			add_action( 'widgets_init', array(&$this,'register_widgets') );
		
		// Load styles and scripts to be used across is_admin and !is_admin
			wp_register_style( 'jquery-ui-base', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css', null, '1.8.16', 'screen' );
		
		if(is_admin()) {
			// Add Admin Only Actions
			
			// Load tutorials for first time installations
			if($settings['first_time'] == '1')
				add_action( 'admin_init', array(&$this,'tutorial') );
			
			add_action( 'add_meta_boxes_funder', array(&$this,'add_meta_boxes') );
			add_action( 'add_meta_boxes_donation', array(&$this,'add_meta_boxes') );
			add_action( 'admin_menu', array(&$this,'admin_menu') );
			add_action( 'admin_enqueue_scripts', array(&$this,'admin_enqueue_scripts') );
			add_action( 'admin_enqueue_styles', array(&$this,'admin_enqueue_styles') );
			add_action( 'manage_funder_posts_custom_column', array(&$this,'column_display') );
			add_action( 'manage_donation_posts_custom_column', array(&$this,'column_display') );
			add_action( 'media_buttons', array(&$this,'media_buttons'), 30 );
			add_action( 'media_upload_fundraising', array(&$this,'media_fundraising'));
			add_action( 'media_upload_donate_button', array(&$this,'media_donate_button'));
			add_action( 'media_upload_progress_bar', array(&$this,'media_progress_bar'));
			add_action( 'wp_insert_post', array(&$this,'wp_insert_post') );
			
			// Add Admin Only Filters		
			add_filter( 'manage_edit-funder_columns', array(&$this,'edit_columns') );
			add_filter( 'manage_edit-donation_columns', array(&$this,'edit_columns') );
			add_filter( 'media_upload_tabs', array(&$this,'media_upload_tabs') );
						
			//Register Styles and Scripts For The Admin Area
			wp_register_script( 'wdf-post', WDF_PLUGIN_URL . '/js/wdf-post.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-edit', WDF_PLUGIN_URL . '/js/wdf-edit.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-media', WDF_PLUGIN_URL . '/js/wdf-media.js', array('jquery'), $this->version, true );
			wp_register_script( 'wdf-widget', WDF_PLUGIN_URL . '/js/wdf-widget.js', array('jquery'), $this->version, true );
			wp_register_style( 'wdf-admin', WDF_PLUGIN_URL . '/css/wdf-admin.css', null, $this->version, 'all' );
			
		} else {
			
			//Not the admin area so lets load up our front-end actions, scripts and filters
			wp_register_script( 'wdf-base', WDF_PLUGIN_URL . '/js/wdf-base.js', array('jquery'), $this->version, false );
			
			// Very low priority number is needed here to make sure it fires before themes can output headers
			add_action( 'wp', array(&$this, 'handle_payment'), 1 );
			add_action( 'template_redirect', array(&$this, 'template_redirect'), 20 );
			
			if($settings['inject_menu'] == 'yes') {
				add_filter( 'wp_list_pages', array(&$this, 'filter_nav_menu'), 10, 2 );
			}
		}
	}
	function register_widgets() {
		
		if(!class_exists('WDF_Simple_Donation')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.simple_donation.php');
			register_widget('WDF_Simple_Donation');
		}
		if(!class_exists('WDF_Recent_Fundraisers')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.recent_fundraisers.php');
			register_widget('WDF_Recent_Fundraisers');
		}
		if(!class_exists('WDF_Fundraisers_List')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.fundraisers_list.php');
			register_widget('WDF_Fundraisers_List');
		}
		if(!class_exists('WDF_Fundraiser_Panel')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.fundraiser_panel.php');
			register_widget('WDF_Fundraiser_Panel');
		}
	}
	function tutorial() {
		include(WDF_PLUGIN_BASE_DIR .'/lib/wdf_tutorials.php');
	}
	function create_funder_labels() {
		$settings = get_option('wdf_settings');
		
		$menu = apply_filters( 'wdf_funder_menu_name', esc_attr($settings['funder_labels']['menu_name'], $this->defaults['funder_labels']['menu_name']) );
		$single = apply_filters( 'wdf_funder_singular_name', esc_attr($settings['funder_labels']['singular_name'], $this->defaults['funder_labels']['singular_name']) );
		$plural = apply_filters( 'wdf_funder_plural_name', esc_attr($settings['funder_labels']['plural_name'], $this->defaults['funder_labels']['plural_name']) );
				
		$funder_labels = array(
			'name' => $plural,
			'singular_name' => $single,
			'add_new' => __('New '.$single,'wdf'),
			'add_new_item' => __('Add New '.$single,'wdf'),
			'edit_item' => __('Edit '.$single,'wdf'),
			'new_item' => __('New '.$single,'wdf'),
			'all_items' => __('All '.$plural,'wdf'),
			'view_item' => __('View '.$single,'wdf'),
			'search_items' => __('Search '.$plural,'wdf'),
			'not_found' =>  __('No '.$plural.' found','wdf'),
			'not_found_in_trash' => __('No '.$plural.' found in Trash','wdf'), 
			'parent_item_colon' => '',
			'menu_name' => $menu
		);
		
		return apply_filters('wdf_create_funder_labels', $funder_labels);
	}
	function create_donation_labels() {
		$settings = get_option('wdf_settings');
		
		$single = apply_filters( 'wdf_donation_singular_name', esc_attr($settings['donation_labels']['singular_name'], $this->defaults['donation_labels']['singular_name']) );
		$plural = apply_filters( 'wdf_donation_plural_name', esc_attr($settings['donation_labels']['plural_name'], $this->defaults['donation_labels']['plural_name']) );
		$menu = apply_filters( 'wdf_donation_menu_name', $plural );
		
		$donation_labels = array(
			'name' => $plural,
			'singular_name' => $single,
			'add_new' => __('New '.$single,'wdf'),
			'add_new_item' => __('Add New '.$single,'wdf'),
			'edit_item' => __('Edit '.$single,'wdf'),
			'new_item' => __('New '.$single,'wdf'),
			'all_items' => __('All '.$plural,'wdf'),
			'view_item' => __('View '.$single,'wdf'),
			'search_items' => __('Search '.$plural,'wdf'),
			'not_found' =>  __('No '.$plural.' found','wdf'),
			'not_found_in_trash' => __('No '.$plural.' found in Trash','wdf'), 
			'parent_item_colon' => '',
			'menu_name' => $menu
		);
		
		return apply_filters('wdf_create_donation_labels', $donation_labels);
	}
	function _init() {
		$settings = get_option('wdf_settings');
		
		// Create A New Labels Array Based on the settings input
		$funder_labels = $this->create_funder_labels();
		$donation_labels = $this->create_donation_labels();
		
		//Funder Custom Post Type Arguments
		$funder_args = array(
			'labels' => $funder_labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => true,
			'rewrite'            => array(
				'slug' => $settings['dir_slug'],
				'with_front' => true,
				'feeds'      => true
			),
			'capability_type'    => 'post',
			'has_archive'        => true, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => WDF_PLUGIN_URL . '/img/sm_ico.png',
			'supports'           => array('title','thumbnail','editor','excerpt','author','comments')
		);
		//Donation Custom Post Type arguments
		$donation_args = array(
			'labels' => $donation_labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true, 
			'show_in_menu'       => false, 
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('')
		);
		
		//Register Post Types
		register_post_type( 'funder', $funder_args );
		register_post_type( 'donation', $donation_args );
		
		// Construct Arguments for our pledge status
		$approved_args = array(
			'label'       => __('Approved', 'wdf'),
			'label_count' =>  _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>' ),
			'post_type'   => 'donation',
			'private'      => true,
			'exclude_from_search' => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true,
			'show_in_admin_all' => true
		);
		$complete_args = array(
			'label'       => __('Complete', 'wdf'),
			'label_count' =>  _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
			'post_type'   => 'donation',
			'private'      => true,
			'exclude_from_search' => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true,
			'show_in_admin_all' => true
		);
		$canceled_args = array(
			'label'       => __('Cancled', 'wdf'),
			'label_count' =>  _n_noop( 'Cancled <span class="count">(%s)</span>', 'Cancled <span class="count">(%s)</span>' ),
			'post_type'   => 'donation',
			'private'      => true,
			'exclude_from_search' => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true,
			'show_in_admin_all' => true
		);
		$refunded_args = array(
			'label'       => __('Refunded', 'wdf'),
			'label_count' =>  _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>' ),
			'post_type'   => 'donation',
			'private'      => true,
			'exclude_from_search' => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all' => true,
			'show_in_admin_all_list' => true
		);
		// Register the needed post statuses
		register_post_status('wdf_canceled', $canceled_args);
		register_post_status('wdf_refunded', $refunded_args);
		register_post_status('wdf_approved', $approved_args);
		register_post_status('wdf_complete', $complete_args);
		
		$this->register_styles();
		
		$pledges = wp_count_posts('donation');
		if($pledges->publish > 0 || $pledges->draft > 0 ) {
			// The is the best way to determine the 1.0 to 2.0 jump remove after 2.1
			$this->upgrade_fundraisers();
		}
	}
	function upgrade_fundraisers() {
		// A few things to cycle through if we think we are upgrading from an earlier version.
		$args = array(
			'numberposts' => -1,
			'post_type' => 'donation',
			'post_status' => array('publish', 'draft')
		);
		if( $query = get_posts($args) ) {
			foreach($query as $post) {
				if($post->post_status == 'publish') {
					$post->post_status == 'wdf_complete';
					wp_insert_post($post);
				} else {
					$post->post_status == 'wdf_canceled';
					wp_insert_post($post);
				}
			}
		}	
	}
	function upgrade_settings() {
		// Re-Merge our defaults so we don't have any unexpected errors on an upgrade.
		$settings = get_option('wdf_settings');
		$settings = array_merge($this->defaults, $settings);
		update_option('wdf_settings', $settings);
	}
	function flush_rewrite() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	function add_rewrite_rules($rules){
		$settings = get_option('wdf_settings');
	
		$new_rules = array();
		// Checkout Page
		$new_rules[$settings['dir_slug'] . '/([^/]+)/' . $settings['checkout_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_checkout=1';
		
		// Thank You / Confirmation Page
		$new_rules[$settings['dir_slug'] . '/([^/]+)/' . $settings['confirm_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_confirm=1';
		
		// Fundraiser Activity Page - Coming Soon
		//$new_rules[$settings['dir_slug'] . '/([^/]+)/' . $settings['activity_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_activity=1';
		
		$new_rules = apply_filters('wdf_rewrite_rules',$new_rules);
		
		$rules = array_merge($new_rules, $rules);
		
		return $rules;
	}
	function add_queryvars($vars) {
		// This function add the checkout queryvars to the list that WordPress is looking for.
		if(!in_array('funder_checkout', $vars))
			$vars[] = 'funder_checkout';
		if(!in_array('funder_confirm', $vars))
			$vars[] = 'funder_confirm';
		if(!in_array('funder_activity', $vars))
			$vars[] = 'funder_activity';
		
		$vars = apply_filters('wdf_query_vars', $vars);
		
		return $vars;
	}
	
	function template_redirect() {
		global $wp_query;
		
		if ($wp_query->query_vars['post_type'] == 'funder') {
			$funder_name = get_query_var('funder');
			$funder_id = (int) $wp_query->get_queried_object_id();
			$templates = array();
			$this->front_scripts($funder_id);
					
			if ($wp_query->query_vars['funder_checkout'] == 1) {
				$this->is_funder_checkout = true;
				
				if ( $funder_name )
					$templates[] = "wdf_checkout-$funder_name.php";
				if ( $funder_id )
					$templates[] = "wdf_checkout-$funder_id.php";
				$templates[] = "wdf_checkout.php";
				add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );
				}
			} elseif ($wp_query->query_vars['funder_confirm'] == 1) {
				$this->is_funder_confirm = true;
				
				if ( $funder_name )
					$templates[] = "wdf_confirm-$funder_name.php";
				if ( $funder_id )
					$templates[] = "wdf_confirm-$funder_id.php";
				$templates[] = "wdf_confirm.php";
				add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );	
				}
			}/* elseif ($wp_query->query_vars['funder_activity'] == 1) {
				$this->is_funder_activity = true;
				if ( $funder_name )
					$templates[] = "wdf_activity-$funder_name.php";
				if ( $funder_id )
					$templates[] = "wdf_activity-$funder_id.php";
				$templates[] = "wdf_activity.php";
				add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );
				}
			}*/ elseif ($wp_query->is_single()) {
				$this->is_funder_single = true;
				if ( $funder_name )
					$templates[] = "wdf_funder-$funder_name.php";
				if ( $funder_id )
					$templates[] = "wdf_funder-$funder_id.php";
				$templates[] = "wdf_funder.php";
				
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );
				} else {
					add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				}
			}
		}
	}
	function custom_funder_template() {
		return apply_filters('wdf_funder_template',$this->funder_template);
	}
	function funder_content($content) {
		if ( !in_the_loop() )
			return $content;

		$settings = get_option('wdf_settings');
		global $post;
		
		if($this->is_funder_single && !is_active_widget( false, false, 'wdf_fundraiser_panel', true )) {
			$position = get_post_meta($post->ID,'wdf_panel_pos',true);
			if($position == 'top')
				$content = wdf_fundraiser_page(false, $post->ID) . $content;
			else
				$content .= wdf_fundraiser_page(false, $post->ID);
		}
		
		if($this->is_funder_checkout) {
			$content = wdf_show_checkout( false, $post->ID, $_POST['wdf_step'] );
		}
		
		if($this->is_funder_confirm) {
			$content = wdf_confirmation_page(false, $post->ID);
		}
		
		/*if($this->is_funder_activity)
			$content = wdf_activity_page(false,$post->ID);*/
		
		return $content;
	}
	
	function filter_nav_menu($list, $args = array()) {
    	$settings = get_option('wdf_settings');
		$list = $list . '<li class="page_item'. ((get_query_var('post_type') == 'funder') ? ' current_page_item' : '') . '"><a href="' . home_url($settings['dir_slug'].'/') . '" title="' . esc_attr($settings['funder_labels']['plural_name']) . '">' . esc_attr($settings['funder_labels']['plural_name']) . '</a></li>';
		return $list;
	}

	function load_plugins() {
		$settings = get_option('wdf_settings');
		
		//load gateway plugin API
		require_once( WDF_PLUGIN_BASE_DIR . '/lib/classes/class.gateway.php' );
		$this->load_gateway_plugins();
		
		// Load up our available styles
		$this->load_styles();
		
	}
	function load_gateway_plugins() {
		
		if(isset($_POST['wdf_settings']) && is_array($_POST['wdf_settings'])) {
			$this->save_settings($_POST['wdf_settings']);
		}
		
		//get gateway plugins dir
		$dir = WDF_PLUGIN_BASE_DIR . '/lib/gateways/';
		
		//search the dir for files
		$gateway_plugins = array();
		if ( !is_dir( $dir ) )
			return;
		if ( ! $dh = opendir( $dir ) )
			return;
		while ( ( $plugin = readdir( $dh ) ) !== false ) {
			if ( substr( $plugin, -4 ) == '.php' )
				$gateway_plugins[] = $dir . '/' . $plugin;
		}
		closedir( $dh );
		sort( $gateway_plugins );
		
		//include them suppressing errors
		foreach ($gateway_plugins as $file)
			include( $file );
		
		//allow plugins from an external location to register themselves
		do_action('wdf_load_gateway_plugins');
		
		//load chosen plugin classes
		global $wdf_gateway_plugins, $wdf_gateway_active_plugins;
		$settings = get_option('wdf_settings');
		
		foreach ((array)$wdf_gateway_plugins as $code => $plugin) {
			$class = $plugin[0];
			if( isset($settings['active_gateways']) ) {
				if ( class_exists($class) && !$plugin[3] && $settings['active_gateways'][$code] == '1'  )
					$wdf_gateway_active_plugins[$code] = new $class;
			}
			
		}
		
		// Action used for saving gateway settings
		do_action('wdf_gateway_plugins_loaded');
	
	}
	function load_styles() {
		$style_dir = WDF_PLUGIN_BASE_DIR.'/styles/';
		$styles = array();
		if( $files = scandir($style_dir) ) {
			 foreach ($files as $file) {
				 //TO DO Tokenize each CSS file to have a name generated automatically
				//$string = file_get_contents($style_dir.$style,null,null,null,100);
					switch($file) {
						case 'wdf-basic.css' :
							$styles['wdf-basic'] = __('Basic','wdf');
							break;
						case 'wdf-dark.css' :
							$styles['wdf-dark'] = __('Dark','wdf');
							break;
						case 'wdf-fresh.css' :
							$styles['wdf-fresh'] = __('Fresh','wdf');
							break;
						case 'wdf-minimal.css' :
							$styles['wdf-minimal'] = __('Minimal','wdf');
							break;
						case 'wdf-note.css' :
							$styles['wdf-note'] = __('Note','wdf');
							break;
						default :
							if(preg_match('/.css/',$file) ) {
								$name = apply_filters( 'wdf_custom_style_name', str_replace('.css','',$style) );
								$styles[$name] = $name;
							}
							break;
					}
			}
		}
		$styles['wdf-custom'] = __('None (Custom CSS)','wdf');
		$this->styles = $styles;
	}
	function register_styles() {
		if(is_array($this->styles) && !empty($this->styles)) {
			foreach($this->styles as $key => $label) {
				wp_register_style( 'wdf-style-'.$key, WDF_PLUGIN_URL . '/styles/'.$key.'.css', null, $this->version );
			}
		}
	}
	function load_style($style = false) {
		if($style != false)
			wp_enqueue_style('wdf-style-'.$style);
	}
	function front_scripts($id = NULL, $add_style = false) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script('wdf-base');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('jquery-ui-base');
		
		add_action('wp_head', array(&$this,'inject_custom_css'));
		// $add_style will always be used before a saved style.
		if($add_style != false) {
			wp_enqueue_style('wdf-style-'.$add_style);
		} else if(!empty($id) && $style = wdf_get_style($id))
			wp_enqueue_style('wdf-style-'.$style);

	}
	function inject_custom_css() {
		$settings = get_option('wdf_settings');
		if(isset($settings['custom_css']) && !empty($settings['custom_css'])) {
			$css = sprintf('<style type="text/css">%s</style>',$settings['custom_css']);
			echo $css;
		} else {
			return;
		}
	}
	function start_session() {
	//start the session for pledges
	if (session_id() == "")
		session_start();
	}
	function clear_session() {
		unset($_SESSION['wdf_pledge']);
		unset($_SESSION['wdf_gateway']);
		unset($_SESSION['wdf_step']);
		unset($_SESSION['wdf_type']);
		unset($_SESSION['wdf_pledge_id']);
		unset($_SESSION['wdf_sender_email']);
		unset($_SESSION['wdf_recurring']);
		
		// Action hook for plugins that need to clear session variables.
		do_action('wdf_clear_session');
	}
	function handle_payment() {
		$this->start_session();
			
		if( isset($_POST['funder_id']) && !empty($_POST['funder_id']) ) {
			$_SESSION['funder_id'] = $_POST['funder_id'];
			$_SESSION['wdf_type'] = $this->get_payment_type($_POST['funder_id']);
		}
		if( isset($_POST['wdf_pledge']) && !empty($_POST['wdf_pledge']) )	
			$_SESSION['wdf_pledge'] = $this->filter_price($_POST['wdf_pledge']);
		if( isset($_POST['wdf_gateway']) && !empty($_POST['wdf_gateway']) )	
			$_SESSION['wdf_gateway'] = $_POST['wdf_gateway'];
		if( isset($_POST['wdf_step']) && !empty($_POST['wdf_step']) )	
			$_SESSION['wdf_step'] = $_POST['wdf_step'];
		
		
		if( !isset($_POST['wdf_recurring']) || empty($_POST['wdf_recurring']) || $_POST['wdf_recurring'] == '0') {
			$_SESSION['wdf_recurring'] = false;
		} else if( isset($_POST['wdf_recurring']) && !empty($_POST['wdf_recurring']) ) {
			$_SESSION['wdf_recurring'] = $_POST['wdf_recurring'];
		}
		
		if( isset($_POST['wdf_bp_activity']) && $_POST['wdf_bp_activity'] == '1' )
			$_SESSION['wdf_bp_activity'] = true;
			
		$process_payment = false;
		global $wdf_gateway_active_plugins;
		
		$skip_gateway_form = $wdf_gateway_active_plugins[$_SESSION['wdf_gateway']]->skip_form;
		
		if( isset($_POST['wdf_send_donation']) && $skip_gateway_form === true)
			$process_payment = true;
			
		if( isset($_POST['wdf_payment_submit']) )
			$process_payment = true;
						
		if($process_payment) {
			if( !isset($_SESSION['wdf_type']) || empty($_SESSION['wdf_type']) )
				$this->create_error(__('Could not determine pledge type.','wdf'),'payment_submit');
				
			if( !isset($_SESSION['wdf_gateway']) || empty($_SESSION['wdf_gateway']) )
				$this->create_error(__('Please choose a payment method.','wdf'),'payment_submit');
			
			if( !isset($_SESSION['funder_id']) || empty($_SESSION['funder_id']) ) {
				$this->create_error(__('Fundraiser could not be determined.','wdf'),'payment_submit');
			}
			
			if($this->wdf_error !== true) {
				do_action('wdf_gateway_pre_process_'.$_SESSION['wdf_gateway']);
				do_action('wdf_gateway_process_'.$_SESSION['wdf_type'].'_'.$_SESSION['wdf_gateway']);
			}
		}
		if($this->is_funder_confirm){
			
			if( !isset($_SESSION['wdf_pledge_id']) || empty($_SESSION['wdf_pledge_id']) )
				$this->create_error(__('You have not made a pledge yet.','wdf'),'no_pledge');

			if(!$this->wdf_error) {
				do_action('wdf_gateway_confirm_'.$_SESSION['wdf_gateway']);
				
			}
		}
	}
	function process_complete_funder( $funder_id = false ) {
		if($funder_id == false || $funder_id == '')
			return false;
		
		if($pledges = $this->get_pledge_list($funder_id)) {	
			foreach($pledges as $pledge) {
				if($pledge->post_status == 'wdf_approved') {
					$transaction = $this->get_transaction($pledge->ID);
					do_action('wdf_execute_payment_'.$transaction['gateway'],$transaction['type'], $pledge, $transaction);
				}
			}
		}
		do_action('wdf_after_goal_complete', $pledges);
	}
	function filter_thank_you( $msg = '', $trans = false) {
		if($trans !== false) {
			$search = array('%DONATIONTOTAL%','%FIRSTNAME%','%LASTNAME%');
			$replace = array($this->format_currency('',$trans['gross']),$trans['first_name'],$trans['last_name']);
			$msg = str_replace($search, $replace, $msg);
		}
		
		return $msg;
	}
	function create_thank_you($funder_id = false, $trans = false) {
		
		if($send_email = get_post_meta($funder_id,'wdf_send_email',true) && $trans != false) {
						
			//remove any other filters
			remove_all_filters( 'wp_mail_from' );
			remove_all_filters( 'wp_mail_from_name' );
	
			//add our own filters
			//add_filter( 'wp_mail_from_name', create_function('', 'return get_bloginfo("name");') );
			//add_filter( 'wp_mail_from', create_function('', 'return get_option("admin_email")') );
			$msg = get_post_meta($funder_id,'wdf_email_msg', true);
			$search = array('%DONATIONTOTAL%','%FIRSTNAME%','%LASTNAME%');
			$replace = array($this->format_currency('',$trans['gross']),$trans['first_name'],$trans['last_name']);
			
			$subject = get_post_meta($funder_id,'wdf_email_subject',true);
			$msg = str_replace($search, $replace, $msg);
			$subject = str_replace($search, $replace, $subject);
			
			if($subject && $msg && $trans['payer_email']) {
				wp_mail($trans['payer_email'],$subject,$msg);
			}
		}
	}	
	function update_pledge( $post_title = false, $funder_id = false, $status = false, $transaction = false ) {
		if( !$post_title || !$funder_id || !$status || !$transaction ) {
			return false;
		}
		
		global $wpdb;
		//Check to see if we have created this donation yet
		$search = false;
		$search = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $post_title . "'" );
		$donation = array();
		if(!empty($search) &&  $search != false) {
			$donation['ID'] = $search;
		} else {
			//First time to see this pledge lets do some thank you operations
			$this->create_thank_you($funder_id,$transaction);
		}
		$donation['post_title'] = $post_title;
		$donation['post_name'] = $post_title;
		$donation['post_status'] = $status;
		$donation['post_parent'] = $funder_id;
		$donation['post_type'] = 'donation';
		$id = wp_insert_post($donation);
		foreach($transaction as $k => $v) {
			if(!is_array($v))
				$transaction[$k] = esc_attr($v);
			else
				$transaction[$k] = array_map('esc_attr', $v);
				
		}
		update_post_meta($id, 'wdf_transaction', $transaction);
		update_post_meta($id,'wdf_native', '1');
		
		// Check and see if we have now hit our goal.
		if( $this->has_goal($funder_id) ){	
			if( (int)$this->get_amount_raised($funder_id) >= (int)$this->get_goal_amount($funder_id) ) {
				$this->process_complete_funder( $funder_id );
			}
		}
		
		return $id;
	}
	
	function add_meta_boxes() {
		global $post, $wp_meta_boxes, $typenow;
		$settings = get_option('wdf_settings');
		if($typenow == 'funder') {
			
			$wdf_type = get_post_meta($post->ID,'wdf_type',true);
			$wdf_type = ($wdf_type == false ? '' : $wdf_type);
			$has_pledges = $this->get_pledge_list($post->ID);

			if( !in_array($wdf_type,$settings['payment_types']) || $has_pledges == false ) {
				
				if($post->post_status != 'publish' && $has_pledges == false)
					add_meta_box( 'wdf_type', esc_attr($settings['funder_labels']['menu_name']) . __(' Type','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
					
				if( $post->post_status == 'auto-draft' || $wdf_type == '' ) {
					// Search for the submit div and remove it
					foreach($wp_meta_boxes['funder'] as $context => $priorities) {
						foreach($priorities as $meta_boxes) {
							if( isset($meta_boxes['submitdiv']) ) {
								remove_meta_box( 'submitdiv', 'funder', $context );
							}
						}
					}
				}
			} 
			
			if($wdf_type != false && !empty($wdf_type)) { // We have a type so show the available options
				
				if( $has_pledges != false || $post->post_status == 'publish' )
					add_meta_box( 'wdf_progress', esc_attr($settings['funder_labels']['singular_name']) . __(' Progress','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
				
				add_meta_box( 'wdf_options', esc_attr($settings['funder_labels']['singular_name']) . __(' Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
				add_meta_box( 'wdf_goals', __('Set Your '.esc_attr($settings['funder_labels']['singular_name']).' Goals','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
					
				add_meta_box( 'wdf_messages', __('Thank You Message Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');	
				// Show pledge activity if funds have been raised
				if( $has_pledges != false )
					add_meta_box( 'wdf_activity', esc_attr($settings['donation_labels']['singular_name']) . __(' Activity','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			}
			
			
				
		} elseif($typenow == 'donation') {
			add_meta_box( 'wdf_pledge_info', esc_attr($settings['donation_labels']['singular_name']) . __(' Information','wdf'), array(&$this,'meta_box_display'), 'donation', 'normal', 'high');
			add_meta_box( 'wdf_pledge_status', esc_attr($settings['donation_labels']['singular_name']) . __(' Status','wdf'), array(&$this,'meta_box_display'), 'donation', 'side', 'high');
			
			// Search for the submit div and remove it
			foreach($wp_meta_boxes['donation'] as $context => $priorities) {
				foreach($priorities as $meta_boxes) {
					if( isset($meta_boxes['submitdiv']) ) {
						remove_meta_box( 'submitdiv', 'donation', $context );
					}
				}
			}
				
		}
	}
	function meta_box_display($post,$data) {
		include(WDF_PLUGIN_BASE_DIR . '/lib/form.meta_boxes.php');
	}
	function admin_menu() {
		$settings = get_option('wdf_settings');
		
		add_submenu_page( 'edit.php?post_type=funder', __('Getting Started','wdf'), __('Getting Started','wdf'), 'manage_options', 'wdf', array(&$this,'admin_display') );
		add_submenu_page( 'edit.php?post_type=funder', $settings['donation_labels']['plural_name'], $settings['donation_labels']['plural_name'], 'manage_options', 'wdf_donations', array(&$this,'admin_display') );		
		add_submenu_page( 'edit.php?post_type=funder', $settings['funder_labels']['menu_name'] . __(' Settings','wdf'), __('Settings','wdf'), 'manage_options', 'wdf_settings', array(&$this,'admin_display') );
		
		//Some quick fixes for the menu
		//TO-DO use array filters and in_array to pick out the correct menu item no matter the position
		global $submenu;
				
		$submenu['edit.php?post_type=funder'][5][0] = $settings['funder_labels']['plural_name'];
		$submenu['edit.php?post_type=funder'][10][0] = __('Add New','wdf');
		$submenu['edit.php?post_type=funder'][4] = $submenu['edit.php?post_type=funder'][11];
		unset($submenu['edit.php?post_type=funder'][11]);
		$submenu['edit.php?post_type=funder'][12][2] = 'edit.php?post_type=donation';
		ksort($submenu['edit.php?post_type=funder']);		
	}
	
	function admin_display(){
		$content = '';
		if(!current_user_can('manage_options'))
			wp_die(__('You are not allowed to view this page.','wdf'));
			
		switch($_GET['page']) {
			case 'wdf_settings' : 
				include(WDF_PLUGIN_BASE_DIR . '/lib/form.blog_settings.php');
				break;
			default : 
				include(WDF_PLUGIN_BASE_DIR . '/lib/form.blog_dashboard.php');
				break;
		}
	}
	function save_settings($new) {
		$die = false;
		
		if(isset($_POST['wdf_nonce'])) {
			$nonce = $_POST['wdf_nonce'];
		}
		if (!wp_verify_nonce($nonce,'_wdf_settings_nonce') ) {
			$this->create_error(__('Security Check Failed.  Whatchu doing??','wdf'), 'wdf_nonce');
			$die = true;
		}
		
		foreach($new as $k => $v) {
			if($k == 'slug') {
				$new[$k] = sanitize_title($v,__('donations','wdf'));
			} else if ( $k == 'paypal_email' ) {
				$new[$k] = is_email($v);
			} elseif ( $k == 'payment_types' || $k == 'active_gateways' || $k == 'funder_labels' || $k == 'donation_labels' ) {
				$new[$k] = array_map('esc_attr',$v);
			} else {
				$new[$k] = esc_attr($v);
			}
		}
		
		// If no die flags have been triggered then lets merge our settings array together and save.
		if(!$die) {
			$settings = get_option('wdf_settings');
			
			$settings = wp_parse_args($new,$settings);
			update_option('wdf_settings',$settings);
			$this->create_msg('Settings Saved', 'general');			
		}
	}
	
	function create_error($msg, $context) {
		$classes = 'error';
		$content = 'return $content."<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_filter('wdf_error_' . $context, create_function('$content', $content));
		$this->wdf_error = true;
	}
	
	function create_msg($msg, $context) {
		if(is_admin())
			$classes = 'updated below-h2';
		else
			$classes = 'wdf_msg';
		$content = 'return "<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_filter('wdf_msg_' . $context, create_function('', $content));
		$this->wdf_msg = true;
	}
	
	function wp_insert_post() {
		global $post;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
			
		if(!current_user_can('edit_post') || !isset($_POST['wdf']) || !is_array($_POST['wdf']))
			return;
			
		if(isset($_POST['wdf']['levels']) && count($_POST['wdf']['levels']) < 2 && $_POST['wdf']['levels'][0]['amount'] == '')
			$_POST['wdf']['levels'] = '';
		
		if ( 'funder' == $_POST['post_type'] && is_array($_POST['wdf'])) {
			foreach($_POST['wdf'] as $key => $value) {
					if($value != '') {
						if($key == 'goal') {
							$value = $this->filter_price($value);
						} else if($key == 'levels') {
							foreach($value as $level => $data) {
								$value[$level]['amount'] = $this->filter_price($data['amount']);
								$value[$level]['description'] = esc_textarea($data['description']);
							}
						} else if($key == 'recurring_cycle') {
							
						} else if($key == 'thanks_url') {
							$value = esc_attr($value);
						} else if($key == 'thanks_custom') {
							$value = (current_user_can('unfiltered_html') ? $value : wp_kses_post($value));
						} else if($key == 'thanks_post') {
							$value = absint($value);
							$is_page = get_page($value);
							$is_post = get_post($value);
							if($is_page == false && $is_post == false) {
								$this->create_error('You must supply a valid post or page ID','thanks_post');
								$value = '';
							}
						} else {
							$value = esc_attr($value);
						}
						update_post_meta($post->ID,'wdf_'.$key,$value);
					} else {
						delete_post_meta($post->ID,'wdf_'.$key);
					}
			}
		} elseif ( 'donation' == $_POST['post_type'] && isset($_POST['wdf'])) {
			if(isset($_POST['wdf']['transaction'])) {
				foreach($_POST['wdf']['transaction'] as $k => $v) {
					$_POST['wdf']['transaction'][$k] = esc_attr($v);
				}
				if(isset($_POST['wdf']['transaction']['name'])) {
					$name = explode(' ',$_POST['wdf']['transaction']['name'],2);
					$_POST['wdf']['transaction']['first_name'] = $name[0];
					$_POST['wdf']['transaction']['last_name'] = $name[1];
				}
				if(isset($_POST['wdf']['transaction']['gross']))
					$_POST['wdf']['transaction']['gross'] = $this->filter_price($_POST['wdf']['transaction']['gross']);
				update_post_meta($post->ID,'wdf_transaction',$_POST['wdf']['transaction']);
			}	
		}
	}
	
	function admin_enqueue_scripts($hook) {
		global $typenow, $pagenow;

		if($typenow == 'funder' || $pagenow == 'admin.php') {
			if($typenow == 'funder' || $_GET['page'] == 'wdf' || $_GET['page'] == 'wdf_settings')
				wp_enqueue_style('wdf-admin');
			if( $hook === 'post.php' || $hook === 'post-new.php') {
				wp_enqueue_style('jquery-ui-base');
				wp_enqueue_script('jquery-ui-progressbar');
				wp_enqueue_style('wdf-admin');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('wdf-post');
			} elseif( $hook == 'edit.php') {
				wp_enqueue_style('jquery-ui-base');
				wp_enqueue_script('jquery-ui-progressbar');
				wp_enqueue_script('wdf-edit');
			}
		} elseif( $typenow == 'donation' ) {
			wp_enqueue_style('wdf-admin');
			if($hook = 'edit.php') {
				wp_enqueue_script('wdf-edit');
			}
		} elseif( $typenow != 'funder' && $typenow != 'donation') {
			if($hook == 'post.php' || $hook == 'post-new.php') {
				//Media Button Includes For Other Post Types
				wp_enqueue_style('colorpicker');
				wp_enqueue_style('wdf-base');
				wp_enqueue_script('wdf-media');
			}
		} 
		if( $pagenow == 'widgets.php') {
			wp_enqueue_script('wdf-widget');
			
			if((int)$_GET['wdf_show_widgets'] == 1)
				wp_enqueue_style('wdf-admin');
		}
		
		if($hook = 'edit.php')
			wp_localize_script('wdf-edit', 'WDF', array( 'hook' => $hook, 'typenow' => $typenow) );
	}
	
	function edit_columns($columns) {
		global $typenow;
		$settings = get_option('wdf_settings');
		if($typenow == 'funder') {
			// Remove Author
			unset($columns['author']);
			
			// Move Comments
			$move_comments = $columns['comments'];
			unset($columns['comments']);
			
			//Move Title
			$move_title = $columns['title'];
			unset($columns['title']);
			
			$columns['funder_thumb'] = '';
			$columns['funder_pledges'] = esc_attr($settings['donation_labels']['plural_name']);
			$columns['funder_raised'] = __('Raised','wdf');
			$columns['title'] = $move_title;
			$columns['funder_type'] = __('Type','wdf');
			$columns['funder_time_left'] = __('Time Left', 'wdf');
			$columns['comments'] = $move_comments;
			
			unset($columns['date']);
		} elseif($typenow == 'donation') {
			$columns['pledge_amount'] = __('Amount', 'wdf');
			$columns['pledge_status'] = __('Status', 'wdf');
			$columns['pledge_recurring'] = __('Recurring', 'wdf');
			$columns['pledge_funder'] = esc_attr($settings['funder_labels']['singular_name']);
			$columns['pledge_from'] = esc_attr($settings['donation_labels']['backer_single']);
			$columns['pledge_method'] = __('Method', 'wdf');
			$columns['title'] = esc_attr($settings['donation_labels']['singular_name']) . __(' ID', 'wdf');
			$title_move = $columns['title'];
			unset($columns['title']);
			$move_date = $columns['date'];
			unset($columns['date']);
			$columns['title'] = $title_move;
			$columns['date'] = $move_date;
		}
		return $columns;
	}
	
	function column_display($name) {
		global $post;
		switch($name) {
			case 'funder_type' : 
				if($type = get_post_meta($post->ID,'wdf_type',true)) {
					if($type == 'simple')
						$type = __('Donations','wdf');
					else if($type == 'advanced')
						$type = __('Crowdfunding','wdf');
					else
						$type = apply_filters( 'wdf_column_funder_type_'.$type, $type );
				}
					echo $type;
				break;
			case 'funder_pledges' :
				if($donations = $this->get_pledge_list($post->ID))
					echo count($donations);
				else
					echo '0';
				break;
			case 'funder_thumb' :
				if(function_exists('has_post_thumbnail')) {
					if(has_post_thumbnail($post->ID))
						echo '<a href="'.get_edit_post_link($post->ID).'">'.get_the_post_thumbnail($post->ID, array(45,45)).'</a>';
				}
				break;
			case 'funder_raised' :
				$goal = $this->get_goal_amount($post->ID);
				$total = $this->get_amount_raised($post->ID);
				// If The Type is goal display the raise amount with the goal total
				if($this->has_goal($post->ID) && $goal != 0) {
					$classes = ($total >= $goal && $goal != 0 ? 'class="wdf_complete"' : '');
					echo '<div '.$classes.'>'.$this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</div>';
					if($bar = $this->prepare_progress_bar(null,$total,$goal,'column',false)) {
						echo $bar;
					}
				} else {
						echo $this->format_currency('',$total);
				}
				break;
			case 'funder_time_left' :
					wdf_time_left(true,$post->ID);
				break;
			case 'pledge_amount' :
				$trans = $this->get_transaction();
				$currency = (isset($trans['currency_code']) ? $trans['currency_code'] : '');
				echo '<a href="'.get_edit_post_link($post->ID).'">'.$this->format_currency( $currency, $trans['gross'] ).'</a>';
				break;
			case 'pledge_recurring' :
				$trans = $this->get_transaction();
				if($trans['cycle'])
					echo $trans['cycle'];
				break;
			case 'pledge_funder' :
				$parent = get_post($post->post_parent);
				echo $parent->post_title;
				break;
			case 'pledge_from' :
				$trans = $this->get_transaction();
				echo esc_attr($trans['payer_email']);
				break;
			case 'pledge_method' : 
				$trans = $this->get_transaction();
				echo esc_attr($trans['gateway_public']);
				break;
			case 'pledge_status' :
				$trans = $this->get_transaction($post->ID);
				echo $trans['status'];
				break;
		}
	}
	function media_upload_tabs($tabs) {
		$settings = get_option('wdf_settings');
		if($_GET['tab'] == 'fundraising' || $_GET['tab'] == 'donate_button' || $_GET['tab'] == 'progress_bar') {
			$tabs = array();
			$tabs['fundraising'] = esc_attr($settings['funder_labels']['singular_name']) . __(' Form','wdf');
			$tabs['donate_button'] = esc_attr($settings['donation_labels']['singular_name']) . __(' Button','wdf');
			$tabs['progress_bar'] = __('Progress Bar','wdf');
		}
	
		return $tabs;
	}
	function media_fundraising() {
		wp_iframe(array(&$this, 'media_fundraiser_iframe'));
	}
	function media_donate_button() {
		wp_iframe(array(&$this, 'media_donate_button_iframe'));
	}
	function media_progress_bar() {
		wp_iframe(array(&$this, 'media_progress_bar_iframe'));
	}
	function media_buttons($context) {
		global $typenow, $pagenow, $post;
		if($typenow != 'funder' && $typenow != 'donation' && $context == 'content' && $pagenow != 'index.php') {
			echo '<a title="Insert Funraising Shortcodes" class="thickbox add_media" id="add_wdf" href="'.admin_url('media-upload.php?post_id='.$post->ID).'&tab=fundraising&TB_iframe=1&wdf=1"><img onclick="return false;" alt="Insert Funraising Shortcodes" src="'.WDF_PLUGIN_URL.'/img/sm_ico.png"></a>';
		}
	}
	function media_progress_bar_iframe() {
		$settings = get_option('wdf_settings');
		$args = array(
			'post_type' => 'funder',
			'numberposts' => -1,
			'post_status' => 'publish'
		);
		$funders = get_posts($args);
		media_upload_header(); ?>
		<form class="wdf_media_cont" id="media_progress_bar">
			<h3 class="media-title"><?php _e('Add a progress bar'); ?></h3>
			<p>
				<span class="description"><?php _e('Only fundraisers that have a goal can display a progress bar','wdf'); ?></span>
				<select class="widefat" id="wdf_funder_select" name="id">
					<option value="0"><?php echo __('Choose a ','wdf') . esc_attr($settings['funder_labels']['singular_name']); ?></option>
						<?php foreach($funders as $funder) { ?>
							<?php if(wdf_has_goal($funder->ID) != false) : ?>
								<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
							<?php endif; ?>
						<?php } ?>
				</select>
			</p>
			<p><label><input type="checkbox" name="show_title" value="yes" /> <?php _e('Show Title','wdf'); ?></label></p>
			<p><label><input type="checkbox" name="show_totals" value="yes" /> <?php _e('Show Totals','wdf'); ?></label></p>
			<p><label><?php _e('Choose a style','wdf'); ?></label>
			<select name="style">
				<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
					<option value="0"><?php _e('Default','wdf'); ?></option>
					<?php foreach($this->styles as $key => $label) : ?>
						<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select></p>
			<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p>
		</form>
		
		<?php
	}
	function media_donate_button_iframe () {
		$settings = get_option('wdf_settings');
		media_upload_header(); ?>
		<form class="wdf_media_cont" id="media_donate_button">
		<h3 class="media-title"><?php _e('Add a donation button'); ?></h3>
		<p>
			<label><?php _e('Title','wdf') ?><br />
			<input type="text" class="widefat" name="title" value="" /></label>
		</p>
		<p>
			<label><?php _e('Description','wdf') ?></label><br />
			<textarea class="widefat" name="description"></textarea>
		</p>
		
		<p>
			<label><?php echo esc_attr($settings['donation_labels']['singular_name']) . __(' Amount (blank = choice)','wdf') ?><input type="text" name="donation_amount" value="" /></label>
		</p>
		<p>
			<label><?php _e('Button Type','wdf'); ?></label><br/>
			<label><input class="wdf_toggle" type="radio" name="button_type" value="default" rel="wdf_button_type_default"/> <?php _e('Default PayPal Button','wdf'); ?></label><br />
			<label><input class="wdf_toggle" type="radio" name="button_type" value="custom" rel="wdf_button_type_custom"/> <?php _e('Custom Button','wdf');?></label>
		</p>

		<div rel="wdf_button_type_custom">
			<p><select name="style">
				<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
					<?php foreach($this->styles as $key => $label) : ?>
						<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select></p>
			<p><label><?php _e('Button Text','wdf'); ?><input type="text" class="widefat" name="button_text" value="" /></label></p>
		</div>
		
		<div rel="wdf_button_type_default">
			<p><label><input type="checkbox" name="show_cc" value="yes" /> <?php _e('Show Accepted Credit Cards','wdf'); ?></label></p>
			<p><label><input type="checkbox" name="allow_note" value="yes" /> <?php _e('Allow extra note field','wdf'); ?></label></p>
			<p><label><input type="checkbox" name="small_button" value="yes" /> <?php _e('Use Small Button','wdf'); ?></label></p>
		</div>
		<p>
			<label><?php _e('Override PayPal Email Address','wdf') ?></label><br />
				<label class="code"><?php echo $settings['paypal_email']; ?></label><br />
				<input class="widefat" type="text" name="paypal_email" value="" />
			</label>
		</p>
		
		<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p>
		</form>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				$(document).on('change', 'select.wdf_toggle', function(e) {
					var rel = $(this).attr('rel');
					var val = $(this).val();
				alert(rel + val);
						if(rel == 'wdf_panel_single' && val == '1') {
							var elm = $('*[rel="'+rel+'"]').not(this);
							elm.show();
						} else {
							var elm = $('*[rel="'+rel+'"]').not(this);
							elm.hide();
						}
				});
			});
		</script>
		<?php
	}
	function media_fundraiser_iframe() {
			$content = '';
			$settings = get_option('wdf_settings');
			$args = array(
				'post_type' => 'funder',
				'numberposts' => -1,
				'post_status' => 'publish'
			);
			$funders = get_posts($args);
			media_upload_header();?>
			<form class="wdf_media_cont" id="media_fundraising">
			<h3 class="media-title"><?php _e('Add A Fundraising Form','wdf'); ?></h3>
			
			<p><select class="widefat" id="wdf_funder_select" name="id">
				<option value="0"><?php echo __('Choose a ','wdf') . esc_attr($settings['funder_labels']['singular_name']); ?></option>
					<?php foreach($funders as $funder) { ?>
						<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
					<?php } ?>
			</select></p>
						
			<p><select name="style">
				<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
					<?php foreach($this->styles as $key => $label) : ?>
						<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select></p>
			
			<p><label><input type="checkbox" name="show_title" value="yes" /> <?php _e('Show Title','wdf'); ?></label></p>
			<p><label><input type="checkbox" name="show_content" value="yes" /> <?php _e('Show Content','wdf'); ?></label></p>
			
			<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p>
			</form><?php
			
	}

	function format_currency($currency = '', $amount = false) {
		
		$settings = get_option('wdf_settings');
	
		if (!$currency)
			$currency = $settings['currency'];
	
		// get the currency symbol
		$symbol = $this->currencies[$currency][1];
		// if many symbols are found, rebuild the full symbol
		$symbols = explode(', ', $symbol);
		if (is_array($symbols)) {
			$symbol = "";
			foreach ($symbols as $temp) {
				$symbol .= '&#x'.$temp.';';
			}
		} else {
			$symbol = '&#x'.$symbol.';';
		}
	
		//check decimal option
		if ( $settings['curr_decimal'] === '0' ) {
			$decimal_place = 0;
			$zero = '0';
		} else {
			$decimal_place = 2;
			$zero = '0.00';
		}
	
		//format currency amount according to preference
		if ($amount) {
		  if ($settings['curr_symbol_position'] == 1 || !$settings['curr_symbol_position'])
			return $symbol . number_format_i18n($amount, $decimal_place);
		  else if ($settings['curr_symbol_position'] == 2)
			return $symbol . ' ' . number_format_i18n($amount, $decimal_place);
		  else if ($settings['curr_symbol_position'] == 3)
			return number_format_i18n($amount, $decimal_place) . $symbol;
		  else if ($settings['curr_symbol_position'] == 4)
			return number_format_i18n($amount, $decimal_place) . ' ' . $symbol;
	
		} else if ($amount === false) {
		  return $symbol;
		} else {
		  if ($settings['curr_symbol_position'] == 1 || !$settings['curr_symbol_position'])
			return $symbol . $zero;
		  else if ($settings['curr_symbol_position'] == 2)
			return $symbol . ' ' . $zero;
		  else if ($settings['curr_symbol_position'] == 3)
			return $zero . $symbol;
		  else if ($settings['curr_symbol_position'] == 4)
			return $zero . ' ' . $symbol;
		}
	}
	function is_new_install() {
		return true;
	}
	function prepare_progress_bar($post_id = false, $total = false, $goal = false, $context = 'general', $echo = false) {
		$content = '';
		if($post_id == false) {
			global $post; $post_id = $post->ID;
		}
		
		if($this->has_goal($post_id)) {
			$goal = $this->get_goal_amount($post_id);
			$total = $this->get_amount_raised($post_id);
			$classes = ($total >= $goal ? 'wdf_complete' : '');
			if($context == 'admin_metabox') {
				$content .= '<h1 class="'.$classes.'">' . $this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</h1>';
			} elseif($context == 'general') {
				
			} elseif($context == 'shortcode_title') {
				$content .= '<div class="wdf_progress_shortcode_title">'.get_the_title($post_id).'</div>';
			} elseif($context == 'shortcode_totals') {
				$content .= '<div class="'.$classes.'">' . $this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</div>';
			} elseif($context == 'shortcode_title_totals') {
				$content .= '<div class="wdf_progress_shortcode_title">'.get_the_title($post_id).'</div>';
				$content .= '<div class="'.$classes.'">' . $this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</div>';
			}
			$content .= '<div rel="'.$post_id.'" class="wdf_goal_progress '.$classes.' not-seen '.$context.'" total="'.$total.'" goal="'.$goal.'"></div>';
		}
		if($echo) {echo $content;} else {return $content;}
	}
	
	function get_pledge_list($post_id = false) {
		global $post;
		$post_id = ($post_id != false ? $post_id : $post->ID);
		$args = array(
			'post_parent' => $post_id,
			'numberposts' => -1,
			'post_type' => 'donation',
			'post_status' => array('wdf_complete','wdf_approved')
		);
		$list = get_posts($args);
		if( !$list || empty($list) )
			return false;
		else
			return $list;
			
	}
	function get_amount_raised($post_id = false) {
		global $post;
		$post_id = ($post_id != false ? $post_id : $post->ID);
		$donations = $this->get_pledge_list($post_id);
		$totals = 0;
		if($donations) {
			foreach($donations as $donation) {
				$trans = maybe_unserialize(get_post_meta($donation->ID,'wdf_transaction',true));
				if($trans['gross']) {
					$totals = $totals + intval($trans['gross']);
				}
			}
		}
		return apply_filters('wdf_get_amount_raised', $totals);
	}
	function get_goal_amount($post_id = false){
		global $post;
		$post_id = ($post_id != false ? $post_id : $post->ID);
		$goal = get_post_meta($post_id, 'wdf_goal_amount', true);
		
		return apply_filters('wdf_get_goal_amount', $goal);
	}
	function generate_pledge_id() {
		global $wpdb;
		
		$count = true;
		while ($count) { //make sure it's unique
			$wdf_pledge_id = substr(sha1(uniqid('')), rand(1, 24), 12);
			$count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_title = '" . $wdf_pledge_id . "' AND post_type = 'donation'");
		}
		
		$wdf_pledge_id = apply_filters( 'wdf_pledge_id', $wdf_pledge_id ); //Very important to make sure order numbers are unique and not sequential if filtering
		
		return $wdf_pledge_id;
	}
	function get_transaction($post_id = false) {
		global $post;
		if(!$post_id)
			$post_id = $post->ID;
			
		return maybe_unserialize(get_post_meta($post_id,'wdf_transaction',true));
			
	}
	function datediff($interval, $datefrom, $dateto, $using_timestamps = false) {
		/*
		$interval can be:
		yyyy - Number of full years
		q - Number of full quarters
		m - Number of full months
		y - Difference between day numbers
			(eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
		d - Number of full days
		w - Number of full weekdays
		ww - Number of full weeks
		h - Number of full hours
		n - Number of full minutes
		s - Number of full seconds (default)
		*/
		
		if (!$using_timestamps) {
			$datefrom = strtotime($datefrom, 0);
			$dateto = strtotime($dateto, 0);
		}
		$difference = $dateto - $datefrom; // Difference in seconds
		 
		switch($interval) {
		 
		case 'yyyy': // Number of full years
	
			$years_difference = floor($difference / 31536000);
			if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
				$years_difference--;
			}
			if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
				$years_difference++;
			}
			$datediff = $years_difference;
			break;
	
		case "q": // Number of full quarters
	
			$quarters_difference = floor($difference / 8035200);
			while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
				$months_difference++;
			}
			$quarters_difference--;
			$datediff = $quarters_difference;
			break;
	
		case "m": // Number of full months
	
			$months_difference = floor($difference / 2678400);
			while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
				$months_difference++;
			}
			$months_difference--;
			$datediff = $months_difference;
			break;
	
		case 'y': // Difference between day numbers
	
			$datediff = date("z", $dateto) - date("z", $datefrom);
			break;
	
		case "d": // Number of full days
	
			$datediff = floor($difference / 86400);
			break;
	
		case "w": // Number of full weekdays
	
			$days_difference = floor($difference / 86400);
			$weeks_difference = floor($days_difference / 7); // Complete weeks
			$first_day = date("w", $datefrom);
			$days_remainder = floor($days_difference % 7);
			$odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
			if ($odd_days > 7) { // Sunday
				$days_remainder--;
			}
			if ($odd_days > 6) { // Saturday
				$days_remainder--;
			}
			$datediff = ($weeks_difference * 5) + $days_remainder;
			break;
	
		case "ww": // Number of full weeks
	
			$datediff = floor($difference / 604800);
			break;
	
		case "h": // Number of full hours
	
			$datediff = floor($difference / 3600);
			break;
	
		case "n": // Number of full minutes
	
			$datediff = floor($difference / 60);
			break;
	
		default: // Number of full seconds (default)
	
			$datediff = $difference;
			break;
		}    
	
		return $datediff;
	
	}
	function get_payment_type( $post_id = '' ) {
		if(empty($post_id))
			return false;
		$payment_type = get_post_meta($post_id,'wdf_type',true);
		
		return $payment_type;
	}
	function filter_price($price) {
		 $price = round(preg_replace("/[^0-9.]/", "", $price), 2);return ($price) ? $price : 0;
	}
	
	function has_goal($post_id = false) {
		global $post;
		$post_id = ($post_id ? $post_id : $post->ID);
		$goal = get_post_meta($post_id, 'wdf_has_goal', true);
		if( $goal == '1' && $this->get_goal_amount($post_id) > 0 )
			return true;
		else 
			return false;
	}
}
		
global $wdf;
$wdf = &new WDF();

//Load Our Template Function
// Use the action below to override any template functions you want.
// All the functions in template-functions.php are wrapped with if(!function_exists)
do_action('wdf_custom_template_functions');
require_once( WDF_PLUGIN_BASE_DIR . '/lib/template-functions.php');

// Check for BuddyPress and boot up our component structure
if (defined('BP_VERSION') && version_compare( BP_VERSION, '1.5', '>' ) )
	require_once( WDF_PLUGIN_BASE_DIR . '/fundraiser-bp.php' );
?>