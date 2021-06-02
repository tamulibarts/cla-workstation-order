<?php
/**
 * The file that renders the single page template
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/templates/order-template.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/templates
 */

/**
 * Registers and enqueues template styles.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_styles() {

	wp_register_style(
		'cla-workstation-order',
		CLA_WORKSTATION_ORDER_DIR_URL . 'css/styles.css',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'css/styles.css' ),
		'screen'
	);

	wp_enqueue_style( 'cla-workstation-order' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_styles', 1 );

/**
 * Registers and enqueues order deletion scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_delete_scripts() {

	if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_admin' ) ) {

		wp_register_script(
			'cla-workstation-order-delete-scripts',
			CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-delete.js',
			array('jquery'),
			filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-delete.js' ),
			true
		);

		// wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'cla-workstation-order-delete-scripts' );
		// Include admin ajax URL and nonce.
		$script_variables = 'var WSODeleteOrderAJAX = {"ajaxurl":"'.admin_url('admin-ajax.php').'","nonce":"'.wp_create_nonce('delete_order').'"};';

		wp_add_inline_script( 'cla-workstation-order-delete-scripts', $script_variables, 'before' );

	}

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_delete_scripts', 1 );

/**
 * Empty the edit link for this page.
 *
 * @return string
 */
function cla_empty_edit_link( $link ) {
	if ( ! current_user_can( 'wso_admin' ) ) {
		$link = '';
	}
	return $link;
}
add_filter( 'edit_post_link', 'cla_empty_edit_link' );

/**
 * Modify post title.
 */
add_filter( 'the_title', function( $title ) {
	return 'Order ' . $title . ' Details';
});

/**
 * Remove entry meta.
 */
remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );

/**
 * Add class to Genesis entry header.
 *
 * @param array $attr The element's attributes.
 */
add_filter( 'genesis_attr_entry-header', function( $attr ) {
	$attr['class'] .= ' grid-x';
	return $attr;
});

/**
 * Add class to Genesis entry header.
 *
 * @param array $attr The element's attributes.
 */
add_filter( 'genesis_attr_entry-title', function( $attr ) {
	$attr['class'] .= ' cell auto';
	return $attr;
});

/**
 * Add print and maybe delete button.
 */
add_action( 'genesis_entry_header', function(){
	global $post;
	$output = '';
	if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_admin' ) ) {
		$output .= '<div class="cell shrink"><button class="btn btn-square btn-outline-red" type="button" title="Delete this order" id="cla_delete_order"><span class="dashicons dashicons-trash"></span></button></div>';
	}
	if ( 'publish' === get_post_status( $post ) ) {
		$bare_url     = CLA_WORKSTATION_ORDER_DIR_URL . 'order-receipt.php?postid=' . $post->ID;
		$complete_url = wp_nonce_url( $bare_url, 'auth-post_' . $post->ID, 'token' );
		$output .= "<div class=\"cell shrink\"><a class=\"btn btn-square btn-outline-dark\" href=\"{$complete_url}\" target=\"_blank\"><span class=\"dashicons dashicons-printer\"></span></a></div>";
	}
	if ( ! empty( $output ) ) {
		$output = "<div class=\"cell shrink\"><div class=\"grid-x\">{$output}</div></div>";
		echo wp_kses_post( $output );
	}
});



/**
 * Decide if user can update the order. Return true or the error message. Copied from class-wsorder-posttype.php
 *
 * @param int $post_id The post ID.
 *
 * @return true|string
 */
function can_current_user_update_order_public( $post_id ) {

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
  	if ( current_user_can( 'wso_logistics' ) ) {
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
	} elseif ( current_user_can( 'wso_logistics' ) ) {
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
 * Render the order form.
 *
 * @return void
 */
function cla_render_order( $content ) {

	if ( ! is_user_logged_in() ) {

		$content = 'You must be logged in to view this page.';

	} else {

		/**
		 * Variables used to output work order info.
		 */
		global $post;
		$post_id   = $post->ID;
		$post_meta = get_post_meta( $post->ID );
		$order_author_id = $post_meta['order_author'][0];
		$order_author    = get_user_by( 'id', $order_author_id );
		preg_match( '/^([^\s]+)\s+(.*)/', $order_author->data->display_name, $order_author_name );
		$first_name    = $order_author_name[1];
		$last_name     = $order_author_name[2];
		$department_id = $post_meta['author_department'][0];
		$department    = get_post( $department_id );
		$contribution  = $post_meta['contribution_amount'][0];
		$current_asset = $post_meta['current_asset'][0];
		date_default_timezone_set('America/Chicago');
		$creation_time = strtotime( $post->post_date_gmt.' UTC' );
		$creation_date = date( 'M j, Y \a\t g:i a', $creation_time );
		$it_rep_date = 'Not yet confirmed';
		if ( isset( $post_meta['it_rep_status_date'] ) && is_array( $post_meta['it_rep_status_date'] ) && ! empty( $post_meta['it_rep_status_date'][0] ) ) {
			$it_rep_time = strtotime( $post_meta['it_rep_status_date'][0].' UTC' );
			$it_rep_date = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $it_rep_time );
		}
		$business_admin_date = 'Not yet confirmed';
		if ( isset( $post_meta['business_staff_status_date'] ) && is_array( $post_meta['business_staff_status_date'] ) && ! empty( $post_meta['business_staff_status_date'][0] ) ) {
			$business_admin_time = strtotime( $post_meta['business_staff_status_date'][0].' UTC' );
			$business_admin_date = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $business_admin_time );
		}
		$logistics_date = 'Not yet confirmed';
		if ( isset( $post_meta['it_logistics_status_date'] ) && is_array( $post_meta['it_logistics_status_date'] ) && ! empty( $post_meta['it_logistics_status_date'][0] ) ) {
			$logistics_time      = strtotime( $post_meta['it_logistics_status_date'][0].' UTC' );
			$logistics_date      = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $logistics_time );
		}
		$logistics_ordered_date = 'Not yet ordered';
		if ( isset( $post_meta['it_logistics_status_ordered_at'] ) && is_array( $post_meta['it_logistics_status_ordered_at'] ) && ! empty( $post_meta['it_logistics_status_ordered_at'][0] ) ) {
			$logistics_ordered_time = strtotime( $post_meta['it_logistics_status_ordered_at'][0].' UTC' );
			$logistics_ordered_date = '<span class="badge badge-success">Ordered</span> ' . date( 'M j, Y \a\t g:i a', $logistics_ordered_time );
		}
		$program             = get_post( $post_meta['program'][0] );
		$program_fiscal_year = get_post_meta( $post_meta['program'][0], 'fiscal_year', true );
		$it_rep              = get_user_by( 'id', $post_meta['it_rep_status_it_rep'][0] );
		$it_rep_comments     = isset( $post_meta['it_rep_status_comments'] ) ? $post_meta['it_rep_status_comments'][0] : '';
		$department_comments = isset( $post_meta['business_staff_status_comments'] ) ? $post_meta['business_staff_status_comments'][0] : '';
		$business_admin      = get_user_by( 'id', $post_meta['business_staff_status_business_staff'][0] );
		$subtotal            = (float) 0;

		if ( 'publish' !== $post->post_status ) {
			$reason = can_current_user_update_order_public( $post_id );
			$content .= "<div class=\"notice notice-red\"><em>You cannot edit the order right now. $reason</em></div>";
		}

		/**
		 * User Details
		 */
		$content .= '<div class="grid-x grid-margin-x"><div class="cell small-12 medium-6"><h2>User Details</h2><dl class="row horizontal">';
		$content .= "<dt>First Name</dt><dd>{$first_name}</dd>";
		$content .= "<dt>Last Name</dt><dd>{$last_name}</dd>";
		$content .= "<dt>Email Address</dt><dd>{$order_author->data->user_email}</dd>";
		$content .= "<dt>Department</dt><dd>{$department->post_title}</dd>";
		if ( ! empty( $contribution ) ) {
			$content        .= "<dt>Contribution Amount</dt><dd>{$contribution}</dd>";
			$account_number = $post_meta['contribution_account'][0];
			if ( isset( $post_meta['business_staff_status_account_number'] ) && ! empty( $post_meta['business_staff_status_account_number'][0] ) ) {
				$account_number = $post_meta['business_staff_status_account_number'][0];
			}
			$content .= "<dt>Account Number</dt><dd>{$account_number}</dd>";
		}
		$content .= "<dt>Office Location</dt><dd>{$post_meta['building'][0]} {$post_meta['office_location'][0]}</dd>";
		if ( ! empty( $current_asset ) ) {
			$content .= "<dt>Current Asset</dt><dd>{$current_asset}</dd>";
		} else {
			$content .= "<dt>Current Asset</dt><dd>No asset</dd>";
		}
		$content .= "<dt>Order Comment</dt><dd>{$post_meta['order_comment'][0]}</dd>";
		$content .= "<dt>Order Placed At</dt><dd>{$creation_date}</dd>";
		$content .= "<dt>Program</dt><dd>{$program->post_title}</dd>";
		$content .= "<dt>Fiscal Year</dt><dd>{$program_fiscal_year}</dd>";
		$content .= '</div>';

		/**
		 * Processing.
		 */
		$content .= '<div class="cell small-12 medium-6"><h2>Processing</h2><dl class="row horizontal">';
		$content .= "<dt>IT Staff ({$it_rep->data->display_name})</dt><dd>{$it_rep_date}</dd>";
		if ( $business_admin ) {
			$tt_wrap_open         = '';
			$tt_wrap_close        = '';
			$tt_content           = '';
			$affiliated_bus_staff = get_field( 'affiliated_business_staff', $post_id );
			if ( is_array( $affiliated_bus_staff ) && count( $affiliated_bus_staff ) > 1 ) {
				$tt_wrap_open  = '<a href="#" class="tooltip tooltip-up">';
				$tt_wrap_close = '</a>';
				$tt_content    = '<span class="tooltip-content">Business admins for this department are: ';
				$admins        = array();
				foreach ( $affiliated_bus_staff as $bus_staff_id ) {
					$first_name = get_user_meta( $bus_staff_id, 'first_name', true );
					$last_name  = get_user_meta( $bus_staff_id, 'last_name', true );
					$admins[]   = "$first_name $last_name";
				}
				$tt_content .= implode( ', ', $admins ) . '</span>';
			}
			$content .= "<dt>{$tt_wrap_open}Business Staff{$tt_content}{$tt_wrap_close} ({$business_admin->data->display_name})</dt><dd>{$business_admin_date}</dd>";
		} else {
			$content .= '<dt>Business Staff</dt><dd>Not required</dd>';
		}
		$content .= "<dt>IT Logistics</dt><dd>{$logistics_date}<br>{$logistics_ordered_date}</dd>";
		$content .= "<dt>IT Staff Comments</dt><dd>{$it_rep_comments}</dd>";
		$content .= "<dt>Department Comments</dt><dd>{$department_comments}</dd>";
		$content .= '</dl></div></div>';

		/**
		 * Order Items.
		 */
		$content .= '<h2>Order Items</h2><p>Note: some items in the catalog are bundles, which are a collection of products. Any bundles that you selected will be expanded as their products below.</p>';

		// Products.
		$content .= '<table>';
		if ( array_key_exists( 'order_items', $post_meta ) && ! empty( $post_meta['order_items'][0] ) ) {
			$content .= '<thead><tr><th colspan="7"><h3>Products</h3></th></tr></thead>';
			$content .= '<thead class="thead-light"><tr><th>SKU</th><th colspan="2">Item</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
			$order_items = get_field( 'order_items', $post_id );
			foreach ( $order_items as $item ) {
				$content .= "<tr><td>{$item['sku']}</td>";
				$content .= "<td colspan=\"2\">{$item['item']}</td>";
				$content .= "<td>{$item['requisition_number']}</td>";
				if ( ! empty( $item['requisition_date'] ) ) {
					$requisition_time = strtotime( $item['requisition_date'] . ' UTC' );
					$requisition_date = date( 'm/d/Y', $requisition_time );
				} else {
					$requisition_date = '';
				}
				$content .= "<td>{$requisition_date}</td>";
				$content .= "<td>{$item['asset_number']}</td>";
				$price = '$' . number_format( $item['price'], 2, '.', ',' );
				$content .= "<td>{$price}</td></tr>";
				$subtotal = $subtotal + floatval( $item['price'] );
			}
			$content .= '</tbody>';
		}

		// Quotes.
		if ( array_key_exists( 'quotes', $post_meta ) && ! empty( $post_meta['quotes'][0] ) ) {
			$content .= '<thead><tr><th colspan="7"><h3>External Items</h3></th></tr></thead>';
			$content .= '<thead class="thead-light"><tr><th>Name</th><th>Description</th><th>Quote</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead>';
			$quotes = get_field( 'quotes', $post_id );
			foreach ( $quotes as $item ) {
				$content .= "<tr><td>{$item['name']}</td>";
				$content .= "<td>{$item['description']}</td>";
				$content .= "<td><a class=\"btn btn-outline-dark\" target=\"_blank\" href=\"{$item['file']['url']}\" title=\"{$item['file']['title']}\"><span class=\"dashicons dashicons-media-text\"></span></a></td>";
				$content .= "<td>{$item['requisition_number']}</td>";
				if ( ! empty( $item['requisition_date'] ) ) {
					$requisition_time = strtotime( $item['requisition_date'] . ' UTC' );
					$requisition_date = date( 'm/d/Y', $requisition_time );
				} else {
					$requisition_date = '';
				}
				$content .= "<td>{$requisition_date}</td>";
				$content .= "<td>{$item['asset_number']}</td>";
				$price = '$' . number_format( $item['price'], 2, '.', ',' );
				$content .= "<td>{$price}</td></tr>";
				$subtotal = $subtotal + floatval( $item['price'] );
			}
			$content .= '</tbody>';
		}

		$content .= '<tbody>';

		// Subtotal.
		$subtotal = '$' . number_format( $subtotal, 2, '.', ',' );
		$content .= "<tr><td colspan=\"6\" class=\"text-right\"><strong>Products Total</strong></td><td>{$subtotal}</td></tr>";

		// Contributions.
		if ( array_key_exists( 'contribution_amount', $post_meta ) && ! empty( $post_meta['contribution_amount'][0] ) && intval( $post_meta['contribution_amount'][0] ) !== 0 ) {
			$contribution = '$' . number_format( $post_meta['contribution_amount'][0], 2, '.', ',' );
			$account_number = $post_meta['contribution_account'][0];
			if ( isset( $post_meta['business_staff_status_account_number'] ) && ! empty( $post_meta['business_staff_status_account_number'][0] ) ) {
				$account_number = $post_meta['business_staff_status_account_number'][0];
			}
			$content .= "<tr><td colspan=\"6\" class=\"text-right\"><strong>Contributions from {$account_number}</strong></td><td>{$contribution}</td></tr>";
		}

		$content .= '</tbody></table>';

	}

	return $content;

}
add_filter( 'the_content', 'cla_render_order' );

if ( function_exists( 'genesis' ) ) {
	genesis();
}
