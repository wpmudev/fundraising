<?php
/*
 *Network Settings Class
 *
 */

class WDF_MS {
	
	function _construct() {
		add_action( 'network_admin_menu', array(&$this,'wdf_network_admin_menu') );
	}

	function wdf_network_admin_menu() {
		add_submenu_page('settings.php', 'Donations', 'Donations', 'manage_options', 'wdf-ms-settings', array(&$this,'wdf_network_admin_display'));
	}
	function wdf_network_admin_display() {
		wp_enqueue_style('wdf-admin');
		if(!current_user_can('manage_options'))
			wp_die(__('You lack sufficient privledges to view this page','wdf'));
			
			//$settings = get_option('wdf_settings'); ?>
			<div class="wrap column-2">
            	<div id="icon-wdf-admin" class="icon32"><br></div>
                <h2><?php echo __('Network Donation Options','wdf'); ?></h2>
                 <form id="wdf-ms-settings">
                 	<div id="" class="metabox-holder has-right-sidebar">
                    
                    </div>
                 </form>
            </div>
            
            <?php
	}
	function WDF_MS() {
		$this->_construct();
	}
	
}?>