<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-cla-workstation-order.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

/**
 * The core plugin class
 *
 * @since 1.0.0
 * @return void
 */
class CLA_Workstation_Order {

	/**
	 * File name
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * Instance
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Handle GravityForms leads.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-assets.php';
		new \CLA_Workstation_Order\Assets();

		// Create post types.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-wsorder-posttype.php';
		new \CLA_Workstation_Order\WSOrder_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-product-posttype.php';
		new \CLA_Workstation_Order\Product_PostType();

		// require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-bundle-posttype.php';
		// new \CLA_Workstation_Order\Bundle_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-program-posttype.php';
		new \CLA_Workstation_Order\Program_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-department-posttype.php';
		new \CLA_Workstation_Order\Department_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-pagetemplate.php';
		$order_form = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'order-form-template.php', 'Order Form' );
		$order_form->register();

		// Register settings page.
		add_action( 'acf/init', array( $this, 'register_settings_page' ) );

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'init', array( $this, 'stop_guests' ) );

	}

	/**
	 * Initialization hook.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {

		// Create product category taxonomy.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-taxonomy.php';
		new \CLA_Workstation_Order\Taxonomy(
			array('Product Category', 'Product Categories'),
			'product-category',
			array('product', 'bundle'),
			array(),
			array(),
			'',
			true
		);

	}

	/**
	 * Register the settings page
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_settings_page() {

		if ( function_exists( 'acf_add_options_page' ) ) {

			require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/settings-fields.php';

			acf_add_options_page(
				array(
					'page_title' => 'Workstation Order Settings',
					'menu_title' => 'WSO Settings',
					'menu_slug'  => 'wsorder-settings',
					'capability' => 'manage_options',
					'redirect'   => false,
				)
			);

		}
	}


	public function stop_guests() {

    if ( $GLOBALS['pagenow'] !== 'wp-login.php' && ! is_user_logged_in() ) {
      auth_redirect();
    }

	}

}
