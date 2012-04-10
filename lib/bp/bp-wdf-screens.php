<?php

/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

/**
 * If your component uses a top-level directory, this function will catch the requests and load
 * the index page.
 *
 * @package BuddyPress_Template_Pack
 * @since 1.6
 */
function bp_wdf_directory_setup() {
	if ( bp_is_wdf_component() && !bp_current_action() && !bp_current_item() ) {
		// This wrapper function sets the $bp->is_directory flag to true, which help other
		// content to display content properly on your directory.
		bp_update_is_directory( true, 'wdf' );

		// Add an action so that plugins can add content or modify behavior
		do_action( 'bp_wdf_directory_setup' );

		bp_core_load_template( apply_filters( 'wdf_directory_template', 'wdf/index' ) );
	}
}
add_action( 'bp_screens', 'bp_wdf_directory_setup' );


/**
 * bp_wdf_screen_one()
 *
 * Sets up and displays the screen output for the sub nav item "wdf/screen-one"
 */
function bp_wdf_screen_one() {
	global $bp;

	/**
	 * There are three global variables that you should know about and you will
	 * find yourself using often.
	 *
	 * $bp->current_component (string)
	 * This will tell you the current component the user is viewing.
	 *
	 * Example: If the user was on the page http://example.org/members/andy/groups/my-groups
	 *          $bp->current_component would equal 'groups'.
	 *
	 * $bp->current_action (string)
	 * This will tell you the current action the user is carrying out within a component.
	 *
	 * Example: If the user was on the page: http://example.org/members/andy/groups/leave/34
	 *          $bp->current_action would equal 'leave'.
	 *
	 * $bp->action_variables (array)
	 * This will tell you which action variables are set for a specific action
	 *
	 * Example: If the user was on the page: http://example.org/members/andy/groups/join/34
	 *          $bp->action_variables would equal array( '34' );
	 *
	 * There are three handy functions you can use for these purposes:
	 *   bp_is_current_component()
	 *   bp_is_current_action()
	 *   bp_is_action_variable()
	 */

	/* Add a do action here, so your component can be extended by others. */
	do_action( 'bp_wdf_screen_one' );

	/****
	 * Displaying Content
	 */

	/****
	 * OPTION 1:
	 * You've got a few options for displaying content. Your first option is to bundle template files
	 * with your plugin that will be used to output content.
	 *
	 * In an earlier function bp_wdf_load_template_filter() we set up a filter on the core BP template
	 * loading function that will make it first look in the plugin directory for template files.
	 * If it doesn't find any matching templates it will look in the active theme directory.
	 *
	 * This example component comes bundled with a template for screen one, so we can load that
	 * template to display what we need. If you copied this template from the plugin into your theme
	 * then it would load that one instead. This allows users to override templates in their theme.
	 */

	/* This is going to look in wp-content/plugins/[plugin-name]/includes/templates/ first */
	bp_core_load_template( apply_filters( 'bp_wdf_template_screen_one', 'wdf/screen-one' ) );

	/****
	 * OPTION 2 (NOT USED FOR THIS SCREEN):
	 * If your component is simple, and you just want to insert some HTML into the user's active theme
	 * then you can use the bundle plugin template.
	 *
	 * There are two actions you need to hook into. One for the title, and one for the content.
	 * The functions you hook these into should simply output the content you want to display on the
	 * page.
	 *
	 * The follow lines are commented out because we are not using this method for this screen.
	 * You'd want to remove the OPTION 1 parts above and uncomment these lines if you want to use
	 * this option instead.
	 *
	 * Generally, this method of adding content is preferred, as it makes your plugin
	 * work better with a wider variety of themes.
 	 */

//	add_action( 'bp_template_title', 'bp_wdf_screen_one_title' );
//	add_action( 'bp_template_content', 'bp_wdf_screen_one_content' );

//	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}
	/***
	 * The second argument of each of the above add_action() calls is a function that will
	 * display the corresponding information. The functions are presented below:
	 */
	function bp_wdf_screen_one_title() {
		_e( 'Fundraiser Projects', 'wdf' );
	}

	function bp_wdf_screen_one_content() {
		global $bp;

		$donations = bp_wdf_get_donations_for_user( $bp->displayed_user->id );

		/**
		 * For security reasons, we MUST use the wp_nonce_url() function on any actions.
		 * This will stop naughty people from tricking users into performing actions without their
		 * knowledge or intent.
		 */
		$send_link = wp_nonce_url( $bp->displayed_user->domain . $bp->current_component . '/screen-one/send-donation', 'bp_wdf_send_donation' );
	?>
		<h4><?php _e( 'Your Fundraisers', 'wdf' ) ?></h4>
		<p><?php printf( __( 'Send %s a <a href="%s" title="Send high-five!">high-five!</a>', 'wdf' ), $bp->displayed_user->fullname, $send_link ) ?></p>

		<?php if ( $donations ) : ?>
			<h4><?php _e( 'Received Donations!', 'wdf' ) ?></h4>

			<table id="bp_wdf_donations">
				<?php foreach ( $donations as $user_id ) : ?>
				<tr>
					<td width="1%"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'width' => 25, 'height' => 25 ) ) ?></td>
					<td>&nbsp; <?php echo bp_core_get_userlink( $user_id ) ?></td>
	 			</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	<?php
	}

/**
 * bp_wdf_screen_two()
 *
 * Sets up and displays the screen output for the sub nav item "wdf/screen-two"
 */
function bp_wdf_screen_two() {
	global $bp;

	/**
	 * On the output for this second screen, as an example, there are terms and conditions with an
	 * "Accept" link (directs to http://example.org/members/andy/example/screen-two/accept)
	 * and a "Reject" link (directs to http://example.org/members/andy/example/screen-two/reject)
	 */

	if ( bp_is_wdf_component() && bp_is_current_action( 'screen-two' ) && bp_is_action_variable( 'accept', 0 ) ) {
		if ( bp_wdf_accept_terms() ) {
			/* Add a success message, that will be displayed in the template on the next page load */
			bp_core_add_message( __( 'Terms were accepted!', 'wdf' ) );
		} else {
			/* Add a failure message if there was a problem */
			bp_core_add_message( __( 'Terms could not be accepted.', 'wdf' ), 'error' );
		}

		/**
		 * Now redirect back to the page without any actions set, so the user can't carry out actions multiple times
		 * just by refreshing the browser.
		 */
		bp_core_redirect( bp_loggedin_user_domain() . bp_get_wdf_slug() );
	}

	if ( bp_is_wdf_component() && bp_is_current_action( 'screen-two' ) && bp_is_action_variable( 'reject', 0 ) ) {
		if ( bp_wdf_reject_terms() ) {
			/* Add a success message, that will be displayed in the template on the next page load */
			bp_core_add_message( __( 'Terms were rejected!', 'wdf' ) );
		} else {
			/* Add a failure message if there was a problem */
			bp_core_add_message( __( 'Terms could not be rejected.', 'wdf' ), 'error' );
		}

		/**
		 * Now redirect back to the page without any actions set, so the user can't carry out actions multiple times
		 * just by refreshing the browser.
		 */
		bp_core_redirect( bp_loggedin_user_domain() . bp_get_wdf_slug() );
	}

	/**
	 * If the user has not Accepted or Rejected anything, then the code above will not run,
	 * we can continue and load the template.
	 */
	do_action( 'bp_wdf_screen_two' );

	add_action( 'bp_template_title', 'bp_wdf_screen_two_title' );
	add_action( 'bp_template_content', 'bp_wdf_screen_two_content' );

	/* Finally load the plugin template file. */
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

	function bp_wdf_screen_two_title() {
		_e( 'Donations', 'wdf' );
	}

	function bp_wdf_screen_two_content() {
		global $bp; ?>

		<h4><?php _e( 'Recieved Donations', 'wdf' ) ?></h4>

		<?php
			$accept_link = '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->wdf->slug . '/screen-two/accept', 'bp_wdf_accept_terms' ) . '">' . __( 'Accept', 'wdf' ) . '</a>';
			$reject_link = '<a href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->wdf->slug . '/screen-two/reject', 'bp_wdf_reject_terms' ) . '">' . __( 'Reject', 'wdf' ) . '</a>';
		?>

		<p><?php printf( __( 'You must %s or %s the terms of use policy.', 'wdf' ), $accept_link, $reject_link ) ?></p>
	<?php
	}

/**
 * The following screen functions are called when the Settings subpanel for this component is viewed
 */
function bp_wdf_screen_settings_menu() {
	global $bp, $current_user, $bp_settings_updated, $pass_error;

	if ( isset( $_POST['submit'] ) ) {
		/* Check the nonce */
		check_admin_referer('bp-wdf-admin');

		$bp_settings_updated = true;

		/**
		 * This is when the user has hit the save button on their settings.
		 * The best place to store these settings is in wp_usermeta.
		 */
		update_user_meta( $bp->loggedin_user->id, 'bp-wdf-option-one', attribute_escape( $_POST['bp-wdf-option-one'] ) );
	}

	add_action( 'bp_template_content_header', 'bp_wdf_screen_settings_menu_header' );
	add_action( 'bp_template_title', 'bp_wdf_screen_settings_menu_title' );
	add_action( 'bp_template_content', 'bp_wdf_screen_settings_menu_content' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

	function bp_wdf_screen_settings_menu_header() {
		_e( 'Fundraising Settings Header', 'wdf' );
	}

	function bp_wdf_screen_settings_menu_title() {
		_e( 'Fundraising Settings', 'wdf' );
	}

	function bp_wdf_screen_settings_menu_content() {
		global $bp, $bp_settings_updated; ?>

		<?php if ( $bp_settings_updated ) { ?>
			<div id="message" class="updated fade">
				<p><?php _e( 'Changes Saved.', 'wdf' ) ?></p>
			</div>
		<?php } ?>

		<form action="<?php echo $bp->loggedin_user->domain . 'settings/wdf-admin'; ?>" name="bp-wdf-admin-form" id="account-delete-form" class="bp-wdf-admin-form" method="post">

			<input type="checkbox" name="bp-wdf-option-one" id="bp-wdf-option-one" value="1"<?php if ( '1' == get_user_meta( $bp->loggedin_user->id, 'bp-wdf-option-one', true ) ) : ?> checked="checked"<?php endif; ?> /> <?php _e( 'Do you love clicking checkboxes?', 'wdf' ); ?>
			<p class="submit">
				<input type="submit" value="<?php _e( 'Save Settings', 'wdf' ) ?> &raquo;" id="submit" name="submit" />
			</p>

			<?php
			/* This is very important, don't leave it out. */
			wp_nonce_field( 'bp-wdf-admin' );
			?>

		</form>
	<?php
	}
?>