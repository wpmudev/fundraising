<?php
/**
 * Makes sure we have everything we absolutely need.
 */
class Wdd_Installer {
	
	/**
	 * @access public
	 * @static
	 */
	static function check () {
		$is_installed = get_option('wdsm', false);
		if (!$is_installed) Wdsm_Installer::install();
	}
	
	/**
	 * @access private
	 * @static
	 */
	function install () {
		$me = new Wdsm_Installer;
		$me->create_default_options();
	}
	
	/**
	 * @access private
	 */
	function create_default_options () {
		update_option('wdd_settings', array (
			''
		));
	}
}
