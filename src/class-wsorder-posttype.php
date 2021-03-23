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
		// Prevent users from seeing posts they aren't involved with.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		// Allow programs to link to a list of associated orders in admin.
		add_filter( 'parse_query', array( $this, 'admin_list_posts_filter' ) );
		// Change post type counts and URLs based on currently viewed program.
		add_filter( 'views_edit-wsorder', array( $this, 'change_order_list_status_link_counts_and_urls' ) );
		// Add the currently viewed program name before the list of posts.
		add_action( 'in_admin_header', array( $this, 'program_name_before_order_list_view' ) );

		// Register email action hooks/filters
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-wsorder-posttype-emails.php';
		new \CLA_Workstation_Order\WSOrder_PostType_Emails();

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
	 * Filter orders based on program URL variable.
	 *
	 * @param object $query The query object.
	 *
	 * @return void
	 */
	public function admin_list_posts_filter( $query ){
		global $pagenow;
		$type = 'post';
		if (isset($_GET['post_type'])) {
	    $type = $_GET['post_type'];
		}
		// Modify wsorder edit page queries.
		if ( 'wsorder' === $type && is_admin() && 'edit.php' === $pagenow) {

			// Allow wsorders to be sorted by program ID if "program" URL parameter is present.
	    $meta_query = array(); // Declare meta query to fill afterwards
	    if (isset($_GET['program']) && $_GET['program'] != '') {
        // first meta key/value
        $meta_query = array (
          'key'   => 'program',
          'value' => $_GET['program']
        );
		    $query->query_vars['meta_query'][] = $meta_query; // add meta queries to $query
		    $query->query_vars['name'] = '';
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
		if ( ! is_admin() ) {
			return;
		}
		// Allow admins and logistics to see all orders.
		if (
			current_user_can( 'administrator' )
			|| current_user_can( 'wso_admin' )
			|| current_user_can( 'wso_logistics' )
		) {
			return;
		}
		// Everyone else must be restricted.
		if ( 'wsorder' === $query->get('post_type') ) {
			$user              = wp_get_current_user();
			$current_user_id   = $user->ID;
			$meta_query        = array(
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
			unset( $mine_args['meta_query'] );
			$mine_args           = $args;
			$mine_args['author'] = $current_user_id;
			$mine_query          = new \WP_Query( $mine_args );
			$count               = $mine_query->post_count;
			$views['mine']       = preg_replace( '/<span class="count">\(\d+\)<\/span>/', '<span class="count">('.$count.')</span></a>', $views['mine'] );
			$views['mine']       = str_replace( 'post_type=wsorder', "post_type=wsorder&program={$program_id}", $views['mine'] );
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
		}
		return $views;
	}

	/**
	 * Show the current admin Order post query's program name before the list.
	 */
	public function program_name_before_order_list_view () {

		$program_id   = $_GET['program'];
		$program_post = get_post( $program_id );
		echo '<div class="h1" style="font-size:23px;font-weight:400;line-height:29.9px;padding-top:16px;">Orders - '.$program_post->post_title.'</div><style type="text/css">.wrap h1.wp-heading-inline{display:none;}</style>';

	}
}
