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

		// Add custom fields.
		add_action( 'acf/init', array( $this, 'load_custom_fields' ) );

		// Handle GravityForms leads.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-leads-helper.php';
		new \CLA_Workstation_Order\Leads_Helper();

		// Create post types.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-wsorder-posttype.php';
		new \CLA_Workstation_Order\WSOrder_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-product-posttype.php';
		new \CLA_Workstation_Order\Product_PostType();

	}

	/**
	 * Add custom fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_custom_fields() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/order-fields.php';

	}

	/**
	 * Initialize page templates
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function register_templates() {

		// Register page templates.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-pagetemplate.php';
		$landing = new \WordPress_Plugin\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'page-template.php', 'Landing Page' );
		$landing->register();

	}

	/**
	 * Init action hook
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {

		$this->register_shortcodes();

	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public static function register_shortcodes() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-shortcode.php';
		new \WordPress_Plugin\Shortcode();

	}

	/**
	 * Register widgets
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function register_widgets() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-widget.php';
		$widget = new \WordPress_Plugin\Widget();
		register_widget( $widget );

	}

}
