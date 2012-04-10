<?php

/**
 * NOTE: You should always use the wp_enqueue_script() and wp_enqueue_style() functions to include
 * javascript and css files.
 */

/**
 * bp_wdf_add_js()
 *
 * This function will enqueue the components javascript file, so that you can make
 * use of any javascript you bundle with your component within your interface screens.
 */
function bp_wdf_add_js() {
	global $bp;

	if ( $bp->current_component == $bp->wdf->slug )
		wp_enqueue_script( 'bp-wdf-js', BP_WDF_PLUGIN_BASE_DIR . '/js/general.js' );
}
add_action( 'template_redirect', 'bp_wdf_add_js', 1 );

?>