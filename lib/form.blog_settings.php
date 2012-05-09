<?php
$settings = get_option('wdf_settings');
				
if (!class_exists('WpmuDev_HelpTooltips')) require_once WDF_PLUGIN_BASE_DIR . '/lib/external/class.wd_help_tooltips.php';
	$tips = new WpmuDev_HelpTooltips();
	$tips->set_icon_url(WDF_PLUGIN_URL.'/img/information.png');
	
	$tabs = array(
		'payments' => __('Payments','wdf'),
		'presentation' => __('Presentation','wdf'),
		'other' => __('Other','wdf'),
	);
	if( defined('WDF_ALLOW_RESET') && WDF_ALLOW_RESET == true )
		$tabs['reset'] = __('Reset','wdf');
	
	if(!isset($_GET['tab']))
		$active_tab = 'payments';
	else
		$active_tab = $_GET['tab'];
	
	$tabs = apply_filters('wdf_settings_tabs',$tabs);
	$active_tab = apply_filters('wdf_settings_active_tab',$active_tab);
	
?>
<div class="wrap">
	<div id="icon-wdf-admin" class="icon32"><br></div>
		<h2><?php echo $settings['funder_labels']['menu_name'] . __(' Settings','wdf') ?></h2>
		<?php do_action('wdf_msg_general');?>
		<form action="" method="post" id="wdf_settings_<?php echo $active_tab ?>" class="nav-tabs">
			<input type="hidden" name="wdf_nonce" value="<?php echo wp_create_nonce('_wdf_settings_nonce');?>" />
			<h3 class="nav-tab-wrapper">
				<?php foreach($tabs as $k => $v) : ?>
					<a class="nav-tab <?php echo ($active_tab == $k ? 'nav-tab-active' : '') ?>" href="<?php echo admin_url('edit.php?post_type=funder&page=wdf_settings&tab='.$k); ?>" rel="#tab_<?php echo $k ?>"><?php echo $v ?></a>
				<?php endforeach; ?>
			</h3>
			<?php echo apply_filters('wdf_error_wdf_nonce',''); ?>
				
				<div>
					<?php switch($active_tab) {
						
						case 'presentation' : ?>
							<table class="form-table" id="wdf_label_settings">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Plugin Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[funder_labels][menu_name]" value="<?php echo esc_attr($settings['funder_labels']['menu_name']); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							<h3><?php _e('Supporter Labels','wdf'); ?><?php echo $tips->add_tip(__('Used to describe someone who makes a payment','wdf')); ?></h3>
							<table class="form-table" id="wdf_label_settings">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Single Supporter','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][backer_single]" value="<?php echo esc_attr($settings['donation_labels']['backer_single']); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Multiple Supporters','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][backer_plural]" value="<?php echo esc_attr($settings['donation_labels']['backer_plural']); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							
							<h3><?php _e('Level / Reward Labels','wdf'); ?><?php echo $tips->add_tip(__('This is used to describe suggested payment levels.','wdf')); ?></h3>
							<table class="form-table" id="wdf_label_settings">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Singlular Level','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[funder_labels][singular_level]" value="<?php echo esc_attr($settings['funder_labels']['singular_level']); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Plural Level','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[funder_labels][plural_level]" value="<?php echo esc_attr($settings['funder_labels']['plural_level']); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
							<h3><?php _e('Fundraiser Labels','wdf'); ?><?php echo $tips->add_tip(__('Each fundraising project will be labeled with these.','wdf')); ?></h3>
							<table class="form-table" id="wdf_label_settings">
								<tbody>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Singular Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[funder_labels][singular_name]" value="<?php echo esc_attr($settings['funder_labels']['singular_name']); ?>" />
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Plural Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[funder_labels][plural_name]" value="<?php echo esc_attr($settings['funder_labels']['plural_name']); ?>" />
										</td>
									</tr>
									
								</tbody>
							</table>
							
							<h3><?php _e('Pledge Labels','wdf'); ?><?php echo $tips->add_tip(__('','wdf')); ?></h3>
							<table class="form-table" id="wdf_label_settings">
								<tbody>
									<?php /*?><tr valign="top">
										<th scope="row">
											<label><?php _e('Menu Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][menu_name]" value="<?php echo esc_attr($settings['donation_labels']['menu_name']); ?>" />
										</td>
									</tr><?php */?>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Singular Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][singular_name]" value="<?php echo esc_attr($settings['donation_labels']['singular_name']); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Plural Name','wdf'); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][plural_name]" value="<?php echo esc_attr($settings['donation_labels']['plural_name']); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Action Name','wdf'); ?><?php echo $tips->add_tip(__('This is describing the action of making a payment.  It is used as a call to action for making a payment.','wdf')); ?></label>
										</th>
										<td>
											<input type="text" name="wdf_settings[donation_labels][action_name]" value="<?php echo esc_attr($settings['donation_labels']['action_name']); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
						
							<h3><?php _e('Permalink Settings','wdf'); ?></h3>
							<table class="form-table" id="wdf_permalink_settings">
								<tbody>
									
									<?php if(!get_option('permalink_structure')) : ?>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Fundraising Permalink Structure','wdf'); ?></label>
										</th>
										<td>
											<div class="error below-h2"><p><?php _e('You Need To Setup Your Permalink Structure Before Setting Your Donations Slugs','wdf'); ?></p></div>
										</td>
									</tr>
									
									<?php else : ?>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Fundraising Directory Location','wdf'); ?></label>
										</th>
										<td>
											<span class="code"><?php echo home_url(); ?>/</span><input id="wdf_dir_slug" type="text" name="wdf_settings[dir_slug]" value="<?php echo esc_attr($settings['dir_slug']); ?>" />
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Checkout Page','wdf'); ?></label>
										</th>
										<td>
											<span class="code"><?php echo home_url().'/'.$settings['dir_slug'].'/{The Fundraiser\'s Name}/'; ?></span><input id="wdf_checkout_slug" type="text" name="wdf_settings[checkout_slug]" value="<?php echo esc_attr($settings['checkout_slug']); ?>" />
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Thank You Page','wdf'); ?></label>
										</th>
										<td>
											<span class="code"><?php echo home_url().'/'.$settings['dir_slug'].'/{The Fundraiser\'s Name}/'; ?></span><input id="wdf_confirm_slug" type="text" name="wdf_settings[confirm_slug]" value="<?php echo esc_attr($settings['confirm_slug']); ?>" />
										</td>
									</tr>
									
									<?php /*?><tr valign="top">
										<th scope="row">
											<label><?php _e('Activity Page','wdf'); ?></label>
										</th>
										<td>
											<span class="code"><?php echo home_url().'/'.$settings['dir_slug'].'/{The Fundraiser\'s Name}/'; ?></span><input id="wdf_activity_slug" type="text" name="wdf_settings[activity_slug]" value="<?php echo esc_attr($settings['activity_slug']); ?>" />
										</td>
									</tr><?php */?>
								</tbody>
							</table>
							<h3><?php _e('Style Settings','wdf'); ?></h3>
							<table class="form-table">
								<tbody>											
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Choose a default display style','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[default_style]" id="wdf_default_style">
												<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
													<?php foreach($this->styles as $key => $label) : ?>
														<option <?php selected($settings['default_style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
													<?php endforeach; ?>
												<?php endif; ?>
											</select>
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Allow per fundraiser styles','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[single_styles]">
												<option value="no" <?php selected($settings['single_styles'],'no') ?>><?php _e('No','wdf'); ?></option>
												<option value="yes" <?php selected($settings['single_styles'],'yes') ?>><?php _e('Yes','wdf'); ?></option>
											</select><?php echo $tips->add_tip(__('Allowing this option will allow each fundraiser to override your site\'s default styles','wdf')); ?>
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Choose a checkout style','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[checkout_type]">
												<option value="2" <?php selected($settings['checkout_type'],'2') ?>><?php _e('Use elaborated checkout page','wdf'); ?></option>
												<option value="1" <?php selected($settings['checkout_type'],'1') ?>><?php _e('Checkout directly from panel','wdf'); ?></option>
											</select><?php echo $tips->add_tip(__('An eloborated checkout page allows backers to view additional information about your fundraiser.  Checking out from the fundraising panel will begin the payment process immediately','wdf')); ?>
										</td>
									</tr>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Custom CSS','wdf'); ?></label><?php echo $tips->add_tip(__('CSS styles saved in this box will be loaded on any page containing fundraising content. Do not use '.esc_js('<style>').' tags in this box.','wdf')); ?>
										</th>
										<td>
											<textarea rows="15" class="widefat" id="wdf_custom_css" name="wdf_settings[custom_css]"><?php echo esc_attr($settings['custom_css']); ?></textarea>
										</td>
									</tr>
									
									<?php endif; ?>
								</tbody>
							</table>
								<?php break;
									
						case 'other' : ?>
						
							<h3><?php _e('Other Settings','wdf'); ?></h3>
							<table class="form-table">
								<tbody>
									
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Add fundraising directory to menu?','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[inject_menu]">
												<option value="no" <?php selected($settings['inject_menu'],'no') ?>>No</option>
												<option value="yes" <?php selected($settings['inject_menu'],'yes') ?>>Yes</option>
											</select><?php echo $tips->add_tip(__('This option will only work for page menus not custom theme menus','wdf')); ?>
										</td>
									</tr>
								
								</tbody>
							</table>
						
						<?php break;
						
						case 'payments' : ?>
							<h3><?php _e('Currency Settings','wdf'); ?></h3>
							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Set Your Currency','wdf'); ?></label>
										</th>
										<td>
											<select id="wdf_settings_currency" name="wdf_settings[currency]">
											
												<?php foreach ($this->currencies as $key => $value) { ?>
													<option value="<?php echo $key; ?>"<?php selected($settings['currency'], $key); ?>><?php echo esc_attr($value[0]) . ' - ' . $this->format_currency($key); ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Currency Symbol Position', 'wdf') ?></label>
										</th>
										<td>
										<label><input value="1" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 1); ?>>
											<?php echo $this->format_currency($settings['currency']); ?>100</label><br />
											<label><input value="2" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 2); ?>>
											<?php echo $this->format_currency($settings['currency']); ?> 100</label><br />
											<label><input value="3" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 3); ?>>
											100<?php echo $this->format_currency($settings['currency']); ?></label><br />
											<label><input value="4" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 4); ?>>
											100 <?php echo $this->format_currency($settings['currency']); ?></label>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Show Decimal in Prices', 'wdf') ?></label>
										</th>
										<td>
										<label><input value="1" name="wdf_settings[curr_decimal]" type="radio"<?php checked( ( ($settings['curr_decimal'] !== 0) ? 1 : 0 ), 1); ?>>
												<?php _e('Yes', 'wdf') ?></label>
												<label><input value="0" name="wdf_settings[curr_decimal]" type="radio"<?php checked($settings['curr_decimal'], 0); ?>>
												<?php _e('No', 'wdf') ?></label>
										</td>
									</tr>
								</tbody>
							</table>
							<h3><?php _e('Allowed Fundraiser Types','wdf'); ?></h3>
		
							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Simple Donations','wdf'); ?></label>
											<input type="hidden" name="wdf_settings[payment_types]" value="" />
										</th>
										<td>
											<input class="wdf_auto_submit" type="checkbox" name="wdf_settings[payment_types][]" value="simple" <?php checked( in_array( 'simple', $settings['payment_types'] ), true ); ?> />
											<?php //echo $tips->add_tip(__('Simple donations only require a valid paypal email address.','wdf')); ?>
										</td>
									</tr>
									<?php /*?><tr valign="top">
										<th scope="row">
											<label><?php _e('Standard Fundraising','wdf'); ?></label>
										</th>
										<td>
											<input class="wdf_auto_submit" type="checkbox" name="wdf_settings[payment_type][]" value="standard" <?php checked( in_array( 'standard', $settings['payment_types']), true ); ?> />
											<?php echo $tips->add_tip(__('Standard Fundraising','wdf')); ?>
										</td>
									</tr><?php */?>
									<tr valign="top">
										<th scope="row">
											<label><?php _e('Advanced Crowdfunding','wdf'); ?></label>
										</th>
										<td>
											<input class="wdf_auto_submit" type="checkbox" name="wdf_settings[payment_types][]" value="advanced" <?php checked( in_array( 'advanced', $settings['payment_types'] ), true); ?> id="wdf_allowed_fundraier_types" />
											<?php echo $tips->add_tip(__('Crowdfunding allows you to create fundraisers with Goals & Rewards.  Payments for advanced crowdfunding are not processed until the goal has been reached.','wdf')); ?>
										</td>
									</tr>
								</tbody>
							</table>
							
							<?php if(isset($settings['payment_types']) && !empty($settings['payment_types']) ) : ?>
								<?php global $wdf_gateway_plugins, $wdf_gateway_active_plugins; ?>
								<?php if( is_array($wdf_gateway_plugins) ): ?>
									<h3><?php _e('Available Payment Gateways'); ?></h3>
									<table class="form-table">
										<tbody>
											<?php foreach( $wdf_gateway_plugins as $gateway => $data) : ?>
												<?php $flag = false; ?>
												<?php foreach($data[2] as $type) {
													if( in_array($type, $settings['payment_types']) )
														$flag = true;
												} ?>
												<?php if($flag != false) : ?>
												<tr valign="top">
													<th scope="row">
														<label for="wdf_settings_gateway_<?php echo $gateway; ?>"><span class="title"><?php echo $data[1] ?></span></label>
													</th>
													<td>
														<input type="hidden" name="wdf_settings[active_gateways][<?php echo $gateway ?>]" value="0" />
														<input class="gateway_switch wdf_auto_submit" type="checkbox" id="wdf_active_gateway_<?php echo $gateway; ?>" name="wdf_settings[active_gateways][<?php echo $gateway ?>]" value="1" <?php checked($settings['active_gateways'][$gateway],'1'); ?> />
													</td>
												</tr>
												<?php endif; ?>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php endif; ?>
								
								<?php if( is_array($wdf_gateway_active_plugins) ) : ?>
									<?php foreach( $wdf_gateway_active_plugins as $gateway => $data) : ?>
										<?php if( isset( $settings['active_gateways'][$gateway]) ) : ?>
											<h3><?php echo $data->admin_name ?> <?php _e('Settings','wdf'); ?></h3>
											<?php do_action('wdf_gateway_settings_form_'.$gateway); ?>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							
							<?php endif; ?>
						<?php break;
						
						case 'reset' : ?>
							
							<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row">
												<label><span class="title"><?php _e('Reset your installation to the default settings?','wdf'); ?></span></label>
											</th>
											<td>
												<input type="submit" class="button" name="wdf_reset" id="wdf_reset" value="Reset Data" />
											</td>
										</tr>
									</tbody>
							</table>
							
						
						<?php break;
						case 'default' :
							
							do_action('wdf_settings_custom_tab_'.$k,$settings);	
						
							break;
					
					} ?>
					
				</div>			
				<p><input type="submit" value="Save Changes" class="button-primary" name="save_settings" /></p>
			</form>
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			$('#wdf_reset').click( function(e) {
				var check = confirm("Are you sure you want to do this?  You will lose all the data associated with your fundraisers donations and settings!");
				if (check == true)  {
					return true;
				} else {
					return false;
				}
			});
			$('input.wdf_auto_submit').change(function(e) {
				$(this).parents('form').trigger('submit');
				return false;
			});
		});
	</script>
</div>