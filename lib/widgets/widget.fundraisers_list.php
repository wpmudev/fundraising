<?php

class WDF_Fundraisers_List extends WP_Widget {

	function WDF_Fundraisers_List() {
		// Instantiate the parent object
		$settings = get_option('wdf_settings');
		parent::__construct( false, sprintf(__('%s List','wdf'),esc_attr($settings['funder_labels']['plural_name'])), array(
			'description' =>  sprintf(__('Choose a list of simple %s links you wish to display.','wdf'),esc_attr($settings['funder_labels']['plural_name']))
		) );
	}

	function widget( $args, $instance ) {
		$limit = (isset($instance['limit']) && $instance['limit'] && is_numeric($instance['limit'])) ? $instance['limit'] : -1;
		// Widget output
		$content = $args['before_widget'];
		$content .= $args['before_title'] . esc_attr(apply_filters('widget_title', $instance['title'])) . $args['after_title'];
		$query = array(
			'post_type' => 'funder',
			'post_status' => 'publish',
			'posts_per_page' => $limit
		);
		if(isset($instance['funders']) && is_array($instance['funders']) && count($instance['funders']) > 0) {
			$selected_funders = array();
			foreach ($instance['funders'] as $funder_id => $true)
				$selected_funders[] = $funder_id;
			$query['post__in'] = $selected_funders;
		}
		$query = get_posts($query);
		$content .= '<ul class="wdf_featured_fundraisers">';
		foreach($query as $funder) {
			$content .= '<li><a href="'.get_post_permalink($funder->ID).'">'.$funder->post_title.'</a></li>';
		}
		$content .= '</ul>';
		$content .= $args['after_widget'];
		echo $content;
	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = esc_attr($new_instance['title']);
		$instance['description'] = esc_textarea($new_instance['description']);
		$instance['limit'] = esc_attr($new_instance['limit']);
		$instance['funders'] = $new_instance['funders'];
		return $instance;
	}

	function form( $instance ) {
		$settings = get_option('wdf_settings');
		?>
		<p><label><?php echo __('Title','wdf') ?><br /><input type="text" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo (isset($instance['title']) ? $instance['title'] : __('Featured Fundraisers','wdf')); ?>" /></label></p>
		<p><label><?php echo __('Description','wdf') ?></label><br />
		<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php echo (isset($instance['description']) ? esc_textarea($instance['description']) : ''); ?></textarea></p>
		<p><label><?php echo __('Maximum number of fundraisers to display','wdf') ?><br /><input type="text" name="<?php echo $this->get_field_name('limit'); ?>" class="widefat" value="<?php echo (isset($instance['limit']) ? $instance['limit'] : ''); ?>" /></label></p>
		<?php
		$query = array( 'numberposts' => -1, 'post_type' => 'funder', 'post_status' => 'publish');
		$query = get_posts($query);
		if(count($query) > 0) {
			echo '<p><label>'.sprintf(__('Optional: select %s to display','wdf'), esc_attr($settings['funder_labels']['plural_name'])).'</label><br/>';
			foreach($query as $funder) : ?>
				<input <?php echo checked(isset($instance['funders'][$funder->ID]),true); ?> type="checkbox" id="<?php echo $this->get_field_id('funders_'.$funder->ID); ?>" name="<?php echo $this->get_field_name('funders'); ?>[<?php echo $funder->ID; ?>]" value="<?php echo $funder->ID; ?>" />
				<label for="<?php echo $this->get_field_id('funders_'.$funder->ID); ?>">
					<?php echo $funder->post_title; ?>
				</label><br />
			<?php
			endforeach;
			echo '</p>';
		}
	}
}
?>