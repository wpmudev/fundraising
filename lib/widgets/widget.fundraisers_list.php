<?php

class WDF_Fundraisers_List extends WP_Widget {

	function WDF_Fundraisers_List() {
		// Instantiate the parent object
		parent::__construct( false, __('Fundraisers List','wdf'), array(
			'description' =>  __('Choose a list of simple fundraiser links you wish to display.','wdf')
		) );
	}

	function widget( $args, $instance ) {
		// Widget output
		
		$content = $args['before_widget'];
		$content .= $args['before_title'] . esc_attr($instance['title']) . $args['after_title'];
		$query = array(
			'numberposts' => $instance['numberposts'],
			'post_type' => 'funder',
			'post_status' => 'publish'
		);
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
		$instance['funders'] = $new_instance['funders'];
		return $instance;
	}

	function form( $instance ) {		
		?>
		<p><label><?php echo __('Title','wdf') ?><br /><input type="text" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo (isset($instance['title']) ? $instance['title'] : __('Featured Fundraisers','wdf')); ?>" /></label></p>
		<p><label><?php echo __('Description','wdf') ?></label><br />
		<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php echo esc_textarea($instance['description']) ?></textarea></p>
		<?php $query = array( 'numberposts' => -1, 'post_type' => 'funder', 'post_status' => 'publish');
			$query = get_posts($query);?>
		<?php foreach($query as $funder) : ?>
			<label>
				<input <?php echo checked(isset($instance['funders'][$funder->ID]),true); ?> type="checkbox" name="<?php echo $this->get_field_name('funders'); ?>[<?php echo $funder->ID; ?>]" value="<?php echo $funder->ID; ?>" />
				<?php echo $funder->post_title; ?>
			</label><br />
		<?php endforeach; ?>
		<?php
	}
}
?>