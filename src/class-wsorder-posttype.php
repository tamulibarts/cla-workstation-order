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
		add_filter( 'query_vars', function($vars){
			$vars[] = 'program';
			return $vars;
		});
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
		// Add columns to dashboard post list screen.
		add_filter( 'manage_wsorder_posts_columns', array( $this, 'add_list_view_columns' ) );
		add_action( 'manage_wsorder_posts_custom_column', array( $this, 'output_list_view_columns' ), 10, 2 );
		// Manipulate post title into a certain format.
		add_filter( 'default_title', array( $this, 'default_post_title' ), 11, 2 );
		// Allow programs to link to a list of associated orders in admin.
		add_filter( 'parse_query', array( $this, 'admin_list_posts_filter' ) );
		// Prevent users from seeing posts they aren't involved with.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		// Redirect new order post creation to the order page.
		add_filter( 'admin_url', array( $this, 'replace_all_orders_url' ), 10, 3 );
		add_filter( 'admin_url', array( $this, 'replace_new_order_url' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'redirect_to_order_form' ) );
		// Hide the publish button from users other than admins.
		add_action( 'admin_body_class', array( $this, 'set_admin_body_class' ) );
		// Prevent users uninvolved with an order from editing it.
		add_action( 'admin_init', array( $this, 'redirect_uninvolved_users_from_editing' ) );

		add_action( 'post_submitbox_misc_actions', function( $post ){
	    if ( ! $post
	         || 'publish' !== $post->post_status
	         || 'wsorder' !== $post->post_type ) {
	        return;
	    }
	    $meta = get_post_meta( $post->ID );
	    foreach ($meta as $key => $value) {
	    	if ( strpos($key, '_') === 0 ) {
	    		unset($meta[$key]);
	    	} elseif ( count( $value ) === 1 ) {
	    		$meta[$key] = $value[0];
	    	}
	    }
	    $meta['logo'] = CLA_WORKSTATION_ORDER_DIR_URL . 'images/logo-support-center.png';
	    // Extra basic order data.
	    $publish_date = strtotime( $post->post_date );
	    $meta['publish_date_formatted'] = date('M j, Y \a\t g:i a', $publish_date);
	    $meta['post_title'] = $post->post_title;
	    $meta['now'] = date( 'M j, Y \a\t g:i a' );
	    $meta['program_name'] = get_the_title( $meta['program'] );
	    $meta['program_fiscal_year'] = get_post_meta( $meta['program'], 'fiscal_year', true );
	    // Extra author data.
	    $author = get_userdata( $post->post_author );
	    $meta['author'] = $author->data->display_name;
	    $meta['first_name'] = get_user_meta( $post->post_author, 'first_name', true );
	    $meta['last_name'] = get_user_meta( $post->post_author, 'last_name', true );
	    $meta['author_email'] = $author->data->user_email;
	    $meta['author_department'] = get_the_title( $meta['author_department'] );
	    // Extra IT Rep data.
	    $it_rep_user = get_user_by( 'id', intval( $meta['it_rep_status_it_rep'] ) );
	    $meta['it_rep_status_it_rep'] = $it_rep_user->data->display_name;
	    $it_rep_confirm_date = strtotime( $meta['it_rep_status_date'] );
	    $meta['it_rep_status_date'] = 'Confirmed - ' . date('M j, Y \a\t g:i a', $it_rep_confirm_date);
	    // Extra Business Staff data.
	    if ( '0' === $meta['business_staff_status_confirmed'] ) {
	    	$meta['business_staff_status_date'] = 'Not required';
	    } else {
		    $business_staff_confirm_date = strtotime( $meta['business_staff_status_date'] );
	    	$meta['business_staff_status_date'] = 'Confirmed - ' . date('M j, Y \a\t g:i a', $business_staff_confirm_date);
		    $business_user = get_user_by('id', intval( $meta['business_staff_status_business_staff'] ) );
		    $meta['business_staff_status_business_staff'] = $business_user->data->display_name;
	    }
	    // Extra Logistics data.
	    $logistics_confirm_date = strtotime( $meta['it_logistics_status_date'] );
	    $meta['it_logistics_status_date'] = 'Confirmed - ' . date('M j, Y \a\t g:i a', $logistics_confirm_date);
	    // Modify purchase item data.
	    $meta['products_subtotal'] = '$' . number_format( $meta['products_subtotal'], 2, '.', ',' );
	    for ($inc=0; $inc < $meta['order_items']; $inc++) {
	    	$price = $meta["order_items_{$inc}_price"];
	    	$meta["order_items_{$inc}_price"] = '$' . number_format( $price, 2, '.', ',' );
	    	$date = $meta["order_items_{$inc}_requisition_date"];
	    	if ( ! empty( $date ) ) {
	    		$date = strtotime( $date );
	    		$date = date('M j, Y', $date);
	    		$meta["order_items_{$inc}_requisition_date"] = $date;
	    	}
	    }
	    $json = json_encode( $meta, JSON_FORCE_OBJECT );
	    echo '<pre>';
	    print_r($meta);
	    echo '</pre>';
      $html  = '<div id="major-publishing-actions" style="overflow:hidden">';
      $html .= '<div id="publishing-action">';
      $html .= '<button type="button" accesskey="p" tabindex="5" class="button-primary" id="print" name="print">Print</button>';
      $html .= '<script type="text/javascript">var wsorder_data=' . $json . '</script>';
      $html .= '</div>';
      $html .= '</div>';
      echo $html;
		}, 10, 1 );

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
			// echo '<pre>';
			// print_r($query);
			// echo '</pre>';
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
				$location = admin_url() . 'edit.php?post_type=wsorder';
				wp_safe_redirect($location);
				exit();
			}
		}
	}
}
