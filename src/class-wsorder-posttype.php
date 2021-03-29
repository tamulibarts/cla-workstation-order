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
		// Enqueue JavaScript file for admin
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_script' ), 11 );
		// Manipulate post title into a certain format.
		add_filter( 'default_title', array( $this, 'default_post_title' ), 11, 2 );
		// Redirect new order post creation to the order page.
		add_filter( 'admin_url', array( $this, 'replace_all_orders_url' ), 10, 3 );
		add_filter( 'admin_url', array( $this, 'replace_new_order_url' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'redirect_to_order_form' ) );
		// Hide the publish button from users other than admins.
		add_action( 'admin_body_class', array( $this, 'set_admin_body_class' ) );
		// Prevent users uninvolved with an order from editing it.
		add_action( 'admin_init', array( $this, 'redirect_uninvolved_users_from_editing' ) );
		// Generate a print button for the order.
		add_action( 'post_submitbox_misc_actions', array( $this, 'pdf_print_receipt' ) );
		// When a user other than the assigned user confirms an order, update the assigned user to that user.
		add_action( 'save_post', array( $this, 'update_affiliated_it_bus_user_confirmed' ) );

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

		// Create order posts from form.
		add_action( 'wp_ajax_make_order', array( $this, 'make_order' ) );

	}

	public function get_disallowed_product_and_bundle_ids(){

		$user                        = wp_get_current_user();
		$user_id                     = $user->get( 'ID' );
		$user_department_post        = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id     = $user_department_post->ID;
		$hidden_products             = get_field( 'hidden_products', $user_department_post_id );
		$hidden_bundles              = get_field( 'hidden_products', $user_department_post_id );
		$hidden_products_and_bundles = array();
		if ( is_array( $hidden_products ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_products );
		}
		if ( is_array( $hidden_bundles ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_bundles );
		}
		return $hidden_products_and_bundles;

	}

	public function make_order() {

		// Ensure nonce is valid.
		check_ajax_referer( 'make_order' );

		// Convert form data.
		$fields = explode( '&', $_POST['fields'] );
		$data = array();
		foreach ( $fields as $key => $value ) {
			preg_match('/([^=]*)=(.*)/', $value, $matches);
			$data_value = urldecode( $matches[2] );
			$data[$matches[1]] = $data_value;
		}

		// Validate data.
		$product_post_ids = $data['cla_product_ids'];
		$product_post_ids = explode( ',', $product_post_ids );
		// Get product IDs user is not allowed to buy.
		$disallowed_product_ids = $this->get_disallowed_product_and_bundle_ids();
		if ( ! empty( $disallowed_product_ids ) ) {
			foreach ( $product_post_ids as $id ) {
				if ( in_array( $id, $disallowed_product_ids ) ) {
					echo 'That product is no longer available.<br>';
					die();
				}
			}
		}

		// Get current user and user ID.
		$user    = wp_get_current_user();
		$user_id = $user->get( 'ID' );

		// Get current program meta.
		$current_program_post      = get_field( 'current_program', 'option' );
		$current_program_id        = $current_program_post->ID;
		$current_program_post_meta = get_post_meta( $current_program_id );
		$current_program_prefix    = $current_program_post_meta['prefix'][0];

		// Get new wsorder ID.
		$last_wsorder_id = $this->get_last_order_id( $current_program_id );
		$new_wsorder_id  = $last_wsorder_id + 1;

		// Insert post.
		$postarr = array(
			'post_author'    => $user_id,
			'post_status'    => 'action_required',
			'post_type'      => 'wsorder',
			'comment_status' => 'closed',
			'post_title'     => "{$current_program_prefix}-{$new_wsorder_id}",
			'post_content'   => '',
		);
		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {

			// Failed to generate a new post.
			error_log( $post_id );
			return 0;

		} else {

			// Get user's department.
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$user_department_post_id = $user_department_post->ID;

			// Get users assigned to active user's department for current program, as array.
			$dept_assigned_business_admin = $this->get_program_business_admin_user_id( $current_program_id, $user_department_post_id );

			/**
			 * Save ACF field values.
			 * https://www.advancedcustomfields.com/resources/update_field/
			 */

			// Save order ID.
			$value = $new_wsorder_id;
			update_field( 'order_id', $value, $post_id );

			// Save order author.
			$value = $user_id;
			update_field( 'order_author', $value, $post_id );

			// Save order author.
			$value = $user_department_post_id;
			update_field( 'author_department', $value, $post_id );

			// Save order affiliated it reps.
			// Save order affiliated business reps.
			$program_department_fields = $this->get_program_department_fields( $user_department_post_id, $current_program_id );
			$value = $program_department_fields['it_reps'];
			update_field( 'affiliated_it_reps', $value, $post_id );
			$value = $program_department_fields['business_admins'];
			update_field( 'affiliated_business_staff', $value, $post_id );

			// Save program.
			$value = $current_program_id;
			update_field( 'program', $value, $post_id );

			// Save building location.
			$value = $data['cla_building_name'];
			update_field( 'building', $value, $post_id );

			// Save office location.
			$value = $data['cla_room_number'];
			update_field( 'office_location', $value, $post_id );

			// Save contribution amount.
			$value = $data['cla_contribution_amount'];
			update_field( 'contribution_amount', $value, $post_id );

			// Save account number.
			$value = $data['cla_account_number'];
			update_field( 'contribution_account', $value, $post_id );

			// Save order comment.
			$value = $data['cla_order_comments'];
			update_field( 'order_comment', $value, $post_id );

			// Save current asset.
			$value = $data['cla_current_asset_number'];
			update_field( 'current_asset', $value, $post_id );

			// Save no computer yet field.
			if ( array_key_exists( 'cla_no_computer_yet', $data ) ) {
				$value = $data['cla_no_computer_yet'];
				update_field( 'i_dont_have_a_computer_yet', $value, $post_id );
			}

			// Save department IT Rep.
			// $value = $dept_assigned_users['it_rep'];
			$value = $data['cla_it_rep_id'];
			update_field( 'it_rep_status', array( 'it_rep' => $value ), $post_id );

			// Save department Business Admin.
			$value = $dept_assigned_business_admin === 0 ? '' : $dept_assigned_business_admin;
			$value = 869; // zwatkins2
			update_field( 'business_staff_status', array( 'business_staff' => $value ), $post_id );

			// Let WordPress handle the upload.
			// Remember, 'cla_quote_0_file' is the name of our file input in our form above.
			// Here post_id is 0 because we are not going to attach the media to any post.
			$quote_count  = $data['cla_quote_count'];
			if ( $quote_count > 0 ) {

				/**
				 * Handle quote fields and file uploads.
				 */
				// These files need to be included as dependencies when on the front end.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );

				$quote_fields = array();
				for ($i=0; $i < $quote_count; $i++) {

					$quote_fields[$i] = array(
						'name'        => $data["cla_quote_{$i}_name"],
						'price'       => $data["cla_quote_{$i}_price"],
						'description' => $data["cla_quote_{$i}_description"],
					);

					// Handle uploading quote file.
					$attachment_id = media_handle_upload( "cla_quote_{$i}_file", 0 );
					if ( is_wp_error( $attachment_id ) ) {
						// There was an error uploading the image.
						error_log($attachment_id->get_error_message());
					} else {
						// Attach file.
						$quote_fields[$i]['file'] = $attachment_id;
					}

				}
				update_field( 'quotes', $quote_fields, $post_id );

			}

			/**
			 * Save product information.
			 */
			$product_post_ids = $data['cla_product_ids'];
			$product_post_ids = explode( ',', $product_post_ids );
			$product_count    = count( $product_post_ids );
			$product_subtotal = 0;
			if ( $product_count > 0 ) {
				$product_fields = array();

				// Break down bundles into individual product post ids.
				$bundle_product_collection = array();
				$actual_product_collection = array();
				foreach ( $product_post_ids as $product_post_id ) {
					$product_post_type = get_post_type( $product_post_id );
					if ( 'bundle' === $product_post_type ) {
						// Get products in bundle as post IDs.
						$bundle_products           = get_field( 'products', $product_post_id );
						$bundle_product_collection = array_merge( $bundle_product_collection, $bundle_products );
					} else {
						$actual_product_collection[] = $product_post_id;
					}
				}
				$product_post_ids = array_merge( $bundle_product_collection, $actual_product_collection );

				// Convert products into ACF fields.
				for ($i=0; $i < $product_count; $i++) {
					$product_post_id = $product_post_ids[$i];
					// Add to subtotal.
					$product_subtotal = $product_subtotal + get_field( 'price', $product_post_id );
					$product_fields[$i] = array(
						'sku'   => get_field( 'sku', $product_post_id ),
						'item'  => get_the_title( $product_post_id ),
						'price' => get_field( 'price', $product_post_id ),
					);
				}
				update_field( 'order_items', $product_fields, $post_id );

			}

			// Save product subtotal.
			$value = $product_subtotal;
			update_field( 'products_subtotal', $value, $post_id );

		}

		$this->send_confirmation_email( "{$current_program_prefix}-{$new_wsorder_id}", $user, $data['cla_it_rep_id'], $post_id, $data );

		die();

	}

	/**
	 * Get the user ID of the designated business admin within a program's department.
	 */
	private function get_program_business_admin_user_id( $program_id, $user_department_post_id ) {

		// Get users assigned to active user's department for current program, as array.
		$program_meta_keys_departments = array(
			'assign_political_science_department_post_id',
			'assign_sociology_department_post_id',
			'assign_philosophy_humanities_department_post_id',
			'assign_performance_studies_department_post_id',
			'assign_international_studies_department_post_id',
			'assign_history_department_post_id',
			'assign_hispanic_studies_department_post_id',
			'assign_english_department_post_id',
			'assign_economics_department_post_id',
			'assign_communication_department_post_id',
			'assign_anthropology_department_post_id',
			'assign_psychology_department_post_id',
			'assign_dean_department_post_id',
		);
		$current_program_post_meta     = get_post_meta( $program_id );
		$value                         = 0;

		foreach ( $program_meta_keys_departments as $meta_key ) {
			$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
			if ( $user_department_post_id === $assigned_dept ) {
				$base_key   = preg_replace( '/_department_post_id$/', '', $meta_key );
				$meta_value = $current_program_post_meta[ "{$base_key}_business_admins" ];
				if ( gettype( $meta_value ) === 'boolean' ) {
					$value = 0;
				} else {
					$meta_value                   = unserialize( $meta_value[0] );
					$dept_assigned_business_admin = $meta_value[0];
					$value                        = $dept_assigned_business_admin[0];
				}
				break;
			}
		}

		return $value;

	}

	/**
	 * Get the department fields within a program based from the department associated with the given user ID.
	 * Todo
	 */
	private function get_program_department_fields( $department_id, $program_id ) {

		// Get users assigned to active user's department for current program, as array.
		$program_meta_keys_departments = array(
			'assign_political_science_department_post_id',
			'assign_sociology_department_post_id',
			'assign_philosophy_humanities_department_post_id',
			'assign_performance_studies_department_post_id',
			'assign_international_studies_department_post_id',
			'assign_history_department_post_id',
			'assign_hispanic_studies_department_post_id',
			'assign_english_department_post_id',
			'assign_economics_department_post_id',
			'assign_communication_department_post_id',
			'assign_anthropology_department_post_id',
			'assign_psychology_department_post_id',
			'assign_dean_department_post_id',
		);
		$current_program_post_meta     = get_post_meta( $program_id );
		$value                         = array();

		foreach ( $program_meta_keys_departments as $meta_key ) {
			$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
			if ( $department_id === $assigned_dept ) {
				$base_key        = preg_replace( '/_department_post_id$/', '', $meta_key );
				$it_reps         = $current_program_post_meta[ "{$base_key}_it_reps" ];
				$value['business_admins'] = unserialize( $current_program_post_meta[ "{$base_key}_business_admins" ][0] );
				$value['it_reps']         = unserialize( $current_program_post_meta[ "{$base_key}_it_reps" ][0] );
				break;
			}
		}

		return $value;

	}

	/**
	 * Send the order form submission confirmation email to the end user and the IT rep.
	 *
	 * @param string $order_name   The order post's name.
	 * @param object $current_user The current WP_User object.
	 * @param int    $it_rep_id    The user ID of the IT rep.
	 * @param int    $post_id      The post ID of the new wsorder post.
	 * @param array  $data         The submission data.
	 */
	private function send_confirmation_email( $order_name, $current_user, $it_rep_id, $post_id, $data ) {

		// Get user information.
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;
		$it_reps            = get_field( 'affiliated_it_reps', $post_id );
		$it_rep_emails      = array();
		foreach ($it_reps as $rep_user_id ) {
			$user_data       = get_userdata( $rep_user_id );
			$it_rep_emails[] = $user_data->user_email;
		}
		$it_rep_emails = implode(',', $it_rep_emails);

		// Email settings.
		$headers = array('Content-Type: text/html; charset=UTF-8');

		// Get current program meta.
		$current_program_post = get_field( 'current_program', 'option' );
		$current_program_id   = $current_program_post->ID;
		$program_name         = get_the_title( $current_program_id );

		// Get order information.
		$order_url = admin_url() . "post.php?post={$post_id}&action=edit";

		// Email end user.
		$message = "<p>Howdy,</p>
<p>Liberal Arts IT has received your order.</p>

<p>Your {$program_name} order will be reviewed to ensure all necessary information and funding is in place.</p>
<p>
  Following review, your workstation request will be combined with others from your department to create a consolidated {$program_name} purchase. Consolidated orders are placed to maximize efficiency. Your order will be processed and received by IT Logistics in 4-6 weeks, depending on how early in the order cycle you make your selection. Once received, your workstation will be released to departmental IT staff who will then image your workstation, install software and prepare the device for delivery. These final steps generally take one to two days.
</p>
<p>You may view your order online at any time using this link: {$order_url}.</p>

<p>
  Have a great day!
  <em>-Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";
		wp_mail( $current_user_email, 'Workstation Order Received', $message, $headers );

		// Email IT Rep.
		$admin_url = admin_url() . "post.php?post={$post_id}&action=edit";
		$message = "<p>
  <strong>There is a new {$program_name} order that requires your attention.</strong>
</p>
<p>
  Please review this order carefully for any errors or omissions, then confirm it to pass along in the ordering workflow, or return it to the customer with your feedback and ask that they correct the order.
</p>
<p>
  You can view the order at this link: {$order_url}.
</p>
<p>
  Have a great day!
  <em>-Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";
		wp_mail( $it_rep_emails, 'Workstation Order Received', $message, $headers );

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
	 * Set the body class with the current post status and user roles so that CSS can hide or show appropriate features.
	 *
	 * @return void
	 */
	public function set_admin_body_class ( $classes ) {
		global $pagenow;

		if ( $pagenow === 'post.php' ) {
			$post_id      = get_the_ID();
			$post_status  = get_post_status( $post_id );
			$user         = wp_get_current_user();
			$roles        = ( array ) $user->roles;
			$classes     .= " $post_status " . implode(' ', $roles);
		}

		return $classes;
	}

	/**
	 * Redirect visitors trying to see all orders to the current program year's orders.
	 */
	public function redirect_to_current_program_orders() {

		global $pagenow;
		if (
			'edit.php' === $pagenow
			&& isset($_GET['post_type'])
			&& $_GET['post_type'] === 'wsorder'
			&& ! isset( $_GET['program'] )
		) {
			$current_program_id = get_site_option( 'options_current_program' );
			$url = get_admin_url() . "edit.php?".$_SERVER['QUERY_STRING']."&program={$current_program_id}";
			wp_redirect( $url ); exit;
		}

	}

	/**
	 * Determine if business approval is required.
	 *
	 * @var int $post_id The wsorder post ID to check.
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
			'dashicons-portfolio',
			array( 'title' ),
			array(
				'capabilities' => array(
	        'edit_post'                 => 'edit_wsorder',
	        'read_post'                 => 'read_wsorder',
	        'delete_post'               => 'delete_wsorder',
	        'create_posts'              => 'create_wsorders',
	        'delete_posts'              => 'delete_wsorders',
	        'delete_others_posts'       => 'delete_others_wsorders',
	        'delete_private_posts'      => 'delete_private_wsorders',
	        'delete_published_posts'    => 'delete_published_wsorders',
	        'edit_posts'                => 'edit_wsorders',
	        'edit_others_posts'         => 'edit_others_wsorders',
	        'edit_private_posts'        => 'edit_private_wsorders',
	        'edit_published_posts'      => 'edit_published_wsorders',
	        'publish_posts'             => 'publish_wsorders',
	        'read_private_posts'        => 'read_private_wsorders'
				),
				'map_meta_cap' => true,
				'publicly_queryable' => false,
			)
		);

		register_post_status( 'action_required', array(
			'label'                     => _x( 'Action Required', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Action Required <span class="count">(%s)</span>', 'Action Required <span class="count">(%s)</span>' )
		) );

		register_post_status( 'returned', array(
			'label'                     => _x( 'Returned', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>' )
		) );

		register_post_status( 'completed', array(
			'label'                     => _x( 'Completed', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' )
		) );

		register_post_status( 'awaiting_another', array(
			'label'                     => _x( 'Awaiting Another', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Awaiting Another <span class="count">(%s)</span>', 'Awaiting Another <span class="count">(%s)</span>' )
		) );

	}

	public function add_program_url_var( $vars ) {
		$vars[] = 'program';
		return $vars;
	}

	/**
	 * Add JS for edit wsorder pages in admin.
	 *
	 * @return void
	 */
	public function admin_script() {
		global $post_type;
		if( 'wsorder' == $post_type ) {
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
		if( $post->post_type != 'wsorder' ) {
			return false;
		}

		$status = '';
		switch ( $post->post_status ) {
			case 'action_required':
				$status = "jQuery( '#post-status-display' ).text( 'Action Required' ); jQuery(
						'select[name=\"post_status\"]' ).val('action_required')";
				break;

			case 'returned':
				$status = "jQuery( '#post-status-display' ).text( 'Returned' ); jQuery(
						'select[name=\"post_status\"]' ).val('returned')";
				break;

			case 'completed':
				$status = "jQuery( '#post-status-display' ).text( 'Completed' ); jQuery(
						'select[name=\"post_status\"]' ).val('completed')";
				break;

			case 'awaiting_another':
				$status = "jQuery( '#post-status-display' ).text( 'Awaiting Another' ); jQuery(
						'select[name=\"post_status\"]' ).val('awaiting_another')";
				break;

			case 'publish':
				$status = "jQuery( '#post-status-display' ).text( 'Published' ); jQuery(
						'select[name=\"post_status\"]' ).val('publish')";
				break;

			default:
				break;
		}

		$subscriber_disabled = '';
		if (
			! current_user_can( 'wso_logistics' )
			&& ! current_user_can( 'wso_it_rep' )
			&& ! current_user_can( 'wso_business_admin' )
			&& ! current_user_can( 'wso_admin' )
		) {
			$subscriber_disabled = ' disabled="disabled"';
		}

		$it_rep_disabled = '';
		if (
			! current_user_can( 'wso_logistics' )
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
			&& ! current_user_can( 'wso_admin' )
		) {
			$non_logistics_disabled = ' disabled="disabled"';
		}
		echo "<script>
			jQuery(document).ready( function() {
				jQuery( 'select[name=\"post_status\"]' ).html( '<option value=\"action_required\">Action Required</option><option value=\"returned\"$subscriber_disabled>Returned</option><option value=\"completed\"{$it_rep_disabled}{$subscriber_disabled}>Completed</option><option value=\"awaiting_another\"$subscriber_disabled>Awaiting Another</option><option value=\"publish\"$non_logistics_disabled>Publish</option>' );
				".$status."
			});
		</script>";

	}

	public function display_status_state( $states ) {

		global $post;
		$arg = get_query_var( 'post_status' );
		switch ( $post->post_status ) {
			case 'action_required':
				if ( $arg != $post->post_status ) {
					return array( 'Action Required' );
				}
				break;

			case 'returned':
				if ( $arg != $post->post_status ) {
					return array( 'Returned' );
				}
				break;

			case 'completed':
				if ( $arg != $post->post_status ) {
					return array( 'Completed' );
				}
				break;

			case 'awaiting_another':
				if ( $arg != $post->post_status ) {
					return array( 'Awaiting Another' );
				}
				break;

			default:
				return $states;
				break;
		}

	}

	private function get_last_order_id( $program_post_id ) {

		$args              = array(
			'post_type'      => 'wsorder',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'order_id',
					'compare' => 'EXISTS'
				),
				array(
					'key'     => 'program',
					'compare' => '=',
					'value'   => $program_post_id,
				)
			),
		);
		$the_query = new \WP_Query( $args );
		$posts = $the_query->posts;
		if ( ! empty( $posts ) ) {
			$last_wsorder_id = (int) get_post_meta( $posts[0], 'order_id', true );
		} else {
			$last_wsorder_id = 0;
		}

		return $last_wsorder_id;

	}

	/**
	 * Make post title using current program ID and incremented order ID from last order.
	 */
	public function default_post_title( $post_title, $post ) {

		if ('wsorder' === $post->post_type) {

			// Get current program meta.
			$current_program_post      = get_field( 'current_program', 'option' );
			if ( ! empty( $current_program_post ) ) {
				$current_program_id        = $current_program_post->ID;
				$current_program_post_meta = get_post_meta( $current_program_id );
				$current_program_prefix    = $current_program_post_meta['prefix'][0];

				// Get last order ID.
				$last_wsorder_id = $this->get_last_order_id( $current_program_id );
				$wsorder_id = $last_wsorder_id + 1;

				// Push order ID value to post details.
				$post_title = "{$current_program_prefix}-{$wsorder_id}";
			}
		}

		return $post_title;

	}

	public function add_list_view_columns( $columns ){

	  $status = array('status' => '');
	  $columns = array_merge( $status, $columns );
	  unset($columns['date']);

	  $columns['author']           = 'Ordered By';
	  $columns['ordered_at']       = 'Ordered At';
	  $columns['amount']           = 'Amount';
	  $columns['it_status']        = 'IT';
	  $columns['business_status']  = 'Business';
	  $columns['logistics_status'] = 'Logistics';
    return $columns;

	}

	/**
	 * Add columns to order post list view.
	 */
	public function output_list_view_columns( $column_name, $post_id ) {

		if ( 'status' === $column_name ) {
			$status = get_post_status( $post_id );
			echo "<div class=\"status-color-key {$status}\"></div>";
		} elseif( 'amount' === $column_name ) {
      $number = (float) get_post_meta( $post_id, 'products_subtotal', true );
      if ( class_exists('NumberFormatter') ) {
				$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
				echo $formatter->formatCurrency($number, 'USD');
			} else {
				echo '$' . number_format($number, 2,'.', ',');
			}
    } elseif ( 'it_status' === $column_name ) {
    	$status = get_field( 'it_rep_status', $post_id );
    	if ( empty( $status['confirmed'] ) ) {
    		echo '<span class="approval not-confirmed">Not yet confirmed</span>';
    	} else {
    		echo '<span class="approval confirmed">Confirmed</span><br>';
    		echo $status['it_rep']['display_name'];
    	}
    } elseif ( 'business_status' === $column_name ) {
    	// Determine status message.
    	$business_staff_id = get_post_meta( $post_id, 'business_staff_status_business_staff', true );
    	if ( ! empty( $business_staff_id ) ) {
	    	$status = get_field( 'business_staff_status', $post_id );
	    	if ( empty( $status['confirmed'] ) ) {
	    		echo '<span class="approval not-confirmed">Not yet confirmed</span>';
	    	} else {
	    		echo '<span class="approval confirmed">Confirmed</span><br>';
	    		echo $status['business_staff']['display_name'];
	    	}
    	} else {
    		echo '<span class="approval">Not required</span>';
    	}
    } elseif ( 'logistics_status' === $column_name ) {
    	$status = get_field( 'it_logistics_status', $post_id );
    	if ( empty( $status['confirmed'] ) ) {
    		echo '<span class="approval not-confirmed">Not yet confirmed</span>';
    	} else {
    		echo '<span class="approval confirmed">Confirmed</span> ';
	    	if ( empty( $status['ordered'] ) ) {
	    		echo '<span class="approval not-fully-ordered">Not fully ordered</span>';
	    	} else {
	    		echo '<span class="approval ordered">Ordered</span>';
	    	}
    	}
    } elseif ( 'ordered_at' === $column_name ) {
    	$ordered = get_post_meta( $post_id, 'it_logistics_status_ordered_at', true );
    	if ( ! empty( $ordered ) ) {
    		echo date( 'F j, Y \a\t g:i a', strtotime($ordered));
    	}
    }
	}

	/**
	 * Modify the Order post type query in admin so that people not involved with the order
	 * cannot see the post.
	 *
	 * @param object $query The query object.
	 *
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		if ( 'wsorder' === $query->get('post_type') ) {
			if ( ! is_admin() ) {
				return;
			}
			// Fix bugs which should be addressed if I can ever find a solution.
			$query->query_vars['name'] = '';
			if ( empty( $query->query_vars['post_status'] ) ) {
				$query->query_vars['post_status'] = 'any';
			}
			// Overwrite existing meta query.
			$meta_query = $query->get('meta_query');
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}
			// Apply "program" URL parameter.
			$program = get_query_var('program');
			if ( ! empty( $program ) ) {
				$meta_query[] = array(
					'key'   => 'program',
					'value' => $program,
				);
			}
			$query->set('meta_query', $meta_query);
			// Allow admins and logistics to see all orders.
			if (
				current_user_can( 'administrator' )
				|| current_user_can( 'wso_admin' )
				|| current_user_can( 'wso_logistics' )
			) {
				return;
			}
			// Everyone else must be restricted.
			$user              = wp_get_current_user();
			$current_user_id   = $user->ID;
			$meta_query[]      = array(
				'relation' => 'OR',
				array(
					'key'   => 'affiliated_it_reps',
					'value' => '"' . $current_user_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'   => 'affiliated_business_staff',
					'value' => '"' . $current_user_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'   => 'order_author',
					'value' => $current_user_id,
				)
			);
			$query->set('meta_query', $meta_query);
		}
	}

	/**
	 * Redirect visits from new order edit page to public order form.
	 *
	 * @return void
	 */
	public function redirect_to_order_form() {

	  global $pagenow;

	  if (
	  	! current_user_can( 'administrator' )
	  	&& 'post-new.php' === $pagenow
	  	&& isset($_GET['post_type'])
	  	&& $_GET['post_type'] === 'wsorder'
	  ) {

	  	$blog_id = get_current_blog_id();
	  	$url = get_site_url( $blog_id, 'order-form/' );
	    wp_redirect( $url ); exit;

	  }

	}

	/**
	 * Filter admin_url to rewrite new order URLs so users must use the public order form.
	 *
	 * @param string $url     Current URL.
	 * @param string $path    Current path.
	 * @param int    $blog_id The current site ID
	 */
	public function replace_new_order_url ( $url, $path, $blog_id ) {

		if ( 'post-new.php?post_type=wsorder' === $path ) {
			$url = get_site_url( $blog_id, 'order-form/' );
		}

  	return $url;

	}

	/**
	 * Filter admin_url to rewrite all order URLs so users see the current program year.
	 *
	 * @param string $url     Current URL.
	 * @param string $path    Current path.
	 * @param int    $blog_id The current site ID
	 */
	public function replace_all_orders_url ( $url, $path, $blog_id ) {

		if ( 'edit.php?post_type=wsorder' === $path ) {
			$current_program_id = get_site_option( 'options_current_program' );
			$url = get_site_url( $blog_id, $path . '&program=' . $current_program_id );
		}

  	return $url;

	}

	/**
	 * Prevent users not involved with a work order from accessing the edit page.
	 *
	 * @return void
	 */
	public function redirect_uninvolved_users_from_editing() {
		// Prevent users who aren't on a work order from viewing/editing it.
		global $pagenow;
		if( isset($_GET['post']) ) {
			$user = wp_get_current_user();
			$current_user_id = $user->ID;
			$post_id = absint($_GET['post']); // Always sanitize
			$author_id = (int) get_post_field( 'post_author', $post_id );
			$it_rep_ids = (int) get_field( 'affiliated_it_reps', $post_id );
			$business_admin_ids = get_field( 'affiliated_business_staff', $post_id );
			if (
				'post.php' === $pagenow
				&& isset($_GET['post'])
				&& 'wsorder' === get_post_type( $_GET['post'] )
				&& ! current_user_can( 'administrator' )
				&& ! current_user_can( 'wso_admin' )
				&& ! current_user_can( 'wso_logistics' ) // Not a logistics user
				&& $current_user_id !== $author_id // Not the author
				&& ! in_array( $current_user_id, $it_rep_ids ) // Not the IT rep
				&& ! in_array( $current_user_id, $business_admin_ids ) // Not the business admin
			) {
				// User isn't involved with this order and should be redirected away.
				$current_program_id = get_site_option( 'options_current_program' );
				$location = admin_url() . 'edit.php?post_type=wsorder&program=' . $current_program_id;
				wp_safe_redirect($location);
				exit();
			}
		}
	}

	/**
	 * Generate PDF print link.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function pdf_print_receipt ( $post ) {
    if ( ! $post
			|| 'publish' !== $post->post_status
			|| 'wsorder' !== $post->post_type
		) {
        return;
    }
    $bare_url     = CLA_WORKSTATION_ORDER_DIR_URL . 'order-receipt.php?postid='.$post->ID;
    $complete_url = wp_nonce_url( $bare_url, 'auth-post_'.$post->ID, 'token' );
    $html         = '<div id="major-publishing-actions" style="overflow:hidden">';
    $html         .= '<div id="publishing-action">';
    $html         .= '<a class="button-primary" href="'.$complete_url.'" id="printpdf" target="_blank">Save as PDF</a>';
    $html         .= '</div>';
    $html         .= '</div>';
    echo $html;
	}

	/**
	 * When a user other than the assigned user confirms an order, update the assigned user to that user.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function update_affiliated_it_bus_user_confirmed( $post_id ) {

		// IT Rep confirmed by someone other than the designated IT rep.
		if (
			isset( $_POST['acf']['field_5fff6b46a22af'] )
			&& isset( $_POST['acf']['field_5fff6b46a22af']['field_5fff6b71a22b0'] )
		) {
			$old_post_it_confirm = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
			$new_post_it_confirm = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff6b71a22b0'];

			if (
				0 === $old_post_it_confirm
				&& 1 === $new_post_it_confirm
			) {
				$current_user    = wp_get_current_user();
				$current_user_id = (int) $current_user->ID;
				$it_rep_user_id = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff703a5289f'];
				if ( $current_user_id != $it_rep_user_id ) {
					update_post_meta( $post_id, 'it_rep_status_it_rep', $current_user_id );
				}
			}
		}

		// Business Staff confirmed by someone other than the designated business staff.
		if (
			isset( $_POST['acf']['field_5fff6ec0e2f7e'] )
			&& isset( $_POST['acf']['field_5fff6ec0e2f7e']['field_5fff6ec0e4385'] )
		) {
			$old_post_bus_confirm = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );
			$new_post_bus_confirm = (int) $_POST['acf']['field_5fff6ec0e2f7e']['field_5fff6ec0e4385'];

			if (
				0 === $old_post_bus_confirm
				&& 1 === $new_post_bus_confirm
			) {
				$current_user    = wp_get_current_user();
				$current_user_id = (int) $current_user->ID;
				$business_user_id = (int) $_POST['acf']['field_5fff6ec0e2f7e']['field_5fff70b84ffe4'];
				if ( $current_user_id != $business_user_id ) {
					update_post_meta( $post_id, 'business_staff_status_business_staff', $current_user_id );
				}
			}
		}
	}

	/**
	 *
	 */
	public function change_order_list_status_link_counts_and_urls( $views ) {

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wsorder' ) {

			$program_id      = $_GET['program'];
			$user            = wp_get_current_user();
			$current_user_id = $user->ID;
			$args            = array(
				'post_type'  => 'wsorder',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'program',
						'value' => $program_id,
					)
				),
				'fields' => 'ids'
			);
			if ( ! current_user_can( 'wso_admin' ) && ! current_user_can( 'wso_logistics' ) ) {
				$args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'affiliated_it_reps',
						'value'   => '"' . $current_user_id . '"',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'affiliated_business_staff',
						'value'   => '"' . $current_user_id . '"',
						'compare' => 'LIKE',
					),
					array(
						'key'   => 'order_author',
						'value' => $current_user_id,
					)
				);
			}
			// All link.
			$query = new \WP_Query( $args );
			$count = $query->post_count;
			$views['all'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['all'] );
			$views['all'] = str_replace( 'edit.php?post_type=wsorder', "edit.php?post_type=wsorder&program={$program_id}", $views['all'] );
			// Mine link.
			if ( isset( $views['mine'] ) ) {
				$mine_args           = $args;
				unset( $mine_args['meta_query'] );
				$mine_args['author'] = $current_user_id;
				$mine_query          = new \WP_Query( $mine_args );
				$count               = $mine_query->post_count;
				$views['mine']       = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['mine'] );
				$views['mine']       = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['mine'] );
			}
			// Publish link.
			if ( isset( $views['publish'] ) ) {
				$pub_args                = $args;
				$pub_args['post_status'] = 'publish';
				$pub_query               = new \WP_Query( $pub_args );
				$count                   = $pub_query->post_count;
				$views['publish']        = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['publish'] );
				$views['publish']        = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['publish'] );
			}
			// Action Required link.
			if ( isset( $views['action_required'] ) ) {
				$args['post_status']      = 'action_required';
				$ar_query                 = new \WP_Query( $args );
				$count                    = $ar_query->post_count;
				$views['action_required'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['action_required'] );
				$views['action_required'] = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['action_required'] );
			}
			// Returned link.
			if ( isset( $views['returned'] ) ) {
				$args['post_status'] = 'returned';
				$ar_query            = new \WP_Query( $args );
				$count               = $ar_query->post_count;
				$views['returned']   = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['returned'] );
				$views['returned']   = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['returned'] );
			}
			// Completed link.
			if ( isset( $views['completed'] ) ) {
				$args['post_status'] = 'completed';
				$ar_query            = new \WP_Query( $args );
				$count               = $ar_query->post_count;
				$views['completed']  = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['completed'] );
				$views['completed']  = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['completed'] );
			}
			// Awaiting Another link.
			if ( isset( $views['awaiting_another'] ) ) {
				$args['post_status']       = 'awaiting_another';
				$ar_query                  = new \WP_Query( $args );
				$count                     = $ar_query->post_count;
				$views['awaiting_another'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['awaiting_another'] );
				$views['awaiting_another'] = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['awaiting_another'] );
			}
			// Trash link.
			if ( isset( $views['trash'] ) ) {
				$args['post_status'] = 'trash';
				$trash_query         = new \WP_Query( $args );
				$count               = $trash_query->post_count;
				$views['trash'] = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['trash'] );
				$views['trash'] = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['trash'] );
			}
		}
		return $views;
	}

	/**
	 * Show the current admin Order post query's program name before the list.
	 */
	public function program_name_before_order_list_view () {

		global $pagenow;

		if ( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wsorder' ) {
			$program_id   = $_GET['program'];
			$program_post = get_post( $program_id );
			echo '<div class="h1" style="font-size:23px;font-weight:400;line-height:29.9px;padding-top:16px;">Orders - '.$program_post->post_title.'</div><style type="text/css">.wrap h1.wp-heading-inline{display:none;}</style>';
		}

	}
}
