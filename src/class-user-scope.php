<?php
/**
 * The file that defines subjective user access customization based on their role.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-user-scope.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
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
	 * Registers all scoped filters.
	 * This is helpful to only register filter functions when needed for better performance.
	 *
	 * @var string[] scoped_filters Associative array of filter names and string arrays of user roles to scope.
	 */
	private $scoped_filters = array(
		'filters' => array(),
		'roles'   => array(),
	);

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

		add_action( 'after_setup_theme', array( $this, 'hooks' ) );

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

		// Keep a list of all scoped filters for improved performance.
		if ( array_key_exists( 'filters', $args ) ) {

			// Register the filter and role association for filter function queue control (better performance).
			foreach ( $args['filters'] as $filter => $settings ) {

				// Store a list of user roles associated with each filter.
				if ( ! array_key_exists( $filter, $this->scoped_filters['filters'] ) ) {
					$this->scoped_filters['filters'][ $filter ] = array( $user_role );
				} else {
					$this->scoped_filters['filters'][ $filter ][] = $user_role;
				}

				// Store a list of filters associated with each user role.
				if ( ! array_key_exists( $user_role, $this->scoped_filters['roles'] ) ) {
					$this->scoped_filters['roles'][ $user_role ] = array( $filter => $settings['count'] );
				} else {
					$this->scoped_filters['roles'][ $user_role ][ $filter ] = $settings['count'];
				}
			}
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
			add_filter( 'editable_roles', array( $this, 'editable_roles' ) );

			// Scope roles with user capabilities appropriately.
			if ( $this->scoped_capabilities ) {
				add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );
			}

			// Scope roles by specific filters.
			if ( $this->scoped_filters['roles'] ) {

				// Get the current user object.
				$user = wp_get_current_user();

				// Reduce the list of the current user's roles to those which have a custom filter.
				$roles = array_intersect( $user->roles, array_keys( $this->scoped_filters['roles'] ) );

				// Compile a list of filter functions to initialize.
				$filters = array();
				foreach ( $roles as $role ) {

					$filters = array_merge( $filters, $this->scoped_filters['roles'][ $role ] );

				}

				// Initialize custom filter hooks.
				if ( ! empty( $filters ) ) {
					foreach ( $filters as $filter => $arg_count ) {
						add_filter( $filter, array( $this, $filter ), 11, $arg_count );
					}
				}

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
	 * @return array
	 */
	private function scope_user_cap( $caps, $cap, $user, $args = array() ) {

		$result            = array(
			'allow'         => true,
			'cap'           => $cap,
			'cap_to_remove' => $cap,
		);
		$is_affecting_user = in_array( $cap, $this->user_capabilities, true ) ? true : false;

		// End execution early if the capability is not scoped. Improves site performance.
		// If the capability being checked is not scoped, or it is but it is a user capability
		// and a target user is not provided, then the user scope does not apply.
		if (
			! in_array( $cap, $this->scoped_capabilities, true )
			|| (
				$is_affecting_user
				&& ( ! isset( $args[0] ) || ! is_object( $args[0] ) || 'WP_User' !== get_class( $args[0] ) )
			)
		) {

			return $result;

		}

		// Only look at the user's roles which are scoped.
		$user_roles = array_intersect( $user->roles, $this->scoped_roles );

		// Check the scoped role or roles, if any, for capabilities.
		foreach ( $user_roles as $role ) {

			// Get the role's scope settings.
			$scope = $this->scopes[ $role ];

			// If the capability being checked is not scoped for this role, then end
			// this iteration of the foreach loop and continue to check the next role.
			if ( ! array_key_exists( $cap, $scope['capabilities'] ) ) {
				continue;
			}

			/**
			 * Scope a user-influencing capability against one or more user roles.
			 * The running method is scoping it against a user ID. Example: current_user_can( 'edit_user', 12 ).
			 * The User_Scope class scopes it between one user role and another.
			 * We need to make sure the current user's scoped capability involves the target user's role(s).
			 */
			if ( $is_affecting_user ) {

				// Check the target user's roles to ensure all are scoped for the capability check.
				$cap_scope         = $scope['capabilities'][ $cap ];
				$target_user       = $args[0];
				$target_user_roles = $target_user->roles;
				$untouchable_roles = array_diff( $target_user_roles, $cap_scope );
				$untouchable_roles = array_values( $untouchable_roles );

				// Are untouchable roles found?
				if ( $untouchable_roles ) {

					// The current user is not allowed to influence users with this role in this way.
					$result['allow'] = false;

					// The capability checked is sigular while the capability to remove from the user is plural.
					$result['cap_to_remove'] = $result['cap'] . 's';

					break;

				}
			}
		}

		return $result;

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

		if ( isset( $args[2] ) ) {

			// If the target user is not equal to the current user, temporarily remove this filter method.
			// This prevents a self-calling infinite loop when calling get_userdata().
			remove_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );
			$target_user_id = $args[2];
			$target_user    = get_userdata( $target_user_id );
			add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 11, 4 );

		}

		// If the user capability is scoped then block it.
		$args           = $target_user ? array( $target_user ) : array();
		$scope_user_cap = $this->scope_user_cap( $caps, $cap, $user, $args );

		if ( false === $scope_user_cap['allow'] ) {
			$allcaps[ $scope_user_cap['cap_to_remove'] ] = false;
		}

		return $allcaps;
	}

	/**
	 * Filters the action links displayed under each user in the Users list table.
	 *
	 * @param string[] $actions     An array of action links to be displayed.
	 *                              Default 'Edit', 'Delete' for single site, and
	 *                              'Edit', 'Remove' for Multisite.
	 * @param WP_User  $user_object WP_User object for the currently listed user.
	 */
	public function user_row_actions( $actions, $user_object ) {

		$user             = wp_get_current_user();
		$filter_roles     = $this->scoped_filters['filters']['user_row_actions'];
		$user_roles       = array_intersect( $user->roles, $filter_roles );
		$actions_to_check = array();
		$role_actions     = array();

		// Check each of the current user's roles scoped to this filter.
		foreach ( $user_roles as $role ) {

			$role_actions[ $role ] = $this->scopes[ $role ]['filters']['user_row_actions']['args']['actions'];
			$actions_to_check      = array_merge( $actions_to_check, array_keys( $role_actions[ $role ] ) );

		}

		// Iterate over each action to manipulate for this role.
		foreach ( $role_actions as $role => $actions ) {

			// Only make a change if the action is present.
			if ( array_key_exists( $action, $actions ) ) {

				// If the scope setting is false then completely remove the option.
				if ( false === $value ) {

					unset( $actions[ $action ] );
					unset( $actions_to_check[ $action ] );

				}
			}
		}

		return $actions;

	}
}
