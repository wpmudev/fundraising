<?php

class WDF_Simple_Donation extends WP_Widget {
	
	/**
     * @var		string	$translation_domain	Translation domain
     */
	
	function WDF_Simple_Donation() {
		// Instantiate the parent object
		parent::__construct( false, __('Simple Donation Button','wdf') );
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
		?>
		<p>
			<label><?php echo __('Title','wdf') ?><br />
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label>
		</p>
		<p>
			<label><?php echo __('Description','wdf') ?></label><br />
			<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php echo esc_attr($instance['description']); ?></textarea>
		</p>
		
		<p>
			<label><?php echo __('Donation Amount (blank = choice)','wdf') ?><input type="text" name="<?php echo $this->get_field_name('donation_amount'); ?>" value="<?php echo ($instance['donation_amount'] == '' ? '' : round(preg_replace("/[^0-9.]/", "", $instance['donation_amount']), 2)); ?>" /></label>
		</p>
		<p>
			<label>Button Type</label><br/>
			<label><input type="radio" name="<?php echo $this->get_field_name('button_type'); ?>" value="default" <?php checked($instance['button_type'],'default'); ?> /> Default PayPal Button</label><br />
			<label><input type="radio" name="<?php echo $this->get_field_name('button_type'); ?>" value="custom" <?php checked($instance['button_type'],'custom'); ?> /> Custom Button</label>
		</p>
		<?php if($instance['button_type'] == 'custom') : ?>
			<p>
				<label><?php echo __('Choose a display style','wdf'); ?>
				<select name="<?php echo $this->get_field_name('style'); ?>">
					<option <?php selected($instance['style'],'wdf_default'); ?> value="wdf_default"><?php echo __('Basic','wdf'); ?></option>
					<option <?php selected($instance['style'],'wdf_dark'); ?> value="wdf_dark"><?php echo __('Dark','wdf'); ?></option>
					<option <?php selected($instance['style'],'wdf_fresh'); ?> value="wdf_fresh"><?php echo __('Fresh','wdf'); ?></option>
					<option <?php selected($instance['style'],'wdf_note'); ?> value="wdf_note"><?php echo __('Note','wdf'); ?></option>
					<option <?php selected($instance['style'],'wdf_custom'); ?> value="custom"><?php echo __('None (Custom CSS)','wdf'); ?></option>
				</select></label>
			</p>
			<p>
			<label>Donate Button Text<br />
				<input type="text" class="widefat" name="<?php echo $this->get_field_name('button_text'); ?>" value="<?php echo esc_attr($instance['button_text']); ?>" />
			</label>
		</p>
		<?php endif; ?>
		<?php if($instance['button_type'] == 'default') : ?>
			<p>
				<label><input type="checkbox" name="<?php echo $this->get_field_name('show_cc'); ?>" value="yes" <?php checked($instance['show_cc'],'1'); ?> /> <?php echo __('Show Accepted Credit Cards','wdf'); ?></label><br />
				<label><input type="checkbox" name="<?php echo $this->get_field_name('allow_note'); ?>" value="yes" <?php checked($instance['allow_note'],'1'); ?> /> <?php echo __('Allow extra note field','wdf'); ?></label><br />
				<label><input type="checkbox" name="<?php echo $this->get_field_name('small_button'); ?>" value="yes" <?php checked($instance['small_button'],'1'); ?> /> <?php echo __('Use Small Button','wdf'); ?></label>
			</p>
		<?php endif; ?>
		<p>
			<label><?php _e('Override PayPal Email Address','wdf') ?></label><br />
				<label class="code"><?php echo $settings['paypal_email']; ?></label><br />
				<input class="widefat" type="text" name="<?php echo $this->get_field_name( 'paypal_email' ); ?>" value="<?php echo is_email($instance['paypal_email']); ?>" />
			</label>
		</p>
		<?php /*?><p>
			<label><?php echo __('Thank You Message','wdf') ?></label><br />
			<textarea class="widefat" name="<?php echo $this->get_field_name( 'thankyou_msg' ); ?>"><?php echo esc_textarea($instance['thankyou_msg']); ?></textarea>
		</p><?php */?>
		<?php
	}
}
?>