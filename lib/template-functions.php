<?php
add_shortcode('fundraiser', 'fundraiser_shortcode');
add_shortcode('donate_button', 'donate_button_shortcode');
function donate_button_shortcode($atts) {
	$content = wdf_pledge_button(false,'widget_simple_donate',null,array('widget_args' => $atts));
	//var_export($atts);
	//die();
	return $content;
}
function fundraiser_shortcode($atts) {
	global $wdf;
	$wdf->front_scripts($atts['id']);
	$atts['shortcode'] = true;
	$content = wdf_fundraiser_page(false,$atts['id'],$atts);
	return $content;
}
function wdf_fundraiser_panel($echo = true, $post_id = '', $context = '', $widget_args = NULL ) {
	$content = ''; global $post;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	
	$style = wdf_get_style($post_id);
	
	switch($context) {
		
		default :
			$content .= '<div class="wdf_fundraiser_panel '.$style.'">';
			//$content .= '<ul>';
			$content .= '<div class="wdf_total_backers"><div class="wdf_big_num">'.wdf_total_backers(false, $post_id).'</div><p>'.apply_filters('wdf_backer_label',__('Backers','wdf')).'</p></div>';
			if(wdf_has_goal($post_id)) {
				$content .= '<div class="wdf_amount_raised"><div class="wdf_big_num">'.wdf_amount_raised(false, $post_id).'</div><p>'.__('raised of a','wdf').' '.wdf_goal(false, $post_id).' '.__('goal','wdf').'</p></div>';
				$content .= '<div class="wdf_time_left">'.wdf_time_left(false, $post_id).'</div>';

				// Checking to see if this fundraiser can accept pledges yet.
				if( wdf_time_left(false, $post_id, true) === false ) {
					$content .= '<div class="wdf_backer_button">'.wdf_backer_button(false, $post_id).'</div>';
				}
			} else {
				$content .= '<div class="wdf_amount_raised"><div class="wdf_big_num">'.wdf_amount_raised(false, $post_id).'</div><p>'.__('raised','wdf').'</p></div>';
				$content .= '<div class="wdf_backer_button">'.wdf_backer_button(false, $post_id).'</div>';
			}
			
			$content .= '<div class="wdf_panel_progress_bar">'.wdf_progress_bar(false, $post_id).'</div>';
			
			
				
			if(wdf_has_rewards($post_id)) {
				$content .= '<div>'.wdf_rewards(false, $post_id).'</div>';
			}
			//$content .= '</ul>';
			$content .= '</div>';
			break;	
		
	}
	if($echo) {echo $content;} else {return $content;}
	
}

function wdf_rewards($echo = false, $post_id = '') {
	global $wdf, $post;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return;
	
	$meta = get_post_custom($post_id);
	
	if(isset($meta['wdf_levels'][0])) {
		$levels = '<div class="wdf_rewards">';
		foreach($meta['wdf_levels'] as $level) {
			$level = maybe_unserialize($level);
			foreach($level as $index => $data) {
				$levels .= '
					<div class="wdf_reward_item wdf_reward_'.$index.'">
						<div class="wdf_level_amount" rel="'.$data['amount'].'">'.$wdf->format_currency('',$data['amount']).'</div>
						<p>'.$data['description'].'</p>
					</div>';
			}
		}
		$levels .= '</div>';
	}
	if($echo) {echo $levels;} else {return $levels;}
}
function wdf_has_rewards($echo = false, $post_id = '') {
	global $wdf, $post;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
		
	return true;
}
function wdf_has_goal($post_id = '') {
	global $wdf, $post;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
		
	return $wdf->has_goal($post_id);
}
function wdf_goal($echo = true, $post_id = '') {
	global $wdf, $post;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return;
	
	$goal = get_post_meta($post_id,'wdf_goal_amount',true);
	$goal = $wdf->format_currency('',$goal);
	if($echo) {echo $goal;} else {return $goal;}	
}
function wdf_amount_raised($echo = true, $post_id = '') {
	global $post, $wdf;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	$raised = $wdf->format_currency('',$wdf->get_amount_raised($post_id));
	
	if($echo) {echo $raised;} else {return $raised;}
	
}
function wdf_time_left($echo = true, $post_id = '', $future_bool = false ) {
	global $post, $wdf;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	
	if(wdf_has_goal($post_id)) {
			$future_start = false;
			$end_date = strtotime(get_post_meta($post_id, 'wdf_goal_end',true));
			$start_date = strtotime(get_post_meta($post_id, 'wdf_goal_start', true));
			$now = date('Y-m-d');
			$now = strtotime($now);
			
				
			if($now > $end_date) {
				$end_date = false;
				$content = 'Time Up';
				if($echo) {echo $content;} else {return $content;}
			}
			
			if($start_date < $now) {
				$start_date = $now;
			} else if($start_date > $now) {
				$future_start = true;
				$end_date = $start_date;
				$start_date = $now;
			}
			
			if( $start_date === false || $end_date === false )
				return false;
			
			$days = $wdf->datediff('d', $start_date, $end_date, true);
			$hours = $wdf->datediff('h', $start_date, $end_date, true);
			$weeks = $wdf->datediff('ww', $start_date, $end_date, true);
			$months = $wdf->datediff('m', $start_date, $end_date, true);
			//$content .= var_export($days,true);
			//$content .= var_export($hours,true);
			
			if((int)$days >= 2) {
				$time = $days . ' ' . ((int)$days == 1 ? __('Day Left','wdf') : __('Days Left','wdf'));
			} else {
				$time = $hours . ' ' . ((int)$hours == 1 ? __('Hour Left','wdf') : __('Hours Left','wdf'));
			}
			if($future_start === true) {
				$time = __('Starts In','wdf') .  ' ' . ( (int)$days >= 2 ? (int)$days == 1 ? $days . ' ' . __('Day','wdf') : $days . ' ' . __('Days','wdf') : ((int)$hours == 1 ? $hours . ' ' . __('Hour','wdf') : $hours . ' ' . __('Hours','wdf')) );
			}
			
			$content = apply_filters('wdf_time_left', $time, $hours, $days, $weeks, $months, $start_date, $end_date );
	}
	// If $future_bool is true then only return if the fundraising has started or not
	if($future_bool === true)
		return $future_start;
		
		
	if($echo) {echo $time;} else {return $time;}
}
function wdf_backer_button($echo = false, $post_id = '') {
	global $post;
	$settings = get_option('wdf_settings');
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	
	$link = apply_filters('wdf_backer_button_link',trailingslashit(get_permalink($post_id) . $settings['checkout_slug']) );
	$classes = apply_filters('wdf_backer_button_classes','wdf_button button');
	$button = '<a class="'.$classes.'" href="'.$link.'">'.__('Support This Fundraiser!','wdf').'</a>';
	return apply_filters('wdf_backer_button', $button);
}
function wdf_get_style( $post_id = '' ) {
	global $post;
	$settings = get_option('wdf_settings');
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	
	if( $settings['single_styles'] == 'no' ) {
		$style = $settings['default_style'];
	} else {
		$style = (isset($meta['wdf_style'][0]) ? $meta['wdf_style'][0] : $settings['default_style'] );
	}
	
	return $style;
}
function wdf_total_backers($echo = false, $post_id = '') {
	global $post, $wdf;
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	$backers = $wdf->get_pledge_list($post_id);
	if($backers)
		return count($backers);
	else
		return 0;
}
function wdf_confirmation_page( $echo = true, $post_id = '' ) {
	global $wdf; $content = '';
	
	$pledge_id = (isset($_SESSION['wdf_pledge_id']) ? $_SESSION['wdf_pledge_id'] : $_REQUEST['pledge_id']);
	
	$content .= '<div class="wdf_confirmation_page">';
	if( $funder = get_post($post_id) && $pledge = get_page_by_title( $pledge_id, null, 'donation' ) ) {
		$transaction = $wdf->get_transaction($pledge->ID);
		$content .= wdf_thanks_panel( false, $funder->ID, $transaction );
		$content .= '<div class="wdf_gateway_payment_info">'.apply_filters('wdf_gateway_payment_info_'.$_SESSION['wdf_gateway'], '', $transaction).'</div>';
		//Unset all the session information
		$wdf->clear_session();
	} else {
		$content .= '<p class="error">You have not made a pledge yet.</p>';
	}
	$content .= '</div>';
	
	if($echo) {echo $content;} else {return $content;}
}
function wdf_thanks_panel( $echo = true, $post_id = '', $trans = '' ) {
	global $wdf; $content = '';
	$meta = get_post_custom($post_id);
	//var_export($meta);
	if($funder = get_post($post_id) && !empty($trans)) {
		$content .= '<div class="wdf_thanks_panel">';
		//$content .= var_export($trans,true);
		$content .= '<h3 class="wdf_confirm_pledge_amount">' . 'You pledged ' . $wdf->format_currency('',$trans['gross']) . '!</h3>';
		$content .= '<h3 class="wdf_left_to_go">';
		if(!wdf_has_goal($post_id))
			$content .= wdf_amount_raised(false, $post_id) . ' Raised so far';
		$content .= '</h3>';
		
		if(wdf_has_goal($post_id))
			$content .= wdf_progress_bar(false, $post_id);
			
		if($meta['wdf_thanks_custom'][0])
			$content .= '<div class="wdf_custom_thanks"><p>' . $meta['wdf_thanks_custom'][0] . '<p></div>';

		$content .= '</div>';
	}
	$content = apply_filters('wdf_thanks_panel',$content);
	if($echo) {echo $content;} else {return $content;}
}
function wdf_progress_bar( $echo = true, $post_id = '' ) {
	global $wdf;
	if(wdf_has_goal($post_id) != false)
		$content = $wdf->prepare_progress_bar($post_id,null,null,'general',false);
	
	if($echo) {echo $content;} else {return $content;}
}
function wdf_activity_page($echo = false, $post_id = '') {
	global $post; $content = '';
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
		
	$content .= '<h1>Activity Page</h1>';
	if($echo) {echo $content;} else {return $content;}
}
function wdf_gateway_choices( $echo = true ) {
	global $wdf_gateway_active_plugins; $content = '';
	
	if(count($wdf_gateway_active_plugins) == 1 ) {
		$gateway = array_keys($wdf_gateway_active_plugins);
		$gateway = $gateway[0];
		$content .= '<input type="hidden" name="wdf_step" value="gateway" />';
		$content .= '<input type="hidden" name="wdf_gateway" value="'.$gateway.'" />';
	} elseif(count($wdf_gateway_active_plugins) > 1) {
		$content .= '<input type="hidden" name="wdf_step" value="gateway" />';
		$content .= '<div class="wdf_payment_options_title">Payment Options</div>';
		$content .= '<div class="wdf_payment_options">';
		foreach($wdf_gateway_active_plugins as $gateway => $data) {
			$content .= '<label><input type="radio" name="wdf_gateway" value="'.$gateway.'" />'.$data->public_name.'</label>';
		}
		$content .= '</div>';
	} else {
		$content .= __('No Payment Gateways Have Been Enabled', 'wdf');
	}
	
	if($echo) {echo $content;} else {return $content;}
}
function wdf_checkout_page( $echo = true, $post_id = '' ) {
	global $wdf, $post; $content = '';
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	
	if( wdf_time_left(false, $post_id, true) === true ) {
		$content = '<div class="wdf_no_pledge_start"><h3>'.__('This fundraiser is not accepting pledges yet.','wdf').'  '. wdf_time_left(false, $post_id).'</h3></div>';
		if($echo) {echo $content;} else {return $content;}
	}
	
	$meta = get_post_custom($post_id);
	$settings = get_option('wdf_settings');
	
	$wdf->front_scripts($post_id);
	$content = '';
	
	$style = wdf_get_style($post_id);
	
	
	$content .= '<form class="wdf_checkout_form '.$style.'" action="" method="post" >';
			
			$raised = $wdf->get_amount_raised($post_id);
			$goal = $meta['wdf_goal_amount'][0];
		
			$content .= '<div class="wdf_rewards">';
			
			$content .= '<div class="wdf_payment_options"><div class="wdf_donate_button">'.wdf_pledge_button(false, 'single', $post_id).'</div><div class="wdf_gateway_choices">'.wdf_gateway_choices(false).'</div></div>';
			
			if(wdf_has_goal($post_id) && isset($meta['wdf_levels'][0])) {
				$content .= '<div class="wdf_choose_reward_title">'.__('Choose Your Reward','wdf').'</div>';
				//foreach($meta['wdf_levels'] as $level) {
					$level = maybe_unserialize($meta['wdf_levels'][0]);
					foreach($level as $index => $data) {
						$content .= '
							<div class="wdf_reward_item">
								<div class="wdf_reward_choice"><input type="radio" name="wdf_reward" value="'.$index.'" /><span class="wdf_level_amount" rel="'.$data['amount'].'">'.$wdf->format_currency('',$data['amount']).'</span></div>
								<div class="wdf_reward_description">'.$data['description'].'</div>
							</div>';
					}
					$content .= '
							<div class="wdf_reward_item">
								<div class="wdf_reward_choice"><input type="radio" name="wdf_reward" value="none" /></div>
								<div class="wdf_reward_description">'.apply_filters('wdf_no_reward_description',__('No Reward','wdf')).'</div>
							</div>';
				//}
			}
		
		//if($atts['shortcode'] == true)
			//$content .= '<div class="wdf_content">' . apply_filters('the_content',$funder->post_content) . '</div>';


		$content .= '</form>';
	
	if($echo) {echo $content;} else {return $content;}
}
function wdf_show_checkout( $echo = true, $post_id = '', $checkout_step = '' ) {
	
	switch($checkout_step) {
		case 'gateway' :
			$content = apply_filters('wdf_checkout_payment_form_'.$_SESSION['wdf_gateway'],'');
			break;
		default :
			$content = wdf_checkout_page( false, $post_id);	
			break;
	}
	
	if($echo) {echo $content;} else {return $content;}

}
function wdf_fundraiser_page($echo = true, $post_id = false, $atts = array()) {
	global $post; $content = '';
	$post_id = (empty($post_id) ? $post->ID : $post_id );
	if(!get_post($post_id))
		return false;
	//$content = '<a href="'.wdf_get_funder_page('checkout', $post_id).'">'.__('Support This Project','wdf').'</a>';
	$content = wdf_fundraiser_panel(false,$post_id,'','');
	if($echo) {echo $content;} else {return $content;}
}
function wdf_get_funder_page($context = '', $post_id = '') {
	if(empty($post_id)) {
		global $post;
		$post_id = $post->ID;
	}
	if($funder = get_post($post_id)) {
		$settings = get_option('wdf_settings');
		if($context == 'checkout') {
			return get_post_permalink($post_id) . $settings['checkout_slug'] .'/';
		}
		else if($context == 'confirmation') {
			return get_post_permalink($post_id) . $settings['confirm_slug'] .'/';
		}
	} else {
		return false;
	}
}
function wdf_pledge_button($echo = true, $context = '', $post_id = '', $args = array()) {
	global $wdf; $content = '';
	if(empty($post_id)) {
		global $post;
		$post_id = $post->ID;
	}
	$settings = get_option('wdf_settings');
	$meta = get_post_custom($post_id);
	//Default $atts
	$default_args = array(
		'widget_args' => '',
		'recurring' => false,
		'style'    => wdf_get_style($post_id)
	);
	
	$args = array_merge($default_args,$args);
	
	if($context == 'widget_simple_donate') {
		$paypal_email = is_email((isset($args['widget_args']['paypal_email']) ? $args['widget_args']['paypal_email'] : $settings['paypal_email'] ));
		$style = (isset($args['widget_args']['style']) ? $args['widget_args']['style'] : $meta['wdf_style'][0] );
		$content .= '
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" class="'.$style.'">
			<input type="hidden" name="cmd" value="_donations">
			<input type="hidden" name="business" value="'.is_email($paypal_email).'">
			<input type="hidden" name="lc" value="'.esc_attr($settings['currency']).'">
			<input type="hidden" name="item_name" value="'.esc_attr($args['widget_args']['title']).'">
			<input type="hidden" name="currency_code" value="'.esc_attr($settings['currency']).'">
		';
		if(!empty($args['widget_args']['donation_amount']) && isset($args['widget_args']['donation_amount'])) {
			$content .= '<input type="hidden" name="amount" value="'.$wdf->filter_price($args['widget_args']['donation_amount']).'">';
			$content .= '<label>Donate ';
			$content .= ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
			$content .= $wdf->filter_price($args['widget_args']['donation_amount']);
			$content .= ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
			$content .= '</label><br />';
		}
		
		if($args['widget_args']['no_note'] == '1')
			$content .= '<input type="hidden" name="no_note" value="'.$wdf->filter_price($args['widget_args']['no_note']).'">';	
		
		if($args['widget_args']['button_type'] == 'default') {
			//Use default PayPal Button
			
			if($args['widget_args']['small_button'] == '1') {
				$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">';
				$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
			} else {
				if($args['widget_args']['show_cc'] == '1') {
					$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">';
					$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
				} else {
					$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">';
					$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
				}
			}
			$content .= '<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">';
			$content .= '</form>';
		} else if ($args['widget_args']['button_type'] == 'custom') {
			//Use Custom Submit Button	
			wp_enqueue_style('wdf-style-'.$args['widget_args']['style']);
			$button_text = (!empty($args['widget_args']['button_text']) ? esc_attr($args['widget_args']['button_text']) : __('Donate Now','wdf'));
			$content .= '<input class="wdf_send_pledge" type="submit" name="submit" value="'.$button_text.'" />';
		}
	} else {
		$settings = get_option('wdf_settings');
		//Default Button Display
		$content .= '<input type="hidden" name="funder_id" value="'.$post_id.'" />';
		$content .= '<input type="hidden" name="send_nonce" value="'.wp_create_nonce('send_nonce_'.$post_id).'" />';
		$content .= '<div class="wdf_custom_donation_label">'.__('How much would you like to pledge?','wdf').'</div>';
		$content .= ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
		$content .= '<input type="text" name="wdf_pledge" class="wdf_pledge_amount" value="" />';
		$content .= ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');

		if($meta['wdf_recurring'][0] == 'yes' && $meta['wdf_type'][0] == 'simple') {			
			$content .= '
			<label>Make this donation </label>
			<select name="wdf_recurring">
				<option value="0">'.__('Once','wdf').'</option>
				<option value="D">'.__('Daily','wdf').'</option>
				<option value="W">'.__('Weekly','wdf').'</option>
				<option value="M">'.__('Monthly','wdf').'</option>
				<option value="Y">'.__('Yearly','wdf').'</option>
			</select>
			';
		}
		
		$content .= '<input type="hidden" name="funder_id" value="'.$post_id.'" />';
		$content .= '<input id="wdf_step" type="hidden" name="wdf_step" value="" />';
		$content .= '<input class="button wdf_send_donation" type="submit" name="wdf_send_donation" value="'.apply_filters('wdf_donate_button_text',__('Donate Now','wdf')).'" />';
		
		
	}
	
	$content = apply_filters('wdf_pledge_button',$content,$funder);

	if($echo) {echo $content;} else {return $content;}
}
?>