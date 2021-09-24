<?php
/**
 * The file that defines the Order post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-wsorder-posttype.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @package cla-workstation-order
 * @since 1.0.0
 */
class WSOrder_PostType {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Register_post_type.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'query_vars', array( $this, 'add_program_url_var' ) );
		// Redirect users trying to view all orders to the current year's program.
		add_action( 'admin_init', array( $this, 'redirect_to_current_program_orders' ) );
		// Register custom fields.
		add_action( 'acf/init', array( $this, 'register_custom_fields' ) );

		// Add custom post status elements to dropdown box.
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_status_add_to_dropdown' ) );
		// Return readable custom post status title.
		add_filter( 'display_post_states', array( $this, 'display_status_state' ) );
		// Enqueue JavaScript file for admin.
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_script' ), 11 );
		// Prevent users uninvolved with an order from editing it.
		add_action( 'admin_init', array( $this, 'redirect_uninvolved_users_from_editing' ) );

		/**
		 * Change features of edit.php list view for order posts.
		 */
		// Add columns to dashboard post list screen.
		add_filter( 'manage_wsorder_posts_columns', array( $this, 'add_list_view_columns' ) );
		add_action( 'manage_wsorder_posts_custom_column', array( $this, 'output_list_view_columns' ), 10, 2 );
		// Prevent users from seeing posts they aren't involved with and filter orders based on program URL variable.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		// Change post type counts and URLs based on currently viewed program.
		add_filter( 'views_edit-wsorder', array( $this, 'change_order_list_status_link_counts_and_urls' ) );
		// Add the currently viewed program name before the list of posts.
		add_action( 'in_admin_header', array( $this, 'program_name_before_order_list_view' ) );

		// Register email action hooks/filters.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-wsorder-posttype-emails.php';
		new \CLA_Workstation_Order\WSOrder_PostType_Emails();

		// Register page template for My Orders.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-pagetemplate.php';
		$orders = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'orders.php', 'Orders' );
		$orders->register();
		$my_orders = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'my-orders.php', 'My Orders' );
		$my_orders->register();

		// AJAX action hooks.
		add_action( 'wp_ajax_make_order', array( $this, 'make_order' ) );
		add_action( 'wp_ajax_confirm_order', array( $this, 'confirm_order' ) );
		add_action( 'wp_ajax_return_order', array( $this, 'return_order' ) );
		add_action( 'wp_ajax_update_order_acquisitions', array( $this, 'update_order_acquisitions' ) );
		add_action( 'wp_ajax_publish_order', array( $this, 'publish_order' ) );
		add_action( 'wp_ajax_reassign_order', array( $this, 'reassign_order' ) );
		add_action( 'wp_ajax_delete_order', array( $this, 'delete_order' ) );
		add_action( 'wp_ajax_search_order', array( $this, 'search_order' ) );
		add_action( 'wp_ajax_get_program_products', array( $this, 'get_program_products' ) );

		// Add program dropdown filter to post list screen.
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_post_program_filter' ), 10 );
		add_filter( 'parse_query', array( $this, 'parse_query_program_filter' ), 10);

		// Custom returned order action.
		add_action( 'transition_post_status', array( $this, 'do_action_wsorder_returned' ), 12, 3 );
		add_action( 'wsorder_returned', array( $this, 'reset_approvals' ) );

		// Customize post permissions.
		add_action( 'acf/validate_save_post', array( $this, 'disable_save_order' ) );

		// Render single order view.
		add_filter( 'single_template', array( $this, 'get_single_template' ) );

		// Update affiliated it reps when the primary it rep is changed.
		add_filter( 'acf/update_value/key=field_5fff703a5289f', array( $this, 'approving_it_rep_changed' ), 12, 2 );

		// Update affiliated business staff when the primary business admin is changed.
		add_filter( 'acf/update_value/key=field_5fff70b84ffe4', array( $this, 'approving_business_admin_changed' ), 12, 2 );

	}

	/**
	 * Reset all approval fields for the order.
	 *
	 * @param int $post_id The order post ID.
	 */
	public function reset_approvals( $post_id ) {

		// Reset IT rep approval.
		update_post_meta( $post_id, 'it_rep_status_confirmed', 0 );
		update_post_meta( $post_id, 'it_rep_status_date', '' );
		update_post_meta( $post_id, 'it_rep_status_comments', '' );
		// Reset business admin approval.
		update_post_meta( $post_id, 'business_staff_status_confirmed', 0 );
		update_post_meta( $post_id, 'business_staff_status_date', '' );
		update_post_meta( $post_id, 'business_staff_status_comments', '' );
		update_post_meta( $post_id, 'business_staff_status_account_number', '' );
		// Reset logistics approval.
		update_post_meta( $post_id, 'it_logistics_status_confirmed', 0 );
		update_post_meta( $post_id, 'it_logistics_status_date', '' );
		update_post_meta( $post_id, 'it_logistics_status_comments', '' );

	}

	/**
	 * Trigger a custom action when an order is returned.
	 */
	public function do_action_wsorder_returned( $new_status, $old_status, $post ) {

		if ( 'wsorder' !== $post->post_type ) {
			return;
		}

		// Verify either nonce for admin or ajax updates.
		if (
			(
				! isset( $_POST['_wpnonce'] )
				|| false === wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-post_' . $post->ID )
			)
			&& (
				! isset( $_POST['_ajax_nonce'] )
				|| false === wp_verify_nonce( sanitize_key( $_POST['_ajax_nonce'] ), 'confirm_order' )
			)
			&& (
			  ! isset( $_POST['_ajax_nonce'] )
			  || false === wp_verify_nonce( sanitize_key( $_POST['_ajax_nonce'] ), 'make_order' )
			)
		) {
			return;
		}
		if ( 'returned' === $new_status && 'returned' !== $old_status ) {
			update_post_meta( $post->ID, 'returned_by', get_current_user_id() );
			// Do custom action.
			do_action( 'wsorder_returned', $post->ID );
		}
	}

	/**
	 * Publish order.
	 */
	public function reassign_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'confirm_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );

		if ( 'wsorder' !== $post_type ) {
			return;
		}

		$json_out                  = array( 'status' => 'The order could not be reassigned.' );
		$current_user_id           = get_current_user_id();
		$affiliated_it_reps        = get_field( 'affiliated_it_reps', $post_id );
		$affiliated_business_staff = get_field( 'affiliated_business_staff', $post_id );
		if ( ! is_array( $affiliated_it_reps ) ) {
			$affiliated_it_reps = array();
		}
		if ( ! is_array( $affiliated_business_staff ) ) {
			$affiliated_business_staff = array();
		}
		$is_aff_it_rep         = in_array( $current_user_id, $affiliated_it_reps );
		$is_aff_business_staff = in_array( $current_user_id, $affiliated_business_staff );

		// Decide what kind of user this is.
		if ( $is_aff_it_rep && current_user_can( 'wso_it_rep' ) ) {
			$update = update_post_meta( $post_id, 'it_rep_status_it_rep', $current_user_id );
		} elseif ( $is_aff_business_staff && current_user_can( 'wso_business_admin' ) ) {
			$update = update_post_meta( $post_id, 'business_staff_status_business_staff', $current_user_id );
		}

		if ( false === $update ) {
			$json_out['status'] = 'The order could not be reassigned to you.';
		} else {
			$json_out['status'] = 'success';
		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Confirm the order by this user.
	 *
	 * @return void
	 */
	public function confirm_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'confirm_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );

		if ( 'wsorder' !== $post_type ) {
			return;
		}

		$json_out              = array( 'status' => 'The order could not be confirmed.' );
		$current_user_id       = get_current_user_id();
		$it_rep_status         = get_field( 'it_rep_status', $post_id );
		$business_staff_status = get_field( 'business_staff_status', $post_id );
		$it_logistics_status   = get_field( 'it_logistics_status', $post_id );
		$comments              = sanitize_text_field( wp_unslash( $_POST['approval_comments'] ) );
		$json_out['comments']  = $comments;

		// Decide what kind of user this is.
		if ( current_user_can( 'wso_it_rep' ) ) {
		 	if (
		 		is_array( $it_rep_status )
		 		&& $current_user_id === $it_rep_status['it_rep']['ID']
		 		&& false === $it_rep_status['confirmed']
		 	) {
				// Current user is an IT rep.
				// Update the comments, confirmation, and date fields.
				$it_rep_status['comments']  = $comments;
				$it_rep_status['confirmed'] = true;
				// Save the update.
				update_field( 'it_rep_status', $it_rep_status, $post_id );
				update_post_meta( $post_id, 'it_rep_status_date', date('Y-m-d H:i:s') );
				$json_out['status'] = 'success';
			}
		} elseif (
			current_user_can( 'wso_business_admin' )
			|| (
				( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) )
				&& is_array( $business_staff_status )
		 		&& is_array( $business_staff_status['business_staff'] )
		 		&& array_key_exists( 'ID', $business_staff_status['business_staff'] )
		 		&& $current_user_id === $business_staff_status['business_staff']['ID']
		 		&& false === $business_staff_status['confirmed']
			)
		) {
		 	if (
		 		is_array( $business_staff_status )
		 		&& is_array( $business_staff_status['business_staff'] )
		 		&& array_key_exists( 'ID', $business_staff_status['business_staff'] )
		 		&& $current_user_id === $business_staff_status['business_staff']['ID']
		 		&& false === $business_staff_status['confirmed']
		 	) {
				// Update the comments, confirmation, and date fields.
				$business_staff_status['comments']  = $comments;
				$business_staff_status['confirmed'] = true;
				// Update the account number if provided.
				if ( isset( $_POST['account_number'] ) ) {
					$account_number = sanitize_text_field( wp_unslash( $_POST['account_number'] ) );
					if ( ! empty( $account_number ) ) {
						$business_staff_status['account_number'] = $account_number;
					}
				}
				// Save the update.
				update_field( 'business_staff_status', $business_staff_status, $post_id );
				update_post_meta( $post_id, 'business_staff_status_date', date('Y-m-d H:i:s') );
				$json_out['status'] = 'success';
			}
		} elseif ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
		 	if (
		 		is_array( $it_logistics_status )
		 		&& false === $it_logistics_status['confirmed']
		 	) {
				// Update the comments, confirmation, and date fields.
				$it_logistics_status['comments']  = $comments;
				$it_logistics_status['confirmed'] = true;
				// Save the update.
				update_field( 'it_logistics_status', $it_logistics_status, $post_id );
				update_post_meta( $post_id, 'it_logistics_status_date', date('Y-m-d H:i:s') );
				$json_out['status'] = 'success';
				// Instruct the page to refresh.
				$json_out['refresh'] = true;
			}
		}

		echo json_encode( $json_out );
		die();

	}

	public function return_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'confirm_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );

		if ( 'wsorder' !== $post_type ) {
			return;
		}

		$json_out             = array();
		$current_user_id      = get_current_user_id();
		$it_rep_id            = (int) get_post_meta( $post_id, 'it_rep_status_it_rep', true );
		$business_staff_id    = (int) get_post_meta( $post_id, 'business_staff_status_business_staff', true );
		$comments             = sanitize_text_field( wp_unslash( $_POST['approval_comments'] ) );
		$json_out['comments'] = $comments;

		// Decide what kind of user this is.
		if (
			$current_user_id === $it_rep_id
			|| $current_user_id === $business_staff_id
			|| ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) )
		) {
			// Store user ID who returned the order.
			update_post_meta( $post_id, 'returned_comments', $comments );
			// Save the post status.
			$args = array(
				'ID'          => $post_id,
				'post_status' => 'returned',
			);
			$updated = wp_update_post( $args );

			if ( 0 === $updated || is_wp_error( $updated ) ) {
				$json_out['status'] = 'The order could not be returned.';
			} else {
				$json_out['status'] = 'success';
			}
		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Publish order.
	 */
	public function publish_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'confirm_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );

		if ( 'wsorder' !== $post_type ) {
			return;
		}

		$json_out = array();

		// Decide what kind of user this is.
		if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
			// Save the post status.
			$args = array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			);
			$published = wp_update_post( $args );

			if ( is_wp_error( $published ) ) {
				$json_out['errors'] = implode( ' ', $published->get_error_messages() );
			} else {
				$json_out['status'] = 'publish';
			}
		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Delete order.
	 *
	 * @return void
	 */
	public function delete_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'delete_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( isset( $_REQUEST['order_post_id'] ) ) {
			$post_id = (int) $_REQUEST['order_post_id'];
		} else {
			// Get referring post properties.
			$url     = wp_get_referer();
			$post_id = url_to_postid( $url );
		}
		$post_type = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );

		if ( 'wsorder' !== $post_type || ! in_array( $post_status, array( 'action_required', 'returned', 'publish' ) ) ) {
			return;
		}

		$json_out = array( 'status' => 'The post was not deleted due to an error.' );

		// Decide what kind of user this is.
		if ( current_user_can( 'wso_logistics_admin' ) || current_user_can( 'wso_logistics' ) || current_user_can( 'wso_admin' ) ) {
			$deleted = wp_delete_post( $post_id, true );
			if ( is_object( $deleted ) ) {
				$json_out['status'] = 'deleted';
			}
		} else {
			$json_out['status'] = 'You do not have permission.';
		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Confirm the order by this user.
	 *
	 * @return void
	 */
	public function search_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'search_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		$json_out     = array( 'status' => 'No orders found.' );
		$program_id   = (int) $_POST['program_id'];
		$status       = $_POST['order_status'];
		$args         = array(
			'post_type'      => 'wsorder',
			'post_status'    => array( 'publish', 'returned', 'action_required' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
		);

		if ( 'action_required' === $status ) {
			$args['post_status'] = 'action_required';
		} elseif ( 'returned' === $status ) {
			$args['post_status'] = 'returned';
		} elseif ( 'publish' === $status ) {
			$args['post_status'] = 'publish';
		}

		if ( 0 !== $program_id ) {
			$args['meta_key']   = 'program';
			$args['meta_value'] = $program_id;
		}

		$order_posts = get_posts( $args );
		if ( ! empty( $order_posts ) ) {
			$json_out['status']         = 'success';
			$json_out['program_prefix'] = get_post_meta( $program_id, 'prefix', true );
			if ( empty( $json_out['program_prefix'] ) ) {
				$json_out['program_prefix'] = get_the_title( $program_id );
			}
			$output                     = '';
			foreach ( $order_posts as $key => $order_id ) {
				$output .= $this->get_order_output( $order_id );
			}
			$json_out['output'] = $output;
		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Get order output for search page, identical to orders page template function.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	private function get_order_output( $post_id ) {
		$post = get_post( $post_id );
		date_default_timezone_set('America/Chicago');
		$creation_time         = strtotime( $post->post_date_gmt.' UTC' );
		$creation_date         = date( 'M j, Y \a\t g:i a', $creation_time );
		$status                = get_post_status( $post_id );
		$permalink             = get_permalink( $post_id );
		$author_id             = (int) get_post_field( 'post_author', $post_id );
		$author                = get_user_by( 'ID', $author_id );
		$author_name           = $author->display_name;
		$author_dept           = get_the_author_meta( 'department', $author_id );
		$dept_name             = get_the_title( $author_dept );
		$subtotal              = get_field( 'products_subtotal', $post_id );
		$subtotal              = '$' . number_format( $subtotal, 2, '.', ',' );
		$it_rep_fields         = get_field( 'it_rep_status', $post_id );
		$it_rep_name           = $it_rep_fields['it_rep']['display_name'];
		$business_admin_fields = get_field( 'business_staff_status', $post_id );
		$business_admin_name   = empty( $business_admin_fields['business_staff'] ) ? '' : $business_admin_fields['business_staff']['display_name'];
		$logistics_fields      = get_field( 'it_logistics_status', $post_id );
		$output                = '';

		// Combined output.
		$output .= "<tr class=\"post-{$post_id} wsorder entry status-{$status}\">";
		$output .= "<td class=\"status-indicator {$status}\"></td>";
		$output .= "<td><a href=\"{$permalink}\">{$author_name}</a><br>{$dept_name}</td>";
		$output .= "<td>{$creation_date}</td>";
		$output .= "<td>{$subtotal}</td>";
		$output .= "<td>";
		if ( $it_rep_fields['confirmed'] === true ) {
			$output .= "<span class=\"badge badge-success\">Confirmed</span>";
		} else {
			$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
		}
		$output .= "<br><small>{$it_rep_name}</small></td>";
		$output .= "<td>";
		if ( empty ( $business_admin_fields['business_staff'] ) ) {
			$output .= "<span class=\"badge badge-light\">Not required</span>";
		} else if ( $business_admin_fields['confirmed'] === true ) {
			$output .= "<span class=\"badge badge-success\">Confirmed</span>";
		} else {
			$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
		}
		$output .= "<br><small>{$business_admin_name}</small></td>";
		$output .= "<td>";
		if ( $logistics_fields['confirmed'] === true ) {
			$output .= "<span class=\"badge badge-success\">Confirmed</span>";
		} else {
			$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
		}
		if ( $logistics_fields['ordered'] === true ) {
			$output .= " <span class=\"badge badge-success\">Ordered</span>";
		} else {
			$output .= " <span class=\"badge badge-light\">Not yet ordered</span>";
		}
		$output .= "</td>";
		$output .= "<td>";
		if ( current_user_can( 'administrator' ) || current_user_can( 'wso_admin' ) || current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
			if ( 'publish' !== $status ) {
				$output .= '<a class="btn btn-sm btn-outline-yellow" title="Edit this order" href="' . $permalink . '"><span class="dashicons dashicons-welcome-write-blog"></span></a>';
			}
			$output .= '<button class="cla-delete-order btn btn-sm btn-outline-red" data-post-id="' . $post_id . '" data-clear-container="true" type="button" title="Delete this order"><span class="dashicons dashicons-trash"></span></button>';
		}
		$output .= "</td>";
		$output .= "</tr>";
		return $output;
	}

	/**
	 * Shows which single template is needed
	 *
	 * @param  string $single_template The default single template.
	 * @return string                  The correct single template
	 */
	public function get_single_template( $single_template ) {

		global $post;

		if ( 'wsorder' === get_query_var( 'post_type' ) ) {

			$can_update = $this->can_current_user_update_order_public( $post->ID );

			if ( 'publish' === $post->post_status || true !== $can_update ) {

				$single_template = CLA_WORKSTATION_ORDER_TEMPLATE_PATH . '/order-template.php';

			} else {

		    $current_user_id = get_current_user_id();
		    $customer_id     = (int) get_post_meta( $post->ID, 'order_author', true );

		    if ( $customer_id === $current_user_id && 'returned' === get_post_status( $post->ID ) ) {
					$single_template = CLA_WORKSTATION_ORDER_TEMPLATE_PATH . '/order-form-template.php';
		    } else {
					$single_template = CLA_WORKSTATION_ORDER_TEMPLATE_PATH . '/order-approval-template.php';
				}
			}
		}

		return $single_template;

	}

	/**
	 * Decide if user can update the order. Return true or the error message.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return true|string
	 */
	private function can_current_user_update_order_public( $post_id ) {

	  $post_status          = get_post_status( $post_id );
	  $can_update           = true;
	  $message              = '';
	  $current_user_id      = get_current_user_id();
	  $customer_id          = (int) get_post_meta( $post_id, 'order_author', true );
	  $affiliated_it_reps   = get_field( 'affiliated_it_reps', $post_id );
	  $affiliated_bus_staff = get_field( 'affiliated_business_staff', $post_id );
		$bus_user_id          = (int) get_post_meta( $post_id, 'business_staff_status_business_staff', true );
		$it_rep_confirmed     = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
		$bus_user_confirmed   = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );

		if ( 'publish' === $post_status ) {
			$can_update = false;
			$message    = 'This order is already published and cannot be changed.';
		} elseif (
			$customer_id !== $current_user_id
			&& 'returned' === $post_status
		) {
			$can_update = false;
			$message    = 'This order is being corrected by the customer.';
		} elseif (
			$customer_id === $current_user_id
			&& 'returned' !== $post_status
		) {
	  	// The user who submitted the order.
	  	$can_update = false;
	  	$message    = 'You can only change the order when it is returned to you.';
	  	// Handle when the order is for the logistics user.
	  	if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
	  		if ( 1 !== $it_rep_confirmed ) {
					$can_update = false;
					$message    = 'An IT Rep has not confirmed the order yet.';
	  		} elseif ( 0 !== $bus_user_id && $current_user_id !== $bus_user_id ) {
					$can_update = false;
					$message    = 'A business admin has not confirmed the order yet.';
	  		} else {
	  			$can_update = true;
	  		}
	  	}
  	// Handle when the order is for the business admin.
  	if ( current_user_can( 'wso_business_admin' ) ) {
  		if ( 1 !== $it_rep_confirmed ) {
				$can_update = false;
				$message    = 'An IT Rep has not confirmed the order yet.';
  		} elseif ( 1 === $bus_user_confirmed ) {
  			$can_update = false;
  			$message    = 'You have already confirmed the order.';
  		} else {
  			$can_update = true;
  		}
  	}
	  } elseif ( in_array( $current_user_id, $affiliated_it_reps ) ) {
			if ( 1 === $it_rep_confirmed ) {
				// IT Rep already confirmed the order, so they cannot change it right now.
				$can_update = false;
				$message    = 'An IT representative has already confirmed the order.';
			}
		} elseif ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
			// Sometimes the logistics user can be a business admin too.
			if ( 0 === $it_rep_confirmed && 'returned' !== $post_status ) {
				$can_update = false;
				$message    = 'An IT Rep has not confirmed the order yet.';
			} elseif ( 0 !== $bus_user_id && 0 === $bus_user_confirmed && $current_user_id !== $bus_user_id ) {
				$can_update = false;
				$message    = 'A business admin has not confirmed the order yet.';
			}
		} elseif ( in_array( $current_user_id, $affiliated_bus_staff ) ) {
			if ( 0 === $it_rep_confirmed ) {
				$can_update = false;
				$message    = 'An IT Rep has not confirmed the order yet.';
			} elseif ( 1 === $bus_user_confirmed ) {
				$can_update = false;
				$message    = 'A business admin has already confirmed the order.';
			} elseif ( 0 === $bus_user_id ) {
				$can_update = false;
				$message    = 'A business admin is not needed for this order.';
			}
	  }

	  if ( $can_update ) {
	    return true;
	  } else {
	  	return $message;
	  }
	}

	/**
	 * Decide if user can update the order. Return true or the error message.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return true|string
	 */
	private function can_current_user_update_order( $post_id ) {

		return 'Orders cannot be updated in the dashboard.';
    $post_status = get_post_status( $post_id );
    $can_update  = true;
    $message     = '';
  	if (
  		'publish' === $post_status
  		&& ! current_user_can( 'wso_admin' )
  		&& ( ! current_user_can( 'wso_logistics' ) || ! current_user_can( 'wso_logistics_admin' ) )
  	) {
  		$can_update = false;
  		$message    = 'This order is already published and cannot be changed.';
  	} elseif (
  		'returned' === $post_status
  		&& (
  			current_user_can( 'wso_it_rep' )
  			|| current_user_can( 'wso_business_admin' )
  			|| current_user_can( 'wso_logistics' )
  			|| current_user_can( 'wso_logistics_admin' )
  			|| current_user_can( 'wso_admin' )
  			|| current_user_can( 'administrator' )
  		)
  	) {
  		$can_update = false;
  		$message    = 'This order is being corrected by the customer.';
  	} elseif ( current_user_can('wso_it_rep') ) {
  		$it_rep_confirmed = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
  		if ( 1 === $it_rep_confirmed ) {
  			// IT Rep already confirmed the order, so they cannot change it right now.
  			$can_update = false;
  			$message    = 'You have already confirmed the order.';
  		}
    } elseif ( current_user_can( 'wso_business_admin' ) ) {
  		$it_rep_confirmed   = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
  		$bus_user_confirmed = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );
  		if ( 0 === $it_rep_confirmed ) {
  			$can_update = false;
  			$message    = 'The IT Rep has not confirmed the order yet.';
  		} elseif ( 1 === $bus_user_confirmed ) {
  			$can_update = false;
  			$message    = 'You have already confirmed the order.';
  		}
    } elseif ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
  		$it_rep_confirmed   = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
  		$bus_user           = (int) get_post_meta( $post_id, 'business_staff_status_business_staff', true );
  		$bus_user_confirmed = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );
  		if ( 0 === $it_rep_confirmed ) {
  			$can_update = false;
  			$message    = 'The IT Rep has not confirmed the order yet.';
  		} elseif ( ! empty( $bus_user ) && 0 === $bus_user_confirmed ) {
  			$can_update = false;
  			$message    = 'The business admin has not confirmed the order yet.';
  		}
    } elseif (
    	! current_user_can( 'wso_admin' )
    	&& ! current_user_can( 'administrator' )
    	&& 'returned' !== $post_status
    ) {
    	// Likely the user who submitted the order.
    	$can_update = false;
    	$message    = 'You can only change the order when it is returned to you.';
    }

    if ( $can_update ) {
      return true;
    } else {
    	return $message;
    }
	}

	/**
	 * Prevent users from saving changes to the order.
	 */
	public function disable_save_order() {

		// Ensure this is only running on the order edit page.
		if ( isset( $_POST['post_id'] ) ) {
	    $post_id    = $_POST['post_id'];
	    $post_type  = get_post_type( $post_id );
		} elseif (isset( $_POST['POST_ID'] ) ) {
			$post_id = $_POST['POST_ID'];
			$post_type = get_post_type( $post_id );
		} else {
	    $screen = get_current_screen();
	    if ( $screen ) {
	    	$post_type = $screen->post_type;
	    }
		}
    if ( ! isset( $post_type ) || 'wsorder' !== $post_type ) {
    	return;
    }

    // Remove all errors if user is an administrator.
    if ( current_user_can('manage_options') ) {
      acf_reset_validation_errors();
    }

    if ( ! current_user_can('wso_logistics') || ! current_user_can( 'wso_logistics_admin' ) ) {
      acf_add_validation_error( false, 'You cannot update the order.' );
    }

	}

	/**
	 * Register the post type and post statuses.
	 *
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-taxonomy.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Order',
				'plural'   => 'Orders',
			),
			'wsorder',
			array(),
			'dashicons-media-spreadsheet',
			array( 'title' ),
			array(
				'capabilities'       => array(
					'edit_post'              => 'edit_wsorder',
					'read_post'              => 'read_wsorder',
					'delete_post'            => 'delete_wsorder',
					'create_posts'           => 'create_wsorders',
					'delete_posts'           => 'delete_wsorders',
					'delete_others_posts'    => 'delete_others_wsorders',
					'delete_private_posts'   => 'delete_private_wsorders',
					'delete_published_posts' => 'delete_published_wsorders',
					'edit_posts'             => 'edit_wsorders',
					'edit_others_posts'      => 'edit_others_wsorders',
					'edit_private_posts'     => 'edit_private_wsorders',
					'edit_published_posts'   => 'edit_published_wsorders',
					'publish_posts'          => 'publish_wsorders',
					'read_private_posts'     => 'read_private_wsorders',
				),
				'map_meta_cap'       => true,
				'rewrite'            => array(
					'with_front' => false,
					'slug'       => 'orders',
				),
				'has_archive'        => false,
				// 'publicly_queryable' => false,
			)
		);

		register_post_status(
			'action_required',
			array(
				'label'                     => _x( 'Action Required', 'post' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: placeholder is the post count */
				'label_count'               => _n_noop( 'Action Required <span class="count">(%s)</span>', 'Action Required <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'returned',
			array(
				'label'                     => _x( 'Returned', 'post' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: placeholder is the post count */
				'label_count'               => _n_noop( 'Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>' ),
			)
		);

	}

	/**
	 * Make the order from AJAX data.
	 */
	public function make_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'make_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );
		$json_out  = array( 'errors' => array() );

		if ( 'wsorder' === $post_type && 'publish' === get_post_status( $post_id ) ) {
			return;
		}

		// Validate the file uploads, if present.
		if ( isset( $_POST['cla_quote_count'] ) && 0 < intval( $_POST['cla_quote_count'] ) ) {
			$quote_count = (int) sanitize_text_field( wp_unslash( $_POST['cla_quote_count'] ) );
			if ( $quote_count > 0 ) {

				/**
				 * Handle quote fields and file uploads.
				 */
				// These files need to be included as dependencies when on the front end.
				require_once CLA_WORKSTATION_ORDER_DIR_PATH . '/src/class-order-form-helper.php';
				$order_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();
				$quote_fields = array();
				for ( $i = 0; $i < $quote_count; $i++ ) {
					// Handle uploading quote file.
					if ( array_key_exists( "cla_quote_{$i}_file", $_FILES ) ) {
						$validate_file = $order_form_helper->validate_file_field( $_FILES["cla_quote_{$i}_file"], $i );
						if ( ! $validate_file['passed'] ) {
							$json_out['errors'][] = $validate_file['message'];
						}
					}
				}
				if ( count( $json_out['errors'] ) > 0 ) {
					echo json_encode( $json_out );
					die();
				}
			}
		}

		if ( 'wsorder' !== $post_type ) {

			// Make a new order.
			// Get current user and user ID.
			$user    = wp_get_current_user();
			$user_id = $user->get( 'ID' );

			// Get current program meta.
			$maybe_program_id = (int) isset( $_POST['cla_funding_program'] ) ? sanitize_text_field( wp_unslash( $_POST['cla_funding_program'] ) ) : 0;
			if ( $maybe_program_id ) {
				$program_id = (int) sanitize_text_field( wp_unslash( $_POST['cla_funding_program'] ) );
				$unfunded_program = get_field( 'unfunded_program', 'option' );
				if ( $unfunded_program->ID !== $program_id ) {
					// Validate the program ID.
					$author_post_args = array(
						'post_type'      => 'wsorder',
						'author'         => $user_id,
						'posts_per_page' => 1,
						'meta_key'       => 'program',
						'meta_value'     => $program_id,
						'post_status'    => array( 'publish', 'action_required', 'returned' ),
						'fields'         => 'ids',
					);
					$previous_order = get_posts( $author_post_args );
					if ( $previous_order ) {
						$json_out['errors'][] = 'You have already placed an order for that program: ' . get_permalink( $previous_order[0] );
						echo json_encode( $json_out );
						die();
					}
				}
			} else {
				$program_post = get_field( 'unfunded_program', 'option' );
				$program_id   = $program_post->ID;
			}
			$program_prefix = get_post_meta( $program_id, 'prefix', true );

			// Get new wsorder ID.
			$last_wsorder_id = $this->get_last_order_id( $program_id );
			$wsorder_id      = $last_wsorder_id + 1;

			// Get user's department.
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$user_department_post_id = $user_department_post->ID;

			// Save order affiliated it reps.
			// Save order affiliated business reps.
			$program_department_fields = $this->get_program_department_fields( $user_department_post_id, $program_id );
			$affiliated_it_reps        = $program_department_fields['it_reps'];
			$affiliated_business_staff = $program_department_fields['business_admins'];

			// Insert post.
			$postarr = array(
				'post_author'    => $user_id,
				'post_status'    => 'action_required',
				'post_type'      => 'wsorder',
				'comment_status' => 'closed',
				'post_title'     => "{$program_prefix}-{$wsorder_id}",
				'post_content'   => '',
			);
			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {

				// Failed to generate a new post.
				$message .= implode( ' ', $post_id->get_error_messages() );
				$message .= "
	Here is the form data:
	";
				$message = serialize($_POST);
				wp_mail( 'zwatkins2@tamu.edu', 'Failed to create order.', $message, array( 'Content-Type: text/html; charset=UTF-8' ) );

				$json_out['errors'][] = 'Failed to create the order. The webmaster has been notified.';
				echo json_encode( $json_out );
				die();

			}

			// Update ACF fields.
			update_field( 'order_id', $wsorder_id, $post_id );
			update_field( 'order_author', $user_id, $post_id );
			update_field( 'author_department', $user_department_post_id, $post_id );
			update_field( 'affiliated_it_reps', $affiliated_it_reps, $post_id );
			update_field( 'affiliated_business_staff', $affiliated_business_staff, $post_id );
			update_field( 'program', $program_id, $post_id );
			$it_rep_status = array(
				'it_rep'    => 0,
				'confirmed' => false,
				'comments'  => '',
			);
			update_field( 'it_rep_status', $it_rep_status, $post_id );
			$business_staff_status = array(
				'business_staff' => 0,
				'confirmed'      => false,
				'comments'       => '',
			);
			update_field( 'business_staff_status', $business_staff_status, $post_id );
			$logistics_status = array(
				'comments'       => '',
				'account_number' => '',
				'confirmed'      => false,
				'ordered'        => false,
			);
			update_field( 'it_logistics_status', $logistics_status, $post_id );

		} else {

			// Update an existing order.
			// Get user and user ID.
			$user_id = get_field( 'order_author', $post_id );
			$user    = get_user_by( 'id', $user_id );

			// Get user's department.
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$user_department_post_id = $user_department_post->ID;

			// Update program choice.
			$maybe_program_id = (int) isset( $_POST['cla_funding_program'] ) ? sanitize_text_field( wp_unslash( $_POST['cla_funding_program'] ) ) : get_post_meta( $post_id, 'program', true );
			if ( 0 !== $maybe_program_id ) {
				$program_post = get_post( $maybe_program_id );
			} else {
				$program_post = get_field( 'unfunded_program', 'option' );
			}
			$program_id     = $program_post->ID;
			$program_prefix = get_post_meta( $program_id, 'prefix', true );
			update_field( 'program', $program_id, $post_id );

			if ( 'returned' === get_post_status( $post_id ) ) {
				wp_update_post( array(
						'ID' => $post_id,
						'post_status' => 'action_required',
				) );
			}

		}

		$json_out['order_url'] = get_permalink( $post_id );

		/**
		 * Save ACF field values.
		 * https://www.advancedcustomfields.com/resources/update_field/
		 */

		// Save building location.
		if ( isset( $_POST['cla_building_name'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['cla_building_name'] ) );
			update_field( 'building', $value, $post_id );
		}

		// Save office location.
		if ( isset( $_POST['cla_room_number'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['cla_room_number'] ) );
			update_field( 'office_location', $value, $post_id );
		}

		// Save contribution amount.
		if ( isset( $_POST['cla_contribution_amount'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['cla_contribution_amount'] ) );
			update_field( 'contribution_amount', $value, $post_id );
		}

		// Save account number.
		if ( isset( $_POST['cla_account_number'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['cla_account_number'] ) );
			update_field( 'contribution_account', $value, $post_id );
		}

		// Save order comment.
		if ( isset( $_POST['cla_order_comments'] ) ) {
			$value = sanitize_textarea_field( wp_unslash( $_POST['cla_order_comments'] ) );
			update_field( 'order_comment', $value, $post_id );
		}

		// Save current asset.
		if ( isset( $_POST['cla_current_asset_number'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['cla_current_asset_number'] ) );
			update_field( 'current_asset', $value, $post_id );
		}

		// Save no computer yet field.
		if ( isset( $_POST['cla_no_computer_yet'] ) ) {
			$value = sanitize_key( wp_unslash( $_POST['cla_no_computer_yet'] ) );
			if ( 'on' === $value ) {
				$value = 1;
			} else {
				$value = 0;
			}
			update_field( 'i_dont_have_a_computer_yet', $value, $post_id );
		}

		// Save department IT Rep.
		if ( isset( $_POST['cla_it_rep_id'] ) ) {
			$value           = get_field( 'it_rep_status', $post_id );
			$it_rep          = sanitize_text_field( wp_unslash( $_POST['cla_it_rep_id'] ) );
			$value['it_rep'] = $it_rep;
			update_field( 'it_rep_status', $value, $post_id );
		}

		// Product subtotal.
		$product_subtotal = 0;

		// Let WordPress handle the upload.
		// Remember, 'cla_quote_0_file' is the name of our file input in our form above.
		// Here post_id is 0 because we are not going to attach the media to any post.
		if ( isset( $_POST['cla_quote_count'] ) && 0 < intval( $_POST['cla_quote_count'] ) ) {

			$quote_count = sanitize_text_field( wp_unslash( $_POST['cla_quote_count'] ) );

			if ( $quote_count > 0 ) {

				/**
				 * Handle quote fields and file uploads.
				 */
				// These files need to be included as dependencies when on the front end.
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';

				$quote_fields = array();
				for ( $i = 0; $i < $quote_count; $i++ ) {

					if (
						isset( $_POST[ "cla_quote_{$i}_name" ] )
						&& isset( $_POST[ "cla_quote_{$i}_price" ] )
						&& isset( $_POST[ "cla_quote_{$i}_description" ] )
					) {
						$quote_fields[ $i ] = array(
							'name'        => sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$i}_name" ] ) ),
							'price'       => sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$i}_price" ] ) ),
							'description' => sanitize_textarea_field( wp_unslash( $_POST[ "cla_quote_{$i}_description" ] ) ),
						);
						$product_subtotal += floatval( sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$i}_price" ] ) ) );
					}

					// Handle uploading quote file.
					if ( array_key_exists( "cla_quote_{$i}_file", $_FILES ) ) {
						$attachment_id = media_handle_upload( "cla_quote_{$i}_file", 0 );
						if ( ! is_wp_error( $attachment_id ) ) {
							// Attach file.
							$quote_fields[ $i ]['file'] = $attachment_id;
						} else {
							$json_out['errors'][] = implode( ' ', $attachment_id->get_error_messages() );
						}
					} else {
						$quote_fields[ $i ]['file'] = get_post_meta( $post_id, "quotes_{$i}_file", true );
					}
				}
				update_field( 'quotes', $quote_fields, $post_id );

			}
		}

		/**
		 * Save product information.
		 */
		// Validate data.
		if ( isset( $_POST['cla_product_ids'] ) && ! empty( $_POST['cla_product_ids'] ) ) {

			$product_post_ids = sanitize_text_field( wp_unslash( $_POST['cla_product_ids'] ) );
			$product_post_ids = explode( ',', $product_post_ids );

			// Remove all product IDs not included in the program.
			if ( ! isset( $cla_form_helper ) ) {
				require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
				$cla_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();
			}
			$products_and_bundles = $cla_form_helper->get_product_post_objects_for_program_by_user_dept( $program_id );
			$restack_product_ids  = false;
			foreach ( $product_post_ids as $key => $id ) {
				if ( ! array_key_exists( $id, $products_and_bundles ) ) {
					unset( $product_post_ids[$key] );
					$restack_product_ids = true;
				}
			}
			if ( $restack_product_ids && count( $product_post_ids ) > 0 ) {
				$product_post_ids = array_values( $product_post_ids );
			}

			if ( count( $product_post_ids ) > 0 ) {

				// Save product and bundle ids.
				update_field( 'selected_products_and_bundles', $product_post_ids, $post_id );

				// Get subtotal for products and bundles.
				foreach ($product_post_ids as $product_post_id) {
					$price = get_field( 'price', $product_post_id );
					$product_subtotal += $price;
				}

				// Break down bundles into individual product post ids.
				$actual_product_collection = array();

				foreach ( $product_post_ids as $product_post_id ) {

					$product_post_type = get_post_type( $product_post_id );

					if ( 'bundle' === $product_post_type ) {

						// Get products in bundle as post IDs.
						$bundle_products = get_field( 'products', $product_post_id );

						foreach ( $bundle_products as $bundle_product_post_id ) {

							$actual_product_collection[] = $bundle_product_post_id;

						}

					} else {

						$actual_product_collection[] = $product_post_id;

					}
				}
				$product_post_ids = $actual_product_collection;

				// Convert products into ACF fields.
				$product_fields = array();
				foreach ( $product_post_ids as $key => $product_post_id ) {
					$product_fields[ $key ] = array(
						'sku'     => get_field( 'sku', $product_post_id ),
						'item'    => get_the_title( $product_post_id ),
						'price'   => get_field( 'price', $product_post_id ),
						'post_id' => $product_post_id,
					);
				}
				update_field( 'order_items', $product_fields, $post_id );

			}
		}

		// Save product subtotal.
		$value = $product_subtotal;
		update_field( 'products_subtotal', $value, $post_id );

		// Save department Business Admin.
		// Get business admin assigned to active user's department for current program.
		$threshold             = (float) get_field( 'threshold', $program_id );
		$business_staff_status = get_field( 'business_staff_status', $post_id );
		if ( $product_subtotal > $threshold ) {
			$current_staff = $business_staff_status['business_staff'];
			if ( empty( $current_staff ) ) {
				$dept_assigned_business_admins = $this->get_program_business_admin_user_id( $program_id, $user_department_post_id );
				$business_staff_status['business_staff'] = empty( $dept_assigned_business_admins ) ? '' : $dept_assigned_business_admins[0];
				update_field( 'business_staff_status', $business_staff_status, $post_id );
			}
		} else {
			$business_staff_status['business_staff'] = '';
			update_field( 'business_staff_status', $business_staff_status, $post_id );
		}

		// Do custom action.
		do_action( 'wsorder_submitted', $post_id );

		echo json_encode( $json_out );
		die();

	}

	public function update_order_acquisitions(){

		// Ensure nonce is valid.
		check_ajax_referer( 'confirm_order' );

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );
		$json_out  = array( 'status' => 'You do not have sufficient privileges' );

		if ( 'wsorder' !== $post_type ) {
			return;
		}

		if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
			$ordered_all = true;
			$was_checked = false;
			// Update products.
			$products = get_field( 'order_items', $post_id );
			foreach ($products as $key => $product) {
				$was_checked = true;
				$req_number   = sanitize_text_field( wp_unslash( $_POST[ "cla_item_{$key}_req_number" ] ) );
				$req_date     = sanitize_text_field( wp_unslash( $_POST[ "cla_item_{$key}_req_date" ] ) );
				$asset_number = sanitize_text_field( wp_unslash( $_POST[ "cla_item_{$key}_asset_number" ] ) );
				$products[$key]['requisition_number'] = $req_number;
				$products[$key]['requisition_date']   = $req_date;
				$products[$key]['asset_number']       = $asset_number;
				if ( empty( $req_date ) || empty( $req_number ) ) {
					$ordered_all = false;
				}
			}
			update_field( 'order_items', $products, $post_id );
			// Update quotes.
			$quotes = get_field( 'quotes', $post_id );
			foreach ($quotes as $key => $quote) {
				$was_checked = true;
				$req_number   = sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$key}_req_number" ] ) );
				$req_date     = sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$key}_req_date" ] ) );
				$asset_number = sanitize_text_field( wp_unslash( $_POST[ "cla_quote_{$key}_asset_number" ] ) );
				$quotes[$key]['requisition_number'] = $req_number;
				$quotes[$key]['requisition_date']   = $req_date;
				$quotes[$key]['asset_number']       = $asset_number;
				if ( empty( $req_date ) || empty( $req_number ) ) {
					$ordered_all = false;
				}
			}
			$json_out['quotes'] = $quotes;
			update_field( 'quotes', $quotes, $post_id );

			if ( $ordered_all && $was_checked ) {
				// Logistics user has finished ordering items.
				$logistics_fields = get_field( 'it_logistics_status', $post_id );
				$logistics_fields['ordered'] = true;
				update_field( 'it_logistics_status', $logistics_fields, $post_id );
				update_post_meta( $post_id, 'it_logistics_status_ordered_at', date('Y-m-d H:i:s') );
			}

			$json_out['status'] = 'success';

		}

		echo json_encode( $json_out );
		die();

	}

	/**
	 * Make the order from AJAX data.
	 */
	public function get_program_products() {

		// Ensure nonce is valid.
		check_ajax_referer( 'make_order' );

		if ( ! is_user_logged_in() ) {
			die();
		}

		// Get referring post properties.
		$url       = wp_get_referer();
		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );
		$json_out  = array( 'status' => 'failed' );

		if ( 'wsorder' === $post_type && 'publish' === get_post_status( $post_id ) ) {
			echo json_encode( $json_out );
			die();
		}

		if ( ! isset( $_POST['program_id'] ) || ! isset( $_POST['selected_products'] ) ) {
			echo json_encode( $json_out );
			die();
		}

		$program_id = (int) sanitize_text_field( wp_unslash( $_POST[ 'program_id' ] ) );
		// Build an array of ints for selected products.
		$selected_products = sanitize_text_field( wp_unslash( $_POST[ 'selected_products' ] ) );
		if ( strpos( $selected_products, ',' ) ) {
			$selected_products = explode( ',', $selected_products );
		} elseif ( $selected_products ) {
			$selected_products = array( $selected_products );
		} else {
			$selected_products = array();
		}
		foreach ( $selected_products as $key => $value ) {
			$selected_products[$key] = (int) $value;
		}

		/**
		 * Get the CLA Form Helper class.
		 */
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
		$cla_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();

		/**
		 * Get product categories.
		 */
		$json_out['apple']  = $cla_form_helper->cla_get_products( 'apple', $program_id, false, $selected_products );
		$json_out['pc']     = $cla_form_helper->cla_get_products( 'pc', $program_id, false, $selected_products );
		$json_out['addons'] = $cla_form_helper->cla_get_products( 'add-on', $program_id, false, $selected_products );

		/**
		 * Get product prices.
		 */
		$products_and_bundles = $cla_form_helper->get_product_post_objects_for_program_by_user_dept( $program_id );
		foreach ( $products_and_bundles as $post_id => $post_title ) {
			$products_and_bundles[$post_id] = get_post_meta( $post_id, 'price', true );
		}
		$json_out['prices'] = $products_and_bundles;

		$json_out['status'] = 'success';
		echo json_encode( $json_out );
		die();

	}

	/**
	 * Get disallowed product and bundle IDs.
	 *
	 * @return array
	 */
	public function get_disallowed_product_and_bundle_ids() {

		$user                        = wp_get_current_user();
		$user_id                     = $user->get( 'ID' );
		$user_department_post        = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id     = $user_department_post->ID;
		$hidden_products             = get_field( 'hidden_products', $user_department_post_id );
		$hidden_bundles              = get_field( 'hidden_bundles', $user_department_post_id );
		$hidden_products_and_bundles = array();
		if ( is_array( $hidden_products ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_products );
		}
		if ( is_array( $hidden_bundles ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_bundles );
		}
		return $hidden_products_and_bundles;

	}

	/**
	 * Get the user ID of the designated business admin within a program's department.
	 *
	 * @param int $program_id              The program ID.
	 * @param int $user_department_post_id The department ID.
	 *
	 * @return array
	 */
	private function get_program_business_admin_user_id( $program_id, $user_department_post_id ) {

		// Get users assigned to active user's department for current program, as array.
		$program_assignments = get_field( 'assign', $program_id );
		$value               = array();

		foreach ( $program_assignments as $department ) {
			$assigned_dept = (int) $department['department_post_id'];
			if ( $user_department_post_id === $assigned_dept ) {
				$business_admins = $department['business_admins'];
				if ( ! empty( $business_admins ) ) {
					$value = $business_admins;
				}
				break;
			}
		}

		return $value;

	}

	/**
	 * Get the department fields within a program based from the department associated with the given user ID.
	 *
	 * @param int $department_id The department ID.
	 * @param int $program_id    The program ID.
	 *
	 * @return array
	 */
	private function get_program_department_fields( $department_id, $program_id ) {

		// Get users assigned to active user's department for current program, as array.
		$program_assignments = get_field( 'assign', $program_id );
		$value               = array();

		foreach ( $program_assignments as $department ) {
			$assigned_dept = (int) $department['department_post_id'];
			if ( $department_id === $assigned_dept ) {
				$value['business_admins'] = $department['business_admins'];
				$value['it_reps']         = $department['it_reps'];
				break;
			}
		}

		return $value;

	}

	/**
	 * Register custom fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_custom_fields() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/wsorder-admin-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/wsorder-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/wsorder-return-to-user-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/order-department-comments-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/it-rep-status-order-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/business-staff-status-order-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/it-logistics-status-order-fields.php';

	}

	/**
	 * Redirect visitors trying to see all orders to the current program year's orders.
	 */
	public function redirect_to_current_program_orders() {

		global $pagenow;

		if (
			'edit.php' === $pagenow
			&& isset( $_GET['post_type'] ) //phpcs:ignore
			&& 'wsorder' === $_GET['post_type'] //phpcs:ignore
			&& ! isset( $_GET['program'] ) //phpcs:ignore
			&& isset( $_SERVER['QUERY_STRING'] )
		) {
			$current_program_id = get_site_option( 'options_current_program' );
			$url                = get_admin_url() . 'edit.php?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) . "&program={$current_program_id}";
			wp_safe_redirect( $url );
			exit();
		}

	}

	/**
	 * Determine if business approval is required.
	 *
	 * @param int $post_id The wsorder post ID to check.
	 *
	 * @return boolean
	 */
	private function order_requires_business_approval( $post_id ) {

		// Get order subtotal.
		$subtotal = (float) get_field( 'products_subtotal', $post_id );
		// Get order program post ID.
		$program_id = get_field( 'program', $post_id );

		if ( ! empty( $program_id ) ) {

			// Get order's program allocation threshold.
			$program_threshold = (float) get_field( 'threshold', $program_id );

			if ( $subtotal > $program_threshold ) {

				return true;

			} else {

				return false;

			}
		} else {

			return true;

		}

	}

	/**
	 * Add program as a URL parameter.
	 *
	 * @param array $vars Current variables.
	 *
	 * @return array
	 */
	public function add_program_url_var( $vars ) {
		if ( ! in_array( 'program', $vars ) ) {
			$vars[] = 'program';
		}
		return $vars;
	}

	/**
	 * Add JS for edit wsorder pages in admin.
	 *
	 * @return void
	 */
	public function admin_script() {
		global $post_type;
		if ( 'wsorder' === $post_type ) {
			wp_enqueue_script( 'cla-workstation-order-admin-script' );
		}
	}

	/**
	 * Add HTML for custom post statuses.
	 *
	 * @return void
	 */
	public function post_status_add_to_dropdown() {

		global $post;
		if ( 'wsorder' !== $post->post_type ) {
			return;
		}

		$status = '';
		switch ( $post->post_status ) {
			case 'action_required':
				$status = "jQuery( '#post-status-display' ).text( 'Action Required' );
jQuery( 'select[name=\"post_status\"]' ).val('action_required')";
				break;

			case 'returned':
				$status = "jQuery( '#post-status-display' ).text( 'Returned' );
jQuery( 'select[name=\"post_status\"]' ).val('returned')";
				break;

			case 'publish':
				$status = "jQuery( '#post-status-display' ).text( 'Published' );
jQuery( 'select[name=\"post_status\"]' ).val('publish')";
				break;

			default:
				break;
		}

		$subscriber_disabled = '';
		if (
			! current_user_can( 'wso_logistics' )
			&& ! current_user_can( 'wso_logistics_admin' )
			&& ! current_user_can( 'wso_it_rep' )
			&& ! current_user_can( 'wso_business_admin' )
			&& ! current_user_can( 'wso_admin' )
		) {
			$subscriber_disabled = ' disabled="disabled"';
		}

		$it_rep_disabled = '';
		if (
			! current_user_can( 'wso_logistics' )
			&& ! current_user_can( 'wso_logistics_admin' )
			&& ! current_user_can( 'wso_business_admin' )
			&& ! current_user_can( 'wso_admin' )
		) {
			if ( empty( $subscriber_disabled ) ) {
				$it_rep_disabled = ' disabled="disabled"';
			}
		}

		$non_logistics_disabled = '';
		if (
			! current_user_can( 'wso_logistics' )
			&& ! current_user_can( 'wso_logistics_admin' )
			&& ! current_user_can( 'wso_admin' )
		) {
			$non_logistics_disabled = ' disabled="disabled"';
		}
		echo wp_kses(
			"<script>
			jQuery(document).ready( function() {
				jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"action_required\">Action Required</option><option value=\"returned\"$subscriber_disabled>Returned</option><option value=\"publish\"$non_logistics_disabled>Publish</option>' );
				" . $status . '
			});
		</script>',
			array(
				'script' => array(),
				'option' => array(
					'value'    => array(),
					'disabled' => array(),
				),
			)
		);

	}

	/**
	 * Display the custom post status taglines on the edit page.
	 *
	 * @param array $states The post status.
	 *
	 * @return array
	 */
	public function display_status_state( $states ) {

		global $post;
		global $pagenow;
		if ( 'edit.php' === $pagenow && 'wsorder' === $post->post_type ) {
			$arg = get_query_var( 'post_status' );
			switch ( $post->post_status ) {
				case 'action_required':
					if ( $arg !== $post->post_status ) {
						$states = array( 'Action Required' );
					}
					break;

				case 'returned':
					if ( $arg !== $post->post_status ) {
						$states = array( 'Returned' );
					}
					break;

				case 'completed':
					if ( $arg !== $post->post_status ) {
						$states = array( 'Completed' );
					}
					break;

				default:
					break;
			}
		}

		return $states;

	}

	/**
	 * Get the last order ID for a given program.
	 *
	 * @param int $program_post_id The program post ID.
	 *
	 * @return int
	 */
	public function get_last_order_id( $program_post_id ) {

		$args  = array(
			'post_type'      => 'wsorder',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'order_id',
			'order'          => 'DESC',
			'meta_query'     => array( //phpcs:ignore
				array(
					'key'     => 'order_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'program',
					'compare' => '=',
					'value'   => $program_post_id,
				),
			),
		);
		$posts = get_posts( $args );
		if ( ! empty( $posts ) ) {
			$last_wsorder_id = (int) get_post_meta( $posts[0], 'order_id', true );
		} else {
			$last_wsorder_id = 0;
		}

		return $last_wsorder_id;

	}

	/**
	 * Add columns to the list view for Order posts.
	 *
	 * @param array $columns THe current set of columns.
	 * @return array
	 */
	public function add_list_view_columns( $columns ) {

		$status  = array( 'status' => '' );
		$columns = array_merge( $status, $columns );
		// unset( $columns['title'] );
		// unset( $columns['date'] );
		unset( $columns['author'] );
		$columns['ordered_by']       = 'Ordered By';
		$columns['ordered_at']       = 'Ordered At';
		$columns['amount']           = 'Amount';
		$columns['it_status']        = 'IT';
		$columns['business_status']  = 'Business';
		$columns['logistics_status'] = 'Logistics';
		return $columns;

	}

	/**
	 * Add columns to order post list view.
	 *
	 * @param string $column_name The currently handled column name.
	 * @param int    $post_id     The current post ID.
	 */
	public function output_list_view_columns( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'status':
				$status = get_post_status( $post_id );
				echo wp_kses_post( "<div class=\"status-color-key {$status}\"></div>" );
				break;

			case 'amount':
				$number = (float) get_post_meta( $post_id, 'products_subtotal', true );
				if ( class_exists( 'NumberFormatter' ) ) {
					$formatter = new \NumberFormatter( 'en_US', \NumberFormatter::CURRENCY );
					echo wp_kses_post( $formatter->formatCurrency( $number, 'USD' ) );
				} else {
					echo wp_kses_post( '$' . number_format( $number, 2, '.', ',' ) );
				}
				break;

			case 'it_status':
				$status = get_field( 'it_rep_status', $post_id );
				if ( empty( $status['confirmed'] ) ) {
					echo wp_kses_post( '<span class="approval not-confirmed">Not yet confirmed</span>' );
				} else {
					echo wp_kses_post( '<span class="approval confirmed">Confirmed</span><br>' );
					echo wp_kses_post( $status['it_rep']['display_name'] );
				}
				break;

			case 'business_status':
				// Determine status message.
				$business_staff_id = get_post_meta( $post_id, 'business_staff_status_business_staff', true );
				if ( ! empty( $business_staff_id ) ) {
					$status = get_field( 'business_staff_status', $post_id );
					if ( empty( $status['confirmed'] ) ) {
						echo wp_kses_post( '<span class="approval not-confirmed">Not yet confirmed</span>' );
					} else {
						echo wp_kses_post( '<span class="approval confirmed">Confirmed</span><br>' );
						echo wp_kses_post( $status['business_staff']['display_name'] );
					}
				} else {
					echo wp_kses_post( '<span class="approval">Not required</span>' );
				}
				break;

			case 'logistics_status':
				$it_status        = get_field( 'it_rep_status', $post_id );
				$business_status  = get_field( 'business_staff_status', $post_id );
				$logistics_status = get_field( 'it_logistics_status', $post_id );
				if (
					( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) )
					&& (
						empty( $it_status['confirmed'] )
						|| (
							! empty( $business_status['business_staff'] )
							&& empty( $business_status['confirmed'] )
						)
					)
				) {
					echo wp_kses_post( '<span class="approval not-confirmed">Awaiting another</span>' );
				} elseif ( empty( $logistics_status['confirmed'] ) ) {
					echo wp_kses_post( '<span class="approval not-confirmed">Not yet confirmed</span>' );
				} else {
					echo wp_kses_post( '<span class="approval confirmed">Confirmed</span> ' );
					if ( empty( $logistics_status['ordered'] ) ) {
						echo wp_kses_post( '<span class="approval not-fully-ordered">Not fully ordered</span>' );
					} else {
						echo wp_kses_post( '<span class="approval ordered">Ordered</span>' );
					}
				}
				break;

			case 'ordered_at':
				$ordered = get_post_meta( $post_id, 'it_logistics_status_ordered_at', true );
				if ( ! empty( $ordered ) ) {
					echo esc_html( date( 'F j, Y \a\t g:i a', strtotime( $ordered ) ) );
				}
				break;

			case 'ordered_by':
				$current_user      = wp_get_current_user();
				$current_user_id   = $current_user->ID;
				$author_id         = (int) get_post_field( 'post_author', $post_id );
				$author            = get_user_by( 'ID', $author_id );
				$author_name       = $author->display_name;
				$author_dept       = get_the_author_meta( 'department', $author_id );
				$dept_name         = get_the_title( $author_dept );
				$author_link_open  = '';
				$author_link_close = '';
				if ( current_user_can( 'wso_admin' ) ) {
					$author_link       = add_query_arg( 'user_id', $author_id, self_admin_url( 'user-edit.php' ) );
					$author_link_open  = "<a href=\"$author_link\">";
					$author_link_close = '</a>';
				} elseif ( $current_user_id === $author_id ) {
					$author_link       = get_edit_profile_url();
					$author_link_open  = "<a href=\"$author_link\">";
					$author_link_close = '</a>';
				}
				echo "{$author_link_open}$author_name{$author_link_close}<br>$dept_name";
				break;

			default:
				break;
		}
	}

	/**
	 * Modify the Order post type query so that people not involved with the order
	 * cannot see the post.
	 *
	 * @param object $query The query object.
	 *
	 * @return void
	 */
	public function pre_get_posts( $query ) {

		if ( 'wsorder' === $query->get( 'post_type' ) ) {
			// Allow admins and logistics to see all orders.
			// Do not limit views in admin.
			if (
				current_user_can( 'administrator' )
				|| current_user_can( 'wso_admin' )
				|| current_user_can( 'wso_logistics' )
				|| current_user_can( 'wso_logistics_admin' )
			) {
				return;
			}

			// Exclude the last order ID query.
			$posts_per_page = $query->get( 'posts_per_page' );
			$meta_key       = $query->get( 'meta_key' );
			if ( 1 === $posts_per_page && 'order_id' === $meta_key ) {
				return;
			}

			// Everyone else must be restricted.
			// Overwrite existing meta query.
			$meta_query = $query->get( 'meta_query' );
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}
			$current_user_id = get_current_user_id();
			$meta_query[]    = array(
				'relation' => 'OR',
				array(
					'key'     => 'affiliated_it_reps',
					'value'   => '[":]{1}' . $current_user_id . '[";]{1}',
					'compare' => 'REGEXP',
				),
				array(
					'key'     => 'affiliated_business_staff',
					'value'   => '[":]{1}' . $current_user_id . '[";]{1}',
					'compare' => 'REGEXP',
				),
				array(
					'key'   => 'order_author',
					'value' => $current_user_id,
				),
			);
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Prevent users not involved with a work order from accessing the edit page.
	 *
	 * @return void
	 */
	public function redirect_uninvolved_users_from_editing() {
		// Prevent users who aren't on a work order from viewing/editing it.
		global $pagenow;
		if (
			isset( $_GET['post'] )
			&& 'post.php' === $pagenow
		) {
			wp_verify_nonce( 'edit' );
			$user               = wp_get_current_user();
			$current_user_id    = $user->ID;
			$post_id            = absint( $_GET['post'] );
			$author_id          = (int) get_post_field( 'post_author', $post_id );
			$it_rep_ids         = get_field( 'affiliated_it_reps', $post_id );
			$business_admin_ids = get_field( 'affiliated_business_staff', $post_id );
			$post_type          = get_post_type( $post_id );
			if (
				'wsorder' === $post_type
				&& ! current_user_can( 'administrator' )
				&& ! current_user_can( 'wso_admin' ) // Not an admin.
				&& ! current_user_can( 'wso_logistics' ) // Not a logistics user.
				&& ! current_user_can( 'wso_logistics_admin' )
				&& $current_user_id !== $author_id // Not the author.
				&& ! in_array( $current_user_id, $it_rep_ids, true ) // Not the IT rep.
				&& ! in_array( $current_user_id, $business_admin_ids, true ) // Not the business admin.
			) {
				// User isn't involved with this order and should be redirected away.
				$post_program_id = (int) get_field( 'program', $post_id );
				$location        = admin_url() . 'edit.php?post_type=wsorder&program=' . $post_program_id;
				wp_safe_redirect( $location );
				exit();
			}
		}
	}

	/**
	 * Add status tags to the order list view.
	 *
	 * @param array $views Views for the current post type.
	 *
	 * @return array
	 */
	public function change_order_list_status_link_counts_and_urls( $views ) {

		if (
			'wsorder' === get_query_var( 'post_type' )
			&& get_query_var( 'program', false )
		) {

			$program_id      = get_query_var( 'program' );
			$user            = wp_get_current_user();
			$current_user_id = $user->ID;
			// All link.
			$args            = array(
				'post_type'      => 'wsorder',
				'posts_per_page' => -1,
				'meta_query'     => array( //phpcs:ignore
					array(
						'key'   => 'program',
						'value' => $program_id,
					),
				),
				'fields'         => 'ids',
			);
			if ( ! current_user_can( 'wso_admin' ) && ! current_user_can( 'wso_logistics' ) && ! current_user_can( 'wso_logistics_admin' ) ) {
				$args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'affiliated_it_reps',
						'value'   => '\D' . $current_user_id . '\D',
						'compare' => 'REGEXP',
					),
					array(
						'key'     => 'affiliated_business_staff',
						'value'   => '\D' . $current_user_id . '\D',
						'compare' => 'REGEXP',
					),
					array(
						'key'   => 'order_author',
						'value' => $current_user_id,
					),
				);
			}
			$query        = new \WP_Query( $args );
			$count        = $query->post_count;
			$views['all'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['all'] );
			$views['all'] = str_replace( 'edit.php?post_type=wsorder', "edit.php?post_type=wsorder&program={$program_id}", $views['all'] );
			// Mine link.
			if ( isset( $views['mine'] ) ) {
				$mine_args     = array(
					'post_type'      => 'wsorder',
					'post_status'    => 'any',
					'author'         => $current_user_id,
					'posts_per_page' => -1,
				);
				$mine_query    = new \WP_Query( $mine_args );
				$count         = $mine_query->post_count;
				$views['mine'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['mine'] );
				$views['mine'] = str_replace( 'post_type=wsorder', "post_type=wsorder&program=0", $views['mine'] );
			}
			// Publish link.
			if ( isset( $views['publish'] ) ) {
				$pub_args                = $args;
				$pub_args['post_status'] = 'publish';
				$pub_query               = new \WP_Query( $pub_args );
				$count                   = $pub_query->post_count;
				$views['publish']        = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['publish'] );
				$views['publish']        = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['publish'] );
			}
			// Action Required link.
			if ( isset( $views['action_required'] ) ) {
				$args['post_status']      = 'action_required';
				$ar_query                 = new \WP_Query( $args );
				$count                    = $ar_query->post_count;
				$views['action_required'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['action_required'] );
				$views['action_required'] = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['action_required'] );
			}
			// Returned link.
			if ( isset( $views['returned'] ) ) {
				$args['post_status'] = 'returned';
				$ar_query            = new \WP_Query( $args );
				$count               = $ar_query->post_count;
				$views['returned']   = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['returned'] );
				$views['returned']   = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['returned'] );
			}
			// Completed link.
			if ( isset( $views['completed'] ) ) {
				$args['post_status'] = 'completed';
				$ar_query            = new \WP_Query( $args );
				$count               = $ar_query->post_count;
				$views['completed']  = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['completed'] );
				$views['completed']  = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['completed'] );
			}
			// Trash link.
			if ( isset( $views['trash'] ) ) {
				$args['post_status'] = 'trash';
				$trash_query         = new \WP_Query( $args );
				$count               = $trash_query->post_count;
				$views['trash']      = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">(' . $count . ')</span></a>', $views['trash'] );
				$views['trash']      = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['trash'] );
			}
		}
		return $views;
	}

	/**
	 * Show the current admin Order post query's program name before the list.
	 */
	public function program_name_before_order_list_view() {

		global $pagenow;

		if (
			'edit.php' === $pagenow
			&& 'wsorder' === get_query_var( 'post_type' )
			&& ! empty( get_query_var( 'program' ) )
		) {
			$program_id   = get_query_var( 'program' );
			$program_post = get_post( $program_id );
			if ( $program_post ) {
				echo wp_kses(
					'<div class="h1" style="font-size:23px;font-weight:400;line-height:29.9px;padding-top:16px;">Orders - ' . $program_post->post_title . '</div><style type="text/css">.wrap h1.wp-heading-inline{display:none;}</style>',
					array(
						'div'   => array(
							'class' => array(),
							'style' => array(),
						),
						'style' => array(
							'type' => array(),
						),
					)
				);
			}
		}

	}

	/**
	 * Render filters for Product post meta on the bulk posts page.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function add_admin_post_program_filter( $post_type ) {

		if ( 'wsorder' !== $post_type ){
		  return; // Do not filter this post.
		}
		$selected     = '';
		$request_attr = 'program';
		if ( isset( $_REQUEST[$request_attr] ) ) {
		  $selected = $_REQUEST[$request_attr];
		}
		// Get values to filter by.
		$args    = array(
			'post_type' => 'program',
			'fields'    => 'ids',
		);
		$results = get_posts( $args );
		// Build a custom dropdown list of values to filter by.
		echo '<select id="program" name="program">';
		echo '<option value="0">' . __( 'Show all Programs', 'cla-workstation-order' ) . ' </option>';
		foreach( $results as $program ) {
			if ( ! empty( $program ) ) {
				$select = ($program == $selected) ? ' selected="selected"':'';
				echo '<option value="' . $program . '"' . $select . '>' . get_the_title( $program ) . ' </option>';
			}
		}
		echo '</select>';

  }

	/**
	 * Modify the post query based on custom product filter dropdown selections.
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return WP_Query
	 */
	public function parse_query_program_filter( $query ){

		//modify the query only if it admin and main query.
		if( !( is_admin() && $query->is_main_query() ) ){
			return $query;
		}
		//we want to modify the query for the targeted custom post and filter option
		if( !('wsorder' === $query->query['post_type'] AND isset($_REQUEST['program']) ) ){
			return $query;
		}
		//for the default value of our filter no modification is required
		if(0 == $_REQUEST['program']){
			return $query;
		}
		//modify the query_vars.
		if ( $_REQUEST['program'] === $query->query_vars['name'] ) {
			$query->query_vars['name'] = '';
		}
		if ( empty( $query->query_vars['post_status'] ) ) {
			$query->query_vars['post_status'] = 'any';
		}
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		if ( ! empty( $_REQUEST['program'] ) ) {
			$meta_query[] = array(
				'key'	  => 'program',
				'value' => $_REQUEST['program'],
			);
		}
		$query->set( 'meta_query', $meta_query );
		return $query;

	}

	/**
	 * Add new business admin user selection to administrative field.
	 *
	 * @param string $value   The new value.
	 * @param int    $post_id The post ID.
	 *
	 * @return string
	 */
	public function approving_it_rep_changed( $value, $post_id ){

		$old_value = (string) get_post_meta( $post_id, 'it_rep_status_it_rep', true );

		if ( $value !== $old_value ) {

			$affiliated_it_reps = get_post_meta( $post_id, 'affiliated_it_reps', true );
			if ( empty( $affiliated_it_reps ) ) {
				$affiliated_it_reps = array();
			}
			$update_meta = false;

			// Remove the previous business admin from the affiliated business staff field.
			if ( ! empty( $old_value ) ) {
				$key = array_search( $old_value, $affiliated_it_reps, true );
				if ( false !== $key ) {
					$update_meta = true;
					unset( $affiliated_it_reps[ $key ] );
					$affiliated_it_reps = array_values( $affiliated_it_reps );
				}
			}

			// Add the new business admin to the affiliated business staff field.
			if ( ! empty( $value ) ) {
				// Validate new user we are about to copy into the affiliated business staff field.
				$new_val_key = array_search( $value, $affiliated_it_reps, true );
				$user_data   = get_userdata( intval( $value ) );
				$user_roles  = $user_data->roles;
				if ( in_array( 'wso_it_rep', $user_roles, true ) ) {
					$update_meta = true;
					// If an existing affiliated business user is being assigned as the primary,
					// move them to the beginning of the list.
					if ( false !== $new_val_key ) {
						unset( $affiliated_it_reps[ $new_val_key ] );
						$affiliated_it_reps = array_values( $affiliated_it_reps );
					}
					array_unshift( $affiliated_it_reps, $value );
				}
			}

			if ( $update_meta ) {
				update_post_meta( $post_id, 'affiliated_it_reps', $affiliated_it_reps );
			}
		}

		return $value;

	}

	/**
	 * Add new business admin user selection to administrative field.
	 *
	 * @param string $value   The new value.
	 * @param int    $post_id The post ID.
	 *
	 * @return string
	 */
	public function approving_business_admin_changed( $value, $post_id ){

		$old_value = (string) get_post_meta( $post_id, 'business_staff_status_business_staff', true );

		if ( $value !== $old_value ) {

			$affiliated_business_staff = get_post_meta( $post_id, 'affiliated_business_staff', true );
			if ( empty( $affiliated_business_staff ) ) {
				$affiliated_business_staff = array();
			}
			$update_meta = false;

			// Remove the previous business admin from the affiliated business staff field.
			if ( ! empty( $old_value ) ) {
				$key = array_search( $old_value, $affiliated_business_staff, true );
				if ( false !== $key ) {
					$update_meta = true;
					unset( $affiliated_business_staff[ $key ] );
					$affiliated_business_staff = array_values( $affiliated_business_staff );
				}
			}

			// Add the new business admin to the affiliated business staff field.
			if ( ! empty( $value ) ) {
				// Validate new user we are about to copy into the affiliated business staff field.
				$new_val_key = array_search( $value, $affiliated_business_staff, true );
				$user_data   = get_userdata( intval( $value ) );
				$user_roles  = $user_data->roles;
				if ( in_array( 'wso_business_admin', $user_roles, true ) ) {
					$update_meta = true;
					// If an existing affiliated business user is being assigned as the primary,
					// move them to the beginning of the list.
					if ( false !== $new_val_key ) {
						unset( $affiliated_business_staff[ $new_val_key ] );
						$affiliated_business_staff = array_values( $affiliated_business_staff );
					}
					array_unshift( $affiliated_business_staff, $value );
				}
			}

			if ( $update_meta ) {
				update_post_meta( $post_id, 'affiliated_business_staff', $affiliated_business_staff );
			}
		}

		return $value;

	}
}
