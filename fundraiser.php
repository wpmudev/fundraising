<?php
/*
Plugin Name: Fundraising
Plugin URI: http://premium.wpmudev.org/project/fundraising/
Description: Create a fundraising page for any purpose or project.
Version: 2.0.0-RC-1
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
	wp_die(__('There was an issue determining where the Donations plugin is installed. Please reinstall.'));
}
$textdomain_handler('wdf', false, WDF_PLUGIN_SELF_DIRNAME . '/languages/');

class WDF {
	function WDF() {
		$this->_vars();
		$this->_construct();
	}
	function _vars() {
		$this->version = '1.0.0';
		$this->defaults = array(
			'currency' => 'USD',
			'dir_slug' => __('fundraisers','wdf'),
			'checkout_slug' => __('pledge','wdf'),
			'confirm_slug' => __('thank-you','wdf'),
			'activity_slug' => __('activity','wdf'),
			'first_time' => 1,
			'default_style' => 'wdf_basic',
			'panel_in_sidebar' => 'no',
			'payment_types' => array(),
			'curr_symbol_position' => 1,
			'curr_decimal' => 1,
			'default_email' => 'Thank you for your pledge. Your donation of %DONATIONTOTAL% has been recieved and is greatly appreciated. Thanks for your support.'
		);
		
		// Setup Additional Data Structure
		require_once(WDF_PLUGIN_BASE_DIR . '/lib/wdf_data.php');
	}
	function _construct() {
		
		//load sitewide features if Network Installation
		if (is_multisite()) {
		  //require_once(WDF_PLUGIN_BASE_DIR . '/lib/class.wdf_admin_ms.php');
		  //new WDF_MS();
		}
		
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
		
		//load APIs and plugins
			add_action( 'plugins_loaded', array(&$this, 'load_plugins') );

		// Initialize our post types and rewrite structures
			add_action( 'init', array(&$this,'_init'),1);
			add_action( 'init', array(&$this, 'flush_rewrite'), 999 );
			add_action( 'wp_insert_post', array(&$this,'wp_insert_post') );
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
			
			// Add Admin Only Filters		
			add_filter( 'manage_edit-funder_columns', array(&$this,'edit_columns') );
			add_filter( 'manage_edit-donation_columns', array(&$this,'edit_columns') );
			add_filter( 'media_upload_tabs', array(&$this,'media_upload_tabs') );
						
			//Register Styles and Scripts For The Admin Area
			wp_register_script( 'wdf-post', WDF_PLUGIN_URL . '/js/wdf-post.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-edit', WDF_PLUGIN_URL . '/js/wdf-edit.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-media', WDF_PLUGIN_URL . '/js/wdf-media.js', array('jquery'), $this->version, true );
			wp_register_style( 'wdf-admin', WDF_PLUGIN_URL . '/css/wdf-admin.css', null, $this->version, null );
			
		} else {
			
			//Not the admin area so lets load up our front-end actions, scripts and filters
			wp_register_style( 'wdf-style-wdf_basic', WDF_PLUGIN_URL . '/styles/wdf-basic.css',null,$this->version );
			wp_register_style( 'wdf-style-wdf_minimal', WDF_PLUGIN_URL . '/styles/wdf-minimal.css',null,$this->version );
			wp_register_style( 'wdf-style-wdf_dark', WDF_PLUGIN_URL . '/styles/wdf-dark.css',null,$this->version );
			wp_register_style( 'wdf-style-wdf_note', WDF_PLUGIN_URL . '/styles/wdf-note.css',null,$this->version );
			wp_register_style( 'wdf-thickbox', WDF_PLUGIN_URL . '/css/wdf-thickbox.css',null,$this->version );
			wp_register_script( 'wdf-base', WDF_PLUGIN_URL . '/js/wdf-base.js', array('jquery'), $this->version, false );
			
			add_action( 'template_redirect', array(&$this,'template_redirect'), 20 );
			add_action( 'template_redirect', array(&$this, 'handle_payment'), 30 );
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
		if(!class_exists('WDF_Featured_Fundraisers')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.featured_fundraisers.php');
			register_widget('WDF_Featured_Fundraisers');
		}
		if(!class_exists('WDF_Fundraiser_Panel')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.fundraiser_panel.php');
			register_widget('WDF_Fundraiser_Panel');
		}
	}
	function tutorial() {
		include(WDF_PLUGIN_BASE_DIR .'/lib/wdf_tutorials.php');
	}
	
	function _init() {
		$settings = get_option('wdf_settings');
		//Funder Custom Post Type Arguments
		$funder_args = array(
			'labels' => array(
				'name' => __('Fundraisers','wdf'),
				'singular_name' => __('Fundraiser','wdf'),
				'add_new' => __('New Fundraiser'),
				'add_new_item' => __('Add New Fundraiser','wdf'),
				'edit_item' => __('Edit Fundraiser','wdf'),
				'new_item' => __('New Fundraiser','wdf'),
				'all_items' => __('All Fundraisers','wdf'),
				'view_item' => __('View Fundraiser','wdf'),
				'search_items' => __('Search Fundraisers','wdf'),
				'not_found' =>  __('No Fundraisers found','wdf'),
				'not_found_in_trash' => __('No Fundraisers found in Trash','wdf'), 
				'parent_item_colon' => '',
				'menu_name' => 'Fundraising'
			),
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
			'labels' => array(
				'name' => __('Pledges','wdf'),
				'singular_name' => __('Pledge','wdf'),
				'add_new' => __('New Pledge'),
				'add_new_item' => __('Add New Pledge','wdf'),
				'edit_item' => __('Edit Pledge','wdf'),
				'new_item' => __('New Pledge','wdf'),
				'all_items' => __('All Pledges','wdf'),
				'view_item' => __('View Pledge','wdf'),
				'search_items' => __('Search Pledges','wdf'),
				'not_found' =>  __('No Pledges found','wdf'),
				'not_found_in_trash' => __('No Pledges found in Trash','wdf'), 
				'parent_item_colon' => '',
				'menu_name' => __('Pledges','wdf')
			),
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
		
		//Register Post Type
		register_post_type( 'funder', $funder_args );
		register_post_type( 'donation', $donation_args );
		//wp_mail('cole@imakethe.com','Goal Complete', var_export(wp_get_schedules(),true) );
		
		$complete_args = array(
			'label'       => __('Complete', 'wdf'),
			'label_count' => array( __('Complete <span class="count">(%s)</span>', 'wdf'), __('Complete <span class="count">(%s)</span>', 'wdf') ),
			'post_type'   => 'donation',
			'public'      => false,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true
		);
		//register_post_status('donation_complete', $complete_args);
		
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
		
		// Fundraiser Activity Page
		$new_rules[$settings['dir_slug'] . '/([^/]+)/' . $settings['activity_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_activity=1';
		
		//ipn handling for payment gateways
		//$new_rules[$settings['slugs']['store'] . '/payment-return/(.+)'] = 'index.php?paymentgateway=$matches[1]';
		
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
		
		if($this->is_funder_single && !is_active_widget( false, false, 'wdf_fundraiser_panel', false )) {
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
		$list = $list . '<li class="page_item'. ((get_query_var('post_type') == 'funder') ? ' current_page_item' : '') . '"><a href="' . home_url($settings['dir_slug'].'/') . '" title="' . __('Fundraisers', 'mp') . '">' . __('Fundraisers', 'mp') . '</a></li>';
		return $list;
	}
	
	function views_list($views) {
		global $wp_query;
		unset($views['publish']);
		unset($views['all']);
		$avail_post_stati = wp_edit_posts_query();
		$num_posts = wp_count_posts( 'donation', 'readable' );
		$argvs = array('post_type' => 'donation');
		foreach ( get_post_stati($argvs, 'objects') as $status ) {
			$class = '';
			$status_name = $status->name;
			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;
			if ( empty( $num_posts->$status_name ) )
				continue;
			if ( isset($_GET['post_status']) && $status_name == $_GET['post_status'] )
				$class = ' class="current"';
			$views[$status_name] = "<li><a href='edit.php?post_type=donation&amp;post_status=$status_name'$class>" . sprintf( _n( $status->label_count[0], $status->label_count[1], $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}
		
		return $views;
    }
	function load_plugins() {
		$settings = get_option('wdf_settings');
		//load gateway plugin API
		require_once( WDF_PLUGIN_BASE_DIR . '/lib/classes/class.gateway.php' );
		$this->load_gateway_plugins();
	}
	function load_gateway_plugins() {
		
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
		/*if(is_multisite())
			$network_settings = get_site_option( 'wdf_network_settings' );*/
		
		foreach ((array)$wdf_gateway_plugins as $code => $plugin) {
			$class = $plugin[0];
			if( isset($settings['active_gateways']) ) {
				if ( class_exists($class) && !$plugin[3] && $settings['active_gateways'][$code] == '1'  )
					$wdf_gateway_active_plugins[$code] = new $class;
			}
			
		}
		if(isset($_POST['wdf_settings']) && is_array($_POST['wdf_settings'])) {
			$this->save_settings($_POST['wdf_settings']);
		}
		// Action used for saving gateway settings
		do_action('wdf_gateway_plugins_loaded');
	}	
	function front_scripts($id = NULL) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script('wdf-base');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('jquery-ui-base');
		if(!empty($id) && $style = wdf_get_style($id))
			wp_enqueue_style('wdf-style-'.$style);
		
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
		if( isset($_POST['wdf_pledge']) && isset($_POST['wdf_gateway']) && isset($_POST['wdf_step']) && isset($_POST['funder_id']) ) {
			$_SESSION['funder_id'] = $_POST['funder_id'];
			$_SESSION['wdf_pledge'] = $this->filter_price($_POST['wdf_pledge']);
			$_SESSION['wdf_gateway'] = $_POST['wdf_gateway'];
			$_SESSION['wdf_step'] = $_POST['wdf_step'];
			$_SESSION['wdf_type'] = $this->get_payment_type($_POST['funder_id']);
			$_SESSION['wdf_recurring'] = ( isset($_POST['wdf_recurring']) || $_POST['wdf_recurring'] == '0' ? $_POST['wdf_recurring'] : false);
		}
		if( isset($_POST['wdf_payment_submit']) ) {
			
			if( !isset($_SESSION['wdf_type']) || empty($_SESSION['wdf_type']) )
				$this->create_error(__('Could not determine pledge type.','wdf'),'payment_submit');
				
			if( !isset($_SESSION['wdf_gateway']) || empty($_SESSION['wdf_gateway']) )
				$this->create_error(__('Please choose a payment method.','wdf'),'payment_submit');
			
			if( !isset($_SESSION['funder_id']) || empty($_SESSION['funder_id']) ) {
				$this->create_error(__('Fundraiser could not be determined.','wdf'),'payment_submit');
			}
			
			if(!$this->wdf_error) {
				do_action('wdf_gateway_pre_process_'.$_SESSION['wdf_gateway']);
				do_action('wdf_gateway_process_'.$_SESSION['wdf_type'].'_'.$_SESSION['wdf_gateway']);
			} else {
				wp_redirect( wdf_get_funder_page('checkout',$_SESSION['funder_id']) );
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
				$transaction = $this->get_transaction($funder_id);
				do_action('wdf_execute_payment_'.$transaction['gateway'], $pledge, $transaction);
			}
			wp_mail('cole@imakethe.com','process_pledges',var_export($pledges,true) );
		}
		
		do_action('wdf_after_goal_complete', $pledges);
	}
	function create_thank_you($funder_id,$trans) {
		
		/*$msg = get_post_meta($funder_id,'wdf_thanks_custom',true);
				
		$search = array('%DONATIONTOTAL%','%FIRSTNAME%','%LASTNAME%');
		$replace = array($this->format_currency('',$trans['gross']),$trans['first_name'], $trans['last_name']);

    	//replace
    	$msg = str_replace($search, $replace, $msg);
		$style = get_post_meta($funder_id,'wdf_style',true);
		
		$this->front_scripts($funder_id);
		$func = 'echo "<div id=\'wdf_thank_you\' class=\''.$style.'\'><div class=\'fade\'></div><div class=\'wdf_ty_content\'><a class=\'close\'>'.__('Close','wdf').'</a><h1>'.apply_filters('wdf_thankyou_title',__('Thank You!','wdf')).'</h1><p>' . $msg . '</p></div></div><script type=\"text/javascript\">jQuery(document).ready( function($) { $(\"#wdf_thank_you .close\").click( function() { $(\"#wdf_thank_you\").fadeOut(500); }); });</script>";';
		
		add_action('wp_footer',  create_function('',$func),50);*/
		
		if($send_email = get_post_meta($funder_id,'wdf_send_email',true)) {
						
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
				wp_mail('cole@imakethe.com','goal complete',var_export($funder_id,true) );
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
			
			//var_export($post);
			if( !in_array($wdf_type,$settings['payment_types']) || $has_pledges == false ) {
				
				if($post->post_status != 'publish' && $has_pledges == false)
					add_meta_box( 'wdf_type', __('Fundraising Type','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
					
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
				
				if( $has_pledges != false )
					add_meta_box( 'wdf_progress', __('Fundraiser Progress','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
				
				add_meta_box( 'wdf_options', __('Fundraiser Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
				if($wdf_type == 'advanced')
					add_meta_box( 'wdf_goals', __('Set Your Fundraising Goals','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
					
				add_meta_box( 'wdf_messages', __('Thank You Message Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');	
				// Show pledge activity if funds have been raised
				if( $has_pledges != false )
					add_meta_box( 'wdf_activity', __('Pledge Activity','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			}
			
			
				
		} elseif($typenow == 'donation') {
			add_meta_box( 'wdf_pledge_info', __('Pledge Information','wdf'), array(&$this,'meta_box_display'), 'donation', 'normal', 'high');
			add_meta_box( 'wdf_pledge_status', __('Pledge Status','wdf'), array(&$this,'meta_box_display'), 'donation', 'side', 'high');
			
		}
	}
	function meta_box_display($post,$data) {
		include(WDF_PLUGIN_BASE_DIR . '/lib/form.meta_boxes.php');
	}
	function admin_menu() {		
		add_submenu_page( 'edit.php?post_type=funder', 'Getting Started', 'Getting Started', 'manage_options', 'wdf', array(&$this,'admin_display') );
		add_submenu_page( 'edit.php?post_type=funder', 'Pledges', 'Pledges', 'manage_options', 'wdf_donations', array(&$this,'admin_display') );		
		add_submenu_page( 'edit.php?post_type=funder', 'Fundraising Settings', 'Settings', 'manage_options', 'wdf_settings', array(&$this,'admin_display') );
		
		//Some quick fixes for the menu
		//TO-DO use array filters and in_array to pick out the correct menu item no matter the position
		global $submenu, $menu;

		$submenu['edit.php?post_type=funder'][5][0] = 'Fundraisers';
		$submenu['edit.php?post_type=funder'][10][0] = 'Add New';
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
			} elseif ( $k == 'payment_types' || $k == 'active_gateways' ) {
				$new[$k] = array_map('esc_attr',$v);
			} else {
				$new[$k] = esc_attr($v);
			}
		}
		
		// If no die flags have been triggered then lets merge our settings array together and save.
		if(!$die) {
			$settings = get_option('wdf_settings');
		
			//If gateways are being saved unset them and re-save to populate the correct active gateways
			if(isset($_POST['wdf_settings']['active_gateways'])) {
				unset($settings['active_gateways']);
			}
			if(isset($_POST['wdf_settings']['payment_types'])) {
				unset($settings['payment_types']);
			}
			
			$settings = array_merge($settings,$new);
			update_option('wdf_settings',$settings);
			$this->create_msg('Settings Saved', 'general');			
		}			
	}
	
	function create_error($msg, $context) {
		$classes = 'error';
		$content = 'return "<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_filter('wdf_error_' . $context, create_function('', $content));
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
		if(!current_user_can('edit_post') && !isset($_POST['wdf']))
			return;
			
		if(isset($_POST['wdf']['levels']) && count($_POST['wdf']['levels']) < 2 && $_POST['wdf']['levels'][0]['amount'] == '')
			$_POST['wdf']['levels'] = '';
		
		if ( 'funder' == $_POST['post_type'] ) {
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
							$value = esc_html($value);
						} else if($key == 'thanks_post') {
							$value = absint($value);
							$is_page = get_page($value);
							$is_post = get_post($value);
							if($is_page == false && $is_post == false) {
								$this->create_error('You must supply a valid post or page ID','thanks_post');
								$value = '';
							}
						}/* else if($key == 'goal_end_date') {
							if(isset($_POST['goal_start_date'])) {
								$start_date = strtotime(esc_attr($_POST['goal_start_date']));
								$end_date = strtotime(esc_attr($value));
								
								if($start_date != false && $end_date != false && $end_date > $start_date)
									//wp_schedule_event( $end_date - mktime(1,minute,second,month,day,year,is_dst), 'hourly', array(&$this,'goal_end'), $post->ID );
							}
						}*/ else {
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
		
		if($hook = 'edit.php')
			wp_localize_script('wdf-edit', 'WDF', array( 'hook' => $hook, 'typenow' => $typenow) );
	}
	
	function edit_columns($columns) {
		global $typenow;
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
			$columns['funder_pledges'] = __('Pledges','wdf');
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
			$columns['pledge_funder'] = __('Fundraiser', 'wdf');
			$columns['pledge_from'] = __('From', 'wdf');
			$columns['pledge_method'] = __('Method', 'wdf');
			$columns['title'] = __('Transaction ID', 'wdf');
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
			case 'pledge_recurring' :
				$trans = $this->get_transaction();
				if($trans['cycle'])
					echo $trans['cycle'];
				break;
			case 'pledge_funder' :
				$parent = get_post($post->post_parent);
				echo $parent->post_title;
				break;
			case 'funder_type' : 
				if($type = get_post_meta($post->ID,'wdf_type',true))
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
			case 'pledge_amount' :
				$trans = $this->get_transaction();
				echo '<a href="'.get_edit_post_link($post->ID).'">'.$this->format_currency('',$trans['gross']).'</a>';
				break;
			case 'pledge_from' :
				$trans = $this->get_transaction();
				echo $trans['payer_email'];
				break;
			case 'pledge_method' : 
				$trans = $this->get_transaction();
				echo esc_attr($trans['gateway']);
				break;
			case 'funder_raised' :
				$has_goal = get_post_meta($post->ID,'wdf_has_goal',true);
				$goal = get_post_meta($post->ID,'wdf_goal_amount',true);
				$total = $this->get_amount_raised($post->ID);
				// If The Type is goal display the raise amount with the goal total
				if($has_goal == '1') {
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
			case 'pledge_status' :
				$trans = $this->get_transaction($post->ID);
				echo $trans['status'];
				break;
		}
	}
	function media_upload_tabs($tabs) {
		
		if($_GET['tab'] == 'fundraising' || $_GET['tab'] == 'donate_button') {
			$tabs = array();
			$tabs['fundraising'] = 'Fundraising Form';
			$tabs['donate_button'] = 'Donate Button';
		}
	
		return $tabs;
	}
	function media_fundraising() {
		wp_iframe(array(&$this, 'media_fundraiser_iframe'));
	}
	function media_donate_button() {
		wp_iframe(array(&$this, 'media_donate_button_iframe'));
	}
	function media_buttons($context) {
		global $typenow, $pagenow, $post;
		if($typenow != 'funder' && $typenow != 'donation' && $context == 'content' && $pagenow != 'index.php') {
			echo '<a title="Insert Funraising Shortcodes" class="thickbox add_media" id="add_wdf" href="'.admin_url('media-upload.php?post_id='.$post->ID).'&tab=fundraising&TB_iframe=1&wdf=1"><img onclick="return false;" alt="Insert Funraising Shortcodes" src="'.WDF_PLUGIN_URL.'/img/sm_ico.png"></a>';
		}
	}
	function media_donate_button_iframe () {
		$settings = get_option('wdf_settings');
		media_upload_header(); ?>
		<form class="wdf_media_cont" id="media_donate_button">
		<h3 class="media-title">Add A Donation Button</h3>
		<p>
			<label>Donation Item Title<br /><input type="text" name="title" class="regular-text" /></label>
		</p>
		<p>
			<label><?php echo __('Donation Amount (blank = choice)','wdf') ?><br /><input type="text" id="donation_amount" value="" /></label>
		</p>
		<p>
			<label>Button Type</label><br/>
			<label><input onchange="input_switch(); return false;" type="radio" value="default" name="button_type" /> Default PayPal Button</label><br />
			<label><input type="radio" name="button_type" value="custom" /> Custom Button</label>
		</p>
		<p>
			<?php /*?><label><?php echo __('Override PayPal Email Address','wdf') ?></label><br />
				<label class="code"><?php echo $settings['paypal_email']; ?></label><br />
				<input class="regular-text" type="text" name="paypal_email" value="" />
			</label><?php */?>
		</p>
		<p>
			<label><?php echo __('Choose a display style','wdf'); ?>
			<select name="style">
				<option value="wdf_default"><?php echo __('Basic','wdf'); ?></option>
				<option value="wdf_dark"><?php echo __('Dark','wdf'); ?></option>
				<option value="wdf_minimal"><?php echo __('Minimal','wdf'); ?></option>
				<option value="wdf_note"><?php echo __('Note','wdf'); ?></option>
				<option value="custom"><?php echo __('None (Custom CSS)','wdf'); ?></option>
			</select></label>
		</p>
		<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;">Insert Form</a></p>
		</form>
		<?php
	}
	function media_fundraiser_iframe() {
			$content = '';
			
			$args = array(
				'post_type' => 'funder',
				'numberposts' => -1,
				'post_status' => 'publish'
			);
			$funders = get_posts($args);
			media_upload_header();?>
			<form class="wdf_media_cont" id="media_fundraising">
			<h3 class="media-title">Add A Fundraising Form</h3>
			<p><select id="wdf_funder_select" name="id">
			<option value="0"> ---------------------------- </option>
			<?php foreach($funders as $funder) { ?>
				<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
			<?php } ?>
			</select></p>
			
			<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;">Insert Form</a></p>
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
	function prepare_progress_bar($post_id = '', $total = false, $goal = false, $context = 'general', $echo = false) {
		$content = '';
		if(!empty($post_id)) {
			$goal = get_post_meta($post_id,'wdf_goal_amount',true);
			$total = $this->get_amount_raised($post_id);
		}
		if($this->has_goal($post_id)) {
			$classes = ($total >= $goal ? 'wdf_complete' : '');
			if($context == 'admin_metabox') {
				$content .= '<h1 class="'.$classes.'">' . $this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</h1>';
			} elseif($context == 'general') {
				
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
			'post_status' => array('publish')
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
		} else {
			$totals = '0';
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
		$settings = get_option('wdf_settings');
		if($payment_type == 'advanced' && $this->has_goal($post_id) && in_array('advanced',$settings['payment_types']))
			$payment_type = 'advanced';
		else
			$payment_type = 'simple';
		
		return $payment_type;
	}
	function filter_price($price) {
		 $price = round(preg_replace("/[^0-9.]/", "", $price), 2);return ($price) ? $price : 0;
	}
	
	function has_goal($post_id = false) {
		global $post;
		$post_id = ($post_id ? $post_id : $post->ID);
		
		if( get_post_meta($post_id, 'wdf_has_goal', true) == '1' && get_post_meta($post_id, 'wdf_type',true) == 'advanced' )
			return true;
		else 
			return false;
	}
}
global $wdf;
$wdf = &new WDF();

//Load Our Template Function
	require_once( WDF_PLUGIN_BASE_DIR . '/lib/template-functions.php');

// Check for BuddyPress and boot up our component structure
if (defined('BP_VERSION') && version_compare( BP_VERSION, '1.5', '>' ) );
	include_once( WDF_PLUGIN_BASE_DIR . '/fundraiser-bp.php' );