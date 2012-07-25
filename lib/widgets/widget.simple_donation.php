<?php

class WDF_Simple_Donation extends WP_Widget {
	
	/**
     * @var		string	$translation_domain	Translation domain
     */
	
	function WDF_Simple_Donation() {
		$settings = get_option('wdf_settings');
		$title = esc_attr($settings['donation_labels']['singular_name']) . __(' Button','wdf');
		// Instantiate the parent object
		parent::__construct( false, $title, array(
			'description' =>  __('Create a simple button for taking donations.','wdf')
		) );
	}

	function widget( $args, $instance ) {
		// Widget output
		
		$content = $args['before_widget'];
		$content .= $args['before_title'] . esc_attr($instance['title']) . $args['after_title'];
		$content .= '<p class="wdf_widget_description">' . esc_attr($instance['description']) . '</p>';
		$content .= wdf_pledge_button(false,'widget_simple_donate',null,array('widget_args' => $instance));
		$content .= $args['after_widget'];
		echo $content;
	}

	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = esc_attr($new_instance['title']);
		$instance['description'] = esc_attr($new_instance['description']);
		$instance['thankyou_msg'] = esc_textarea($new_instance['thankyou_msg']);
		$instance['style'] = esc_attr($new_instance['style']);
		$instance['button_type'] = esc_attr($new_instance['button_type']);
		$instance['button_text'] = esc_attr($new_instance['button_text']);
		$instance['show_cc'] = esc_attr($new_instance['show_cc']);
		$instance['allow_note'] = esc_attr($new_instance['allow_note']);
		$instance['small_button'] = esc_attr($new_instance['small_button']);
		
		if($new_instance['donation_amount'] == '')
			unset($instance['donation_amount']);
		else
			$instance['donation_amount'] = round(preg_replace("/[^0-9.]/", "", $new_instance['donation_amount']), 2);
			
		if($new_instance['paypal_email'] == '')
			unset($instance['paypal_email']);
		else
			$instance['paypal_email'] = is_email($new_instance['paypal_email']);
			
		
		return $instance;
	}

	function form( $instance ) {
		$settings = get_option('wdf_settings');
		global $wdf;
		?>
		<p>
			<label><?php _e('Button Type','wdf'); ?></label><br/>
			<label><input class="autosave_widget" type="radio" name="<?php echo $this->get_field_name('button_type'); ?>" value="default" <?php if(isset($instance['button_type'])) checked($instance['button_type'],'default'); ?> /> <?php _e('Default PayPal Button','wdf'); ?></label><br />
			<label><input class="autosave_widget" type="radio" name="<?php echo $this->get_field_name('button_type'); ?>" value="custom" <?php if(isset($instance['button_type'])) checked($instance['button_type'],'custom'); ?> /> <?php _e('Custom Button','wdf'); ?></label>
		</p>
		<p>
			<label><?php _e('Title','wdf') ?><br />
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if(isset($instance['title'])) echo esc_attr($instance['title']); ?>" /></label>
		</p>
		<p>
			<label><?php _e('Description','wdf') ?></label><br />
			<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php if(isset($instance['description']))  echo esc_attr($instance['description']); ?></textarea>
		</p>
		
		<p>
			<label><?php _e('Donation Amount (blank = choice)','wdf') ?><input type="text" name="<?php echo $this->get_field_name('donation_amount'); ?>" value="<?php if(isset($instance['donation_amount'])) echo ($instance['donation_amount'] == '' ? '' : $wdf->filter_price($instance['donation_amount'])); ?>" /></label>
		</p>
		
		<?php if( isset($instance['button_type']) && $instance['button_type'] == 'custom') : ?>
			<p>
				<label><?php echo __('Choose a display style','wdf'); ?>
				<select name="<?php echo $this->get_field_name('style'); ?>">
					<option <?php if(isset($instance['style'])) selected($instance['style'],'wdf_default'); ?> value="wdf_default"><?php _e('Basic','wdf'); ?></option>
					<option <?php if(isset($instance['style'])) selected($instance['style'],'wdf_dark'); ?> value="wdf_dark"><?php _e('Dark','wdf'); ?></option>
					<option <?php if(isset($instance['style'])) selected($instance['style'],'wdf_fresh'); ?> value="wdf_fresh"><?php _e('Fresh','wdf'); ?></option>
					<option <?php if(isset($instance['style'])) selected($instance['style'],'wdf_note'); ?> value="wdf_note"><?php _e('Note','wdf'); ?></option>
					<option <?php if(isset($instance['style'])) selected($instance['style'],'wdf_custom'); ?> value="custom"><?php _e('None (Custom CSS)','wdf'); ?></option>
				</select></label>
			</p>
			<p>
			<label><?php _e('Donate Button Text','wdf'); ?><br />
				<input type="text" class="widefat" name="<?php echo $this->get_field_name('button_text'); ?>" value="<?php if(isset($instance['button_text'])) echo esc_attr($instance['button_text']); ?>" />
			</label>
		</p>
		<?php endif; ?>
		<?php if(isset($instance['button_type']) && $instance['button_type'] == 'default') : ?>
			<p>
				<label><input type="checkbox" name="<?php echo $this->get_field_name('show_cc'); ?>" value="yes" <?php if(isset($instance['show_cc'])) checked($instance['show_cc'],'1'); ?> /> <?php _e('Show Accepted Credit Cards','wdf'); ?></label><br />
				<label><input type="checkbox" name="<?php echo $this->get_field_name('allow_note'); ?>" value="yes" <?php if(isset($instance['allow_note'])) checked($instance['allow_note'],'1'); ?> /> <?php _e('Allow extra note field','wdf'); ?></label><br />
				<label><input type="checkbox" name="<?php echo $this->get_field_name('small_button'); ?>" value="yes" <?php if(isset($instance['small_button'])) checked($instance['small_button'],'1'); ?> /> <?php _e('Use Small Button','wdf'); ?></label>
			</p>
		<?php endif; ?>
		<p>
			<label><?php _e('Override PayPal Email Address','wdf') ?></label><br />
				<label class="code"><?php echo $settings['paypal_email']; ?></label><br />
				<input class="widefat" type="text" name="<?php echo $this->get_field_name( 'paypal_email' ); ?>" value="<?php if(isset($instance['paypal_email'])) echo is_email($instance['paypal_email']); ?>" />
			</label>
		</p>
		<?php
	}
}
?>