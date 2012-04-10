<?php
if(!class_exists('WDF_Gateway_Authorize_AIM')) {
	class WDF_Gateway_Authorize_AIM extends WDF_Gateway {
		
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = 'authorize_aim';
		
		//name of your gateway, for the admin side.
		var $admin_name = 'Authorize.net';
		
		//public name of your gateway, for lists and such.
		var $public_name = 'Credit Card';
		
		//whether or not ssl is needed for checkout page
		var $force_ssl = false;
		
		var $payment_types = array('simple','standard','advanced');
		
		// If you are redirecting to a 3rd party make sure this is set to true
		var $skip_form = false;
		
		// Allow recurring payments with your gateway
		var $allow_reccuring = false;
		
		function on_creation() {
			$settings = get_option('wdf_settings');

			  $this->API_Username = $settings['aim']['api_user'];
			  $this->API_Password = $settings['aim']['api_pass'];
			  $this->API_Signature = $settings['aim']['api_sig'];
			  $this->currencyCode = $settings['currency'];
			  //$this->locale = $settings['aim']['locale'];
			  //set api urls
			  if ($settings['aim']['mode'] == 'sandbox')	{
				$this->API_Endpoint = "https://test.authorize.net/gateway/transact.dll";
			  } else {
				$this->API_Endpoint = "https://secure.authorize.net/gateway/transact.dll";
			  }
			
		}
		function payment_form() {
			global $post, $wdf;
			$content = '';
			
			 $content .= '<style type="text/css">
				.cardimage {
				  height: 23px;
				  width: 157px;
				  display: inline-table;
				}
				
				.nocard {
				  background-position: 0px 0px !important;
				}
				
				.visa_card {
				  background-position: 0px -23px !important;
				}
				
				.mastercard {
				  background-position: 0px -46px !important;
				}
				
				.discover_card {
				  background-position: 0px -69px !important;
				}
				
				.amex {
				  background-position: 0px -92px !important;
				}
			  </style>
			  <script type="text/javascript">
				function cc_card_pick(card_image, card_num){
				  if (card_image == null) {
						  card_image = "#cardimage";
				  }
				  if (card_num == null) {
						  card_num = "#card_num";
				  }
		  
				  numLength = jQuery(card_num).val().length;
				  number = jQuery(card_num).val();
				  if (numLength > 10)
				  {
						  if((number.charAt(0) == "4") && ((numLength == 13)||(numLength==16))) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage visa_card"); }
						  else if((number.charAt(0) == "5" && ((number.charAt(1) >= "1") && (number.charAt(1) <= "5"))) && (numLength==16)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage mastercard"); }
						  else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage amex"); }
						  else if((number.charAt(0) == "3" && ((number.charAt(1) == "4") || (number.charAt(1) == "7"))) && (numLength==15)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage discover_card"); }
						  else { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage nocard"); }
		  
				  }
				}
				jQuery(document).ready( function() {
				  jQuery(".noautocomplete").attr("autocomplete", "off");
				});
			  </script>';
			$content .= apply_filters('wdf_error_aim_cc', '' );
			$content .= '<table class="wdf_billing_info">
				<thead><tr>
				  <th colspan="2">'.__('Enter Your Billing Information:', 'wdf').'</th>
				</tr></thead>
				<tbody>
				<tr>
				  <td align="right">'.__('Email:', 'wdf').'*</td><td>
				'.apply_filters( 'wdf_error_aim_email', '' ).'
				<input size="35" name="email" type="text" value="'.esc_attr($email).'" /></td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Full Name:', 'wdf').'*</td><td>
				'.apply_filters( 'wdf_error_aim_name', '' ).'
				<input size="35" name="name" type="text" value="'.esc_attr($name).'" /> </td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Address:', 'wdf').'*</td><td>
				'.apply_filters( 'wdf_error_aim_address', '' ).'
				<input size="45" name="address1" type="text" value="'.esc_attr($address1).'" /><br />
				<small><em>'.__('Street address, P.O. box, company name, c/o', 'mp').'</em></small>
				</td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Address 2:', 'wdf').'&nbsp;</td><td>
				<input size="45" name="address2" type="text" value="'.esc_attr($address2).'" /><br />
				<small><em>'.__('Apartment, suite, unit, building, floor, etc.', 'mp').'</em></small>
				</td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('City:', 'mp').'*</td><td>
				'.apply_filters( 'wdf_error_aim_city', '' ).'
				<input size="25" name="city" type="text" value="'.esc_attr($city).'" /></td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('State/Province/Region:', 'mp').'*</td><td>
				'.apply_filters( 'wdf_error_aim_state', '' ).'
				<input size="15" name="state" type="text" value="'.esc_attr($state).'" /></td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Postal/Zip Code:', 'mp').'*</td><td>
				'.apply_filters( 'wdf_error_aim_zip', '' ).'
				<input size="10" id="mp_zip" name="zip" type="text" value="'.esc_attr($zip).'" /></td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Country:', 'mp').'*</td><td>
				  '.apply_filters( 'mp_checkout_error_country', '' ).'
				<select id="mp_" name="country">';
		
				  foreach ($wdf->countries as $code => $name) {
					$content .= '<option value="'.$code.'"'.selected($country, $code, false).'>'.esc_attr($name).'</option>';
				  }
				
			  $content .= '</select>
				</td>
				  </tr>
		  
				  <tr>
				  <td align="right">'.__('Phone Number:', 'mp').'</td><td>
					<input size="20" name="phone" type="text" value="'.esc_attr($phone).'" /></td>
				  </tr>
				  
				  <tr>
					<td align="right">'.__('Credit Card Number:', 'mp').'*</td>
					<td>
					  '.apply_filters( 'mp_checkout_error_card_num', '' ).'
					  <input name="card_num" onkeyup="cc_card_pick(\'#cardimage\', \'#card_num\');"
					   id="card_num" class="credit_card_number input_field noautocomplete"
					   type="text" size="22" maxlength="22" />
						<div class="hide_after_success nocard cardimage"  id="cardimage" style="background: url('.WDF_PLUGIN_BASE_DIR.'/img/card_array.png) no-repeat;"></div></td>
				  </tr>
				  
				  <tr>
					<td align="right">'.__('Expiration Date:', 'mp').'*</td>
					<td>
					'.apply_filters( 'mp_checkout_error_exp', '' ).'
					<label class="inputLabel" for="exp_month">'.__('Month', 'mp').'</label>
						<select name="exp_month" id="exp_month">
						  '.$this->_print_month_dropdown().'
						</select>
						<label class="inputLabel" for="exp_year">'.__('Year', 'mp').'</label>
						<select name="exp_year" id="exp_year">
						  '.$this->_print_year_dropdown('', true).'
						</select>
						</td>
				  </tr>
				  
				  <tr>
					<td align="right">'.__('Security Code:', 'mp').'</td>
					<td>'.apply_filters( 'mp_checkout_error_card_code', '' ).'
					<input id="card_code" name="card_code" class="input_field noautocomplete"
					   style="width: 70px;" type="text" size="4" maxlength="4" /></td>
				  </tr>
		  
				</tbody>
			  </table>';
			  
				return $content;
		}
		function _print_year_dropdown($sel='', $pfp = false) {
			$localDate=getdate();
			$minYear = $localDate["year"];
			$maxYear = $minYear + 15;
			
			$output = "<option value=''>--</option>";
			for($i=$minYear; $i<$maxYear; $i++) {
				if ($pfp) {
						$output .= "<option value='". substr($i, 0, 4) ."'".($sel==(substr($i, 0, 4))?' selected':'').
						">". $i ."</option>";
				} else {
						$output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
				">". $i ."</option>";
				}
			}
			return($output);
		}
		
		function _print_month_dropdown($sel='') {
			$output =  "<option value=''>--</option>";
			$output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
			$output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
			$output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
			$output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
			$output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
			$output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
			$output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
			$output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
			$output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
			$output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
			$output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
			$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Dec</option>";
			
			return($output);
		}
		function create_query() {
		
		}
		function process_simple() {
			$settings = get_option('wdf_settings');
			$payment = new WDF_Gateway_Worker_AuthorizeNet_AIM($this->API_Endpoint,
				$settings['aim']['delim_data'],
				$settings['aim']['delim_char'],
				$settings['aim']['encap_char'],
				$settings['aim']['api_user'],
				$settings['aim']['api_key'],
			  ($settings['aim']['mode'] == 'sandbox'));
			
			$payment->transaction($_POST['card_num']);
			
			$payment->setParameter("x_card_code", $_POST['card_code']);
		    $payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
		    $payment->setParameter("x_amount", $_SESSION['wdf_pledge']);
			
			$payment->setParameter("x_description", "Order ID: ".$_SESSION['mp_order']);
			$payment->setParameter("x_invoice_num",  $_SESSION['mp_order']);
			if ($settings['aim']['mode'] == 'sandbox')	{
			  $payment->setParameter("x_test_request", true);
			} else {
			  $payment->setParameter("x_test_request", false);
			}
			$payment->setParameter("x_duplicate_window", 30);
			$payment->setParameter("x_header_email_receipt", $settings['aim']['header_email_receipt']);
			$payment->setParameter("x_footer_email_receipt", $settings['aim']['footer_email_receipt']);
			$payment->setParameter("x_email_customer", strtoupper($settings['aim']['email_customer']));
			
			$payment->setParameter("x_customer_ip", $_SERVER['REMOTE_ADDR']);
			
			$payment->process();
			
			if ($payment->isApproved()) {
				wp_redirect(wdf_get_funder_page('confirmation',$_SESSION['funder_id']));
				exit;
			} else {
				//$this->create_gateway_error( __('There was an error processing your payment.','wdf'));
				wp_redirect(wdf_get_funder_page('checkout', $_SESSION['funder_id']));
				exit;
			}
		}
		function process_standard() {
			$this->process_simple();
		}
		function process_advanced() {
			$settings = get_option('wdf_settings');
			$payment = new WDF_Gateway_Worker_AuthorizeNet_AIM($this->API_Endpoint,
				$settings['aim']['delim_data'],
				$settings['aim']['delim_char'],
				$settings['aim']['encap_char'],
				$settings['aim']['api_user'],
				$settings['aim']['api_key'],
			  ($settings['aim']['mode'] == 'sandbox'));
			  
			  var_export($payment);
			  die();
		}
		function payment_info( $content, $transaction ) {
			$content = 'Information from the gateway';
			return $content;
		}
		function confirm() {
			add_filter('wdf_gateway_payment_info', array(&$this,'payment_info'), 10, 2);
		}
		function process_ipn() {
			
		}
		function admin_settings() {
			$settings = get_option('wdf_settings');
			?>
			
			<table class="form-table">
				  <tr>
				    <th scope="row"><?php _e('Mode', 'mp') ?></th>
				    <td>
			        <p>
			          <select name="wdf_settings[aim][mode]">
			            <option value="sandbox" <?php selected($settings['aim']['mode'], 'sandbox') ?>><?php _e('Sandbox', 'wdf') ?></option>
			            <option value="live" <?php selected($settings['aim']['mode'], 'live') ?>><?php _e('Live', 'wdf') ?></option>
			          </select>
			        </p>
				    </td>
				  </tr>
				  <tr>
				    <th scope="row"><?php _e('Gateway Credentials', 'mp') ?></th>
				    <td>
			              <span class="description"><?php print sprintf(__('You must login to Authorize.net merchant dashboard to obtain the API login ID and API transaction key. <a target="_blank" href="%s">Instructions &raquo;</a>', 'mp'), "http://www.authorize.net/support/merchant/Integration_Settings/Access_Settings.htm"); ?></span>
				      <p>
					<label><?php _e('Login ID', 'mp') ?><br />
					  <input value="<?php echo esc_attr($settings['aim']['api_user']); ?>" size="30" name="wdf_settings[aim][api_user]" type="text" />
					</label>
				      </p>
				      <p>
					<label><?php _e('Transaction Key', 'mp') ?><br />
					  <input value="<?php echo esc_attr($settings['aim']['api_key']); ?>" size="30" name="wdf_settings[aim][api_key]" type="text" />
					</label>
				      </p>
				    </td>
				  </tr>
			          <tr>
				    <th scope="row"><?php _e('Advanced Settings', 'mp') ?></th>
				    <td>
				      <span class="description"><?php _e('Optional settings to control advanced options', 'mp') ?></span>
			              <p>
			                <label><a title="<?php _e('Authorize.net default is \',\'. Otherwise, get this from your credit card processor. If the transactions are not going through, this character is most likely wrong.', 'mp'); ?>"><?php _e('Delimiter Character', 'mp'); ?></a><br />
			                  <input value="<?php echo (empty($settings['aim']['delim_char']))?",":esc_attr($settings['aim']['delim_char']); ?>" size="2" name="wdf_settings[aim][delim_char]" type="text" />
			                </label>
				      </p>

			              <p>
					<label><a title="<?php _e('Authorize.net default is blank. Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong.', 'mp'); ?>"><?php _e('Encapsulation Character', 'mp'); ?></a><br />
			                  <input value="<?php echo esc_attr($settings['aim']['encap_char']); ?>" size="2" name="wdf_settings[aim][encap_char]" type="text" />
			                </label>
			              </p>

			              <p>
					<label><?php _e('Email Customer (on success):', 'mp'); ?><br />
			                  <select name="wdf_settings[aim][email_customer]">
			                    <option value="yes" <?php selected($settings['aim']['email_customer'], 'yes') ?>><?php _e('Yes', 'mp') ?></option>
			                    <option value="no" <?php selected($settings['aim']['email_customer'], 'no') ?>><?php _e('No', 'mp') ?></option>
			                  </select>
			                </label>
			              </p>

			              <p>
					<label><a title="<?php _e('This text will appear as the header of the email receipt sent to the customer.', 'mp'); ?>"><?php _e('Customer Receipt Email Header', 'mp'); ?></a><br/>
			                  <input value="<?php echo empty($settings['aim']['header_email_receipt'])?__('Thanks for your payment!', 'mp'):esc_attr($settings['aim']['header_email_receipt']); ?>" size="40" name="wdf_settings[aim][header_email_receipt]" type="text" />
			                </label>
				      </p>

			              <p>
					<label><a title="<?php _e('This text will appear as the footer on the email receipt sent to the customer.', 'mp'); ?>"><?php _e('Customer Receipt Email Footer', 'mp'); ?></a><br/>
			                  <input value="<?php echo empty($settings['aim']['footer_email_receipt']) ? '' : esc_attr($settings['aim']['footer_email_receipt']); ?>" size="40" name="wdf_settings[aim][footer_email_receipt]" type="text" />
			                </label>
				      </p>

			              <p>
					<label><a title="<?php _e('The payment gateway generated MD5 hash value that can be used to authenticate the transaction response. Not needed because responses are returned using an SSL connection.', 'mp'); ?>"><?php _e('Security: MD5 Hash', 'mp'); ?></a><br/>
			                  <input value="<?php echo esc_attr($settings['aim']['md5_hash']); ?>" size="32" name="wdf_settings[aim][md5_hash]" type="text" />
			                </label>
			              </p>

			              <p>
					<label><a title="<?php _e('Request a delimited response from the payment gateway.', 'mp'); ?>"><?php _e('Delim Data:', 'mp'); ?></a><br/>
			                  <select name="wdf_settings[aim][delim_data]">
			                    <option value="yes" <?php selected($settings['aim']['delim_data'], 'yes') ?>><?php _e('Yes', 'mp') ?></option>
			                    <option value="no" <?php selected($settings['aim']['delim_data'], 'no') ?>><?php _e('No', 'mp') ?></option>
			                  </select>
			                </label>
			              </p>

				    </td>
				  </tr>
        </table>
			
			<?php 
		}
		function save_gateway_settings() {
			if( isset($_POST['wdf_settings']['aim']) ) {
				// Init array for new settings
				$new = array();
				
				// Advanced Settings
				if( is_array($_POST['wdf_settings']['aim'])) {
					$new['aim'] = $_POST['wdf_settings']['aim'];
					$new['aim'] = array_map('esc_attr',$new['aim']);
					
					$settings = get_option('wdf_settings');
					$settings = array_merge($settings,$new);
					update_option('wdf_settings',$settings);
				}
					
			}
		}
	}
}

if(!class_exists('WDF_Gateway_Worker_AuthorizeNet_AIM')) {
  class WDF_Gateway_Worker_AuthorizeNet_AIM
  {
    var $login;
    var $transkey;
    var $params   = array();
    var $results  = array();
    var $line_items = array();

    var $approved = false;
    var $declined = false;
    var $error    = true;
    var $method   = "";

    var $fields;
    var $response;

    var $instances = 0;

    function __construct($url, $delim_data, $delim_char, $encap_char, $gw_username, $gw_tran_key, $gw_test_mode)
    {
      if ($this->instances == 0)
      {
	$this->url = $url;

	$this->params['x_delim_data']     = $delim_data;
	$this->params['x_delim_char']     = $delim_char;
	$this->params['x_encap_char']     = $encap_char;
	$this->params['x_relay_response'] = "FALSE";
	$this->params['x_url']            = "FALSE";
	$this->params['x_version']        = "3.1";
	$this->params['x_method']         = "CC";
	$this->params['x_type']           = "AUTH_CAPTURE";
	$this->params['x_login']          = $gw_username;
	$this->params['x_tran_key']       = $gw_tran_key;
	$this->params['x_test_request']   = $gw_test_mode;

	$this->instances++;
      } else {
	return false;
      }
    }

    function transaction($cardnum)
    {
      $this->params['x_card_num']  = trim($cardnum);
    }
    
    function addLineItem($id, $name, $description, $quantity, $price, $taxable = 0)
    {
      $this->line_items[] = "{$id}<|>{$name}<|>{$description}<|>{$quantity}<|>{$price}<|>{$taxable}";
    }

    function process($retries = 1)
    {
      global $wdf;
      
      $this->_prepareParameters();
      $query_string = rtrim($this->fields, "&");

      $count = 0;
      while ($count < $retries)
      {
        $args['user-agent'] = "WPMU Fundraising/{$wdf->version}: http://premium.wpmudev.org/project/fundraising | Authorize.net AIM Plugin/{$wdf->version}";
        $args['body'] = $query_string;
        $args['sslverify'] = false;
				$args['timeout'] = 30;
        
        //use built in WP http class to work with most server setups
        $response = wp_remote_post($this->url, $args);
        
        if (is_array($response) && isset($response['body'])) {
          $this->response = $response['body'];
        } else {
          $this->response = "";
          $this->error = true;
          return;
        }
        
	$this->parseResults();
        
	if ($this->getResultResponseFull() == "Approved")
	{
          $this->approved = true;
	  $this->declined = false;
	  $this->error    = false;
          $this->method   = $this->getMethod();
	  break;
	} else if ($this->getResultResponseFull() == "Declined")
	{
          $this->approved = false;
	  $this->declined = true;
	  $this->error    = false;
	  break;
	}
	$count++;
      }
    }

    function parseResults()
    {
      $this->results = explode($this->params['x_delim_char'], $this->response);
    }

    function setParameter($param, $value)
    {
      $param                = trim($param);
      $value                = trim($value);
      $this->params[$param] = $value;
    }

    function setTransactionType($type)
    {
      $this->params['x_type'] = strtoupper(trim($type));
    }

    function _prepareParameters()
    {
      foreach($this->params as $key => $value)
      {
	$this->fields .= "$key=" . urlencode($value) . "&";
      }
      for($i=0; $i<count($this->line_items); $i++) {
        $this->fields .= "x_line_item={$this->line_items[$i]}&";
      }
    }
    
    function getMethod()
    {
      if (isset($this->results[51]))
      {
        return str_replace($this->params['x_encap_char'],'',$this->results[51]);
      }
      return "";
    }

    function getGatewayResponse()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[0]);
    }

    function getResultResponseFull()
    {
      $response = array("", "Approved", "Declined", "Error");
      return $response[str_replace($this->params['x_encap_char'],'',$this->results[0])];
    }

    function isApproved()
    {
      return $this->approved;
    }

    function isDeclined()
    {
      return $this->declined;
    }

    function isError()
    {
      return $this->error;
    }

    function getResponseText()
    {
      return $this->results[3];
      $strip = array($this->params['x_delim_char'],$this->params['x_encap_char'],'|',',');
      return str_replace($strip,'',$this->results[3]);
    }

    function getAuthCode()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[4]);
    }

    function getAVSResponse()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[5]);
    }

    function getTransactionID()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[6]);
    }
  }
}
wdf_register_gateway_plugin('WDF_Gateway_Authorize_AIM', 'authorize_aim', 'Authorize.net', array('simple','standard','advanced'));
?>