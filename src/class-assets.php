<?php
/**
 * The file that defines css and js files loaded for the plugin
 *
 * A class definition that includes css and js files used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-assets.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @since 1.0.0
 */
class Assets {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Register global styles used in the theme.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Enqueue extension styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

	}

	/**
	 * Registers all styles used within the plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_admin_styles() {

		wp_register_style(
			'cla-workstation-order-admin-styles',
			CLA_WORKSTATION_ORDER_DIR_URL . 'css/admin.css',
			false,
			filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'css/admin.css' ),
			'screen'
		);

	}

	/**
	 * Registers all scripts used within the plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_admin_scripts() {

		wp_register_script(
			'cla-workstation-order-admin-script',
			CLA_WORKSTATION_ORDER_DIR_URL . 'js/admin-wsorder.js',
			false,
			filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/admin-wsorder.js' ),
			true
		);

	}

	/**
	 * Enqueues extension styles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue_admin_styles() {

		wp_enqueue_style( 'cla-workstation-order-admin-styles' );

	}

}
