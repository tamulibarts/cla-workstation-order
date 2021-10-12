<?php
/**
 * The file that renders the single page template
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/templates/order-approval-template.php
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
 * Registers and enqueues template scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_approval_scripts() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	wp_register_script(
		'cla-workstation-order-approval-scripts',
		CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-approval.js',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-approval.js' ),
		true
	);

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cla-workstation-order-approval-scripts' );
	// Include admin ajax URL and nonce.
	global $post;
	$item_count  = (int) get_post_meta( $post->ID, 'order_items', true );
	$quote_count = (int) get_post_meta( $post->ID, 'quotes', true );
	$script_variables = 'var WSOAjax = {"ajaxurl":"'.admin_url('admin-ajax.php').'","nonce":"'.wp_create_nonce('confirm_order').'","item_count":'.$item_count.',"quote_count":'.$quote_count.'};';

	wp_add_inline_script( 'cla-workstation-order-approval-scripts', $script_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_approval_scripts', 1 );

/**
 * Registers and enqueues order deletion scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_delete_scripts() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) || current_user_can( 'wso_admin' ) ) {

		wp_register_script(
			'cla-workstation-order-delete-scripts',
			CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-delete.js',
			array('jquery'),
			filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-delete.js' ),
			'screen'
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
 * Add print button.
 */
add_action( 'genesis_entry_header', function(){

	if ( ! is_user_logged_in() ) {
		return;
	}

	$output = '';
	if ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) || current_user_can( 'wso_admin' ) ) {
		$output .= '<div class="cell shrink"><button class="cla-delete-order btn btn-square btn-outline-red" type="button" title="Delete this order"><span class="dashicons dashicons-trash"></span></button></div>';
	}

	// Print button.
	global $post;
	$bare_url     = CLA_WORKSTATION_ORDER_DIR_URL . 'order-receipt.php?postid=' . $post->ID;
	$complete_url = wp_nonce_url( $bare_url, 'auth-post_' . $post->ID, 'token' );
	$output       .= "<div class=\"cell shrink\"><a class=\"cla-print-order btn btn-square btn-outline-dark\" href=\"{$complete_url}\" target=\"_blank\"><span class=\"dashicons dashicons-printer\"></span></a></div>";

	if ( ! empty( $output ) ) {
		$output = "<div class=\"cell shrink\"><div class=\"grid-x\">{$output}</div></div>";
		echo wp_kses_post( $output );
	}

});

/**
 * Render the order form.
 *
 * @return void
 */
function cla_render_order( $content ) {

	if ( ! is_user_logged_in() ) {
		return $content;
	}

	/**
	 * Variables used to output work order info.
	 */
	global $post;
	$post_id         = $post->ID;
	$post_meta       = get_post_meta( $post->ID );
	$current_user_id = get_current_user_id();
	$order_author_id = $post_meta['order_author'][0];
	$order_author    = get_user_by( 'id', $order_author_id );
	preg_match( '/^([^\s]+)\s+(.*)/', $order_author->data->display_name, $order_author_name );
	$first_name              = $order_author_name[1];
	$last_name               = $order_author_name[2];
	$affiliated_it_reps      = get_field( 'affiliated_it_reps', $post->ID );
	$it_rep_id               = (int) get_post_meta( $post->ID, 'it_rep_status_it_rep', true );
	$is_aff_it_rep           = in_array( $current_user_id, $affiliated_it_reps ) ? true : false;
	$it_rep_approved         = (int) get_post_meta( $post->ID, 'it_rep_status_confirmed', true );
	$affiliated_bus_staff    = get_field( 'affiliated_business_staff', $post->ID );
	$is_aff_business_staff   = in_array( $current_user_id, $affiliated_bus_staff ) ? true : false;
	$business_admin_id       = (int) get_post_meta( $post->ID, 'business_staff_status_business_staff', true );
	$business_admin_approved = (int) get_post_meta( $post->ID, 'business_staff_status_confirmed', true );
	$order_items             = get_field( 'order_items', $post_id );
	$quotes                  = get_field( 'quotes', $post_id );
	$department_id           = $post_meta['author_department'][0];
	$department              = get_post( $department_id );
	$contribution            = $post_meta['contribution_amount'][0];
	$current_asset           = $post_meta['current_asset'][0];
	date_default_timezone_set('America/Chicago');
	$creation_time = strtotime( $post->post_date_gmt.' UTC' );
	$creation_date = date( 'M j, Y \a\t g:i a', $creation_time );
	$it_rep_date = '<span class="badge badge-light">Not yet confirmed</span>';
	if ( isset( $post_meta['it_rep_status_date'] ) && is_array( $post_meta['it_rep_status_date'] ) && ! empty( $post_meta['it_rep_status_date'][0] ) ) {
		$it_rep_time = strtotime( $post_meta['it_rep_status_date'][0].' UTC' );
		$it_rep_date = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $it_rep_time );
	}
	$business_admin_date = '<span class="badge badge-light">Not required</span>';
	if ( 0 !== $business_admin_id ) {
		$business_admin_date = '<span class="badge badge-light">Not yet confirmed</span>';
		if ( isset( $post_meta['business_staff_status_date'] ) && is_array( $post_meta['business_staff_status_date'] ) && ! empty( $post_meta['business_staff_status_date'][0] ) ) {
			$business_admin_time = strtotime( $post_meta['business_staff_status_date'][0].' UTC' );
			$business_admin_date = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $business_admin_time );
		}
	}
	$logistics_date         = '<span class="badge badge-light">Not yet confirmed</span>';
	$logistics_ordered_date = '<span class="badge badge-light">Not yet ordered</span>';
	if ( isset( $post_meta['it_logistics_status_date'] ) && is_array( $post_meta['it_logistics_status_date'] ) && ! empty( $post_meta['it_logistics_status_date'][0] ) ) {
		$logistics_time         = strtotime( $post_meta['it_logistics_status_date'][0].' UTC' );
		$logistics_date         = '<span class="badge badge-success">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $logistics_time );
		$logistics_ordered_date = '<span class="badge badge-error">Not fully ordered</span>';
	}
	if ( isset( $post_meta['it_logistics_status_ordered_at'] ) && is_array( $post_meta['it_logistics_status_ordered_at'] ) && ! empty( $post_meta['it_logistics_status_ordered_at'][0] ) ) {
		$logistics_ordered_time = strtotime( $post_meta['it_logistics_status_ordered_at'][0].' UTC' );
		$logistics_ordered_date = '<span class="badge badge-success">Ordered</span> ' . date( 'M j, Y \a\t g:i a', $logistics_ordered_time );
	}
	$program             = get_post( $post_meta['program'][0] );
	$program_fiscal_year = get_post_meta( $post_meta['program'][0], 'fiscal_year', true );
	$it_rep_user_id      = $post_meta['it_rep_status_it_rep'][0];
	$it_rep              = get_user_by( 'id', $it_rep_user_id );
	$it_rep_comments     = isset( $post_meta['it_rep_status_comments'] ) ? $post_meta['it_rep_status_comments'][0] : '';
	$department_comments = isset( $post_meta['business_staff_status_comments'] ) ? $post_meta['business_staff_status_comments'][0] : '';
	$business_admin      = get_user_by( 'id', $business_admin_id );
	$subtotal            = (float) 0;
	$logistics_confirmed = (int) get_post_meta( $post_id, 'it_logistics_status_confirmed', true );
	$permalink           = get_permalink();
	$switch_user_open    = array(
		'end_user'       => '',
		'it_rep'         => '',
		'business_admin' => '',
	);
	$switch_user_close   = array(
		'end_user'       => '',
		'it_rep'         => '',
		'business_admin' => '',
	);

	if (
		( current_user_can( 'wso_admin' ) || current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) )
		&& method_exists( 'user_switching', 'maybe_switch_url' )
	) {
		if ( false !== $order_author ) {
			$end_user_switch = user_switching::maybe_switch_url( $order_author );
	    if ( $end_user_switch ) {
	      $switch_user_open['end_user'] = sprintf( '<a href="%s&redirect_to=%s">', $end_user_switch, get_permalink() );
	      $switch_user_close['end_user'] = '</a>';
	    }
		}
		if ( false !== $it_rep ) {
	    $it_rep_switch = user_switching::maybe_switch_url( $it_rep );
	    if ( $it_rep_switch ) {
	      $switch_user_open['it_rep'] = sprintf( '<a href="%s&redirect_to=%s">', $it_rep_switch, get_permalink() );
	      $switch_user_close['it_rep'] = '</a>';
	    }
		}
		if ( false !== $business_admin ) {
	    $bus_staff_switch = user_switching::maybe_switch_url( $business_admin );
	    if ( $bus_staff_switch ) {
	      $switch_user_open['business_admin'] = sprintf( '<a href="%s&redirect_to=%s">', $bus_staff_switch, get_permalink() );
	      $switch_user_close['business_admin'] = '</a>';
	    }
	  }
	}
	$content = '';

	/**
	 * User Details
	 */
	$content .= '<div class="grid-x grid-margin-x"><div class="cell small-12 medium-6"><h2>User Details</h2><dl class="row horizontal">';
	$content .= "<dt>First Name</dt><dd>{$switch_user_open['end_user']}{$first_name}{$switch_user_close['end_user']}</dd>";
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
	$content .= '<div class="cell small-12 medium-6"><h2>Processing</h2>';
	if ( $current_user_id === $it_rep_id || $current_user_id === $business_admin_id || current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) {
		$show_approval_form = false;
		$extra_fields = '';
		if ( $current_user_id === $it_rep_id && 1 !== $it_rep_approved ) {
			$show_approval_form = true;
			$label = 'IT staff';
		} elseif ( $current_user_id === $business_admin_id && 1 !== $business_admin_approved ) {
			$show_approval_form = true;
			$label = 'business staff';
			$extra_fields = "<input type=\"text\" name=\"cla_account_number\" id=\"cla_account_number\" placeholder=\"Account Number\" />";
		} elseif ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 !== $logistics_confirmed ) {
			$show_approval_form = true;
			$label = 'logistics';
		}
		if ( true === $show_approval_form ) {
			$content .= "<div id=\"approval-fields\" class=\"outline-fields\"><form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_approval_form\" action=\"{$permalink}\"><div class=\"ajax-response\"></div><div class=\"grid-x\"><div class=\"cell auto\"><label for=\"approval_comments\"><strong>This order is pending confirmation by {$label}</strong></label><div>Please look it over for any errors or ommissions then confirm or return.</div></div><div class=\"cell shrink\"><button class=\"button btn btn-outline-green\" type=\"button\" id=\"cla_confirm\">Confirm</button> <button class=\"button btn btn-outline-red\" type=\"button\" id=\"cla_return\">Return</button></div></div>{$extra_fields}<textarea id=\"approval_comments\" name=\"approval_comments\" placeholder=\"Comment\"></textarea></form></div>";
		}
	} elseif ( true === $is_aff_it_rep || true === $is_aff_business_staff ) {
		$content .= "<div id=\"approval-fields\" class=\"p\"><form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_reassign_form\" action=\"{$permalink}\"><h4>This order was not sent to you, but can be reassigned if necessary.</h4><button class=\"btn btn-warning\" type=\"button\" id=\"cla_reassign\">Reassign to me</button><div class=\"ajax-response\"></div></form></div>";
	}
	$content .= '<dl class="row horizontal">';
	$content .= "<dt>IT Staff ({$switch_user_open['it_rep']}{$it_rep->data->display_name}{$switch_user_close['it_rep']})</dt><dd>{$it_rep_date}</dd>";
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
		$content .= "<dt>{$tt_wrap_open}Business Staff{$tt_content}{$tt_wrap_close} ({$switch_user_open['business_admin']}{$business_admin->data->display_name}{$switch_user_close['it_rep']})</dt><dd>{$business_admin_date}</dd>";
	} else {
		$content .= '<dt>Business Staff</dt><dd><span class="badge badge-light">Not required</span></dd>';
	}
	$content .= "<dt>IT Logistics</dt><dd>{$logistics_date}<br>{$logistics_ordered_date}</dd>";
	$content .= "<dt>IT Staff Comments</dt><dd>{$it_rep_comments}</dd>";
	$content .= "<dt>Department Comments</dt><dd>{$department_comments}</dd>";
	$content .= '</dl></div></div>';

	/**
	 * Order Items.
	 */
	$content .= '<h2>Order Items</h2><p>Note: some items in the catalog are bundles, which are a collection of products. Any bundles that you selected will be expanded as their products below.</p>';

	// Logistics user can edit product acquisition fields.
	if ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 === $logistics_confirmed ) {
		$content .= "<form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_acquisition_form\" action=\"{$permalink}\">";
	}

	// Products.
	$content .= '<table>';
	date_default_timezone_set('America/Chicago');
	if ( array_key_exists( 'order_items', $post_meta ) && ! empty( $post_meta['order_items'][0] ) ) {
		$content .= '<thead><tr><th colspan="7"><h3>Products</h3></th></tr></thead>';
		$content .= '<thead class="thead-light"><tr><th>SKU</th><th colspan="2">Item</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
		foreach ( $order_items as $key => $item ) {
			$requisition_number = $item['requisition_number'];
			$requisition_date   = $item['requisition_date'];
			$asset_number       = $item['asset_number'];
			if ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 === $logistics_confirmed ) {
				$requisition_number = "<input type=\"text\" name=\"cla_item_{$key}_req_number\" value=\"{$requisition_number}\" />";
				$requisition_date   = "<input type=\"date\" name=\"cla_item_{$key}_req_date\" value=\"{$requisition_date}\" />";
				$asset_number       = "<input type=\"text\" name=\"cla_item_{$key}_asset_number\" value=\"{$asset_number}\" />";
			} else if ( ! empty( $requisition_date ) ) {
				preg_match( '/(\d+)-(\d+)-(\d+)/', $requisition_date, $matches );
				$requisition_date = "{$matches[2]}/{$matches[3]}/{$matches[1]}";
			}
			$content .= "<tr class=\"cla-order-item\"><td>{$item['sku']}</td>";
			$content .= "<td colspan=\"2\">{$item['item']}</td>";
			$content .= "<td>{$requisition_number}</td>";
			$content .= "<td>{$requisition_date}</td>";
			$content .= "<td>{$asset_number}</td>";
			$price   = '$' . number_format( $item['price'], 2, '.', ',' );
			$content .= "<td>{$price}</td></tr>";
			$subtotal = $subtotal + floatval( $item['price'] );
		}
		$content .= '</tbody>';
	}

	// Quotes.
	if ( array_key_exists( 'quotes', $post_meta ) && ! empty( $post_meta['quotes'][0] ) ) {
		$content .= '<thead><tr><th colspan="7"><h3>External Items</h3></th></tr></thead>';
		$content .= '<thead class="thead-light"><tr><th>Name</th><th>Description</th><th>Quote</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
		foreach ( $quotes as $key => $item ) {
			$requisition_number = $item['requisition_number'];
			$requisition_date   = $item['requisition_date'];
			$asset_number       = $item['asset_number'];
			if ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 === $logistics_confirmed ) {
				$requisition_number = "<input type=\"text\" name=\"cla_quote_{$key}_req_number\" value=\"{$requisition_number}\" />";
				$requisition_date   = "<input type=\"date\" name=\"cla_quote_{$key}_req_date\" value=\"{$requisition_date}\" />";
				$asset_number       = "<input type=\"text\" name=\"cla_quote_{$key}_asset_number\" value=\"{$asset_number}\" />";
			} else if ( ! empty( $requisition_date ) ) {
				preg_match( '/(\d+)-(\d+)-(\d+)/', $requisition_date, $matches );
				$requisition_date = "{$matches[2]}/{$matches[3]}/{$matches[1]}";
			}
			$content .= "<tr class=\"cla-quote-item\"><td>{$item['name']}</td>";
			$content .= "<td>{$item['description']}</td>";
			$content .= "<td><a class=\"btn btn-outline-dark\" target=\"_blank\" href=\"{$item['file']['url']}\" title=\"{$item['file']['title']}\"><span class=\"dashicons dashicons-media-text\"></span></a></td>";
			$content .= "<td>{$requisition_number}</td>";
			$content .= "<td>{$requisition_date}</td>";
			$content .= "<td>{$asset_number}</td>";
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
	if ( array_key_exists( 'contribution_amount', $post_meta ) && ! empty( $post_meta['contribution_amount'][0] ) ) {
		$contribution = '$' . number_format( $post_meta['contribution_amount'][0], 2, '.', ',' );
		$account_number = $post_meta['contribution_account'][0];
		if ( isset( $post_meta['business_staff_status_account_number'] ) && ! empty( $post_meta['business_staff_status_account_number'][0] ) ) {
			$account_number = $post_meta['business_staff_status_account_number'][0];
		}
		$content .= "<tr><td colspan=\"6\" class=\"text-right\"><strong>Contributions from {$account_number}</strong></td><td>{$contribution}</td></tr>";
	}

	if ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 === $logistics_confirmed ) {
		$content .= "<tr><td colspan=\"6\" class=\"text-right\"><div class=\"ajax-response\"></div></td><td class=\"logistics-approval-buttons\"><input type=\"submit\" id=\"cla_submit\" value=\"Update\" />";
		$content .= "<br><button type=\"button\" class=\"button button-submit button-green\" id=\"cla_publish\">Publish</button>";
		$content .= "</td></tr>";
	}

	$content .= '</tbody></table>';

	if ( ( current_user_can( 'wso_logistics' ) || current_user_can( 'wso_logistics_admin' ) ) && 1 === $logistics_confirmed ) {
		$content .= "</form>";
	}

	return $content;

}
add_filter( 'the_content', 'cla_render_order' );

if ( function_exists( 'genesis' ) ) {
	genesis();
}
