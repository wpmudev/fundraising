<?php 
//Setup tooltips for all metaboxes
if (!class_exists('WpmuDev_HelpTooltips')) require_once WDF_PLUGIN_BASE_DIR . '/lib/external/class.wd_help_tooltips.php';
	$tips = new WpmuDev_HelpTooltips();
	$tips->set_icon_url(WDF_PLUGIN_URL.'/img/information.png');

// Setup $meta for all metaboxes
$meta = get_post_custom($post->ID);
$settings = get_option('wdf_settings');
//pull out the meta_box id and pass it through a switch instead of using individual functions
switch($data['id']) {
	
	///////////////////////////
	// PLEDGE STATUS METABOX //
	///////////////////////////
	case 'wdf_pledge_status' : ?>
		
		<?php $trans = $this->get_transaction($post->ID); ?>
		<label><?php _e('Gateway Status','wdf'); ?>: <?php echo $trans['status']; ?></label>
		<p>
			<label>Pledge Status</label><br />
			<select class="widefat" name="post_status">
				<option value="wdf_complete" <?php selected($post->post_status,'wdf_complete'); ?>><?php _e('Complete','wdf'); ?></option>
				<option value="wdf_approved" <?php selected($post->post_status,'wdf_approved'); ?>><?php _e('Approved','wdf'); ?></option>
				<option value="wdf_refunded" <?php selected($post->post_status,'wdf_refunded'); ?>><?php _e('Refunded','wdf'); ?></option>
				<option value="wdf_canceled" <?php selected($post->post_status,'wdf_canceled'); ?>><?php _e('Canceled','wdf'); ?></option>
			</select>
		</p>
		<p><input type="submit" class="button-primary" value="Save Pledge" /></p>
		<?php break;
	///////////////////////////
	// PLEDGE INFO METABOX //
	///////////////////////////
	case 'wdf_pledge_info' : 
		
		$trans = $this->get_transaction($post->ID);
		
		
		if($meta['wdf_native'][0] !== '1') : ?>
			<?php $funders = get_posts(array('post_type' => 'funder', 'numberposts' => -1, 'post_status' => 'publish')); ?>
			<?php if(!$funders) : ?>
				<div class="error below-h2"><p style="width: 100%;"><?php echo __('You have not made any fundraisers yet.  You must create a fundraiser to make a pledge to.','wdf') ?></p></div>
			<?php else : ?>
				<input type="hidden" name="post_title" value="Manual Payment" />
				<input type="hidden" name="wdf[transaction][status]" value="Manual Payment" />
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">
								<label><?php echo __('Choose The Fundraiser','wdf') ?></label>
							</th>
							<td>
								<p>
									<select name="post_parent">
									<?php foreach($funders as $funder) : ?>
										<option <?php selected($post->post_parent,$funder->ID); ?> value="<?php echo $funder->ID ?>"><?php echo $funder->post_title; ?></option>
									<?php endforeach; ?>
									</select>
								</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label><?php _e('First & Last Name','wdf'); ?></label>
							</th>
							<td>
								<p><input type="text" name="wdf[transaction][name]" value="<?php echo $trans['first_name'] . ' ' . $trans['last_name']; ?>" /></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label><?php _e('Email Address','wdf'); ?></label>
							</th>
							<td>
								<p><input type="text" name="wdf[transaction][payer_email]" value="<?php echo $trans['payer_email']; ?>" /></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label><?php _e('Donation Amount','wdf'); ?></label>
							</th>
							<td>
								<p><input type="text" name="wdf[transaction][gross]" value="<?php echo $trans['gross']; ?>" /></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label><?php _e('Payment Source','wdf'); ?>:</label>
							</th>
							<td>
								<select name="wdf[transaction][gateway]">
									<?php global $wdf_gateway_plugins; ?>
									<?php foreach($wdf_gateway_plugins as $name => $plugin) : ?>
										<option value="<?php echo $name; ?>"><?php echo $plugin[1]; ?></option>
									<?php endforeach; ?>
									<option value="manual"><?php _e('Check/Cash','wdf'); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>
		<?php else : ?>
			<?php $parent = get_post($post->post_parent); ?>
			<?php if($parent) : ?>
				<h3><?php _e('Fundraiser','wdf'); ?>:</h3><p><a href="<?php echo get_edit_post_link($parent->ID); ?>"><?php echo $parent->post_title; ?></a></p>
			<?php else : ?> 
				<?php $donations = get_posts(array('post_type' => 'funder', 'numberposts' => -1, 'post_status' => 'publish')); ?>
				<p>
					<?php if(!$donations) : ?>
						<label><?php echo sprintf( __('You have not made any %s yet.','wdf'), esc_attr($settings['funder_labels']['plural_name']) ); ?></label>
					<?php else : ?>
						<label><?php echo sprintf( __('Not attached to any %s please choose one','wdf'), esc_attr($settings['funder_labels']['singular_name']) ); ?></label>
						<select name="post_parent">
						<?php foreach($donations as $donation) : ?>
							<option value="<?php echo $donation->ID ?>"><?php echo $donation->post_title; ?></option>
						<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</p>
			<?php endif; ?>
				<?php $trans = $this->get_transaction(); ?>
				<h3><?php _e('From','wdf'); ?>:</h3><p><label><strong><?php echo __('Name:','wdf'); ?> </strong></label><?php echo $trans['first_name'] . ' ' . $trans['last_name']; ?></p><p><label><strong><?php echo __('Email:','wdf'); ?> </strong></label><?php echo $trans['payer_email']; ?></p>
				<h3><?php _e('Amount Donated','wdf'); ?>:</h3>
				<?php if($trans['recurring'] == 1) :?>
					<p><?php echo $this->format_currency($trans['currency_code'],$trans['gross']); ?> every <?php echo $trans['cycle']; ?></p>
				<?php else: ?>
					<p><?php echo $this->format_currency($trans['currency_code'],$trans['gross']); ?></p>
				<?php endif; ?>
				<?php if( isset($trans['gateway_public']) ) : ?><h3><?php _e('Payment Source','wdf'); ?>:</h3><p><?php echo esc_attr($trans['gateway_public']); ?></p><?php endif; ?>
				<?php if( isset($trans['gateway_msg']) ) : ?><h3><?php _e('Last Gateway Activity','wdf'); ?>:</h3><p><?php echo esc_attr($trans['gateway_msg']); ?></p><?php endif; ?>
				<?php if( isset($trans['ipn_id']) ) : ?><h3><?php _e('Transaction ID','wdf'); ?>:</h3><p><?php echo esc_attr($trans['ipn_id']); ?></p><?php endif; ?>
		<?php endif; ?>
	<?php break;

	/////////////////////
	// FUNDER PROGRESS //
	/////////////////////
	case 'wdf_progress' : ?>

		<?php if($this->has_goal($post->ID)) : ?>
			<?php if(strtotime($meta['wdf_goal_start'][0]) > time()) : ?>
				<div class="below-h2 updated"><p><?php echo __('Your ','wdf') . esc_attr($settings['funder_labels']['singular_name']); ?><?php wdf_time_left(true,$post->ID); ?></p></div>
			<?php endif; ?>
			<?php echo $this->prepare_progress_bar($post->ID,null,null,'admin_metabox',true); ?>
		<?php else : ?>
			<label><?php _e('Amount Raised So Far','wdf'); ?></label><br /><span class="wdf_bignum"><?php echo $this->format_currency('',$this->get_amount_raised($post->ID)); ?></span>
		<?php endif; ?>
		
		<?php break;
		
	/////////////////////////
	// FUNDER TYPE METABOX //
	/////////////////////////
	case 'wdf_type' : 
		$settings = get_option('wdf_settings');	?>
		
		<div id="wdf_type">
			<?php if( isset($settings['payment_types']) && is_array($settings['payment_types']) && count($settings['payment_types']) >= 1 ) : ?>
				<?php foreach($settings['payment_types'] as $name) : ?>
					<?php 
						if($name == 'simple') {
							$label = __('Simple Donations: ','wdf');
							$description = __('Allows for a simple continuous donations with no Goals or Rewards','wdf');
						} elseif($name == 'advanced') {
							$label = __('Advanced Crowdfunding: ','wdf');
							$description = __('Set fundraising goals and rewards.  Pledges are only authorized and payments are not processed until your goal is reached.','wdf');
						} else {
							$label = '';
							$description = '';
						}
						// Some filters incase your trying to make new available types
						$label = apply_filters('wdf_funder_type_label', $label, $name);
						$description = apply_filters('wdf_funder_type_description', $description, $name);
					?>
					<?php // if(!isset($meta['wdf_type'][0]) || empty($meta['wdf_type'][0])) : ?>
						
						<?php //if(isset($settings['payment_types']) && count($settings['payment_types']) >= 1 ) : ?>
							<?php //if(count($settings['payment_types']) > 1) : ?>
								<h3>
									<label><span class="description"><?php echo $label; ?></span></label>
									<div style="float:right;"><input name="wdf[type]" type="radio" value="<?php echo $name; ?>" <?php checked($meta['wdf_type'][0],$name); ?>/><?php echo $tips->add_tip($description); ?></div>
								</h3>
							<?php /*?><?php else : ?>
								<h3>
									<label><span class="description"><?php echo $label; ?></span></label>
									<div style="float:right;"><input name="wdf[type]" type="hidden" value="<?php echo $name; ?>" /><?php echo $tips->add_tip($description); ?></div>
								</h3>
							<?php endif; ?>	<?php */?>					
						<?php //endif; ?>
					
					<?php /*?><?php else : // Type Has Been Set ?>
						<?php if($meta['wdf_type'][0] == $name) : //Current Funder Type Matches The Foreach ?>
							<h3>
								<label><span class="description"><?php echo $label; ?></span></label>
								<div style="float:right;"><input name="wdf[type]" type="hidden" value="<?php echo $meta['wdf_type'][0]; ?>" /><?php echo $tips->add_tip($description); ?></div>
							</h3>
						<?php endif; ?><?php */?>
						
					<?php //endif; ?>
				<?php endforeach; ?>
				<p><input type="submit" name="save" id="save-post" value="<?php _e('Save Fundraising Type','wdf'); ?>" class="button button-primary" /><br /></p>
				
			<?php else : // No Valid Payment Types Available?>
				<div class="message updated below-h2"><p><?php _e('No payment types have been enabled yet.','wdf'); ?></p></div>
			<?php endif; ?>
		</div><!-- #wdf_type -->
		<?php break;
	
	////////////////////////////
	// FUNDER OPTIONS METABOX //
	////////////////////////////
	case 'wdf_options' : 
		global $pagenow;
		$settings = get_option('wdf_settings'); ?>
		<h4><?php _e('Type : ','wdf'); ?><?php echo ($meta['wdf_type'][0] == 'advanced' ? __('Advanced Crowdfunding','wdf') : __('Simple Donations','wdf') ); ?></h4>
		<?php if($settings['single_styles'] == 'yes') : ?>
			<div id="wdf_style">	
				<p>
					<label><?php echo __('Choose a display style','wdf'); ?>
					<select name="wdf[style]">
						<?php if(is_array($this->styles) && !empty($this->styles)) : ?>
							<?php foreach($this->styles as $key => $label) : ?>
								<option <?php selected($meta['wdf_style'][0],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select></label>
				</p>
			</div>
		<?php endif; ?>
		<?php if($meta['wdf_type'][0] == 'simple') : ?>
			<p><label><span class="description"><?php _e('Allow Recurring Donations?','wdf') ?></span>
					<select name="wdf[recurring]" rel="wdf_recurring" class="wdf_toggle">
						<option value="yes" <?php selected($meta['wdf_recurring'][0],'yes'); ?>><?php _e('Yes','wdf'); ?></option>
						<option value="no" <?php selected($meta['wdf_recurring'][0],'no'); ?>><?php _e('No','wdf'); ?></option>
					</select>
				</label>
			</p>
		
			<?php /*?><p>
				<label><?php echo __('Override Default PayPal Email Address','wdf'); ?><br />
					<input type="text" class="widefat" name="wdf[paypal_email_override]" value="<?php echo $meta['wdf_paypal_email_override'][0]; ?>" />
				</label>
			</p><?php */?>
			<?php $cycles = maybe_unserialize($meta['wdf_recurring_cycle'][0]); ?>
			<?php /*?><p rel="wdf_recurring" <?php echo ($meta['wdf_recurring'][0] == 'yes'? '' : 'style="display:none;"') ?>>
				<input type="hidden" name="wdf[recurring_cycle][d]" value="" />
				<input type="hidden" name="wdf[recurring_cycle][w]" value="" />
				<input type="hidden" name="wdf[recurring_cycle][m]" value="" />
				<input type="hidden" name="wdf[recurring_cycle][y]" value="" />
				<label><input type="checkbox" name="wdf[recurring_cycle][d]" value="1" <?php echo checked($cycles['d'],'1'); ?> />Daily</label><br />
				<label><input type="checkbox" name="wdf[recurring_cycle][w]" value="1" <?php echo checked($cycles['w'],'1'); ?> />Weekley</label><br />
				<label><input type="checkbox" name="wdf[recurring_cycle][m]" value="1" <?php echo checked($cycles['m'],'1'); ?> />Monthly</label><br />
				<label><input type="checkbox" name="wdf[recurring_cycle][y]" value="1" <?php echo checked($cycles['y'],'1'); ?> />Yearly</label>
			</p><?php */?>
		<?php endif; ?>
		<p><label><span class="description"><?php _e('Panel Position','wdf') ?></span>
				<select name="wdf[panel_pos]">
					<option value="top" <?php selected($meta['wdf_recurring'][0],'top'); ?>><?php _e('Above Content','wdf'); ?></option>
					<option value="bottom" <?php selected($meta['wdf_recurring'][0],'bottom'); ?>><?php _e('Below Content','wdf'); ?></option>
				</select>
			</label><?php echo $tips->add_tip(__('If you are not using the Fundraiser sidebar widget, choose the position of your info panel.','wdf')); ?>
		</p>
		
		<?php if($post->post_status == 'draft' && $meta['wdf_type'][0] == 'advanced') : ?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
					
					$('input#publish').on( 'click', null, 'some data', function(e) {
						var has_goal = $('select#wdf_has_goal option:selected').val();
						var start_date = $('input#wdf_goal_start_date').val();
						var end_date = $('input#wdf_goal_end_date').val();
						var goal_amount = $('input#wdf_goal_amount').val();
						
						if(has_goal == '1') {
							if(start_date == '' || typeof start_date == 'undefined') {
								alert("<?php _e('You must set a starting date','wdf'); ?>");
								e.preventDefault();
								e.stopImmediatePropagation();
								return false;
							} else if(end_date == '' || typeof start_date == 'undefined') {
								alert("<?php _e('You must set a ending date that is after the current date','wdf'); ?>");
								e.preventDefault();
								e.stopImmediatePropagation()
								return false;
							}  else if( goal_amount == '' || typeof goal_amount == 'undefined' || parseInt(goal_amount) < 1  ) {
								alert("<?php _e('You must set a goal amount greater than at least 1','wdf'); ?>");
								e.preventDefault();
								e.stopImmediatePropagation()
								return false;
							}
						}
						
						var check = confirm("<?php _e('Are you sure you are ready to publish?  You will be unable to change your fundraising type, goals and rewards after publishing.','wdf'); ?>");
						if (check == true)  {
							return true;
						} else {
							e.preventDefault();
							e.stopImmediatePropagation();
							return false;
						}
					});
				});
			</script>
		<?php endif; ?>
	<?php break;
	
	//////////////////////////
	// FUNDER GOALS METABOX //
	//////////////////////////
	case 'wdf_goals' :
		 
		if($meta['wdf_type'][0] == 'advanced' && $post->post_status == 'publish' && $this->get_pledge_list($post->ID) != false)
			$disabled = 'disabled="disabled"';
		else
			$disabled = '';
		$settings = get_option('wdf_settings'); ?>
			<?php if($disabled != '') : ?>
				<div class="below-h2 updated"><p><?php _e('Your fundraising dates, goals and rewards are locked in.','wdf'); ?></p></div>
			<?php endif; ?>
			<div id="wdf_funder_goals">
				<?php //if( in_array('advanced', $settings['payment_types']) || in_array('standard', $settings['payment_types']) ) : ?>
					<p><label><?php echo __('Create a crowdfunding goal?','wdf'); ?>
					<select class="wdf_toggle" id="wdf_has_goal" rel="wdf_has_goal" name="wdf[has_goal]" <?php echo $disabled; ?>>
						<option <?php selected($meta['wdf_has_goal'][0],'0'); ?> value="0">No</option>
						<option <?php selected($meta['wdf_has_goal'][0],'1'); ?> value="1">Yes</option>
					</select></label>
					</p>
				</div>
				<div rel="wdf_has_goal" <?php echo ($meta['wdf_has_goal'][0] == '1' ? '' : 'style="display:none"') ?>>
				<?php /*?><input type="hidden" name="wdf[show_progress]" value="0" />
				<p><label><input type="checkbox" name="wdf[show_progress]" value="1" <?php checked($meta['wdf_show_progress'][0],'1'); ?> /> <?php echo __('Show Progress Bar','wdf') ?></label></p><?php */?>
				
				<table class="widefat">
					<thead>
						<tr>
							<th class="wdf_goal_start_date"><?php echo __('Start Date','wdf') ?></th>
							<th class="wdf_goal_end_date"><?php echo __('End Date','wdf') ?></th>
							<th class="wdf_goal_amount" align="right"><?php echo __('Goal Amount','wdf') ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							
							<td class="wdf_goal_start_date">
								<input <?php echo $disabled; ?> id="wdf_goal_start_date" style="background-image: url(<?php echo admin_url('images/date-button.gif'); ?>);" type="text" name="wdf[goal_start]" class="wdf_biginput" value="<?php echo $meta['wdf_goal_start'][0]; ?>" />
							</td>
							<td class="wdf_goal_end_date">
								<input <?php echo $disabled; ?> id="wdf_goal_end_date" style="background-image: url(<?php echo admin_url('images/date-button.gif'); ?>);" type="text" name="wdf[goal_end]" class="wdf_biginput" value="<?php echo $meta['wdf_goal_end'][0]; ?>" />
							</td>
							<td class="wdf_goal_amount">
								<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
								<input <?php echo $disabled; ?> id="wdf_goal_amount" type="text" name="wdf[goal_amount]" class="wdf_input_switch active wdf_biginput wdf_bignum" value="<?php echo $this->filter_price($this->format_currency(' ',$meta['wdf_goal_amount'][0])); ?>" />
								<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<p><label><?php echo __('Create ','wdf') . esc_attr($settings['funder_labels']['plural_level']); ?>
				<select <?php echo $disabled; ?> class="wdf_toggle" rel="wdf_has_reward" name="wdf[has_reward]">
					<option <?php selected($meta['wdf_has_reward'][0],'0'); ?> value="0">No</option>
					<option <?php selected($meta['wdf_has_reward'][0],'1'); ?> value="1">Yes</option>
				</select></label>
				</p>
				<div id="wdf_has_reward" rel="wdf_has_reward" <?php echo ($meta['wdf_has_reward'][0] == '1' ? '' : 'style="display:none"') ?>>
					<h2><?php apply_filters('wdf_admin_meta_reward_title', esc_attr($settings['funder_labels']['singular_name']) . esc_attr($settings['funder_labels']['plural_level']) ); ?></h2>
					<table id="wdf_levels_table" class="widefat">
					<thead>
						<tr>
							<th class="wdf_level_amount"><?php echo __('Choose Amount','wdf'); ?></th>
							<?php /*?><th class="wdf_level_title"><?php echo __('Optional Title','wdf'); ?></th><?php */?>
							<th class="wdf_level_description"><?php echo esc_attr($settings['funder_labels']['singular_level']) . __(' Description','wdf'); ?></th>
							<?php /*?><th class="wdf_level_reward" align="right"><?php echo __('Add A Reward','wdf'); ?></th><?php */?>
							<th class="delete" align="right"></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						if(isset($meta['wdf_levels']) && is_array($meta['wdf_levels'])) :
						$level_count = count($meta['wdf_levels']);
						$i = 1;
						
						foreach($meta['wdf_levels'] as $level) :
							$level = maybe_unserialize($level);
							foreach($level as $index => $data) : ?>
								<tr class="wdf_level <?php echo ($level_count == $i ? 'last' : ''); ?>">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input <?php echo $disabled; ?> class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" name="wdf[levels][<?php echo $index ?>][amount]" value="<?php echo $this->filter_price($this->format_currency(' ',$data['amount'])); ?>" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?></td>
									<td class="wdf_level_description"><textarea <?php echo $disabled; ?> class="wdf_input_switch active " name="wdf[levels][<?php echo $index ?>][description]"><?php echo $data['description'] ?></textarea></td>
									<td class="delete">
										<?php if($disabled == false) : ?>
											<a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span><?php _e('Delete','wdf'); ?></a>
										<?php endif; ?>
									</td>
								</tr>
								<tr class="wdf_reward_options">
									<td colspan="5">
										<div class="wdf_reward_toggle" <?php echo ($data['reward'] == 1 ? '' : 'style="display:none"'); ?>>
											<p><label><?php echo __('Describe Your ','wdf') . esc_attr($settings['funder_labels']['singular_level']); ?><input <?php echo $disabled; ?> type="text" name="wdf[levels][<?php echo $index ?>][reward_description]" value="<?php echo $data['reward_description'] ?>" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
							<?php $i++; endforeach; endforeach; ?>
							<?php else : ?>
								<tr class="wdf_level last">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input class="wdf_input_switch wdf_biginput wdf_bignum" type="text" name="wdf[levels][0][amount]" value="" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
									</td>
									<?php /*?><td class="wdf_level_title"><input class="wdf_input_switch wdf_biginput wdf_bignum" type="text" name="wdf[levels][0][title]" value="" /></td><?php */?>
									<td class="wdf_level_description"><textarea class="wdf_input_switch" name="wdf[levels][0][description]"><?php //echo __('Add a description for this level','wdf'); ?></textarea></td>
									<?php /*?><td class="wdf_level_reward"><input class="wdf_check_switch" type="checkbox" name="wdf[levels][0][reward]" value="1" /></td><?php */?>
									<td class="delete">
										<?php if($disabled == false) : ?>
											<a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span><?php _e('Delete','wdf'); ?></a>
										<?php endif; ?>
									</td>
								</tr>
								<tr class="wdf_reward_options">
									<td colspan="5">
										<div class="wdf_reward_toggle" style="display:none">
											<p><label><?php echo __('Describe Your ','wdf') . esc_attr($settings['funder_labels']['singular_level']); ?><input type="text" name="wdf[levels][0][reward_description]" value="<?php echo $data['reward_description'] ?>" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
								<tr rel="wdf_level_template" style="display:none">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" rel="wdf[levels][][amount]" value="" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
									</td>
									<?php /*?><td class="wdf_level_title"><input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" rel="wdf[levels][][title]" value="" /></td><?php */?>
									<td class="wdf_level_description"><textarea class="wdf_input_switch active" rel="wdf[levels][][description]"></textarea></td>
									<?php /*?><td class="wdf_level_reward"><input class="wdf_check_switch" type="checkbox" rel="wdf[levels][][reward]" value="1" /></td><?php */?>
									<td class="delete"><a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span><?php _e('Delete','wdf'); ?></a></td>
								</tr>
								<tr rel="wdf_level_template" class="wdf_reward_options" style="display:none">
									<td colspan="5">
										<div class="wdf_reward_toggle" style="display:none">
											<p><label><?php echo __('Describe Your ','wdf') . esc_attr($settings['funder_labels']['singular_level']) ?><input type="text" rel="wdf[levels][][reward_description]" value="" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
								<?php if($disabled == false) : ?>
									<tr><td colspan="3" align="right"><a href="#" id="wdf_add_level"><?php echo __('Add A ','wdf') . esc_attr($settings['funder_labels']['singular_level']); ?></a></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div><!-- #wdf_has_reward -->
		
	<?php break;

	////////////////////
	// LEVELS METABOX //
	////////////////////
	case 'wdf_levels' : ?>
		<?php $settings = get_option('wdf_settings'); ?>
		
	<?php break;
	
	//////////////////////
	// ACTIVITY METABOX //
	//////////////////////
	case 'wdf_activity' : ?>
		<?php $donations = $this->get_pledge_list($post->ID); ?>
		<table class="widefat">
				<thead>
					<tr>
						<th><?php _e('Amount','wdf'); ?>:</th>
						<th><?php _e('Status','wdf'); ?>:</th>
						<th><?php echo esc_attr($settings['donation_labels']['backer_single']) ?>:</th>
						<th><?php _e('Method','wdf'); ?>:</th>
						<th><?php _e('Date','wdf'); ?>:</th>
						<th class="wdf_actvity_edit"><br /></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($donations as $donation) : ?>
					<?php $trans = $this->get_transaction($donation->ID); ?>
					<tr class="wdf_actvity_level">
						<td><?php echo $this->format_currency('',$trans['gross']); ?></td>
						<td><?php echo $trans['status']; ?></td>
						<td><label><?php echo $trans['first_name'].' '.$trans['last_name']; ?></label><br /><a href="mailto:<?php echo $trans['payer_email']; ?>"><?php echo $trans['payer_email']; ?></a></</td>
						<td><?php echo $trans['gateway']; ?></td>
						<td><?php echo get_post_modified_time('F d Y', null, $donation->ID) ?></td>
						<td><a class="hidden" href="<?php echo get_edit_post_link($donation->ID); ?>">View Details</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
	<?php break;
	
	//////////////////////
	// MESSAGES METABOX //
	//////////////////////
	case 'wdf_messages' : 
		$settings = get_option('wdf_settings');
	?>	
		<?php /*?><label id="wdf_thanks_type"><?php echo __('Thank You Message','wdf'); ?>
		<select class="wdf_toggle" rel="wdf_thanks_type" name="wdf[thanks_type]">
			<option <?php selected($meta['wdf_thanks_type'][0],'custom'); ?> value="custom"><?php echo __('Custom Thank You Message','wdf'); ?></option>
			<option <?php selected($meta['wdf_thanks_type'][0],'post'); ?> value="post"><?php echo __('Use A Post or Page ID','wdf'); ?></option>
			<option <?php selected($meta['wdf_thanks_type'][0],'url'); ?> value="url"><?php echo __('Use A Custom URL','wdf'); ?></option>
		</select></label><?php */?>
		<p<?php //echo ($meta['wdf_thanks_type'][0] == 'custom' || $pagenow == 'post-new.php' ? 'style="display: block;"' : ''); ?> rel="wdf_thanks_type" class="wdf_thanks_custom">
			<label><?php echo __('Text or HTML Allowed','wdf'); ?><?php echo $tips->add_tip('Provide a custom thank you message for users.  You can use the following codes to display specific information from the payment: %DONATIONTOTAL% %FIRSTNAME% %LASTNAME%'); ?></label><br />
			<textarea id="wdf_thanks_custom" name="wdf[thanks_custom]"><?php echo urldecode(wp_kses_post($meta['wdf_thanks_custom'][0])); ?></textarea>
		</p>
		<?php /*?><p <?php echo ($meta['wdf_thanks_type'][0] == 'post' ? 'style="display: block;"' : 'style="display: none;"'); ?> rel="wdf_thanks_type" class="wdf_thanks_post">
			<?php do_action('wdf_error_thanks_post');?>
			<label><?php echo __('Insert A Post or Page ID','wdf'); ?><input type="text" name="wdf[thanks_post]" value="<?php echo $meta['wdf_thanks_post'][0]; ?>" /></label>
		</p>
		<p <?php echo ($meta['wdf_thanks_type'][0] == 'url' ? 'style="display: block;"' : 'style="display: none;"'); ?> rel="wdf_thanks_type" class="wdf_thanks_url">
			<label><?php echo __('Insert A Custom URL','wdf'); ?><input type="text" name="wdf[thanks_url]" value="<?php echo $meta['wdf_thanks_url'][0]; ?>" /></label>
		</p><?php */?>
	
		<h3>Email Settings</h3>
		
		<p>
			<label><?php echo __('Send a confirmation email after a payment?','wdf'); ?>
				<select class="wdf_toggle" rel="wdf_send_email" name="wdf[send_email]" id="wdf_send_email">
					<option value="0" <?php echo selected($meta['wdf_send_email'][0],0); ?>><?php _e('No','wdf'); ?></option>
					<option value="1" <?php echo selected($meta['wdf_send_email'][0],1); ?>><?php _e('Yes','wdf'); ?></option>
				</select>
			</label>
		</p>
	
	<div <?php echo ($meta['wdf_send_email'] == 1 ? '' : 'style="display: none;"');?> rel="wdf_send_email">
		<label><?php echo __('Create a custom email message or use the default one.','wdf'); ?></label><?php $tips->add_tip('The email will come from your Administrator email <strong>'.get_bloginfo('admin_email').'</strong>')?><br />
		<p><label><?php echo __('Email Subject','wdf'); ?></label><br />
		<input class="regular-text" type="text" name="wdf[email_subject]" value="<?php echo (isset($meta['email_subject'][0]) ? $meta['email_subject'][0] : __('Thank you for your Donation', 'wdf')); ?>" /></p>
		<p><textarea id="wdf_email_msg" name="wdf[email_msg]">
			<?php echo (isset($meta['wdf_email_msg'][0]) ? $meta['wdf_email_msg'][0] : $settings['default_email']); ?>
		</textarea></p>
	</div>
	<?php break;
}	