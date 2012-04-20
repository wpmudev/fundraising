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
			// Specific Single Fundraiser
			$wdf->front_scripts($instance['funder']);
			if(isset($instance['style']) && !empty($instance['style']))
				$wdf->load_style($instance['style']);
			
			$content = $args['before_widget'];
			
			if(!isset($instance['title']) || empty($instance['title']))
				$content .= $args['before_title'] . get_the_title($instance['funder']) . $args['after_title'];
				
			$content .= $this->show_thumb($instance);
			$content .= '<p class="wdf_widget_description">' . $instance['description'] . '</p>';
				
			$content .= wdf_fundraiser_panel( false, $instance['funder'], 'widget', $instance );
			$content .= $args['after_widget'];
			echo $content;
		} else { 
			if($wp_query->query_vars['post_type'] == 'funder' && $wp_query->is_single && $wp_query->query_vars['funder_checkout'] != '1' && $wp_query->query_vars['funder_confirm'] != '1' ) {
				// Single Fundraiser Page
				$wdf->front_scripts($wp_query->posts[0]->ID);
				if(isset($instance['style']) && !empty($instance['style']))
					$wdf->load_style($instance['style']);
				
				$content = $args['before_widget'];
				
				if(isset($instance['title']) && !empty($instance['title']))
					$content .= $args['before_title'] . esc_attr($instance['title']) . $args['after_title'];
					
				$content .= $this->show_thumb($instance);
					
				if(isset($instance['description']) && !empty($instance['description']))
					$content .= '<p class="wdf_widget_description">' . $instance['description'] . '</p>';
						
				$content .= wdf_fundraiser_panel( false, $wp_query->posts[0]->ID, 'widget', $instance );
				$content .= $args['after_widget'];
				echo $content;
			} /*else if($wp_query->query_vars['post_type'] == 'funder' && $wp_query->query_vars['funder_checkout'] == '1'){
				// Fundraiser Checkout & Confirm Page
				$wdf->front_scripts($wp_query->posts[0]->ID);
				$content = $args['before_widget'];
				
				//if(isset($instance['title']) && !empty($instance['title']))
					$content .= $args['before_title'] . esc_attr(get_the_title($wp_query->posts[0]->ID)) . $args['after_title'];
					
				$content .= '<div><a class="button" href="'.wdf_get_funder_page('',$wp_query->posts[0]->ID).'">'.__('Go Back','wdf').'</a></div>';
				$content .= $args['after_widget'];
				echo $content;
			}*/
		}
	}
	function show_thumb($instance) {
		global $wp_query;
		if( function_exists('has_post_thumbnail') ) {
			if( isset($instance['show_thumb']) && (int)$instance['show_thumb'] == 1 ) {
				$post_id = ($instance['single_fundraiser'] == '1' ? $instance['funder'] : $wp_query->posts[0]->ID );
				if( has_post_thumbnail($post_id) ) {
					// Width and Height Default to the blog's thumbnail size if they are not set in the widget options.
					$width = (isset($instance['thumb_width']) && !empty($instance['thumb_width']) ? $instance['thumb_width'] : get_option('thumbnail_size_w'));
					$height = (isset($instance['thumb_height']) && !empty($instance['thumb_height']) ? $instance['thumb_height'] : get_option('thumbnail_size_h'));
					
					// Run the size and attributes through some filters incase you wanna do hoodrat stuff with your friends
					$size = apply_filters('wdf_panel_widget_thumb_size',array($width,$height));
					$attr = apply_filters('wdf_panel_widget_thumb_atts','');
					
					$thumb_id = apply_filters('wdf_panel_widget_thumb_id',get_post_thumbnail_id($post_id));
					return get_the_post_thumbnail( $post_id, $size, $attr );
				}
			}
		}
	}
	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = esc_attr($new_instance['title']);
		$instance['show_thumb'] = esc_attr($new_instance['show_thumb']);
		if(isset($new_instance['thumb_width']) && !empty($new_instance['thumb_width']))
			$instance['thumb_width'] = absint($new_instance['thumb_width']);
		else
			unset($instance['thumb_width']);
			
		if(isset($new_instance['thumb_height']) && !empty($new_instance['thumb_height']))
			$instance['thumb_height'] = absint($new_instance['thumb_height']);
		else
			unset($instance['thumb_height']);
		
		$instance['style'] = esc_attr($new_instance['style']);	
		$instance['single_fundraiser'] = esc_attr($new_instance['single_fundraiser']);
		$instance['description'] = esc_textarea($new_instance['description']);
		$instance['funder'] = esc_attr( absint($new_instance['funder']) );
		return $instance;
	}

	function form( $instance ) {
		global $wdf;
		?>
		
		<p><label><?php _e('Title','wdf') ?><br /><input type="text" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo (isset($instance['title']) ? $instance['title'] : __('Featured Fundraisers','wdf')); ?>" /></label></p>
		<p><label><?php _e('Extra Description','wdf') ?></label><br />
		<textarea class="widefat" name="<?php echo $this->get_field_name('description'); ?>"><?php echo esc_textarea($instance['description']) ?></textarea></p>
		
		<p><label><input type="checkbox" value="1" name="<?php echo $this->get_field_name('show_thumb'); ?>" <?php checked((int)$instance['show_thumb'],1); ?> /><?php _e('Include Featured Image'); ?></label></p>
		<p><label><?php _e('Max Image Width'); ?> : <input type="text" class="small-text" value="<?php echo $instance['thumb_width']; ?>" name="<?php echo $this->get_field_name('thumb_width'); ?>"/></label></p>
		<p><label><?php _e('Max Image Height'); ?> : <input type="text" class="small-text" value="<?php echo $instance['thumb_height']; ?>" name="<?php echo $this->get_field_name('thumb_height'); ?>"/></label></p>
		
		<p>
			<label><?php _e('Choose a display style','wdf'); ?>
			<select name="<?php echo $this->get_field_name('style'); ?>">
				<?php if(is_array($wdf->styles) && !empty($wdf->styles)) : ?>
					<option <?php selected($instance['style'],$key); ?> value=""><?php _e('Default','wdf'); ?></option>
					<?php foreach($wdf->styles as $key => $label) : ?>
						<option <?php selected($instance['style'],$key); ?> value="<?php echo $key ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select></label>
		</p>
		
		<p><label><?php _e('Display a specific fundraiser','wdf'); ?></label>
			<select class="wdf_toggle" rel="wdf_panel_single" name="<?php echo $this->get_field_name('single_fundraiser'); ?>">
				<option value="0" <?php echo selected($instance['single_fundraiser'],'0'); ?>><?php _e('No','wdf'); ?></option>
				<option value="1" <?php echo selected($instance['single_fundraiser'],'1'); ?>><?php _e('Yes','wdf'); ?></option>
			</select>
		</p>
		<div rel="wdf_panel_single" <?php echo ((int)$instance['single_fundraiser'] != 1 ? 'style="display: none;"' : ''); ?>>
			
			<?php
				$query = array( 'numberposts' => -1, 'post_type' => 'funder', 'post_status' => 'publish');
				if($query = get_posts($query) ) : ?>
					<?php foreach($query as $funder) : ?>
						<label>
							<input <?php echo checked($instance['funder'],$funder->ID); ?> type="radio" name="<?php echo $this->get_field_name('funder'); ?>" value="<?php echo $funder->ID; ?>" />
							<?php echo $funder->post_title; ?>
						</label><br />
					<?php endforeach; ?>
				<?php else : ?>
					<div class="error below-h2"><p><?php _e('You have not created any fundraisers yet','wdf'); ?></p></div>
				<?php endif; ?>
		</div>
		<?php
	}
}
?>