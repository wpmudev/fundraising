<?php

if(!class_exists('WDF_Gateway')) {
	class WDF_Gateway {
		
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
			var $plugin_name = '';
			
		//name of your gateway, for the admin side.
			var $admin_name = '';
			
		//public name of your gateway, for lists and such.
			var $public_name = '';

		//whether or not ssl is needed for checkout page
			var $force_ssl = false;

		//only required for global capable gateways. The maximum stores that can checkout at once
			var $payment_types = array();
		
		// If you are redirecting to a 3rd party make sure this is set to true
			var $skip_form = false;
			
		// Allow recurring payments with your gateway
			var $allow_reccuring = false;
		
		function on_creation() {
			wp_die( __("You must override the on_creation() method in your payment gateway plugin!", 'wdf') );
		}
		function payment_form() {
			wp_die( __("You must override the payment_form() method in your payment gateway plugin!", 'wdf') );
		}
		function create_query() {
			wp_die( __("You must override the create_query() method in your payment gateway plugin!", 'wdf') );
		}
		function process_simple() {
			wp_die( __("You must override the process() method in your payment gateway plugin!", 'wdf') );
		}
		function process_standard() {
			wp_die( __("You must override the process_standard() method in your payment gateway plugin!", 'wdf') );
		}
		function process_advanced() {
			wp_die( __("You must override the process_advanced() method in your payment gateway plugin!", 'wdf') );
		}
		function execute_payment($type, $pledge, $transaction) {
			wp_die( __("You must override the execute_advanced_payment() method in your payment gateway plugin!", 'wdf') );
		}
		function payment_info( $content, $transaction ) {
			wp_die( __("You must override the payment_info() method in your payment gateway plugin!", 'wdf') );
		}
		function confirm() {
			wp_die( __("You must override the confirm() method in your payment gateway plugin!", 'wdf') );
		}
		function handle_ipn() {
			wp_die( __("You must override the handle_ipn() method in your payment gateway plugin!", 'wdf') );
		}
		function admin_settings() {
			wp_die( __("You must override the admin_settings() method in your payment gateway plugin!", 'wdf') );
		}
		function save_gateway_settings() {
			wp_die( __("You must override the save_gateway_settings() method in your payment gateway plugin!", 'wdf') );
		}
		function _payment_form_wrapper($content) {
			global $wdf;
			$pre = '<div class="wdf_payment_summary">';
			$pre .= sprintf( '<h4>'.__('Your pledge of %s is almost complete.','wdf').'</h4>',$wdf->format_currency('',$_SESSION['wdf_pledge']) /*. ' ' .($_SESSION['wdf_recurring'] != '0' ? 'every ' . $_SESSION['wdf_recurring'] : '')*/ );
			if($wdf->wdf_error == true) {
				$pre .= apply_filters('wdf_error_gateway','');
			}
			$pre .= '</div>';
			
			$new = apply_filters('wdf_pre_payment_form',$pre);
			$new .= '<form action="" method="post">';
			$new .= $content;
			$new .= '<input type="hidden" name="wdf_step" value="confirm" />';
				
			$new .= '<input type="submit" name="wdf_payment_submit" value="'.__('Complete Pledge','wdf').'" />';
			$new .= '</form>';
			
			return $new;
		}
		function _pre_process() {
			
			if( !isset($_SESSION['funder_id']) || !isset($_SESSION['wdf_pledge']) || !isset($_SESSION['wdf_gateway']) || !isset($_SESSION['wdf_step']) ) {
				$this->create_gateway_error(__('There was an unknown problem processing your payment.','wdf'));
			}
		}
		function create_gateway_error($msg) {
			global $wdf;
			$wdf->create_error($msg, 'gateway');
		}
		function WDF_Gateway() {
			$this->_construct();
  		}
		function _construct() {				
			
			
			$settings = get_option('wdf_settings');
			
			add_action('wdf_gateway_settings_form_'.$this->plugin_name, array(&$this,'admin_settings'));
			add_action('wdf_gateway_plugins_loaded', array(&$this,'save_gateway_settings'));
			
			add_action('wdf_gateway_pre_process_'.$this->plugin_name, array(&$this,'_pre_process'), 10);
			add_action('wdf_gateway_process_simple_'.$this->plugin_name, array(&$this,'process_simple'), 10);
			add_action('wdf_gateway_process_standard_'.$this->plugin_name, array(&$this,'process_standard'), 10);
			add_action('wdf_gateway_process_advanced_'.$this->plugin_name, array(&$this,'process_advanced'), 10);
			add_action('wdf_gateway_confirm_'.$this->plugin_name, array(&$this, 'confirm'), 10);
			add_action('wdf_execute_payment_'.$this->plugin_name, array(&$this, 'execute_payment'), 10, 3);
			
			//Handle all our Instant Notifcations
			$this->ipn_url = admin_url('admin-ajax.php?action=wdf-ipn-return-'.$this->plugin_name);
			add_action( 'wp_ajax_nopriv_wdf-ipn-return-'.$this->plugin_name, array(&$this,'handle_ipn') );
			
			// This is the gateway form
			add_filter('wdf_checkout_payment_form_'.$this->plugin_name, array(&$this,'payment_form'), 10);
			// This is the gateway form wrapper.  Notice the priorities
			add_filter('wdf_checkout_payment_form_'.$this->plugin_name, array(&$this,'_payment_form_wrapper'), 20);
			add_filter('wdf_gateway_payment_info_' . $this->plugin_name, array(&$this,'payment_info'), 10, 2 );
			$this->on_creation();
		}
	}
}
/**
 * Use this function to register your gateway plugin class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $plugin_name - the sanitized private name for your plugin
 * @param string $admin_name - pretty name of your gateway, for the admin side.
 * @param array $payment_types - Array of allowed payment types for this gateway: Use 'simple' 'standard' 'advanced'
 */
function wdf_register_gateway_plugin($class_name, $plugin_name, $admin_name, $payment_types) {
  global $wdf_gateway_plugins;
  
  if (!is_array($wdf_gateway_plugins)) {
		$wdf_gateway_plugins = array();
	}
	
	if (class_exists($class_name)) {
		$wdf_gateway_plugins[$plugin_name] = array($class_name, $admin_name, $payment_types);
	} else {
		return false;
	}
}
?>