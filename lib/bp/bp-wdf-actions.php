<?php

/**
 * Check to see if a donation is being given, and if so, save it.
 *
 * Hooked to bp_actions, this function will fire before the screen function. We use our function
 * bp_is_wdf_component(), along with the bp_is_current_action() and bp_is_action_variable()
 * functions, to detect (based on the requested URL) whether the user has clicked on "send high
 * five". If so, we do a bit of simple logic to see what should happen next.
 *
 * @package BuddyPress_Skeleton_Component
 * @since 1.6
 */
function bp_wdf_donation_save() {

	if ( bp_is_wdf_component() && bp_is_current_action( 'screen-one' ) && bp_is_action_variable( 'send-donation', 0 ) ) {
		// The logged in user has donated

		if ( bp_is_my_profile() ) {
			// Don't let users donate to themselves
			bp_core_add_message( __( 'You can\'t fund yourself :', 'wdf' ), 'error' );
		} else {
			if ( bp_wdf_send_donation( bp_displayed_user_id(), bp_loggedin_user_id() ) )
				bp_core_add_message( __( 'Funding Sent!', 'wdf' ) );
			else
				bp_core_add_message( __( 'Funding could not be sent.', 'wdf'), 'error' );
		}

		bp_core_redirect( bp_displayed_user_domain() . bp_get_wdf_slug() . '/screen-one' );
	}
}
add_action( 'bp_actions', 'bp_wdf_donation_save' );

?>