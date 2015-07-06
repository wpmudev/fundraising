<?php
/*
Plugin Name: Fundraising
Plugin URI: http://premium.wpmudev.org/project/fundraising/
Description: Create a fundraising page for any purpose or project.
Version: 2.6.1.9
Text Domain: wdf
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
WDP ID: 259

Copyright 2009-2013 Incsub (http://incsub.com)

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


define ('WDF_PLUGIN_SELF_DIRNAME', basename(dirname(__FILE__)), true);

//Setup proper paths/URLs and load text domains
if (is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'mu-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WPMU_PLUGIN_DIR, true);
	//define ('WDF_PLUGIN_URL', WPMU_PLUGIN_URL, true);
	$textdomain_handler = 'load_muplugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'subfolder-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	//define ('WDF_PLUGIN_URL', WP_PLUGIN_URL . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR, true);
	//define ('WDF_PLUGIN_URL', WP_PLUGIN_URL, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else {
	// No textdomain is loaded because we can't determine the plugin location.
	// No point in trying to add textdomain to string and/or localizing it.
	wp_die(__('There was an issue determining where the Fundraising plugin is installed. Please reinstall.'));
}
$textdomain_handler('wdf', false, WDF_PLUGIN_SELF_DIRNAME . '/languages/');

define ('WDF_PLUGIN_URL', plugins_url('', __FILE__ ), true);


// Gotta do this here so it doesnt save over what we just deleted.
if(isset($_POST['wdf_reset']) && current_user_can('wdf_edit_settings')) {
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

class WDF {
	function WDF() {
		$this->_vars();
		$this->_construct();
	}
	function _vars() {
		$this->version = '2.6.1.3';
		$this->defaults = array(
			'currency' => 'USD',
			'dir_slug' => __('fundraisers','wdf'),
			'permlinks_front' => ((function_exists('is_subdomain_install') && is_subdomain_install()) ? 0 : 1),
			'default_gateway' => 'paypal',
			'checkout_slug' => __('pledge','wdf'),
			'confirm_slug' => __('thank-you','wdf'),
			'activity_slug' => __('activity','wdf'),
			'inject_menu' => 'no',
			'single_styles' => 'yes',
			'custom_css' => '',
			'message_pledge_not_found' => '',
			'first_time' => 1,
			'default_style' => 'wdf-basic',
			'panel_in_sidebar' => 'no',
			'payment_types' => array('simple'),
			'curr_symbol_position' => 1,
			'single_checkout_type' => 0,
			'curr_decimal' => 1,
			'default_email' => __('Thank you for your pledge. Your donation of %DONATIONTOTAL% has been received and is greatly appreciated. Thanks for your support.','wdf'),
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
		$this->capabilities = array(
			'wdf_add_fundraisers' => __('Add and manage users\'s fundraisers','wdf'),
			'wdf_manage_all_fundraisers' => __('Manage all fundraisers','wdf'),
			'wdf_manage_pledges' => __('Manage pledges','wdf'),
			'wdf_edit_settings' => __('Edit settings','wdf'),
		);

		// Setup Additional Data Structure
		require_once(WDF_PLUGIN_BASE_DIR . '/lib/wdf_data.php');
	}
	function _construct() {
		global $wpmudev_notices;
		$wpmudev_notices[] = array( 'id'=> 259,'name'=> 'Fundraising', 'screens' => array( 'edit-funder', 'funder', 'edit-donation', 'donation', 'funder_page_wdf_settings', 'funder_page_wdf' ) );
		include_once(WDF_PLUGIN_BASE_DIR . '/lib/external/wpmudev-dash-notification.php');

		$settings = get_option('wdf_settings');
		if(!is_array($settings) || !$settings || empty($settings) ) {
			update_option('wdf_settings',$this->defaults);
			$settings = $this->defaults;
		}
		if(!isset($settings['current_version'])) {
			$this->upgrade_settings();
		}
		$version_compare = version_compare($this->version, $settings['current_version']);
		if($version_compare == 1) {
			// Upgrade
			add_action('init', array(&$this,'upgrade_settings'));
		} else if($version_compare == -1) {
			// Downgrade
			add_action('init', array(&$this,'upgrade_settings'));
		}

		//load APIs and plugins
			add_action( 'plugins_loaded', array(&$this, 'load_plugins') );

		// Initialize our post types and rewrite structures
			add_action( 'after_setup_theme', array(&$this,'_init'));

			add_action( 'after_setup_theme', array(&$this, 'flush_rewrite'), 999 );
			add_filter( 'rewrite_rules_array', array(&$this, 'add_rewrite_rules') );
			add_filter( 'query_vars', array(&$this, 'add_queryvars') );
			add_filter( 'map_meta_cap', array(&$this, 'map_meta_cap'), 10, 4 );

		// Initialize our post types and rewrite structures
			add_action( 'admin_init', array(&$this,'set_capabilities') );

		// Include Widgets
			add_action( 'widgets_init', array(&$this,'register_widgets') );

		// Include our template functions
			add_action( 'after_setup_theme', array(&$this,'after_setup_theme') );

		if(is_admin()) {

			// Load tutorials for first time installations
			if($settings['first_time'] == '1')
				add_action( 'admin_init', array(&$this,'tutorial') );

			add_action( 'add_meta_boxes_funder', array(&$this,'add_meta_boxes') );
			add_action( 'add_meta_boxes_donation', array(&$this,'add_meta_boxes') );
			add_action( 'admin_head-nav-menus.php', array(&$this,'add_menu_meta_boxes') );
			add_action( 'admin_menu', array(&$this,'admin_menu') );
			add_action( 'admin_enqueue_scripts', array(&$this,'admin_enqueue_scripts') );
			add_action( 'admin_enqueue_styles', array(&$this,'admin_enqueue_styles') );
			add_action( 'manage_funder_posts_custom_column', array(&$this,'column_display') );
			add_action( 'manage_donation_posts_custom_column', array(&$this,'column_display') );
			add_action( 'media_buttons', array(&$this,'media_buttons'), 30 );
			add_action( 'media_upload_fundraising', array(&$this,'media_fundraising'));
			add_action( 'media_upload_pledges', array(&$this,'media_pledges'));
			add_action( 'media_upload_donate_button', array(&$this,'media_donate_button'));
			add_action( 'media_upload_progress_bar', array(&$this,'media_progress_bar'));
			add_action( 'wp_insert_post', array(&$this,'wp_insert_post') );
			add_action( 'before_delete_post', array(&$this,'before_delete_post') );

			// Add Admin Only Filters
			add_filter( 'manage_edit-funder_columns', array(&$this,'edit_columns') );
			add_filter( 'manage_edit-donation_columns', array(&$this,'edit_columns') );
			add_filter( 'media_upload_tabs', array(&$this,'media_upload_tabs') );

		} else {

			//Not the admin area so lets load up our front-end actions, scripts and filters
			add_action('wp_enqueue_scripts', array(&$this,'enqueue_scripts'));

			// Menu Fix for displaying parent archive page correctly
			add_filter( 'wp_nav_menu_objects', array(&$this, 'filter_nav_menu'), 10, 2 );

			// Very low priority number is needed here to make sure it fires before themes can output headers
			add_action( 'wp', array(&$this, 'handle_payment'), 1 );
			add_action( 'template_redirect', array(&$this, 'template_redirect'), 20 );
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
		if(!class_exists('WDF_Pledges_Panel')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.pledges_panel.php');
			register_widget('WDF_Pledges_Panel');
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
			'add_new' => sprintf(__('New %s','wdf'), $single),
			'add_new_item' => sprintf(__('Add New %s','wdf'), $single),
			'edit_item' => sprintf(__('Edit %s','wdf'), $single),
			'new_item' => sprintf(__('New %s','wdf'), $single),
			'all_items' => sprintf(__('All %s','wdf'), $plural),
			'view_item' => sprintf(__('View %s','wdf'), $single),
			'search_items' => sprintf(__('Search %s','wdf'), $plural),
			'not_found' =>  sprintf(__('No %s found','wdf'), $plural),
			'not_found_in_trash' => sprintf(__('No %s found in Trash','wdf'), $plural),
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
			'add_new' => sprintf(__('New %s','wdf'), $single),
			'add_new_item' => sprintf(__('Add New %s','wdf'), $single),
			'edit_item' => sprintf(__('Edit %s','wdf'), $single),
			'new_item' => sprintf(__('New %s','wdf'), $single),
			'all_items' => sprintf(__('All %s','wdf'), $plural),
			'view_item' => sprintf(__('View %s','wdf'), $single),
			'search_items' => sprintf(__('Search %s','wdf'), $plural),
			'not_found' =>  sprintf(__('No %s found','wdf'), $plural),
			'not_found_in_trash' => sprintf(__('No %s found in Trash','wdf'), $plural),
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
		$capabilities_funder = array(
		    'publish_posts' => 'wdf_add_fundraisers',
		    'edit_posts' => 'wdf_add_fundraisers',
		    'edit_others_posts' => 'wdf_manage_all_fundraisers',
		    'delete_posts' => 'wdf_add_fundraisers',
		    'delete_others_posts' => 'wdf_manage_all_fundraisers',
		    'read_private_posts' => 'wdf_add_fundraisers',
		    'edit_post' => 'edit_funder',
		    'read_post' => 'read_funder',
		    'delete_post' => 'delete_funder'
		);
		$funder_args = array(
			'labels' => $funder_labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug' => $settings['dir_slug'],
				'with_front' => $settings['permlinks_front'],
				'feeds'      => true
			),
			'capability_type' => 'funder',
			'capabilities' => $capabilities_funder,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => WDF_PLUGIN_URL . '/img/sm_ico.png',
			'supports'           => array('title','thumbnail','editor','excerpt','author','comments')
		);
		//Donation Custom Post Type arguments
		$capabilities_donation = array(
		    'publish_posts' => 'wdf_manage_pledges',
		    'edit_posts' => 'wdf_manage_pledges',
		    'edit_others_posts' => 'wdf_manage_pledges',
		    'delete_posts' => 'wdf_manage_pledges',
		    'delete_others_posts' => 'wdf_manage_pledges',
		    'read_private_posts' => 'wdf_manage_pledges',
		    'edit_post' => 'edit_donation',
		    'read_post' => 'read_donation',
		    'delete_post' => 'delete_donation'
		);
		$donation_args = array(
			'labels' => $donation_labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'donation',
			'capabilities' => $capabilities_donation,
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

		$pledges = wp_count_posts('donation');
		if($pledges->publish > 0 || $pledges->draft > 0 ) {
			// The is the best way to determine the 1.0 to 2.0 jump remove after 2.1
			$this->upgrade_fundraisers();
		}

	}
	function set_capabilities() {
		//adds cap for admin
        $admin_role = get_role('administrator');
        $caps = $this->capabilities;
        $caps['read_funder'] = 1;
        $caps['edit_funder'] = 1;
        $caps['delete_funder'] = 1;
        foreach($caps as $key => $cap) {
            if(!isset($admin_role->capabilities[$key]) || $admin_role->capabilities[$key] == false ) {
                $admin_role->add_cap($key,true);
            }
        }
	}
	function map_meta_cap( $caps, $cap, $user_id, $args ) {

	    if ( 'edit_funder' == $cap || 'delete_funder' == $cap || 'read_funder' == $cap || 'edit_donation' == $cap || 'delete_donation' == $cap || 'read_donation' == $cap ) {
	        $post = get_post( $args[0] );
	        $post_type = get_post_type_object( $post->post_type );
	        $caps = array();
	    }

	    if ( 'edit_funder' == $cap || 'edit_donation' == $cap ) {
	        if ( $user_id == $post->post_author )
	            $caps[] = $post_type->cap->edit_posts;
	        else
	            $caps[] = $post_type->cap->edit_others_posts;
	    }

	    elseif ( 'delete_funder' == $cap || 'delete_donation' == $cap ) {
	        if ( $user_id == $post->post_author )
	            $caps[] = $post_type->cap->delete_posts;
	        else
	            $caps[] = $post_type->cap->delete_others_posts;
	    }

	    elseif ( 'read_funder' == $cap || 'read_donation' == $cap ) {
	        if ( 'private' != $post->post_status )
	            $caps[] = 'read';
	        elseif ( $user_id == $post->post_author )
	            $caps[] = 'read';
	        else
	            $caps[] = $post_type->cap->read_private_posts;
	    }

	    return $caps;
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

		// Update the current_version
		$old_version = $settings['current_version'];
		$settings['current_version'] = $this->version;

		// Merge and save
		$settings = array_merge($this->defaults, $settings);
		update_option('wdf_settings', $settings);

		//lets flash rules if needed
		if($this->version == '2.6.1.3' && version_compare($this->version, $old_version) == 1)
			flush_rewrite_rules();
	}
	function flush_rewrite($force = 0) {
		global $wp_rewrite;
		if(isset($_POST['wdf_settings']) && is_array($_POST['wdf_settings']))
			$force = 1;

		$settings = get_option('wdf_settings');
		if(isset($wp_rewrite->extra_permastructs['funder']['struct']) || $force == 1) {
			if(!isset($settings['rewrite_match']) || $wp_rewrite->extra_permastructs['funder']['struct'] !== $settings['rewrite_match'] || $force == 1) {
				$wp_rewrite->flush_rules();
				$settings['rewrite_match'] = $wp_rewrite->extra_permastructs['funder']['struct'];
				update_option('wdf_settings',$settings);
			}
		}

	}
	function add_rewrite_rules($rules){
		$settings = get_option('wdf_settings');

		$permlink_front = $this->get_mu_front_permlink();

		$new_rules = array();

		// Archive Page Fix For Multi-Site Sub-Directory Installs
		$new_rules[$permlink_front.$settings['dir_slug'] . '/?$'] = 'index.php?post_type=funder';

		// Checkout Page
		$new_rules[$permlink_front.$settings['dir_slug'] . '/([^/]+)/' . $settings['checkout_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_checkout=1';

		// Thank You / Confirmation Page
		$new_rules[$permlink_front.$settings['dir_slug'] . '/([^/]+)/' . $settings['confirm_slug'] . '/?$'] = 'index.php?post_type=funder&name=$matches[1]&funder_confirm=1';

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
	function filter_nav_menu($list, $args = array()) {
		$settings = get_option('wdf_settings');
		global $wp_query;
		$archive_url = home_url($settings['dir_slug']);
		foreach($list as $key => $menu_item) {
			if( strstr($menu_item->url,$archive_url)) {
				if(isset($wp_query->query_vars['funder']) && $wp_query->is_single) {
					$list[$key]->current_item_ancestor = true;
					$list[$key]->current_item_parent = true;
					$list[$key]->classes[] = 'current-menu-ancestor';
					$list[$key]->classes[] = 'current-menu-parent';
				}
			}
		}
		return $list;
	}
	function template_redirect() {
		global $wp_query;

		if ($wp_query->query_vars['post_type'] == 'funder') {
			$funder_name = get_query_var('funder');
			$funder_id = (int) $wp_query->get_queried_object_id();
			$templates = array();
			$this->front_scripts($funder_id);

			if (isset($wp_query->query_vars['funder_checkout']) && $wp_query->query_vars['funder_checkout'] == 1) {
				$this->is_funder_checkout = true;
				if ( $funder_id )
					$templates[] = "wdf_checkout-$funder_id.php";
				if ( $funder_name )
					$templates[] = "wdf_checkout-$funder_name.php";
				$templates[] = "wdf_checkout.php";
				add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );
				}
			} elseif ( isset($wp_query->query_vars['funder_confirm']) && $wp_query->query_vars['funder_confirm'] == 1) {
				$this->is_funder_confirm = true;
				if ( $funder_id )
					$templates[] = "wdf_confirm-$funder_id.php";
				if ( $funder_name )
					$templates[] = "wdf_confirm-$funder_name.php";
				$templates[] = "wdf_confirm.php";
				add_filter( 'the_content', array(&$this,'funder_content'), 99 );
				if ($this->funder_template = locate_template($templates)) {
					add_filter( 'template_include', array(&$this, 'custom_funder_template') );
				}
			} elseif ($wp_query->is_single()) {
				$this->is_funder_single = true;

				if ( $funder_id )
					$templates[] = "wdf_funder-$funder_id.php";
				if ( $funder_name )
					$templates[] = "wdf_funder-$funder_name.php";
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

		//checks if single fundraiser widget is displayed
		$settings = get_option('wdf_settings');
		global $post;

		$fundraising_widget_active = 0;
		$fundraising_widget_options = get_option( 'widget_wdf_fundraiser_panel', array() );
		foreach ($fundraising_widget_options as $id => $value) {
			$widget_position = 0;
			$widget_position = is_active_widget( false, 'wdf_fundraiser_panel-'.$id, 'wdf_fundraiser_panel', true );

			if(!empty($widget_position) && $value['single_fundraiser'] == 0) {
				$fundraising_widget_active = 1;
				break;
			}
		}

		if(isset($this->is_funder_single) && $this->is_funder_single && $fundraising_widget_active == 0) {
			$position = get_post_meta($post->ID,'wdf_panel_pos',true);
			if($position == 'top')
				$content = wdf_fundraiser_page(false, $post->ID) . $content;
			else
				$content .= wdf_fundraiser_page(false, $post->ID);
		}

		if(isset($this->is_funder_checkout) && $this->is_funder_checkout) {
			$content = wdf_show_checkout( false, $post->ID, (isset($_POST['wdf_step']) ? $_POST['wdf_step'] : '')  );
		}

		if(isset($this->is_funder_confirm) && $this->is_funder_confirm) {
			$content = wdf_confirmation_page(false, $post->ID);
		}

		/*if($this->is_funder_activity)
			$content = wdf_activity_page(false,$post->ID);*/

		return $content;
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
				if ( class_exists($class) && !isset($plugin[3]) && $settings['active_gateways'][$code] == '1'  )
					$wdf_gateway_active_plugins[$code] = new $class;
			}

		}

		// Action used for saving gateway settings
		do_action('wdf_gateway_plugins_loaded');

	}
	function load_styles() {

		$style_dirs = array();

		$style_dirs[] = WDF_PLUGIN_BASE_DIR.'/styles/';

		if(defined('WDF_EXTERNAL_STYLE_DIRECTORY') && is_dir(WDF_EXTERNAL_STYLE_DIRECTORY))
			$style_dirs[] = WDF_EXTERNAL_STYLE_DIRECTORY;

		if(is_dir(WP_CONTENT_DIR . '/wdf-styles/'))
			$style_dirs[] = WP_CONTENT_DIR . '/wdf-styles/';

		$styles = array();

		foreach($style_dirs as $style_dir) {
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
									$name = apply_filters( 'wdf_custom_style_name', str_replace('.css','',$file), $file );
									$styles[$name] = $name;
								}
								break;
						}
				}
			}
		}
		$styles['wdf-custom'] = __('None (Custom CSS)','wdf');
		$this->styles = $styles;
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
        add_action('wp_footer', array(&$this,'inject_javascript'));
		// $add_style will always be used before a saved style.
		if($add_style != false) {
			wp_enqueue_style('wdf-style-'.$add_style);
		} else if(!empty($id) && $style = $this->get_style($id))
			wp_enqueue_style('wdf-style-'.$style);

	}
	function get_style( $post_id = '' ) {
		global $post;
		$settings = get_option('wdf_settings');
		$post_id = (empty($post_id) ? $post->ID : $post_id );

		if( $settings['single_styles'] == 'no' ) {
			$style = $settings['default_style'];
		} else {
			$meta = get_post_meta($post_id,'wdf_style',true);
			$style = ($meta != false ? $meta : $settings['default_style'] );
		}

		return $style;
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

    function inject_javascript() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                //Disable hover state for touch screen devices.
                var idx, idxs, ignore, rule, stylesheet, _i, _j, _k, _len, _len1, _len2, _ref, _ref1;

                if ('createTouch' in document) {
                    var ignore = /(.wdf_send_donation|input\[type="submit"\]):hover\b/;
                    try {
                        _ref = document.styleSheets;
                        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                            stylesheet = _ref[_i];
                            idxs = [];
                            _ref1 = stylesheet.cssRules;
                            if( !_ref1 || _ref1.length < 1 ){
                                continue;
                            }
                            for (idx = _j = 0, _len1 = _ref1.length; _j < _len1; idx = ++_j) {
                                rule = _ref1[idx];
                                if (rule.type === CSSRule.STYLE_RULE && ignore.test(rule.selectorText)) {
                                    idxs.unshift(idx);
                                }
                            }
                            for (_k = 0, _len2 = idxs.length; _k < _len2; _k++) {
                                idx = idxs[_k];
                                stylesheet.deleteRule(idx);
                            }
                        }
                    } catch (_error) {}
                }
            });
        </script>
    <?php
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
		unset($_SESSION['wdf_reward']);

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
		if( isset($_POST['wdf_reward']) && is_numeric($_POST['wdf_reward']) ) {
			$rewards = get_post_meta($_POST['funder_id'],'wdf_levels', true);
			$reward_amount = isset($rewards[$_POST['wdf_reward']]['amount']) ? $rewards[$_POST['wdf_reward']]['amount'] : 0;
			$reward_limit = isset($rewards[$_POST['wdf_reward']]['limit']) ? $rewards[$_POST['wdf_reward']]['limit'] : 0;
			if($reward_limit)
				$reward_left = $reward_limit - (isset($rewards[$_POST['wdf_reward']]['used']) ? $rewards[$_POST['wdf_reward']]['used'] : 0);
			else
				$reward_left = 1;

			if($reward_left && $_SESSION['wdf_pledge'] >= $reward_amount)
				$_SESSION['wdf_reward'] = $_POST['wdf_reward'] + 1;
			elseif(isset($_SESSION['wdf_reward']))
				unset($_SESSION['wdf_reward']);
		}

		if( isset($_POST['wdf_gateway']) && !empty($_POST['wdf_gateway']) )
			$_SESSION['wdf_gateway'] = $_POST['wdf_gateway'];
		if( isset($_POST['wdf_step']) && !empty($_POST['wdf_step']) )
			$_SESSION['wdf_step'] = $_POST['wdf_step'];


		if( isset($_POST['wdf_recurring']) && !empty($_POST['wdf_recurring']) ) {
			$_SESSION['wdf_recurring'] = $_POST['wdf_recurring'];
		}

		if( isset($_POST['wdf_bp_activity']) && $_POST['wdf_bp_activity'] == '1' )
			$_SESSION['wdf_bp_activity'] = true;

		$process_payment = false;
		global $wdf_gateway_active_plugins;
		$skip_gateway_form = (isset($_SESSION['wdf_gateway']) && method_exists($wdf_gateway_active_plugins[$_SESSION['wdf_gateway']], 'skip_form') ? $wdf_gateway_active_plugins[$_SESSION['wdf_gateway']]->skip_form() : (isset($_SESSION['wdf_gateway']) && isset($wdf_gateway_active_plugins[$_SESSION['wdf_gateway']]->skip_form) ? $wdf_gateway_active_plugins[$_SESSION['wdf_gateway']]->skip_form : false));

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

			if(!isset($this->wdf_error) || $this->wdf_error !== true) {
				do_action('wdf_gateway_pre_process_'.$_SESSION['wdf_gateway']);
				do_action('wdf_gateway_process_'.$_SESSION['wdf_type'].'_'.$_SESSION['wdf_gateway']);
			}
		}
		if(isset($this->is_funder_confirm) && $this->is_funder_confirm){
			$pledge_id = (isset($_SESSION['wdf_pledge_id']) ? $_SESSION['wdf_pledge_id'] : (isset($_REQUEST['pledge_id']) ? $_REQUEST['pledge_id'] : ''));
			if($pledge_id)
				$this->create_error(__('You have not made a pledge yet.','wdf'),'no_pledge');

			if(!$this->wdf_error)
				do_action('wdf_gateway_confirm_'.$_SESSION['wdf_gateway']);
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
    function filter_thank_you( $raw_msg = '', $trans = false, $is_for_mail = 0, $type = 'body') {
        if($trans !== false && !empty($raw_msg)) {
            $amount = isset($trans['recurring']) ? $trans['recurring_amount'] : $trans['gross'];
            $search = array( '%DONATIONTOTAL%', '%FIRSTNAME%', '%LASTNAME%' );
            $replace = array($this->format_currency('', $amount, $is_for_mail), $trans['first_name'], $trans['last_name']);

            $msg = str_replace($search, $replace, $raw_msg);
        }

        $msg = apply_filters( 'wdf_thank_you_message_' . $type, $msg, $trans, $is_for_mail, $raw_msg);

        return $msg;
    }
	function create_thank_you($funder_id = false, $trans = false) {

		if($send_email = get_post_meta($funder_id,'wdf_send_email',true) && $trans != false) {

			//remove any other filters
			//remove_all_filters( 'wp_mail_from' );
			//remove_all_filters( 'wp_mail_from_name' );

			//add our own filters
			//add_filter( 'wp_mail_from_name', create_function('', 'return get_bloginfo("name");') );
			//add_filter( 'wp_mail_from', create_function('', 'return get_option("admin_email")') );
            $msg = get_post_meta($funder_id,'wdf_email_msg', true);
            $msg = $this->filter_thank_you($msg, $trans, 1, 'body');
            $msg = html_entity_decode($msg);

            $subject = get_post_meta($funder_id,'wdf_email_subject',true);
            $subject = $this->filter_thank_you($subject, $trans, 1, 'subject');
            $subject = html_entity_decode($subject, ENT_QUOTES);

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
        $pledge_exists = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s", $post_title) );

        $donation = array();
        $donation['post_title'] = $post_title;
        $donation['post_name'] = $post_title;
        $donation['post_status'] = $status;
        $donation['post_parent'] = $funder_id;
        $donation['post_type'] = 'donation';

        if(!empty($pledge_exists) &&  $pledge_exists != false) {
            $donation['ID'] = $pledge_exists;
            $id = wp_update_post($donation);
        } else {
            //First time to see this pledge lets do some thank you operations
            $this->create_thank_you($funder_id,$transaction);
            $id = wp_insert_post($donation);
        }

        foreach($transaction as $k => $v) {
            if(!is_array($v))
                $transaction[$k] = esc_attr($v);
            else
                $transaction[$k] = array_map('esc_attr', $v);

        }
        update_post_meta($id, 'wdf_transaction', $transaction);
        update_post_meta($id,'wdf_native', '1');

        if(isset($transaction['reward'])) {
            $rewards = get_post_meta($funder_id,'wdf_levels', true);
            if(isset($rewards[$transaction['reward']-1]['used']) && is_numeric($rewards[$transaction['reward']-1]['used']))
                $rewards[$transaction['reward']-1]['used'] ++;
            else
                $rewards[$transaction['reward']-1]['used'] = 1;

            update_post_meta($funder_id,'wdf_levels', $rewards);
        }

        // Check and see if we have now hit our goal.
        if( $this->has_goal($funder_id) ){
            if( (int)$this->get_amount_raised($funder_id) >= (int)$this->get_goal_amount($funder_id) ) {
                $this->process_complete_funder( $funder_id );
            }
        }

        return $id;
    }
	function add_menu_meta_boxes() {
		$settings = get_option('wdf_settings');
		add_meta_box( 'add-funder', __('Fundraisers','wdf'), array(&$this,'meta_box_display'), 'nav-menus', 'side', 'default');
		?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
					$('#wdf_add_nav_archive').on('click', function() {
						<?php $funder_slug = $settings['dir_slug']; ?>
						wpNavMenu.addLinkToMenu('<?php echo trailingslashit(home_url($funder_slug)); ?>','Fundraisers');
						return false;
					});
				});
			</script>
		<?php
	}
	function add_meta_boxes() {
		global $post, $wp_meta_boxes, $typenow, $pagenow;

		$settings = get_option('wdf_settings');
		if($typenow == 'funder') {

			$wdf_type = get_post_meta($post->ID,'wdf_type',true);
			$wdf_type = ($wdf_type == false ? '' : $wdf_type);
			$has_pledges = $this->get_pledge_list($post->ID);

			/*
			if($post->post_status != 'publish' && $has_pledges == false)
				add_meta_box( 'wdf_type', sprintf(__('%s Type','wdf'),esc_attr($settings['funder_labels']['menu_name'])), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');

			if( $post->post_status == 'auto-draft' || $wdf_type == '' ) {
				remove_post_type_support('funder', 'editor');

				$meta_to_remove_normal = array('authordiv', 'categorydiv', 'commentstatusdiv', 'commentsdiv', 'formatdiv', 'postcustom', 'postexcerpt', 'postimagediv', 'revisionsdiv', 'submitdiv', 'trackbacksdiv', 'slugdiv');
				foreach ($meta_to_remove_normal as $value)
					remove_meta_box($value, 'funder', 'normal');
				$meta_to_remove_side = array('authordiv', 'categorydiv', 'commentstatusdiv', 'commentsdiv', 'formatdiv', 'postcustom', 'postexcerpt', 'postimagediv', 'revisionsdiv', 'submitdiv', 'trackbacksdiv', 'slugdiv');
				foreach ($meta_to_remove_side as $value)
					remove_meta_box($value, 'funder', 'side');
			}
			*/
			if($has_pledges == false)
				add_meta_box( 'wdf_type', sprintf(__('%s Type','wdf'),esc_attr($settings['funder_labels']['menu_name'])), array(&$this,'meta_box_display'), 'funder', 'side', 'high');

			if( $has_pledges != false || $post->post_status == 'publish' )
				add_meta_box( 'wdf_progress', sprintf(__('%s Progress','wdf'),esc_attr($settings['funder_labels']['singular_name'])), array(&$this,'meta_box_display'), 'funder', 'side', 'high');

			add_meta_box( 'wdf_options', sprintf(__('%s Settings','wdf'),esc_attr($settings['funder_labels']['singular_name'])), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
			add_meta_box( 'wdf_goals', sprintf(__('Set Your %s Goals','wdf'),esc_attr($settings['funder_labels']['singular_name'])), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');

			add_meta_box( 'wdf_messages', __('Thank You Message Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			// Show pledge activity if funds have been raised
			if( $has_pledges != false )
				add_meta_box( 'wdf_activity', sprintf(__('%s Activity','wdf'), esc_attr($settings['donation_labels']['singular_name'])), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');



		} elseif($typenow == 'donation') {
			add_meta_box( 'wdf_pledge_info', sprintf(__('%s Information','wdf'),esc_attr($settings['donation_labels']['singular_name'])), array(&$this,'meta_box_display'), 'donation', 'normal', 'high');
			add_meta_box( 'wdf_pledge_status', sprintf(__('%s Status','wdf'), esc_attr($settings['donation_labels']['singular_name'])), array(&$this,'meta_box_display'), 'donation', 'side', 'high');

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
		global $submenu;
		$settings = get_option('wdf_settings');

		add_submenu_page( 'edit.php?post_type=funder', $settings['donation_labels']['plural_name'], $settings['donation_labels']['plural_name'], 'wdf_manage_pledges', 'wdf_donations', array(&$this,'admin_display') );
		add_submenu_page( 'edit.php?post_type=funder', sprintf(__('%s Settings','wdf'), $settings['funder_labels']['menu_name']), __('Settings','wdf'), 'wdf_edit_settings', 'wdf_settings', array(&$this,'admin_display') );
		add_submenu_page( 'edit.php?post_type=funder', __('Getting Started','wdf'), __('Getting Started','wdf'), 'wdf_add_fundraisers', 'wdf', array(&$this,'admin_display') );
		if( isset($submenu['edit.php?post_type=funder']) && is_array($submenu['edit.php?post_type=funder']) ) {
			foreach($submenu['edit.php?post_type=funder'] as $key => $menu_item) {
				if($menu_item['2'] == 'wdf_donations')
					$submenu['edit.php?post_type=funder'][$key][2] = 'edit.php?post_type=donation';
			}
		}

	}

	function admin_display(){
		$content = '';

		switch($_GET['page']) {
			case 'wdf_settings' :
				if(!current_user_can('wdf_edit_settings'))
					wp_die(__('You are not allowed to view this page.','wdf'));

				global $wp_roles;
				include(WDF_PLUGIN_BASE_DIR . '/lib/form.blog_settings.php');
				break;
			default :
				if(!current_user_can('wdf_add_fundraisers'))
					wp_die(__('You are not allowed to view this page.','wdf'));

				include(WDF_PLUGIN_BASE_DIR . '/lib/form.blog_dashboard.php');
				break;
		}
	}
	function save_settings($new) {
		global $wp_roles;
		$die = false;

		if(isset($_POST['wdf_nonce'])) {
			$nonce = $_POST['wdf_nonce'];
		}
		if (!wp_verify_nonce($nonce,'_wdf_settings_nonce') ) {
			$this->create_error(__('Security Check Failed.  Whatchu doing??','wdf'), 'wdf_nonce');
			$die = true;
		}

		if(isset($new['user_caps'])) {
			unset($new['user_caps']['viewed']);
			$caps = $new['user_caps'];
			foreach($wp_roles->get_names() as $name => $obj) {
				if($name == 'administrator') continue;
				$role_obj = get_role($name);
				if($role_obj) {
					foreach($this->capabilities as $cap => $label) {
						if(isset($caps[$cap][$name])) {
							$role_obj->add_cap($cap);
							if($cap == 'wdf_manage_all_fundraisers' || $cap == 'wdf_add_fundraisers') {
								$role_obj->add_cap('read_funder');
								$role_obj->add_cap('edit_funder');
								$role_obj->add_cap('delete_funder');
							}
						} else {
							$role_obj->remove_cap($cap);
							if($cap == 'wdf_manage_all_fundraisers' || $cap == 'wdf_add_fundraisers') {
								$role_obj->remove_cap('read_funder');
								$role_obj->remove_cap('edit_funder');
								$role_obj->remove_cap('delete_funder');
							}
						}
					}
				}
			}
			unset($new['user_caps']);
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
			$this->create_msg(__('Settings Saved','wdf'), 'general');
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
		$content = 'echo "<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_filter('wdf_msg_' . $context, create_function('', $content));
		//add_action('wdf_msg_' . $context, create_function('', $content));
		$this->wdf_msg = true;
	}

	function wp_insert_post() {
		global $post;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if(!isset($_POST['wdf']) || !is_array($_POST['wdf']))
			return;

		if(isset($_POST['wdf']['levels']) && count($_POST['wdf']['levels']) < 2 && isset($_POST['wdf']['levels'][0]['amount']) && $_POST['wdf']['levels'][0]['amount'] == '')
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
						$value = wp_kses_post($value);
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
	function before_delete_post($post_id) {
		if(get_post_type($post_id) == 'donation') {
			$trans = $this->get_transaction($post_id);
			if(isset($trans['reward'])) {
				$parent = wp_get_post_parent_id($post_id);
				$rewards = get_post_meta($parent,'wdf_levels', true);
				$rewards[$trans['reward']-1]['used'] --;
				update_post_meta($parent,'wdf_levels', $rewards);
			}
		}
	}
	function enqueue_scripts() {
		wp_register_script( 'wdf-base', WDF_PLUGIN_URL . '/js/wdf-base.js', array('jquery'), $this->version, false );
		wp_register_style( 'jquery-ui-base', WDF_PLUGIN_URL . '/css/jquery-ui.css', null, null, 'screen' );
		if(is_array($this->styles) && !empty($this->styles)) {
			foreach($this->styles as $key => $label) {
				wp_register_style( 'wdf-style-'.$key, WDF_PLUGIN_URL . '/styles/'.$key.'.css', null, $this->version );
			}
		}
		global $wp_query;
		if ($wp_query->query_vars['post_type'] == 'funder') {
			$funder_id = (int) $wp_query->get_queried_object_id();
			$this->front_scripts($funder_id);
		}
	}
	function admin_enqueue_scripts($hook) {
		global $typenow, $pagenow, $wp_version;

		// Google external jQuery UI
		wp_register_style( 'jquery-ui-base', WDF_PLUGIN_URL . '/css/jquery-ui.css', null, null, 'screen' );
		wp_register_style( 'wdf-admin', WDF_PLUGIN_URL . '/css/wdf-admin.css', null, $this->version, 'all' );

		if ( $wp_version >= 3.8 ) {
			wp_register_style( 'wdf-mp6', WDF_PLUGIN_URL . '/css/wdf-mp6.css', null, $this->version, 'all' );
			wp_enqueue_style('wdf-mp6');
		}

		//Register Styles and Scripts For The Admin Area
		wp_register_script( 'wdf-post', WDF_PLUGIN_URL . '/js/wdf-post.js', array('jquery'), $this->version, false );
		wp_register_script( 'wdf-edit', WDF_PLUGIN_URL . '/js/wdf-edit.js', array('jquery'), $this->version, false );
		wp_register_script( 'wdf-media', WDF_PLUGIN_URL . '/js/wdf-media.js', array('jquery'), $this->version, true );
		wp_register_script( 'wdf-widget', WDF_PLUGIN_URL . '/js/wdf-widget.js', array('jquery'), $this->version, true );


		if($typenow == 'funder' || $pagenow == 'admin.php') {
			if($typenow == 'funder' || $_GET['page'] == 'wdf' || $_GET['page'] == 'wdf_settings')
				wp_enqueue_style('wdf-admin');
			if( $hook === 'post.php' || $hook === 'post-new.php') {
				wp_enqueue_style('jquery-ui-base');
				wp_enqueue_script('jquery-ui-progressbar');
				wp_enqueue_style('wdf-admin');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('wdf-post');

				$translation_array = array('title_remind' => __( 'Please enter title first.','wdf'));
				wp_localize_script( 'wdf-post', 'wdf', $translation_array );				
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

			if(isset($_GET['wdf_show_widgets']) && (int)$_GET['wdf_show_widgets'] == 1)
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
			$columns['title'] = sprintf(__('%s ID', 'wdf'),esc_attr($settings['donation_labels']['singular_name']));
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
				$reward = (isset($trans['reward'])) ? ' ('.__('Reward: ','wdf').$trans['reward'].')' : '';
				echo '<a href="'.get_edit_post_link($post->ID).'">'.$this->format_currency( $currency, $trans['gross'] ).$reward.'</a>';
				break;
            case 'pledge_recurring' :
                $trans = $this->get_transaction();
                echo $this->get_recurring_column_detail( $trans );
                break;
			case 'pledge_funder' :
				$parent = get_post($post->post_parent);
				echo $parent->post_title;
				break;
			case 'pledge_from' :
				$trans = $this->get_transaction();
				echo '<a href="mailto:'.$trans['payer_email'].'">'.esc_attr($trans['payer_email']).'</a>';
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
		if(isset($_GET['tab'])) {
			if($_GET['tab'] == 'fundraising' || $_GET['tab'] == 'donate_button' || $_GET['tab'] == 'progress_bar' || $_GET['tab'] == 'pledges') {
				$tabs = array();
				$tabs['donate_button'] = sprintf(__('%s Button','wdf'),esc_attr($settings['donation_labels']['singular_name']));
				$tabs['fundraising'] = sprintf(__('%s Form','wdf'),esc_attr($settings['funder_labels']['singular_name']));
				$tabs['pledges'] = sprintf(__('%s List','wdf'),esc_attr($settings['donation_labels']['plural_name']));
				$tabs['progress_bar'] = __('Progress Bar','wdf');
			}
		}

		return $tabs;
	}
	function media_fundraising() {
		wp_iframe(array(&$this, 'media_fundraiser_iframe'));
	}
	function media_pledges() {
		wp_iframe(array(&$this, 'media_pledges_iframe'));
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
			echo '<a title="'.__('Insert Fundraising Shortcodes','wdf').'" class="thickbox add_media" id="add_wdf" href="'.admin_url('media-upload.php?post_id='.$post->ID).'&tab=donate_button&TB_iframe=1&wdf=1"><img onclick="return false;" alt="'.__('Insert Fundraising Shortcodes','wdf').'" src="'.WDF_PLUGIN_URL.'/img/sm_ico.png"></a>';
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
		<form class="wdf_media_cont media-item" id="media_progress_bar">
			<h3 class="media-title"><?php _e('Add a progress bar','wdf'); ?></h3>
			<span class="description"><?php _e('Only fundraisers that have a goal can display a progress bar','wdf'); ?></span>
			<div id="media-items">
				<div class="media-item media-blank">
					<table class="describe">
						<tbody>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><?php echo sprintf(__('Choose a %s','wdf'),esc_attr($settings['funder_labels']['singular_name'])); ?></span>
								</th>
								<td>
									<?php if(!$funders || empty($funders)) : ?>
										<div class="message alert"><?php echo sprintf(__('You have not created any %s yet.','wdf'),esc_attr($settings['funder_labels']['plural_name'])); ?></div>
									<?php else : ?>
									<select id="wdf_funder_select" name="id">
										<option value="0"></option>
											<?php foreach($funders as $funder) { ?>
												<?php if(wdf_has_goal($funder->ID) != false) : ?>
													<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
												<?php endif; ?>
											<?php } ?>
									</select>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Show Title','wdf'); ?></label></span></th>
								<td><input type="checkbox" name="show_title" value="yes" /></td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Show Totals','wdf'); ?></label></span></th>
								<td><input type="checkbox" name="show_totals" value="yes" /></td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Choose a style','wdf'); ?></label></span></th>
								<td>
									<select name="style">
										<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
											<option value="0"><?php _e('Default','wdf'); ?></option>
											<?php foreach($this->styles as $key => $label) : ?>
												<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"></th>
								<td><p class="alignright"><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</form>

		<?php
	}
	function media_donate_button_iframe () {
		$settings = get_option('wdf_settings');
		media_upload_header(); ?>
		<style type="text/css">
			.wdf_button_type { display:none; }
			table.describe th.label { width:150px; }
		</style>
		<form class="wdf_media_cont" id="media_donate_button">
			<h3 class="media-title"><?php _e('Add a donation button','wdf'); ?></h3>
			<div id="media-items">
				<div class="media-item media-blank">
					<p class="media-types">
						<?php /*?><label class="description"><?php _e('Button Type','wdf'); ?></label><br/><?php */?>
						<label><input onChange="window.parent.wdf_input_switch(this); return false;" class="wdf_toggle" type="radio" name="button_type" value="default" rel="wdf_button_type"/> <?php _e('Default PayPal Button','wdf'); ?></label>
						<label><input onChange="window.parent.wdf_input_switch(this); return false;" class="wdf_toggle" type="radio" name="button_type" value="custom" rel="wdf_button_type"/> <?php _e('Custom Button','wdf');?></label>
					</p>
					<table class="describe">
						<tbody>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('Title','wdf') ?></label></span>
									<?php /*?><span class="alignright"><abbr id="status_img" title="required" class="required">*</abbr></span><?php */?>
								</th>
								<td class="field"><input type="text" name="title" value="" /></td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('Description','wdf') ?></label></span>
								</th>
								<td class="field"><textarea name="description"></textarea></td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php echo sprintf(__('%s Amount','wdf'),esc_attr($settings['donation_labels']['singular_name'])); ?><br /><span class="description"><?php _e('(blank = choice)','wdf'); ?></span></label></span>
								</th>
								<td class="field"><input type="text" name="donation_amount" value="" /></td>
							</tr>
							<tr class="wdf_button_type" rel="custom">
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('Choose a Style','wdf') ?></label></span>
								</th>
								<td class="field">
									<select name="style">
										<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
											<?php foreach($this->styles as $key => $label) : ?>
												<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</td>
							</tr>
							<tr class="wdf_button_type" rel="custom">
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('Button Text','wdf'); ?></label></span>
								</th>
								<td class="field"><input type="text" name="button_text" value="" /></td>
							</tr>
							<tr class="wdf_button_type" rel="default">
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('Paypal Options','wdf'); ?></label></span>
								</th>
								<td class="field">
									<p><label><input type="checkbox" name="show_cc" value="yes" /> <?php _e('Show Accepted Credit Cards','wdf'); ?></label></p>
									<p><label><input type="checkbox" name="allow_note" value="yes" /> <?php _e('Allow special instruction field','wdf'); ?></label></p>
									<p><label><input type="checkbox" name="small_button" value="yes" /> <?php _e('Use Small Button','wdf'); ?></label></p>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><label><?php _e('PayPal Email','wdf') ?><br /><span class="description"><?php _e('defaults to settings','wdf'); ?></span></label></span>
								</th>
								<td class="field">
									<input type="text" name="paypal_email" value="" />
								</td>
							</tr>
							<tr>
								<td></td>
								<td><p class="alignright"><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p></td>
							</tr>
						</tbody>
					</table>


				</div>
			</div>
		</form>
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
			<form class="wdf_media_cont media-item" id="media_fundraising">
			<h3 class="media-title"><?php printf(__('Add A %s Form','wdf'), $settings['funder_labels']['singular_name']); ?></h3>

			<div id="media-items">
				<div class="media-item media-blank">
					<table class="describe">
						<tbody>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><?php echo sprintf(__('Choose a %s','wdf'),esc_attr($settings['funder_labels']['singular_name'])); ?></span>
								</th>
								<td>
									<?php if(!$funders || empty($funders)) : ?>
										<div class="message alert"><?php echo sprintf(__('You have not created any %s yet','wdf'),esc_attr($settings['funder_labels']['plural_name'])); ?></div>
									<?php else : ?>
									<select id="wdf_funder_select" name="id">
										<option value="0"></option>
											<?php foreach($funders as $funder) { ?>
												<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
											<?php } ?>
									</select>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Choose a style','wdf'); ?></label></span></th>
								<td>
									<select name="style">
										<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
											<option value="0"><?php _e('Default','wdf'); ?></option>
											<?php foreach($this->styles as $key => $label) : ?>
												<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Show Title','wdf'); ?></label></span></th>
								<td><input type="checkbox" name="show_title" value="yes" /></td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Show Content','wdf'); ?></label></span></th>
								<td><input type="checkbox" name="show_content" value="yes" /></td>
							</tr>
							<tr>
								<th></th>
								<td><p class="alignright"><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			</form><?php
	}
	function media_pledges_iframe() {
			$content = '';
			$settings = get_option('wdf_settings');
			$args = array(
				'post_type' => 'funder',
				'numberposts' => -1,
				'post_status' => 'publish'
			);
			$funders = get_posts($args);
			media_upload_header();?>
			<form class="wdf_media_cont media-item" id="media_pledges">
			<h3 class="media-title"><?php printf(__('Add List of %s','wdf'), $settings['donation_labels']['plural_name']); ?></h3>

			<div id="media-items">
				<div class="media-item media-blank">
					<table class="describe">
						<tbody>
							<tr>
								<th valign="top" scope="row" class="label">
									<span class="alignleft"><?php echo sprintf(__('Choose a %s','wdf'),esc_attr($settings['funder_labels']['singular_name'])); ?></span>
								</th>
								<td>
									<?php if(!$funders || empty($funders)) : ?>
										<div class="message alert"><?php echo sprintf(__('You have not created any %s yet','wdf'),esc_attr($settings['funder_labels']['plural_name'])); ?></div>
									<?php else : ?>
									<select id="wdf_funder_select" name="id">
										<option value="0"><?php printf(__('All %s','wdf'), $settings['funder_labels']['plural_name']); ?></option>
											<?php foreach($funders as $funder) { ?>
												<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
											<?php } ?>
									</select>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php _e('Sort type','wdf'); ?></label></span></th>
								<td>
									<select name="sort_type">
										<option value="last"><?php _e('Latest first','wdf'); ?></option>
										<option value="top"><?php _e('Biggest first','wdf'); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th valign="top" scope="row" class="label"><span class="alignleft"><label><?php printf(__('Number of %s to show','wdf'),esc_attr($settings['donation_labels']['singular_name']) ); ?></label></span></th>
								<td><input type="number" name="number_pledges" min="1" value="7" /></td>
							</tr>
							<tr>
								<th></th>
								<td><p class="alignright"><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;"><?php _e('Insert Shortcode','wdf'); ?></a></p></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			</form><?php
	}


	function format_currency($currency = '', $amount = false, $for_mail = 0) {

		$settings = get_option('wdf_settings');

		if (!$currency || empty($currency))
			$currency = $settings['currency'];

		// get the currency symbol
		$symbol = $this->currencies[$currency][1];
		$symbol_mail = isset($this->currencies[$currency][2]) ? $this->currencies[$currency][2]: '';

		if(empty($symbol_mail) || $for_mail == 0) {
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
		}
		else {
			$settings['curr_symbol_position'] = 4;
			$symbol = $symbol_mail;
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
			$classes = ($total >= $goal ? 'wdf_goal wdf_complete' : 'wdf_goal');
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
		$post_id = ($post_id !== false ? $post_id : $post->ID);
		$args = array(
			'numberposts' => -1,
			'post_type' => 'donation',
			'post_status' => array('wdf_complete','wdf_approved')
		);
		if($post_id)
			$args['post_parent'] = $post_id;

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
				if($trans['gross'] && is_numeric($trans['gross'])) {
					$totals = $totals + $trans['gross'];
				}
			}
		}
		return apply_filters('wdf_get_amount_raised', round($totals));
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

    /**
     * Format PayPal's period format to readable text.
     *
     * For instance, '1 D' (or 'D' for backward compatibility) will be converted to 'Daily'.
     *
     * @since 2.6.1.3
     * @access public
     *
     * @param  string $cycle Cycle code. Expected values are 1 D, 1 W, 1 M, 1 Y.
     * @return string
     */
    function format_cycle( $cycle ){
        if( !$cycle || empty($cycle)) return '';

        $period = '';
        switch($cycle){
            case 'D':
            case '1 D':
                $period = __( 'Daily', 'wdf' );
                break;
            case 'W':
            case '1 W':
                $period = __( 'Weekly', 'wdf' );
                break;
            case 'M':
            case '1 M':
                $period = __( 'Monthly', 'wdf' );
                break;
            case 'Y':
            case '1 Y':
                $period = __( 'Yearly', 'wdf' );
                break;
        }

        return apply_filters( 'wdf_format_period', $period, $cycle );
    }

    /**
     * Parses transaction object and returns the recurring column detail text.
     *
     * @since 2.6.1.3
     * @access public
     *
     * @param  array $transaction Transaction object.
     * @return string
     */
    function get_recurring_column_detail( $transaction ){
        if(!isset($transaction['cycle'])){
            return 'Not Recurring';
        } else {
            $period = $this->format_cycle($transaction['cycle']);
            $currency = isset( $transaction['currency_code'] ) ? $transaction['currency_code'] : '';
            $recurring_amount = isset( $transaction['recurring_amount'] ) ? $transaction['recurring_amount'] : '';
            $detail_text = $this->format_currency( $currency, $recurring_amount ) . ' ' . $period;

            if( isset( $transaction['recurring_transactions'] ) ){
                $recurring_transactions = $transaction['recurring_transactions'] . ' ' . _n( 'payment completed', 'payments completed', $transaction['recurring_transactions'], 'wdf' );
                $detail_text .= '<br />' . $recurring_transactions;
            }
            return $detail_text;
        }

    }

	function get_transaction($post_id = false) {
		global $post;
		if(!$post_id)
			$post_id = $post->ID;

		return maybe_unserialize(get_post_meta($post_id,'wdf_transaction',true));

	}
	function the_select_options($array, $current) {
		if(empty($array))
			$array = array( 1 => 'True', 0 => 'False' );

		foreach( $array as $name => $label ) {
			$selected = selected( $current, $name, false );
			echo '<option value="'.$name.'" '.$selected.'>'.$label.'</option>';
		}
	}
	function get_mu_front_permlink($before = '', $after = '/', $force = 0) {
		if(is_main_site() && is_multisite()) {
			$settings = get_option('wdf_settings');
			if($settings['permlinks_front'] || $force == 1) {
				$mu_permlink = get_option('permalink_structure');
				$mu_permlink = explode('/',$mu_permlink);
				foreach($mu_permlink as $id => $part) {
					if(empty($part) || $part == '%postname%')
						unset($mu_permlink[$id]);
				}
				$mu_permlink = implode('/', $mu_permlink);
				if(!empty($mu_permlink))
					return $before.$mu_permlink.$after;
				else
					return '';
			}
		}
		else
			return '';
		
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
		 return $price;
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
	function after_setup_theme() {
		do_action('wdf_custom_template_functions');
		if(defined('WDF_CUSTOM_TEMPLATE_FUNCTIONS') && file_exists(WDF_CUSTOM_TEMPLATE_FUNCTIONS)) {
			require_once(WDF_CUSTOM_TEMPLATE_FUNCTIONS);
		} else {
			require_once( WDF_PLUGIN_BASE_DIR . '/lib/template-functions.php');
		}
	}
}

global $wdf;
$wdf = new WDF();


// Check for BuddyPress and boot up our component structure
if (defined('BP_VERSION') && version_compare( BP_VERSION, '1.5', '>' ) )
	require_once( WDF_PLUGIN_BASE_DIR . '/fundraiser-bp.php' );
?>
