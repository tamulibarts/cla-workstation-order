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
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-dashboard.php';
		new \CLA_Workstation_Order\Dashboard();

		// Handle GravityForms leads.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-assets.php';
		new \CLA_Workstation_Order\Assets();

		// Create post types.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-wsorder-posttype.php';
		new \CLA_Workstation_Order\WSOrder_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-product-posttype.php';
		new \CLA_Workstation_Order\Product_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-bundle-posttype.php';
		new \CLA_Workstation_Order\Bundle_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-program-posttype.php';
		new \CLA_Workstation_Order\Program_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-department-posttype.php';
		new \CLA_Workstation_Order\Department_PostType();

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-pagetemplate.php';
		$order_form = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'order-form-template.php', 'Order Form' );
		$order_form->register();
		$my_account = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'my-account.php', 'My Account' );
		$my_account->register();

		// Register settings page.
		add_action( 'acf/init', array( $this, 'register_custom_fields' ) );

		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'manage_users_columns', array( $this, 'add_user_admin_columns' ) );

		add_filter( 'manage_users_custom_column', array( $this, 'render_user_admin_columns' ), 10, 3 );

		// Redirect users based on role.
		add_filter( 'login_redirect', array( $this, 'login_redirect_roles' ), 11, 3 );

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
			array(
				'capabilities' => array(
					'manage_terms' => 'manage_product_categories',
					'edit_terms'   => 'manage_product_categories',
					'delete_terms' => 'manage_product_categories',
					'assign_terms' => 'manage_product_categories',
				),
			),
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
	public function register_custom_fields() {

		if ( function_exists( 'acf_add_options_page' ) ) {

			require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/settings-fields.php';

			acf_add_options_page(
				array(
					'page_title' => 'Workstation Order Settings',
					'menu_title' => 'WSO Settings',
					'menu_slug'  => 'wsorder-settings',
					'capability' => 'manage_wso_options',
					'redirect'   => false,
				)
			);

		}

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/user-fields.php';

	}

	public function add_user_admin_columns( $column ) {

    $column['department'] = 'Department';
    $midpoint = 4;
    $arr_1 = array_slice( $column, 0, $midpoint );
    $arr_2 = array_slice( $column, $midpoint, count( $column ) - $midpoint );
    $arr_1['department'] = 'Department';
    $column = array_merge($arr_1, $arr_2);
    return $column;

	}

	public function render_user_admin_columns( $val, $column_name, $user_id ) {

    switch ($column_name) {
      case 'department' :
        return get_the_title( get_the_author_meta( 'department', $user_id ) );
      default:
    }
    return $val;

	}

	/**
	 * Filters the login redirect URL.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string
	 */
	public function login_redirect_roles( $redirect_to, $requested_redirect_to, $user ) {

    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
      // check for admins
      if ( in_array( 'administrator', $user->roles ) || in_array( 'wso_admin', $user->roles ) ) {
        // redirect them to the default place
        return admin_url('index.php');
      } elseif ( in_array( 'wso_logistics', $user->roles ) ) {
      	return home_url('/orders/');
      } else {
        return home_url();
      }
  	}

		return $redirect_to;

	}

}
