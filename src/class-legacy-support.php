<?php
/**
 * The file that helps support legacy code from many sources.
 *
 * @link       https://github.tamu.edu/zachwatkins/cla-workstation-order/blob/master/src/class-legacy-support.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Builds and registers a custom taxonomy.
 *
 * @package cla-workstation-order
 * @since 1.0.0
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

		// Disable Joseph's CAS plugin when developing locally and not logged in yet.
		if (
			isset( $_GET['localwp_auto_login'] )
			&& defined( 'WP_ENVIRONMENT_TYPE' )
			&& 'local' === WP_ENVIRONMENT_TYPE
			&& defined( 'WP_ADMIN' )
			&& (
				! defined( 'DOING_AJAX' )
				|| false === boolval( DOING_AJAX )
			)
			&& (
				! defined( 'DOING_CRON' )
				|| false === boolval( DOING_CRON )
			)
		) {
			add_action( 'plugins_loaded', function(){
				error_log('removing filters and actions');
				remove_filter('authenticate', array('IUCASAuthentication', 'authenticate_filter'), 30, 3 );
				remove_filter('login_redirect', array('IUCASAuthentication', 'remove_ticket_from_redirect') );
				remove_action('wp_logout', array('IUCASAuthentication', 'logout'));
				remove_filter('show_password_fields', array('IUCASAuthentication', 'show_password_fields'));
				remove_action('check_passwords', array('IUCASAuthentication', 'check_passwords'), 10, 3);
				remove_filter('login_url', array('IUCASAuthentication', 'casify_login_url'));
				remove_action( 'init', 'cla_forcelogin' );
			});
		} else {
			error_log( 'Condition not met. Logging out.' );
			add_action( 'plugins_loaded', function(){
				wp_logout();
			});
		}
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
		) {

			remove_filter( 'login_url', array( 'IUCASAuthentication', 'casify_login_url' ) );

		}
	}

}
