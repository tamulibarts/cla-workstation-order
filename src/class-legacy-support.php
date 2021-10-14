<?php
/**
 * The file that helps support legacy code from many sources.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-legacy-support.php
 * @since      1.1.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Builds and registers a custom taxonomy.
 *
 * @package cla-workstation-order
 * @since 1.1.0
 */
class Legacy_Support {

	/**
	 * Construct the class object instance.
	 *
	 * @return Legacy_Support
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'uncasify_user_switching' ) );
		add_action( 'admin_init', array( $this, 'uncasify_user_switching' ) );

	}

	/**
	 * Remove the filter that reroutes logins through CAS authentication if the current user can switch users.
	 *
	 * @return void
	 */
	public function uncasify_user_switching() {

		if (
			true === is_user_logged_in()
			&& true === method_exists( 'user_switching', 'maybe_switch_url' )
			&& true === current_user_can( 'switch_users' )
			&& class_exists( 'IUCASAuthentication' )
			&& method_exists( 'IUCASAuthentication', 'casify_login_url' )
		) {

			remove_filter( 'login_url', array( 'IUCASAuthentication', 'casify_login_url' ) );

		}
	}

}
