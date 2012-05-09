<?php

if(!function_exists('donate_button_shortcode')) {
	function donate_button_shortcode($atts) {
		if(isset($atts['title']))
			$content = $content .= sprintf( apply_filters( 'wdf_fundaiser_panel_shortcode_title', '<div class="wdf_shortcode_title"><h2>%s</h2></div>'), $atts['title'] );
			
		$content .= wdf_pledge_button(false,'widget_simple_donate',null,array('widget_args' => $atts));
		return $content;
	}
}

if(!function_exists('fundraiser_panel_shortcode')) {
	function fundraiser_panel_shortcode($atts) {
		if(isset($atts['id']) && !empty($atts['id']) ) {
			global $wdf;
			$wdf->front_scripts($atts['id'],$atts['style']);
			$atts['shortcode'] = true;
			$content .= wdf_fundraiser_panel(false, (int)$atts['id'], 'shortcode', $atts);
			return $content;
		} else {
			return __('No ID Given','wdf');
		}
	}
}

if(!function_exists('wdf_fundraiser_panel')) {
	function wdf_fundraiser_panel($echo = true, $post_id = '', $context = '', $args = array() ) {
		$settings = get_option('wdf_settings');
		$content = ''; global $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		$funder = get_post($post_id);
		if(!$funder)
			return false;
		
		$style = ( isset($args['style']) && !empty($args['style']) ? $args['style'] : wdf_get_style($post_id) );
		
		switch($context) {
			
			default :
				$content .= '<div class="wdf_fundraiser_panel '.$style.'">';
				
				if($args['shortcode'] == true) {
					if( strtolower($args['show_title']) == 'yes' )
						$content .= sprintf( apply_filters( 'wdf_fundaiser_panel_shortcode_title', '<div class="wdf_shortcode_title"><h2>%s</h2></div>'), get_the_title($post_id) );
					if( strtolower($args['show_content']) == 'yes') {
						$funder_content = apply_filters('the_content',$funder->post_content);
						$content .= sprintf( apply_filters( 'wdf_fundaiser_panel_shortcode_content', '<div class="wdf_shortcode_content">%s</div>'), $funder_content );
					}
				}
					
				$content .= '<div class="wdf_total_backers"><div class="wdf_big_num">'.wdf_total_backers(false, $post_id).'</div><p>'.apply_filters('wdf_backer_label', $settings['donation_labels']['backer_plural']).'</p></div>';
				if(wdf_has_goal($post_id)) {
					$content .= '<div class="wdf_amount_raised"><div class="wdf_big_num">'.wdf_amount_raised(false, $post_id).'</div><p>'.__('raised of a','wdf').' '.wdf_goal(false, $post_id).' '.__('goal','wdf').'</p></div>';
					$content .= '<div class="wdf_panel_progress_bar">'.wdf_progress_bar(false, $post_id).'</div>';
				} else {
					$content .= '<div class="wdf_amount_raised"><div class="wdf_big_num">'.wdf_amount_raised(false, $post_id).'</div><p>'.__('raised','wdf').'</p></div>';
				}
				
				// Checking to see if this fundraiser can accept pledges yet.
				if( wdf_time_left(false, $post_id, true) === false ) {
					if(wdf_panel_checkout()) {
						global $wdf_checkout_from_panel;
						$wdf_checkout_from_panel = true;
						
						// Show the time left or time till start if a date range is available
						if(wdf_has_date_range($post_id))
							$content .= '<div class="wdf_time_left">'.wdf_time_left(false, $post_id).'</div>';
						
						$content .= wdf_checkout_page(false, $post_id);
					} else {
						$content .= '<div class="wdf_backer_button">'.wdf_backer_button(false, $post_id).'</div>';
					}
				}
					
				// Show the time left or time till start if a date range is available
				if(wdf_has_date_range($post_id) && $wdf_checkout_from_panel !== true)
					$content .= '<div class="wdf_time_left">'.wdf_time_left(false, $post_id).'</div>';					
			
				
				if(wdf_has_rewards($post_id) && $wdf_checkout_from_panel !== true) {
					$content .= '<div>'.wdf_rewards(false, $post_id).'</div>';
				}
				$content .= '</div>';
				break;	
			
		}
		if($echo) {echo $content;} else {return $content;}
		
	}
}

if(!function_exists('wdf_rewards')) {
	function wdf_rewards($echo = false, $post_id = '') {
		global $wdf, $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return;
		
		$meta = get_post_custom($post_id);
		if(wdf_has_rewards($post_id)) {
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
	}
}

if(!function_exists('wdf_has_rewards')) {
	function wdf_has_rewards($post_id = '') {
		global $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
			
		$meta = get_post_meta($post_id,'wdf_has_reward',true);
		
		if( $meta === '1' )
			return true;
		else
			return false;

	}
}
if(!function_exists('wdf_panel_checkout')) {
	function wdf_panel_checkout() {
		$settings = get_option('wdf_settings');
		
		if( $settings['checkout_type'] == '1' )
			return true;
		else
			return false;

	}
}
if(!function_exists('wdf_has_date_range')) {
	function wdf_has_date_range($post_id) {
		global $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
			
		$start = get_post_meta($post_id,'wdf_goal_start',true);
		$end = get_post_meta($post_id,'wdf_goal_end',true);

		if($start != false && $end != false)
			return true;
		else
			return false;
		
	}
}
if(!function_exists('wdf_has_goal')) {
	function wdf_has_goal($post_id = '') {
		global $wdf, $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
			
		return $wdf->has_goal($post_id);
	}
}

if(!function_exists('wdf_goal')) {
	function wdf_goal($echo = true, $post_id = '') {
		global $wdf, $post;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return;
		
		$goal = get_post_meta($post_id,'wdf_goal_amount',true);
		$goal = $wdf->format_currency('',$goal);
		if($echo) {echo $goal;} else {return $goal;}	
	}
}

if(!function_exists('wdf_amount_raised')) {
	function wdf_amount_raised($echo = true, $post_id = '') {
		global $post, $wdf;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
		$raised = $wdf->format_currency('',$wdf->get_amount_raised($post_id));
		
		if($echo) {echo $raised;} else {return $raised;}
		
	}
}

if(!function_exists('wdf_time_left')) {
	function wdf_time_left($echo = true, $post_id = '', $future_bool = false ) {
		global $post, $wdf;
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id) || !wdf_has_date_range($post_id) )
			return false;
			
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
				$min = $wdf->datediff('n', $start_date, $end_date, true);
				$weeks = $wdf->datediff('ww', $start_date, $end_date, true);
				$months = $wdf->datediff('m', $start_date, $end_date, true);
				
				if((int)$days >= 2) {
					$time = $days . ' ' . ((int)$days == 1 ? __('Day Left','wdf') : __('Days Left','wdf'));
				} elseif((int)$hours < 1) {
					$time = $min . ' ' . ((int)$min == 1 ? __('Minute Left','wdf') : __('Minutes Left','wdf'));
				} else {
					$time = $hours . ' ' . ((int)$hours == 1 ? __('Hour Left','wdf') : __('Hours Left','wdf'));
				}
				if($future_start === true) {
					$time = __('Starts In','wdf') .  ' ' . ( (int)$days >= 2 ? (int)$days == 1 ? $days . ' ' . __('Day','wdf') : $days . ' ' . __('Days','wdf') : ((int)$hours == 1 ? $hours . ' ' . __('Hour','wdf') : $hours . ' ' . __('Hours','wdf')) );
				}
				
				$content = apply_filters('wdf_time_left', $time, $hours, $days, $weeks, $months, $start_date, $end_date );
				
				// If $future_bool is true then only return if the fundraising has started or not
				if($future_bool === true)
					return $future_start;
			
			
		if($echo) {echo $time;} else {return $time;}
	}
}

if(!function_exists('wdf_backer_button')) {
	function wdf_backer_button($echo = false, $post_id = '') {
		global $post;
		$settings = get_option('wdf_settings');
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
		
		$link = apply_filters('wdf_backer_button_link',trailingslashit(get_permalink($post_id) . $settings['checkout_slug']) );
		$classes = apply_filters('wdf_backer_button_classes','wdf_button');
		$button = '<a class="'.$classes.'" href="'.$link.'">'.__('Support This','wdf').'</a>';
		return apply_filters('wdf_backer_button', $button);
	}
}

if(!function_exists('wdf_get_style')) {
	function wdf_get_style( $post_id = '' ) {
		global $post;
		$settings = get_option('wdf_settings');
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		
		if( $settings['single_styles'] == 'no' ) {
			$style = $settings['default_style'];
		} else {
			$meta = get_post_meta($post_id,'wdf_style',true);
			$style = ($meta != false ? $meta : $settings['default_style'] );
		}
		
		return $style;
	}
}

if(!function_exists('wdf_total_backers')) {
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
}

if(!function_exists('wdf_confirmation_page')) {
	function wdf_confirmation_page( $echo = true, $post_id = '' ) {
		global $wdf; $content = '';
		$settings = get_option('wdf_settings');
		$pledge_id = (isset($_SESSION['wdf_pledge_id']) ? $_SESSION['wdf_pledge_id'] : $_REQUEST['pledge_id']);
		$content .= '<div class="wdf_confirmation_page">';
		if( $funder = get_post($post_id) && $pledge = get_page_by_title( $pledge_id, null, 'donation' ) ) {
			
			$transaction = $wdf->get_transaction($pledge->ID);
			
			if($_SESSION['wdf_bp_activity'] == true) {
				global $bp;
				if( isset($bp->loggedin_user->id) ) {
					$activity_args = array(
						'action' => sprintf( __('%s made a %s %s towards %s','wdf'), '<a href="'.$bp->loggedin_user->domain.'">'.$bp->loggedin_user->fullname.'</a>', $wdf->format_currency('',$transaction['gross']), esc_attr($settings['donation_labels']['singular_name']), '<a href="'.wdf_get_funder_page('',$funder->ID).'">'.get_the_title($funder->ID).'</a>' ),
						'primary_link' => wdf_get_funder_page('',$funder->ID),
						'type' => 'pledge'
					);
					$activity_args = apply_filters('wdf_bp_activity_args',$activity_args);
					bp_wdf_record_activity($activity_args);
				}
			}

			
			$content .= wdf_thanks_panel( false, $funder->ID, $transaction );
			
			// The gateway can use this filter to provide any transactional details that you need to display
			$content .= '<div class="wdf_gateway_payment_info">'.apply_filters('wdf_gateway_payment_info_'.$_SESSION['wdf_gateway'], '', $transaction).'</div>';
			
			//Unset all the session information
			$wdf->clear_session();
		} else {
			$content .= '<p class="error">'.sprintf( __('Oh No, we can\'t find your %s.  Sometimes it take a few moments for your %s to be logged.  You can try refreshing this page ','wdf'), esc_attr($settings['donation_labels']['singular_name']), esc_attr($settings['donation_labels']['singular_name']) ).'</p>';
		}
		$content .= '</div>';
		
		if($echo) {echo $content;} else {return $content;}
	}
}

if(!function_exists('wdf_thanks_panel')) {
	function wdf_thanks_panel( $echo = true, $post_id = '', $trans = '' ) {
		global $wdf; $content = '';
		$settings = get_option('wdf_settings');
		$meta = get_post_custom($post_id);
		if($funder = get_post($post_id) && !empty($trans)) {
			$content .= '<div class="wdf_thanks_panel">';
			$content .= '<h3 class="wdf_confirm_pledge_amount">' . sprintf(__('Your %s of %s was successful','wdf'), esc_attr($settings['donation_labels']['singular_name']), $wdf->format_currency($trans['currency_code'],$trans['gross']) ) . '</h3>';
			$content .= '<h3 class="wdf_left_to_go">';
			if(!wdf_has_goal($post_id))
				$content .= wdf_amount_raised(false, $post_id) . ' Raised so far';
			$content .= '</h3>';
			
			if(wdf_has_goal($post_id)) {
				$content .= '<div class="wdf_amount_raised"><div class="wdf_big_num">'.wdf_amount_raised(false, $post_id).'</div><p>'.__('raised of a','wdf').' '.wdf_goal(false, $post_id).' '.__('goal','wdf').'</p></div>';
				$content .= wdf_progress_bar(false, $post_id);
			}
				
			if($meta['wdf_thanks_custom'][0]) {
				$thanksmsg = $meta['wdf_thanks_custom'][0];
				$thanksmsg = $wdf->filter_thank_you($thanksmsg, $trans);
				$content .= '<div class="wdf_custom_thanks">' . $thanksmsg . '</div>';
			}
	
			$content .= '</div>';
		}
		$content = apply_filters('wdf_thanks_panel',$content);
		if($echo) {echo $content;} else {return $content;}
	}
}

if(!function_exists('wdf_progress_bar')) {
	function wdf_progress_bar( $echo = true, $post_id = '', $total = NULL, $goal = NULL, $context = 'general' ) {
		global $wdf;
		$content = '';
		if(wdf_has_goal($post_id) != false)
			$content .= $wdf->prepare_progress_bar($post_id, $total, $goal, $context, false);
		//else if(!empty($total) && !empty($goal))
			//$content .= $wdf->prepare_progress_bar($post_id, $total, $goal,'general',false);
		
		if($echo) {echo $content;} else {return $content;}
	}
}
if(!function_exists('wdf_progress_bar_shortcode')) {
	function wdf_progress_bar_shortcode($atts) {
		global $post;
		$defaults = array(
			'id' => ($post->post_type == 'funder' ? $post->ID : ''),
			'total' => NULL,
			'goal' => NULL,
			'show_totals' => 'no',
			'show_title' => 'no'
		);
		$atts = array_merge($defaults, $atts);
		
		if(isset($atts['id']) && !empty($atts['id']) ) {
			global $wdf;
			$wdf->front_scripts($atts['id'],$atts['style']);
		}
		
		if( $atts['show_totals'] == 'yes' || $atts['show_title'] == 'yes') {
			$context = 'shortcode';
			if($atts['show_title'] == 'yes')
				$context .= '_title';
			if($atts['show_totals'] == 'yes')
				$context .= '_totals';
		} else {
			$context = 'general';
		}
		
		return wdf_progress_bar(false, $atts['id'], (int)$atts['total'], (int)$atts['goal'], $context);
	}
}
// Coming Soon
/*if(!function_exists('wdf_activity_page')) {
	function wdf_activity_page($echo = false, $post_id = '') {
		global $post; $content = '';
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
			
		$content .= '<h1>Activity Page</h1>';
		if($echo) {echo $content;} else {return $content;}
	}
}*/

if(!function_exists('wdf_gateway_choices')) {
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
}

if(!function_exists('wdf_checkout_page')) {
	function wdf_checkout_page( $echo = true, $post_id = '' ) {
		global $wdf, $post; $content = '';
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
		
		if( wdf_time_left(false, $post_id, true) === true ) {
			$content = '<div class="wdf_no_pledge_start"><h3>'.sprintf(__('This %s is not accepting %s yet.','wdf'), esc_attr($settings['funder_labels']['singular_name']), esc_attr($settings['donation_labels']['plural_name'])).'  '. wdf_time_left(false, $post_id).'</h3></div>';
			if($echo) {echo $content;} else {return $content;}
		}
		
		$meta = get_post_custom($post_id);
		$settings = get_option('wdf_settings');
		
		$wdf->front_scripts($post_id);
		$content = '';
		global $wdf_checkout_from_panel;
		$style = ($wdf_checkout_from_panel == true ? '' : wdf_get_style($post_id) );
		
		
		$content .= '<form class="wdf_checkout_form '.$style.'" action="'.wdf_get_funder_page('checkout',$post_id).'" method="post" >';
				global $wp_filter;
				$raised = $wdf->get_amount_raised($post_id);
				$goal = $meta['wdf_goal_amount'][0];
			
				$content .= '<div class="wdf_rewards">';
				$content .= apply_filters('wdf_error_payment_submit','');
				
				$content .= '
				<div class="wdf_payment_options">
					<div class="wdf_donate_button">'.wdf_pledge_button(false, 'single', $post_id).'</div>
					<div class="wdf_gateway_choices">'.wdf_gateway_choices(false).'</div>
				</div>';
				
				if(wdf_has_rewards($post_id) && isset($meta['wdf_levels'][0])) {
					$content .= apply_filters('wdf_before_rewards_title','');
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
							<div class="wdf_reward_description">'.apply_filters('wdf_no_reward_description',__('None','wdf')).'</div>
						</div>';
				}
				$content .= '</div>';
			$content .= '</form>';
		
		if($echo) {echo $content;} else {return $content;}
	}
}

if(!function_exists('wdf_show_checkout')) {
	function wdf_show_checkout( $echo = true, $post_id = '', $checkout_step = '' ) {
		if(isset($_SESSION['wdf_pledge']) && (int)$_SESSION['wdf_pledge'] < 1) {
			$checkout_step = '';
			global $wdf;
			$wdf->create_error(__('You must pledge at least','wdf').' '.$wdf->format_currency('',1),'checkout_top');
		}
		
		switch($checkout_step) {
			case 'gateway' :
				$content = apply_filters('wdf_checkout_payment_form_'.$_SESSION['wdf_gateway'],'');
				break;
			default :
				$content = apply_filters('wdf_error_checkout_top', '');
				$content .= wdf_checkout_page( false, $post_id );	
				break;
		}
		
		if($echo) {echo $content;} else {return $content;}
	
	}
}

if(!function_exists('wdf_fundraiser_page')) {
	function wdf_fundraiser_page($echo = true, $post_id = false, $atts = array()) {
		global $post; $content = '';
		$post_id = (empty($post_id) ? $post->ID : $post_id );
		if(!get_post($post_id))
			return false;
		$content = wdf_fundraiser_panel(false,$post_id,'','');
		if($echo) {echo $content;} else {return $content;}
	}
}

if(!function_exists('wdf_get_funder_page')) {
	function wdf_get_funder_page($context = '', $post_id = '') {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		if($funder = get_post($post_id)) {
			$settings = get_option('wdf_settings');
			if($context == 'checkout') {
				return get_post_permalink($post_id) . $settings['checkout_slug'] .'/';
			} else if($context == 'confirmation') {
				return get_post_permalink($post_id) . $settings['confirm_slug'] .'/';
			} else {
				return get_post_permalink($post_id);
			}
		} else {
			return false;
		}
	}
}

if(!function_exists('wdf_pledge_button')) {
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
			//'widget_args' => '',
			//'recurring' => false,
			//'style'    => wdf_get_style($post_id)
		);
		
		$args = array_merge($default_args,$args);
		
		if($context == 'widget_simple_donate') {
			$paypal_email = is_email((isset($args['widget_args']['paypal_email']) ? $args['widget_args']['paypal_email'] : $settings['paypal_email'] ));
			$style = (isset($args['widget_args']['style']) ? $args['widget_args']['style'] : $meta['wdf_style'][0] );
			$content .= '
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" class="'.$style.'">
				<input type="hidden" name="cmd" value="_donations" />
				<input type="hidden" name="business" value="'.is_email($paypal_email).'" />
				<input type="hidden" name="lc" value="'.esc_attr($settings['currency']).'" />
				<input type="hidden" name="item_name" value="'.esc_attr($args['widget_args']['title']).'" />
				<input type="hidden" name="currency_code" value="'.esc_attr($settings['currency']).'" />
			';
			if(!empty($args['widget_args']['donation_amount']) && isset($args['widget_args']['donation_amount'])) {
				$content .= '<input type="hidden" name="amount" value="'.$wdf->filter_price($args['widget_args']['donation_amount']).'" />';
				$content .= '<label>Donate ';
				$content .= ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
				$content .= $wdf->filter_price($args['widget_args']['donation_amount']);
				$content .= ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
				$content .= '</label><br />';
			}
			
			if($args['widget_args']['button_type'] == 'default') {
				//Use default PayPal Button
				
				if($args['widget_args']['small_button'] == 'yes') {
					$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">';
					$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
				} else {
					if($args['widget_args']['show_cc'] == 'yes') {
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
			$content .= '<div class="wdf_custom_donation_label">'.apply_filters('wdf_choose_amount_label',__('Choose An Amount','wdf')).'</div>';
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
			$pledge_label = apply_filters( 'wdf_donate_button_text', esc_attr($settings['donation_labels']['action_name']) );
			if(defined('WDF_BP_INSTALLED') && WDF_BP_INSTALLED == true)
					$content .= '<label class="wdf_bp_show_on_activity">'.__('Post this to your profile','wdf').'<input type="checkbox" name="wdf_bp_activity" value="1" checked="checked" /></label>';
			$content .= '<input class="wdf_send_donation" type="submit" name="wdf_send_donation" value="'.$pledge_label.'" />';
			
			
			
		}
		
		$content = apply_filters('wdf_pledge_button',$content,$funder);
	
		if($echo) {echo $content;} else {return $content;}
	}
}
// Add our shortcodes
add_shortcode('fundraiser_panel', 'fundraiser_panel_shortcode');
add_shortcode('donate_button', 'donate_button_shortcode');
add_shortcode('progress_bar', 'wdf_progress_bar_shortcode');
?>