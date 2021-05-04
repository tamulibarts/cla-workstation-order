<?php
/**
 * The file that defines customizations to user roles for all custom post types.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-user-roles.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * The core plugin class
 *
 * @since 1.0.0
 * @return void
 */
class User_Roles {

	/**
	 * File name
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Register user roles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register() {

		// Update existing Subscriber role.
		$subscriber_role = get_role( 'subscriber' );
    $subscriber_role->add_cap( 'edit_wsorder', true );
    $subscriber_role->add_cap( 'read_wsorder', true );
    $subscriber_role->add_cap( 'create_wsorders', true ); // For some reason this is required to view the post list page.
    $subscriber_role->add_cap( 'delete_wsorder', false );
    $subscriber_role->add_cap( 'delete_wsorders', false );
    $subscriber_role->add_cap( 'delete_others_wsorders', false );
    $subscriber_role->add_cap( 'delete_private_wsorders', false );
    $subscriber_role->add_cap( 'delete_published_wsorders', false );
    $subscriber_role->add_cap( 'edit_wsorders', true );
    $subscriber_role->add_cap( 'edit_others_wsorders', false );
    $subscriber_role->add_cap( 'edit_private_wsorders', false );
    $subscriber_role->add_cap( 'edit_published_wsorders', true );
    $subscriber_role->add_cap( 'publish_wsorders', false ); // Required for changing the post status.
    $subscriber_role->add_cap( 'read_private_wsorders', false );

		/**
		 * Add new roles with custom post type capabilities.
		 */

		// WSO Admin role.
		$wso_admin_caps = array(
      'edit_wsorder'              => true,
      'read_wsorder'              => true,
      'delete_wsorder'            => true,
      'create_wsorders'           => true,
      'delete_wsorders'           => true,
      'delete_others_wsorders'    => true,
      'delete_private_wsorders'   => true,
      'delete_published_wsorders' => true,
      'edit_wsorders'             => true,
      'edit_others_wsorders'      => true,
      'edit_private_wsorders'     => true,
      'edit_published_wsorders'   => true,
      'publish_wsorders'          => true,
      'read_private_wsorders'     => true,
			'edit_program'              => true,
			'read_program'              => true,
			'delete_program'            => true,
			'edit_programs'             => true,
			'edit_others_programs'      => true,
			'publish_programs'          => true,
			'read_private_programs'     => true,
			'create_programs'           => true,
			'edit_department'           => true,
			'read_department'           => true,
			'delete_department'         => true,
			'edit_departments'          => true,
			'edit_others_departments'   => true,
			'publish_departments'       => true,
			'read_private_departments'  => true,
			'create_departments'        => true,
			'edit_product'              => true,
			'read_product'              => true,
			'delete_product'            => true,
			'edit_products'             => true,
			'edit_others_products'      => true,
			'publish_products'          => true,
			'read_private_products'     => true,
			'create_products'           => true,
			'edit_bundle'               => true,
			'read_bundle'               => true,
			'delete_bundle'             => true,
			'edit_bundles'              => true,
			'edit_others_bundles'       => true,
			'publish_bundles'           => true,
			'read_private_bundles'      => true,
			'create_bundles'            => true,
			'manage_wso_options'        => true,
			'upload_files'              => true,
			'unfiltered_html'           => true,
			'read'                      => true,
			'manage_product_categories' => true,
			'remove_users'              => true,
			'upload_files'              => true,
			'promote_users'             => true,
			'list_users'                => true,
			'edit_post'                 => false,
			'read_post'                 => false,
			'delete_post'               => false,
			'create_posts'              => false,
			'delete_posts'              => false,
			'delete_others_posts'       => false,
			'delete_private_posts'      => false,
			'delete_published_posts'    => false,
			'edit_posts'                => false,
			'edit_others_posts'         => false,
			'edit_private_posts'        => false,
			'edit_published_posts'      => false,
			'publish_posts'             => false,
			'read_private_posts'        => false,
		);
		$this->add_role( 'wso_admin', 'WSO Admin', false, $wso_admin_caps );

		// Logistics role.
		$logistics_caps = array(
			'edit_wsorder'              => true,
      'read_wsorder'              => true,
      'create_wsorders'           => true, // This is needed to edit others orders for some reason.
      'edit_wsorders'             => true,
      'edit_others_wsorders'      => true,
      'edit_private_wsorders'     => true,
      'edit_published_wsorders'   => true, // Required to read published wsorders.
      'publish_wsorders'          => true, // Required for changing the post status.
      'read_private_wsorders'     => true,
      'read'                      => true,
      'delete_wsorders'           => true,
      'delete_others_wsorders'    => true,
		);
		$this->add_role( 'wso_logistics', 'Logistics', false, $logistics_caps );

		/**
		 * IT Rep capabilities.
		 */
		$it_rep_caps = array(
			'edit_wsorder'              => true,
      'read_wsorder'              => true,
      'create_wsorders'           => true, // Required to edit others orders for some reason.
      'edit_wsorders'             => true,
      'edit_others_wsorders'      => true,
      'edit_private_wsorders'     => true,
      'edit_published_wsorders'   => true, // Required to read published wsorders.
      'publish_wsorders'          => false, // Required for changing the post status.
      'read_private_wsorders'     => true,
      'read'                      => true,
		);
		$this->add_role( 'wso_it_rep', 'IT Rep', false, $it_rep_caps );

		$primary_it_rep_caps = array();
		$this->add_role( 'wso_primary_it_rep', 'Primary IT Rep', 'wso_it_rep', $primary_it_rep_caps );

		$program_it_rep_caps = array();
		$this->add_role( 'wso_program_it_rep', 'Program IT Rep', 'wso_it_rep', $program_it_rep_caps );

		$department_it_rep_caps = array();
		$this->add_role( 'wso_department_it_rep', 'Department IT Rep', 'wso_it_rep', $department_it_rep_caps );

		/**
		 * Admin capabilities.
		 */
		$business_admin_caps = array(
			'edit_wsorder'              => true,
      'read_wsorder'              => true,
      'create_wsorders'           => true, // Required to edit others orders for some reason.
      'edit_wsorders'             => true,
      'edit_others_wsorders'      => true,
      'edit_private_wsorders'     => true,
      'edit_published_wsorders'   => true, // Required to read published wsorders.
      'read_private_wsorders'     => true,
      'read'                      => true,
		);
		$this->add_role( 'wso_business_admin', 'Business Admin', false, $business_admin_caps );

		$program_business_admin_caps = array();
		$this->add_role( 'wso_program_business_admin', 'Program Business Admin', 'wso_business_admin', $program_business_admin_caps );

		$department_business_admin_caps = array();
		$this->add_role( 'wso_department_business_admin', 'Department Business Admin', 'wso_business_admin', $department_business_admin_caps );

		$primary_business_admin_caps = array();
		$this->add_role( 'wso_primary_business_admin', 'Primary Business Admin', 'wso_business_admin', $primary_business_admin_caps );

	}

	/**
	 * Add new user role.
	 *
	 * @param string $role         Role name.
	 * @param string $display_name Display name for role.
	 * @param string $base_role    The base role name to extend.
	 * @param array  $caps         The new capabilities applied to the base role capabilities.
	 *
	 * @return void
	 */
	private function add_role( $role, $display_name, $base_role, $caps ) {

		$base_caps = $base_role === false ? array() : get_role( $base_role )->capabilities;
		$caps      = array_merge( $base_caps, $caps );
		add_role( $role, $display_name, $caps );

	}

	/**
	 * Unregister user roles and capability changes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function unregister() {

		// Update existing Subscriber role.
		$subscriber_role = get_role( 'subscriber' );
		$subscriber_role->remove_cap( 'edit_wsorder' );
		$subscriber_role->remove_cap( 'read_wsorder' );
		$subscriber_role->remove_cap( 'edit_wsorders' );
		$subscriber_role->remove_cap( 'create_wsorders' );
		$subscriber_role->remove_cap( 'publish_wsorders' );

		remove_role( 'wso_admin' );
		remove_role( 'wso_logistics' );
		remove_role( 'wso_it_rep' );
		remove_role( 'wso_primary_it_rep' );
		remove_role( 'wso_department_it_rep' );
		remove_role( 'wso_program_it_rep' );
		remove_role( 'wso_business_admin' );
		remove_role( 'wso_program_business_admin' );
		remove_role( 'wso_department_business_admin' );
		remove_role( 'wso_primary_business_admin' );

	}
}
