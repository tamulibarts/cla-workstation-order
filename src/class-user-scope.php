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
	 * Registered user scope settings.
	 * This is a custom setting unique to this plugin.
	 *
	 * @var scopes
	 */
	private $scopes = array();

	/**
	 * Registered user roles limited in scope.
	 * https://wordpress.org/support/article/roles-and-capabilities/#summary-of-roles
	 *
	 * @var scoped_roles
	 */
	private $scoped_roles = array();

	/**
	 * Registers all scoped capabilities.
	 * This is helpful to end the filter early if checking an unscoped capability.
	 *
	 * @var scoped_capabilities
	 */
	private $scoped_capabilities = array();

	/**
	 * Capability slugs that can target user IDs.
	 * This is helpful to confirm tagless argument variables later.
	 *
	 * @var user_capabilities
	 */
	private $user_capabilities = array(
		'delete_users',
		'delete_user',
		'remove_users',
		'remove_user',
		'edit_users',
		'edit_user',
		'promote_users',
		'promote_user',
		'create_users',
		'create_user',
		'add_users',
		'add_user',
		'list_users',
		'list_user'
	);

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
	 * Register the settings for the user role to be given a scope.
	 *
	 * @param string $user_role The user role slug to register scope settings for.
	 * @param array  $args      The settings for this user role scope.
	 *
	 * @return void
	 */
	public function register( $user_role, $args ) {

		// Store the scoped user role settings.
		$this->scopes[ $user_role ] = $args;

		// Keep a list of all scoped user roles.
		$this->scoped_roles[] = $user_role;

		// Keep a list of all scoped capabilities.
		if ( array_key_exists( 'capabilities', $args ) ) {
			$this->scoped_capabilities = array_merge( $this->scoped_capabilities, array_keys( $args['capabilities'] ) );
		}

	}

	/**
	 * Initiate hooks if user scopes are registered.
	 *
	 * @return void
	 */
	public function hooks() {

		if ( $this->scopes ) {

			// Restrict access to user role promotion.
			// add_filter( 'editable_roles', array( $this, 'editable_roles' ) );
			// Scope roles with user capabilities appropriately.
			if ( $this->scoped_capabilities ) {
				add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );
				add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );
			}

		}

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
	 * Scope the user, capability, and optional arguments against the registered scopes.
	 *
	 * @param string[] $caps Primitive capabilities required of the user.
	 * @param string   $cap  Capability being checked.
	 * @param WP_User  $user The current user object.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type mixed ...$0 Optional Typically the target user object. WP_User if valid, false if the user data is not found.
	 * }
	 *
	 * @return bool
	 */
	private function deny_user_cap( $caps, $cap, $user, $args ) {

		$deny = false;

		// End execution early if the capability is not scoped. Improves site performance.
		if ( ! in_array( $cap, $this->scoped_capabilities, true ) ) {

			return $deny;

		}

		// Only look at user roles that are scoped.
		$user_roles = array_intersect( $user->roles, $this->scoped_roles );

		// Check the scoped role or roles, if any, for capabilities.
		foreach ( $user_roles as $role ) {

			// Get the user's scope settings.
			$scope = $this->scopes[ $role ];

			// If the capability being checked is scoped, continue.
			if ( array_key_exists( $cap, $scope['capabilities'] ) ) {

				$cap_scope = $scope['capabilities'][ $cap ];

				if ( false === $cap_scope ) {

					// If the scope does not target a user role then return.
					$deny = true;
					break;

				} elseif ( is_array( $cap_scope ) && in_array( $cap, $this->user_capabilities, true ) ) {

					/**
					 * Scope a capability against one or more user roles.
					 * The running method is scoping it against a user ID. Example: current_user_can( 'edit_user', 12 ).
					 * The User_Scope class scopes it between one user role and another.
					 * We need to make sure the current user's scoped capability involves the target user's role(s).
					 */
					if ( $args && isset( $args[0] ) && is_object( $args[0] ) && 'WP_User' === get_class( $args[0] ) ) {

						// Check the target user's roles to ensure all are scoped for the capability check.
						$target_user       = $args[0];
						$target_user_roles = $target_user->roles;
						$untouchable_roles = array_diff( $target_user_roles, $cap_scope );

						// If the target user has an untouchable role then limit the current user's capability.
						if ( $untouchable_roles ) {

							$deny = true;
							break;

						}

					} else {

						// The capability check is for a specific user action and role but
						// this function was not given a target user object.
						$deny = true;
						break;

					}
				}
			}
		}

		return $deny;

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

		/**
		 * The get_userdata function calls the map_meta_cap filter this function is hooked to.
		 * Therefore, we must remove this function temporarily to prevent an infinite loop.
		 */
		remove_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );
		$user = get_userdata( $user_id );
		// If we think the first argument is the target user ID, then attempt to get that user object.
		$target_user = false;
		if ( $args && $args[0] && is_int( $args[0] ) && $user_id !== $args[0] ) {
			$target_user = get_userdata( $args[0] );
		}
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 11, 4 );

		// If the user capability is scoped then block it.
		$args               = $target_user ? array( $target_user ) : array();
		$user_cap_is_scoped = $this->deny_user_cap( $caps, $cap, $user, $args );

		if ( $user_cap_is_scoped ) {
			$cap_arr = array( $cap );
			$caps    = array_diff( $caps, $cap_arr );
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
	public function user_has_cap( $allcaps, $caps, $args, $user ) {

		$cap         = $args[0];
		$user_id     = $user->ID;
		$target_user = false;

		if ( $user->ID !== $args[1] ) {

			$target_user_id = $args[1];

			// If the target user is not equal to the current user, temporarily remove this filter method.
			// This prevents a self-calling infinite loop when calling get_userdata().
			remove_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );
			$target_user = get_userdata( $target_user_id );
			add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );

		}

		// If the user capability is scoped then block it.
		$args               = $target_user ? array( $target_user ) : array();
		$user_cap_is_scoped = $this->deny_user_cap( $caps, $cap, $user, $args );

		if ( $user_cap_is_scoped ) {
			$cap_arr = array( $cap );
			$allcaps = array_diff( $allcaps, $cap_arr );
		}

		return $allcaps;
	}
}
