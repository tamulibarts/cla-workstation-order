<?php
/**
 * Workstation Ordering Application
 *
 * @package      Workstation Ordering Application
 * @since        0.1.0
 * @author       Zachary Watkins <zwatkins2@tamu.edu>
 * @link         https://github.tamu.edu/liberalarts-web/cla-workstation-order
 * @license      https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 *
 * @cla-workstation-order
 * Plugin Name:  Workstation Ordering Application
 * Plugin URI:   https://github.tamu.edu/liberalarts-web/cla-workstation-order
 * Description:  A GNU GPL 2.0 (or later) WordPress Plugin to facilitate product ordering within a robust, multi-role return / approve workflow using team-based settings.
 * Version:      1.1.0
 * Author:       Zachary Watkins
 * Author Email: zwatkins2@tamu.edu
 * Author URI:   https://github.com/zachwatkins
 * Text Domain:  cla-wso-textdomain
 * License:      GPL-2.0+
 * License URI:  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/* Define some useful constants */
define( 'CLA_WORKSTATION_ORDER_DIRNAME', 'cla-workstation-order' );
define( 'CLA_WORKSTATION_ORDER_TEXTDOMAIN', 'cla-wso-textdomain' );
define( 'CLA_WORKSTATION_ORDER_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CLA_WORKSTATION_ORDER_DIR_FILE', __FILE__ );
define( 'CLA_WORKSTATION_ORDER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'CLA_WORKSTATION_ORDER_TEMPLATE_PATH', CLA_WORKSTATION_ORDER_DIR_PATH . 'templates' );

/**
 * The core plugin class that is used to initialize the plugin.
 */
require CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-cla-workstation-order.php';
new CLA_Workstation_Order();

/* Activation hooks */
register_deactivation_hook( CLA_WORKSTATION_ORDER_DIR_FILE, 'cla_workstation_deactivation' );
register_activation_hook( CLA_WORKSTATION_ORDER_DIR_FILE, 'cla_workstation_activation' );

/**
 * Helper option flag to indicate rewrite rules need flushing
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_activation() {

	// Check for missing dependencies.
	$acf_pro = is_plugin_active( 'advanced-custom-fields-pro/acf.php' );

	if ( false === $acf_pro ) {

		$error = sprintf(
			/* translators: %s: URL for plugins dashboard page */
			__(
				'Plugin NOT activated: The <strong>WordPress Plugin</strong> plugin needs the <strong>Advanced Custom Fields Pro</strong> and <strong>Gravity Forms</strong> plugins to be activated first. <a href="%s">Back to plugins page</a>',
				'cla-workstation-order'
			),
			get_admin_url( null, '/plugins.php' )
		);
		wp_die( wp_kses_post( $error ) );

	} else {

		flush_rewrite_rules();

		// Add user roles.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-user-roles.php';
		$new_roles = new \CLA_Workstation_Order\User_Roles();
		$new_roles->register();

	}

}

/**
 * Unregister user roles and flush rewrite rules.
 *
 * @since 0.1.0
 * @return void
 */
function cla_workstation_deactivation() {
	flush_rewrite_rules();

	// Add user roles.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-user-roles.php';
		$new_roles = new \CLA_Workstation_Order\User_Roles();
		$new_roles->unregister();
}
