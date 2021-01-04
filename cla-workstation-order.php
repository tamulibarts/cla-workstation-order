<?php
/**
 * Workstation Ordering System
 *
 * @package      Workstation Ordering System
 * @author       Zachary Watkins
 * @license      GPL-2.0+
 *
 * @cla-workstation-order
 * Plugin Name:  Workstation Ordering System
 * Plugin URI:   https://github.tamu.edu/liberalarts-web/cla-workstation-order
 * Description:  A WordPress plugin for ordering workstations for the Texas A&M College of Liberal Arts
 * Version:      0.1.0
 * Author:       Zachary Watkins
 * Author URI:   https://github.com/ZachWatkins
 * Author Email: watkinza@gmail.com
 * Text Domain:  cla-workstation-order
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/* Define some useful constants */
define( 'WORDPRESS_PLUGIN_DIRNAME', 'cla-workstation-order' );
define( 'WORDPRESS_PLUGIN_TEXTDOMAIN', 'cla-workstation-order' );
define( 'WORDPRESS_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESS_PLUGIN_DIR_FILE', __FILE__ );
define( 'WORDPRESS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WORDPRESS_PLUGIN_TEMPLATE_PATH', WORDPRESS_PLUGIN_DIR_PATH . 'templates' );

/**
 * The core plugin class that is used to initialize the plugin.
 */
require WORDPRESS_PLUGIN_DIR_PATH . 'src/class-cla-workstation-order.php';
new WordPress_Plugin();

/* Activation hooks */
register_deactivation_hook( WORDPRESS_PLUGIN_DIR_FILE, 'flush_rewrite_rules' );
register_activation_hook( WORDPRESS_PLUGIN_DIR_FILE, 'wordpress_plugin_activation' );

/**
 * Helper option flag to indicate rewrite rules need flushing
 *
 * @since 1.0.0
 * @return void
 */
function wordpress_plugin_activation() {

	// Check for missing dependencies.
	$acf_pro       = is_plugin_active( 'advanced-custom-fields-pro/acf.php' );
	$gravity_forms = is_plugin_active( 'advanced-custom-fields-pro/acf.php' );

	if ( true === $plugin ) {

		$error = sprintf(
			/* translators: %s: URL for plugins dashboard page */
			__(
				'Plugin NOT activated: The <strong>WordPress Plugin</strong> plugin needs the <strong>Akismet</strong> plugin to NOT be activated. <a href="%s">Back to plugins page</a>',
				'cla-workstation-order-textdomain'
			),
			get_admin_url( null, '/plugins.php' )
		);
		wp_die( wp_kses_post( $error ) );

	} else {

		update_option( 'wordpress_plugin_permalinks_flushed', 0 );

	}

}
