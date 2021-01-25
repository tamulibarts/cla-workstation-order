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
		$subscriber_role->add_cap( 'delete_wsorder', false );
		$subscriber_role->add_cap( 'edit_wsorders', true );
		$subscriber_role->add_cap( 'edit_others_wsorders', false );
		$subscriber_role->add_cap( 'create_wsorders', true );
		$subscriber_role->add_cap( 'publish_wsorders', true );

		/**
		 * Add new roles with custom post type capabilities.
		 */

		// WSO Admin role.
		$wso_admin_caps = array(
			'edit_program'             => true,
			'read_program'             => true,
			'delete_program'           => true,
			'edit_programs'            => true,
			'edit_others_programs'     => true,
			'publish_programs'         => true,
			'read_private_programs'    => true,
			'create_programs'          => true,
			'edit_department'          => true,
			'read_department'          => true,
			'delete_department'        => true,
			'edit_departments'         => true,
			'edit_others_departments'  => true,
			'publish_departments'      => true,
			'read_private_departments' => true,
			'create_departments'       => true,
			'edit_wsorder'             => true,
			'read_wsorder'             => true,
			'delete_wsorder'           => true,
			'edit_wsorders'            => true,
			'edit_others_wsorders'     => true,
			'publish_wsorders'         => true,
			'read_private_wsorders'    => true,
			'create_wsorders'          => true,
			'edit_product'             => true,
			'read_product'             => true,
			'delete_product'           => true,
			'edit_products'            => true,
			'edit_others_products'     => true,
			'publish_products'         => true,
			'read_private_products'    => true,
			'create_products'          => true,
			'edit_bundle'              => true,
			'read_bundle'              => true,
			'delete_bundle'            => true,
			'edit_bundles'             => true,
			'edit_others_bundles'      => true,
			'publish_bundles'          => true,
			'read_private_bundles'     => true,
			'create_bundles'           => true,
		);
		$this->add_role( 'wso_admin', 'WSO Admin', 'editor', $wso_admin_caps );

		// Logistics role.
		$logistics_caps = array(
			'edit_users'               => true,
			'edit_product'             => true,
			'read_product'             => true,
			'delete_product'           => true,
			'edit_products'            => true,
			'edit_others_products'     => true,
			'publish_products'         => true,
			'read_private_products'    => true,
			'create_products'          => true,
			'edit_bundle'              => true,
			'read_bundle'              => true,
			'delete_bundle'            => true,
			'edit_bundles'             => true,
			'edit_others_bundles'      => true,
			'publish_bundles'          => true,
			'read_private_bundles'     => true,
			'create_bundles'           => true,
			'edit_department'          => true,
			'read_department'          => true,
			'delete_department'        => true,
			'edit_departments'         => true,
			'edit_others_departments'  => true,
			'publish_departments'      => true,
			'read_private_departments' => true,
			'create_departments'       => true,
			'edit_program'             => true,
			'read_program'             => true,
			'delete_program'           => true,
			'edit_programs'            => true,
			'edit_others_programs'     => true,
			'publish_programs'         => true,
			'read_private_programs'    => true,
			'create_programs'          => true,
		);
		$this->add_role( 'wso_logistics', 'Logistics', 'contributor', $logistics_caps );

		$it_rep_caps = array(
			'edit_wsorder'     => true,
			'read_wsorder'     => true,
			'publish_wsorders' => true,
			'create_wsorders'  => true,
		);
		$this->add_role( 'wso_it_rep', 'IT Rep', 'contributor', $it_rep_caps );

		$primary_it_rep_caps = array(
			'edit_wsorder'     => true,
			'read_wsorder'     => true,
			'publish_wsorders' => true,
			'create_wsorders'  => true,
		);
		$this->add_role( 'wso_primary_it_rep', 'Primary IT Rep', 'contributor', $primary_it_rep_caps );

		$business_admin_caps = array(
			'edit_wsorder'          => true,
			'read_wsorder'          => true,
			'delete_wsorder'        => true,
			'edit_wsorders'         => true,
			'edit_others_wsorders'  => true,
			'publish_wsorders'      => true,
			'read_private_wsorders' => true,
			'create_wsorders'       => true,
		);
		$this->add_role( 'wso_business_admin', 'Business Admin', 'editor', $business_admin_caps );

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

		$base_caps = get_role( $base_role )->capabilities;
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
		$subscriber_role->remove_cap( 'delete_wsorder' );
		$subscriber_role->remove_cap( 'edit_wsorders' );
		$subscriber_role->remove_cap( 'edit_others_wsorders' );
		$subscriber_role->remove_cap( 'create_wsorders' );
		$subscriber_role->remove_cap( 'publish_wsorders' );

		remove_role( 'wso_admin' );
		remove_role( 'wso_logistics' );
		remove_role( 'wso_it_rep' );
		remove_role( 'wso_primary_it_rep' );
		remove_role( 'wso_business_admin' );
	}
}
