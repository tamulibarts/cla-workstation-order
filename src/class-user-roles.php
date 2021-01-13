<?php
/**
 * The file that defines the Gravity Form leads helper class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-leads-helper.php
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

		/**
		 * New role capabilities.
		 * edit_department
		 * edit_wsorder
		 * decide_wsorder
		 */
		$logistics_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => false,
		);
		add_role( 'wso_logistics', 'Logistics', 'contributor', $logistics_caps );

		$it_rep_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => false,
		);
		add_role( 'wso_it_rep', 'IT Rep', 'contributor', $it_rep_caps );

		$primary_it_rep_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => false,
		);
		add_role( 'wso_primary_it_rep', 'Primary IT Rep', 'contributor', $primary_it_rep_caps );

		$department_it_rep_caps = array(
			'edit_department' => true,
			'edit_wsorder'    => false,
		);
		add_role( 'wso_department_it_rep', 'Department IT Rep', 'contributor', $department_it_rep_caps );

		$program_it_rep_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => false,
		);
		add_role( 'wso_program_it_rep', 'Program IT Rep', 'contributor', $program_it_rep_caps );

		$wso_admin_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => true,
		);
		add_role( 'wso_admin', 'Admin', 'editor', $wso_admin_caps );

		$business_admin_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => true,
		);
		add_role( 'wso_business_admin', 'Business Admin', 'editor', $business_admin_caps );

		$primary_business_admin_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => true,
		);
		add_role( 'wso_primary_business_admin', 'Primary Business Admin', 'editor', $primary_business_admin_caps );

		$department_business_admin_caps = array(
			'edit_department' => true,
			'edit_wsorder'    => true,
		);
		$this->add_role( 'wso_department_business_admin', 'Department Business Admin', 'editor', $department_business_admin_caps );

		$program_business_admin_caps = array(
			'edit_department' => false,
			'edit_wsorder'    => true,
		);
		add_role( 'wso_program_business_admin', 'Program Business Admin', 'editor', $program_business_admin_caps );

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
}
