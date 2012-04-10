<?php
require_once( WDF_PLUGIN_BASE_DIR . '/lib/external/class.pointers_tutorial.php' );
		
$tutorial = new Pointer_Tutorial('wdf_tutorial', true, false);

if(isset($_POST['wdf_restart_tutorial']))
	$tutorial->restart();

$tutorial->set_textdomain = 'wdf';

$tutorial->add_style('');

$tutorial->set_capability = 'manage_options';

$tutorial->add_step(admin_url('admin.php?page=wdf'), 'funder_page_wdf', '#icon-wdf-admin', __('Getting Started Is Easy', 'wdf'), array(
		'content'  => '<p>' . esc_js( __('Follow these tutorial steps to get your Fundraising project up and running quickly.', 'wdf') ) . '</p>',
		'position' => array( 'edge' => 'top', 'align' => 'left' ),
	));
$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_currency', __('Choose your currency', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Choose your preferred currency for your incoming donations.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
));
if(!get_option('permalink_structure')) {
	$tutorial->add_step(admin_url('options-permalink.php'), 'options-permalink.php', '#permalink_structure', __('Turn On Permalinks', 'wdf'), array(
		'content'  => '<p>' . esc_js( __('Permalinks must been enabled and configured before your donation page can be seen publicly.', 'wdf') ) . '</p>',
		'position' => array( 'edge' => 'top', 'align' => 'left' ),
	));
}
$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_slug', __('Choose your permalink slug', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Choose a base url for all your donations.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'top', 'align' => 'left' ),
));
$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_paypal_email', __('Insert Your PayPal Email', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Insert a personal or business PayPal email address. ', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
));
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#titlediv', __('Give Your Fundraiser A Title', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Enter a title that best describes your fundraiser.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
));
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_goals', __('Set A Goal?', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('If you set a goal for your fundraiser your sites visitors will be able to see how close you are to your goal.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'bottom', 'align' => 'bottom' ), 'post_type' => 'funder',
));
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_levels_table', __('Recommend Donation Levels', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('You can recommend donation levels to your visitors, provide a title, short description, and dollar amount for each level you create.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'bottom', 'align' => 'right' ), 'post_type' => 'funder',
));	
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_messages', __('Create Thank You Messages and Emails', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Send the user back to a specific url, any post or page ID, or enter a custom thank you message customizable with shortcodes.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'bottom', 'align' => 'right' ), 'post_type' => 'funder',
));	
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_style', __('Choose A Style', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Choose a style that best fits your site, or apply no styles and use your own custom css.', 'wdf') ) . '</p>',
	'position' => array( 'edge' => 'right', 'align' => 'left' ), 'post_type' => 'funder',
));
$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#submitdiv', __('Publish or Save As Draft', 'wdf'), array(
	'content'  => '<p>' . esc_js( __('Publish your fundraiser, or save it as a draft.  Now start fundraising!  You can use the fundraiser url or insert the fundraising shortcodes directly into any page or post.', 'wdf')) . '</p>',
	'position' => array( 'edge' => 'right', 'align' => 'left' ), 'post_type' => 'funder',
));
$tutorial->initialize();
?>