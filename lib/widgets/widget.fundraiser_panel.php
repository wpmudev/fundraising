<?php

class WDF_Fundraiser_Panel extends WP_Widget {
	
	/**
     * @var		string	$translation_domain	Translation domain
     */
	
	function WDF_Fundraiser_Panel() {
		// Instantiate the parent object
		parent::__construct( 'wdf_fundraiser_panel', __('Fundraiser Panel','wdf'), array(
			'description' =>  __('If the current page is a single Fundraiser page then this panel will display information and call to actions for the fundraiser.  You can also use it to display information for a specific fundraiser.','wdf')
		) );
		
	}

	function widget( $args, $instance ) {
		// Widget output
		global $wp_query, $wdf;
		if($instance['single_fundraiser'] == '1') {
			$wdf->front_scripts($instance['funder']);
			$content = $args['before_widget'];
			$content .= $args['before_title'] . esc_attr($instance['title']) . $args['after_title'];
			/*if($instance['description'] != '')
				$content .= '<p class="wdf_widget_description">' . $instance['description'] . '</p>';*/
			$content .= wdf_fundraiser_panel( false, $instance['funder'], 'widget', $args );
			$content .= $args['after_widget'];
			echo $content;
		} else if($wp_query->query_vars['post_type'] == 'funder' && $wp_query->is_single && $wp_query->query_vars['funder_checkout'] != 1 && $wp_query->query_vars['funder_confirm'] != 1 ) {
			$wdf->front_scripts($wp_query->posts[0]->ID);
			$content = $args['before_widget'];
			$content .= $args['before_title'] . esc_attr($instance['title']) . $args['after_title'];
			/*if($instance['description'] != '')
				$content .= '<p class="wdf_widget_description">' . $instance['description'] . '</p>';*/
			$content .= wdf_fundraiser_panel( false, $wp_query->posts[0]->ID, 'widget', $args );
			$content .= $args['after_widget'];
			echo $content;
		} else {
			
		}
	}

	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = esc_attr($new_instance['title']);
		$instance['single_fundraiser'] = esc_attr($new_instance['single_fundraiser']);
		//$instance['description'] = esc_textarea($new_instance['description']);
		$instance['funder'] = esc_attr( absint($new_instance['funder']) );
		return $instance;
	}

	function form( $instance ) {		
		if (!class_exists('WpmuDev_HelpTooltips')) require_once WDF_PLUGIN_BASE_DIR . '/lib/external/class.wd_help_tooltips.php';
		$tips = new WpmuDev_HelpTooltips();
		$tips->set_icon_url(WDF_PLUGIN_URL.'/img/information.png');
		
		?>
		
		<p><label><?php _e('Title','wdf') ?><br /><input type="text" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo (isset($instance['title']) ? $instance['title'] : __('Featured Fundraisers','wdf')); ?>" /></label></p>
		<?php /*?><p><label><?php _e('Extra Description','wdf') ?></label><br />
		<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php echo esc_textarea($instance['description']) ?></textarea></p><?php */?>
		<p><label><?php _e('Display a specific fundraiser','wdf'); ?>
			<select name="<?php echo $this->get_field_name('single_fundraiser'); ?>">
				<option value="0" <?php echo selected($instance['single_fundraiser'],'0'); ?>><?php _e('No','wdf'); ?></option>
				<option value="1" <?php echo selected($instance['single_fundraiser'],'1'); ?>><?php _e('Yes','wdf'); ?></option>
			</select>
		</label><?php //echo $tips->add_tip(__('By default the widget will only display if you are on a singular fundraising page.  You must save the widget after choosing yes to get more options.','wdf')); ?></p>
		<?php if($instance['single_fundraiser'] == '1') : ?>
			<?php $query = array( 'numberposts' => -1, 'post_type' => 'funder', 'post_status' => 'publish');
				$query = get_posts($query);?>
			<?php foreach($query as $funder) : ?>
				<label>
					<input <?php echo checked($instance['funder'],$funder->ID); ?> type="radio" name="<?php echo $this->get_field_name('funder'); ?>" value="<?php echo $funder->ID; ?>" />
					<?php echo $funder->post_title; ?>
				</label><br />
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
	}
}
?>