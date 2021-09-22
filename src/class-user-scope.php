<?php
/**
 * The file that defines subjective user access customization based on their role.
 *
 * @link       https://github.tamu.edu/zachwatkins/cla-workstation-order/blob/master/src/class-user-scope.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * The User Scope plugin class.
 *
 * @since 1.0.0
 * @return void
 */
class User_Scope {

	/**
	 * File name.
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * User capabilities
	 *
	 * @var user_caps
	 */
	private $user_caps = array(
		'create_users',
		'delete_users',
		'add_users',
		'edit_users',
		'remove_users',
		'promote_users',
		'list_users',
		'create_user',
		'delete_user',
		'add_user',
		'edit_user',
		'remove_user',
		'promote_user',
		'list_user',
	);

	/**
	 * User scope settings
	 *
	 * @var scopes
	 */
	private $scopes = array();

	/**
	 * Roles limited in scope
	 *
	 * @var limited_roles
	 */
	private $scoped_roles = array();

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'hooks' ) );

	}

	/**
	 * Initiate hooks if user scopes are registered.
	 *
	 * @return void
	 */
	public function hooks() {

		if ( $this->scopes ) {

			// Restrict access to user role promotion.
			add_filter( 'editable_roles', array( $this, 'editable_roles' ) );
			// Scope roles with user capabilities appropriately.
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );
			add_filter( 'user_has_cap', array( $this, 'logistics_has_capabilities' ), 11, 4 );

		}

	}

	/**
	 * * Register scope settings.
	 *
	 * @param string $user_role The user role to register scope settings for.
	 * @param array  $args      The scope settings for this user role.
	 *
	 * @return void
	 */
	public function register( $user_role, $args ) {

		$this->scopes[ $user_role ] = $args;
		array_push( $this->scoped_roles, $user_role );

	}

	/**
	 * Filters the list of editable roles.
	 *
	 * @param array[] $all_roles Array of arrays containing role information.
	 *
	 * @return array
	 */
	public function editable_roles( $all_roles ) {

		if ( ! current_user_can( 'administrator' ) ) {
			unset( $all_roles['administrator'] );
			unset( $all_roles['wso_admin'] );
		}

		// Restrict what user roles the Logistics Admin can assign.
		if ( current_user_can( 'wso_logistics_admin' ) ) {
			unset( $all_roles['wso_logistics_admin'] );
		}

		// Restrict what roles the base Logistics user can assign.
		if ( current_user_can( 'wso_logistics' ) ) {
			unset( $all_roles['wso_logistics_admin'] );
			unset( $all_roles['wso_it_rep'] );
			unset( $all_roles['wso_business_admin'] );
			unset( $all_roles['wso_logistics'] );
		}

		// Remove default WordPress user role assignment since this application doesn't use it.
		unset( $all_roles['editor'] );
		unset( $all_roles['author'] );
		unset( $all_roles['contributor'] );

		return $all_roles;

	}

	/**
	 * Filters the primitive capabilities of the given user to satisfy the
	 * capability being checked.
	 *
	 * @param string[] $caps    Primitive capabilities required of the user.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds context to the capability check, typically
	 *                          starting with an object ID.
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		// If the capability being checked is not important here, end execution early.
		if ( ! in_array( $cap, $this->user_caps, true ) || ! $args || ! is_int( $args[0] ) ) {
			return $caps;
		}

		/**
		 * The current_user_can and get_userdata functions call this method's filter hook.
		 * Therefore, we must remove it temporarily to prevent an infinite loop.
		 */
		remove_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );

		$current_userdata = get_userdata( $user_id );
		$target_userdata  = get_userdata( $args[0] );

		/**
		 * Restore this filter method.
		 */
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );

		// Check each of the current user's roles against the scoped roles.
		$current_user_roles  = $current_userdata->roles;
		$user_role_is_scoped = false;
		foreach ( $current_user_roles as $role ) {
			if ( in_array( $role, $this->scoped_roles, true ) ) {
				$user_role_is_scoped = true;
				break;
			}
		}

		// If the current user does not need to be restricted by this method then end execution early.
		if ( ! $user_role_is_scoped ) {
			return $caps;
		}

		// Get target user information.
		$target_user_roles = $target_userdata->roles;
		$target_user_id    = $args[0];

		// Reduce the target user's list of roles to only include those we are checking for.
		$target_user_roles_to_check = array_intersect( $target_user_roles, $this->scoped_roles );
		if ( ! $target_user_roles_to_check ) {
			return $caps;
		}

		// Check each of the target user's roles.
		// If the capability to check is not allowed for any one role then disallow the action.
		$cap_arr = array( $cap );
		foreach ( $target_user_roles_to_check as $role ) {
			$role_cap_whitelist = $this->logistics_user_scope[ $role ];
			if ( ! in_array( $cap, $role_cap_whitelist, true ) ) {
				// Remove the checked capability from the user's list of capabilities.
				$caps = array_diff( $caps, $cap_arr );
				break;
			}
		}

		return $caps;

	}

	/**
	 * Dynamically filter a Logistics user's capabilities.
	 * This method is similar to that above but the needed information is available in different ways.
	 *
	 * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name
	 *                          and boolean values represent whether the user has that capability.
	 * @param string[] $caps    Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters, typically object ID.
	 * }
	 * @param WP_User  $user    The user object.
	 *
	 * @return bool[]
	 */
	public function logistics_has_capabilities( $allcaps, $caps, $args, $user ) {

		$cap            = $args[0];
		$user_id        = $user->ID;
		$target_user_id = isset( $args[1] ) && is_int( $args[1] ) ? $args[1] : 0;

		// Detect if the capability check is irrelevant and should be ended early.
		if (
			$user_id === $target_user_id
			|| ! in_array( $cap, $this->user_caps, true )
			|| ! in_array( 'wso_logistics', $user->roles, true )
			|| ! isset( $args[1] ) || ! is_int( $args[1] )
		) {
			return $allcaps;
		}

		// If the target user is not equal to the current user, temporarily remove this filter method.
		// This prevents a self-calling infinite loop when calling get_userdata().
		remove_filter( 'user_has_cap', array( $this, 'logistics_has_capabilities' ), 11, 4 );
		$target_user_data = get_userdata( $target_user_id );
		add_filter( 'user_has_cap', array( $this, 'logistics_has_capabilities' ), 11, 4 );

		// If we cannot find the user this capability is checking against then we cannot validate the check.
		if ( ! is_object( $target_user_data ) ) {
			// End execution early.
			return $allcaps;
		}

		// Get the target user's role.
		$target_user_roles_checked = array();
		if ( property_exists( $target_user_data, 'roles' ) && is_array( $target_user_data->roles ) ) {
			$target_user_role = $target_user_data->roles;
			// Reduce the target user's list of roles to only include those we are checking for.
			$target_user_roles_checked = array_intersect( $target_user_role, $disallowed_roles );
		}

		// If we found one or more user roles in the target user's role list that the user is not allowed to affect,
		// then remove this capability from the list.
		if ( count( $target_user_roles_checked ) > 0 ) {
			// Reduce the user capability array to those not found in the restricted list.
			$caps = array_diff( $caps, $this->user_caps );
		}

		return $allcaps;
	}
}
