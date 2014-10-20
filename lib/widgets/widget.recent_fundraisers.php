<?php

class WDF_Recent_Fundraisers extends WP_Widget {
	
	/**
     * @var		string	$translation_domain	Translation domain
     */
	
	function WDF_Recent_Fundraisers() {
		// Instantiate the parent object
		$settings = get_option('wdf_settings');
		parent::__construct( false, sprintf(__('Recent %s','wdf'),esc_attr($settings['funder_labels']['plural_name'])), array(
			'description' =>  sprintf(__('The most recent %s on your site','wdf'),esc_attr($settings['funder_labels']['plural_name']))
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
		$content .= '<ul class="wdf_recent_fundraisers">';
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
		$instance['numberposts'] = absint($new_instance['numberposts']);
		
		return $instance;
	}

	function form( $instance ) {		
		?>
		<p><label>Title<br /><input type="text" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo (isset($instance['title']) ? $instance['title'] : __('Recent Fundraisers','wdf')); ?>" /></label></p>
		<p><label>Number of Fundraisers to show:<input type="text" size="3" name="<?php echo $this->get_field_name('numberposts'); ?>" value="<?php echo (isset($instance['numberposts']) ? $instance['numberposts'] : ''); ?>" /></label></p>
		<?php
	}
}
?>