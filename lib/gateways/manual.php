<?php
if(!class_exists('WDF_Gateway_Manual')) {
	class WDF_Gateway_Manual extends WDF_Gateway {
		
		// Private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = 'manual';
		
		// Name of your gateway, for the admin side.
		var $admin_name = 'Manual';
		
		// Public name of your gateway, for lists and such.
		var $public_name = 'Manual';
		
		// Whether or not ssl is needed for checkout page
		var $force_ssl = false;
		
		// An array of allowed payment types (simple, advanced)
		var $payment_types = 'simple';
		
		// If you are redirecting to a 3rd party make sure this is set to true
		var $skip_form = false;
		
		// Allow recurring payments with your gateway
		var $allow_reccuring = false;
		
		function on_creation() {
		}
		
		function payment_form() {
			$content = '';
			$content .= '<p class="wdf_manual_payment_form wdf_payment_form">';
			$content .= __('Please fill out all details','wdf').'<br/>';
			$content .= '<label for="first_name class="wdf_first_name">'.__('First Name','wdf').':</label><br />';
			$content .= '<input type="text" class="wdf_first_name" name="first_name" value="'.( isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : '' ).'" /><br />';
			$content .= '<label for="last_name class="wdf_last_name">'.__('Last Name','wdf').':</label><br />';
			$content .= '<input type="text" class="wdf_last_name" name="last_name" value="'.( isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : '' ).'" /><br />';
			$content .= '<label for="e-mail" class="wdf_email">'.__('E-mail','wdf').':</label><br />';
			$content .= '<input type="text" class="wdf_email" name="e-mail" value="'.( isset($_POST['e-mail']) ? esc_attr($_POST['e-mail']) : '') .'" />';
			$content .= '</p>';
			return $content;
		}

		function process_simple() {
			if(!empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['e-mail']) && preg_match("/^[-+\\.0-9=a-z_]+@([-0-9a-z]+\\.)+([0-9a-z]){2,4}$/i", $_POST['e-mail']) ) {
				$settings = get_option('wdf_settings');
				global $wdf;
				if($funder = get_post($_SESSION['funder_id']) ){
					$pledge_id = $wdf->generate_pledge_id();
					$funder_id = $_SESSION['funder_id'];
					$this->return_url =  wdf_get_funder_page('confirmation',$funder->ID);
				
					$_SESSION['wdf_pledge_id'] = $pledge_id;
					
					$settings = get_option('wdf_settings');
								
					$transaction = array();
		
					$transaction['gross'] = $_SESSION['wdf_pledge'];
					$transaction['type'] = 'simple';
					$transaction['currency_code'] = ( isset($settings['currency']) ? $settings['currency'] : 'USD');
					$transaction['first_name'] = (isset($_POST['first_name']) ? $_POST['first_name'] : '' );
					$transaction['last_name'] = (isset($_POST['last_name']) ? $_POST['last_name'] : '' );
					$transaction['payer_email'] = (isset($_POST['e-mail']) ? $_POST['e-mail'] : '' );
					$transaction['gateway_public'] = $this->public_name;
					$transaction['gateway'] = $this->plugin_name;
					$status = (isset($settings['manual']['status']) ? $settings['manual']['status'] : 'wdf_complete' );
					$transaction['status'] = __('Pending/Approved','wdf');
					$transaction['gateway_msg'] = __('Manual Payment.','wdf');

					if (isset($_SESSION['wdf_reward'])) {
						$transaction['reward'] = $_SESSION['wdf_reward'];
					}
				
					$wdf->update_pledge( $pledge_id, $funder_id, $status, $transaction);
					
					if(!headers_sent()) {
						wp_redirect($this->return_url);
						exit;
					}
				
				} else {
					$_POST['wdf_step'] = 'gateway';
					//No $_SESSION['funder_id'] was passed to this function.
					$this->create_gateway_error(__('Could not determine fundraiser','wdf'));
				}
			} else {
				$_POST['wdf_step'] = 'gateway';
				$this->create_gateway_error(__('Make sure all details are filled out correctly.','wdf'));
			}
			
			
		}
		function process_advanced() {
		}
		function confirm() {
		}
		function payment_info( $content, $transaction ) {
			$settings = get_option('wdf_settings');
			
			$content = '<div class="manual_transaction_info">';
			$content .= html_entity_decode($settings['manual']['after_info']);
			$content .= '</div>';
			return $content;
		}
		function handle_ipn() {
		}
		function execute_payment($type, $pledge, $transaction) {
		}
		
		function admin_settings() {
			$settings = get_option('wdf_settings');
		?>
			<table class="form-table">
				<tbody>
				<tr valign="top" >
					<th scope="row">
						<label for="wdf_settings[manual][status]"><?php echo __('Default status for payments','wdf'); ?></label>
					</th>
					<td><select name="wdf_settings[manual][status]">
							<option value="wdf_complete" <?php ( isset($settings['manual']['status']) ? selected($settings['manual']['status'],'wdf_complete') : '' ); ?>><?php _e('Complete','wdf'); ?></option>
							<option value="wdf_approved" <?php ( isset($settings['manual']['status']) ?  selected($settings['manual']['status'],'wdf_approved') : '' ); ?>><?php _e('Approved','wdf'); ?></option>
							<option value="wdf_canceled" <?php ( isset($settings['manual']['status']) ?  selected($settings['manual']['status'],'wdf_canceled') : '' ); ?>><?php _e('Canceled','wdf'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top" id="wdf_settings_manual_after_info">
					<th scope="row">
						<label for="wdf_settings[manual][after_info]"><?php echo __('Information displayed after pledging(you can HTML)','wdf'); ?></label>
					</th>
					<td>
						<textarea name="wdf_settings[manual][after_info]" class="widefat" rows="5"><?php echo ( isset($settings['manual']['after_info']) ?  $settings['manual']['after_info'] : '' ); ?></textarea>
					</td>
				</tr>
				</tbody>
			</table>
		<?php
		}
		function save_gateway_settings() {
			
			if( isset($_POST['wdf_settings']['manual']) ) {
				$new['manual']['after_info'] = htmlentities($_POST['wdf_settings']['manual']['after_info']);
				$statuses = array('wdf_complete', 'wdf_approved', 'wdf_canceled');
				if(in_array($_POST['wdf_settings']['manual']['status'], $statuses))
					$new['manual']['status'] = $_POST['wdf_settings']['manual']['status'];
				else
					$new['manual']['status'] = 'wdf_complete';
				
				$settings = get_option('wdf_settings');
				$settings = array_merge($settings,$new);
				update_option('wdf_settings',$settings);
			}
		}
		
	}
wdf_register_gateway_plugin('WDF_Gateway_Manual', 'manual', 'Manual', array('simple','standard','advanced'));
}
?>