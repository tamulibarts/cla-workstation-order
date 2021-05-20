<?php
/**
 * The file that renders the single page template
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/templates/order-approval-template.php
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

	wp_register_script(
		'cla-workstation-order-approval-scripts',
		CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-approval.js',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-approval.js' ),
		'screen'
	);

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cla-workstation-order-approval-scripts' );
	// Include admin ajax URL and nonce.
	$script_variables = 'var WSOAjax = {"ajaxurl":"'.admin_url('admin-ajax.php').'","nonce":"'.wp_create_nonce('confirm_order').'"};';

	wp_add_inline_script( 'cla-workstation-order-approval-scripts', $script_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_approval_scripts', 1 );

/**
 * Empty the edit link for this page.
 *
 * @return string
 */
function cla_empty_edit_link() {
	return '';
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
		$post_id              = $post->ID;
		$post_meta            = get_post_meta( $post->ID );
		$current_user_id      = get_current_user_id();
		$order_author_id      = $post_meta['order_author'][0];
		$order_author         = get_user_by( 'id', $order_author_id );
		preg_match( '/^([^\s]+)\s+(.*)/', $order_author->data->display_name, $order_author_name );
		$first_name           = $order_author_name[1];
		$last_name            = $order_author_name[2];
		$affiliated_it_reps   = get_field( 'affiliated_it_reps', $post->ID );
		$it_rep               = get_post_meta( $post->ID, 'it_rep_status_it_rep', true );
		$is_it_rep            = in_array( $current_user_id, $affiliated_it_reps ) ? true : false;
		$affiliated_bus_staff = get_field( 'affiliated_business_staff', $post->ID );
		$is_business_staff    = in_array( $current_user_id, $affiliated_bus_staff ) ? true : false;
		$business_admin       = (int) get_post_meta( $post->ID, 'business_staff_status_business_staff', true );
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
			$it_rep_date = '<span class=\"badge badge-success\">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $it_rep_time );
		}
		$business_admin_date = 'Not yet confirmed';
		if ( isset( $post_meta['business_staff_status_date'] ) && is_array( $post_meta['business_staff_status_date'] ) && ! empty( $post_meta['business_staff_status_date'][0] ) ) {
			$business_admin_time = strtotime( $post_meta['business_staff_status_date'][0].' UTC' );
			$business_admin_date = '<span class=\"badge badge-success\">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $business_admin_time );
		}
		$logistics_date = 'Not yet confirmed';
		if ( isset( $post_meta['it_logistics_status_date'] ) && is_array( $post_meta['it_logistics_status_date'] ) && ! empty( $post_meta['it_logistics_status_date'][0] ) ) {
			$logistics_time      = strtotime( $post_meta['it_logistics_status_date'][0].' UTC' );
			$logistics_date      = '<span class=\"badge badge-success\">Confirmed</span> ' . date( 'M j, Y \a\t g:i a', $logistics_time );
		}
		$logistics_ordered_date = 'Not yet ordered';
		if ( isset( $post_meta['it_logistics_status_ordered_at'] ) && is_array( $post_meta['it_logistics_status_ordered_at'] ) && ! empty( $post_meta['it_logistics_status_ordered_at'][0] ) ) {
			$logistics_ordered_time = strtotime( $post_meta['it_logistics_status_ordered_at'][0].' UTC' );
			$logistics_ordered_date = '<span class=\"badge badge-success\">Ordered</span> ' . date( 'M j, Y \a\t g:i a', $logistics_ordered_time );
		}
		$program             = get_post( $post_meta['program'][0] );
		$program_fiscal_year = get_post_meta( $post_meta['program'][0], 'fiscal_year', true );
		$it_rep              = get_user_by( 'id', $post_meta['it_rep_status_it_rep'][0] );
		$it_rep_comments     = isset( $post_meta['it_rep_status_comments'] ) ? $post_meta['it_rep_status_comments'][0] : '';
		$department_comments = isset( $post_meta['business_staff_status_comments'] ) ? $post_meta['business_staff_status_comments'][0] : '';
		$business_admin      = get_user_by( 'id', $post_meta['business_staff_status_business_staff'][0] );
		$subtotal            = (float) 0;
		$permalink           = get_permalink();
		$content             = '';

		/**
		 * User Details
		 */
		$content .= '<div class="grid-x grid-margin-x"><div class="cell small-12 medium-6"><h2>User Details</h2><dl class="row horizontal">';
		$content .= "<dt>First Name</dt><dd>{$first_name}</dd>";
		$content .= "<dt>Last Name</dt><dd>{$last_name}</dd>";
		$content .= "<dt>Email Address</dt><dd>{$order_author->data->user_email}</dd>";
		$content .= "<dt>Department</dt><dd>{$department->post_title}</dd>";
		if ( ! empty( $contribution ) ) {
			$content .= "<dt>Contribution Amount</dt><dd>{$contribution}</dd>";
			$content .= "<dt>Account Number</dt><dd>{$post_meta['contribution_account'][0]}</dd>";
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
		if ( $is_it_rep || $is_business_staff || current_user_can( 'wso_logistics' ) ) {
			$label = $is_it_rep ? 'This order is pending confirmation by IT staff' : 'This order is pending confirmation by business staff';
			if ( current_user_can( 'wso_logistics' ) ) {
				$label = 'This order is pending confirmation by logistics';
			}
			$content .= "<div id=\"approval-fields\" class=\"outline-fields\"><form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_approval_form\" action=\"{$permalink}\"><div class=\"ajax-response\"></div><div class=\"grid-x\"><div class=\"cell auto\"><label for=\"approval_comments\"><strong>$label</strong></label><div>Please look it over for any errors or ommissions then confirm or return.</div></div><div class=\"cell shrink\"><button class=\"button btn btn-outline-green\" type=\"button\" id=\"cla_confirm\">Confirm</button> <button class=\"button btn btn-outline-red\" type=\"button\" id=\"cla_return\">Return</button></div></div><textarea id=\"approval_comments\" name=\"approval_comments\" placeholder=\"Comment\"></textarea></form></div>";
		}
		$content .= '<dl class="row horizontal">';
		$content .= "<dt>IT Staff ({$it_rep->data->display_name})</dt><dd>{$it_rep_date}</dd>";
		if ( $business_admin ) {
			$content .= "<dt>Business Staff ({$business_admin->data->display_name})</dt><dd>{$business_admin_date}</dd>";
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

		// Logistics user can edit product acquisition fields.
		if ( current_user_can( 'wso_logistics' ) ) {
			$content .= "<form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_acquisition_form\" action=\"{$permalink}\">";
		}

		// Products.
		$content .= '<table>';
		date_default_timezone_set('America/Chicago');
		if ( array_key_exists( 'order_items', $post_meta ) && ! empty( $post_meta['order_items'][0] ) ) {
			$content .= '<thead><tr><th colspan="7"><h3>Products</h3></th></tr></thead>';
			$content .= '<thead class="thead-light"><tr><th>SKU</th><th colspan="2">Item</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
			$order_items = get_field( 'order_items', $post_id );
			foreach ( $order_items as $key => $item ) {
				$requisition_number = $item['requisition_number'];
				$requisition_date   = $item['requisition_date'];
				$asset_number = $item['asset_number'];
				if ( current_user_can( 'wso_logistics' ) ) {
					$requisition_number = "<input type=\"text\" name=\"cla_item_{$key}_req_number\" value=\"{$requisition_number}\" />";
					$requisition_date   = "<input type=\"date\" name=\"cla_item_{$key}_req_date\" value=\"{$requisition_date}\" />";
					$asset_number       = "<input type=\"text\" name=\"cla_item_{$key}_asset_number\" value=\"{$asset_number}\" />";
				}
				$content .= "<tr class=\"cla-order-item\"><td>{$item['sku']}</td>";
				$content .= "<td colspan=\"2\">{$item['item']}</td>";
				$content .= "<td>{$requisition_number}</td>";
				$content .= "<td>{$requisition_date}</td>";
				$content .= "<td>{$asset_number}</td>";
				$price = '$' . number_format( $item['price'], 2, '.', ',' );
				$content .= "<td>{$price}</td></tr>";
				$subtotal = $subtotal + floatval( $item['price'] );
			}
			$content .= '</tbody>';
		}

		// Quotes.
		if ( array_key_exists( 'quotes', $post_meta ) && ! empty( $post_meta['quotes'][0] ) ) {
			$content .= '<thead><tr><th colspan="7"><h3>External Items</h3></th></tr></thead>';
			$content .= '<thead class="thead-light"><tr><th>Name</th><th>Description</th><th>Quote</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
			$quotes = get_field( 'quotes', $post_id );
			foreach ( $quotes as $item ) {
				$requisition_number = $item['requisition_number'];
				$requisition_date   = $item['requisition_date'];
				$asset_number = $item['asset_number'];
				if ( current_user_can( 'wso_logistics' ) ) {
					$requisition_number = "<input type=\"text\" name=\"cla_quote_{$key}_req_number\" value=\"{$requisition_number}\" />";
					$requisition_date   = "<input type=\"date\" name=\"cla_quote_{$key}_req_date\" value=\"{$requisition_date}\" />";
					$asset_number       = "<input type=\"text\" name=\"cla_quote_{$key}_asset_number\" value=\"{$asset_number}\" />";
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
			$content .= "<tr><td colspan=\"6\" class=\"text-right\"><strong>Contributions from {$post_meta['contribution_account'][0]}</strong></td><td>{$contribution}</td></tr>";
		}

		if ( current_user_can( 'wso_logistics' ) ) {
			$content .= "<tr><td colspan=\"6\" class=\"text-right\"><div class=\"ajax-response\"></div></td><td><input type=\"submit\" id=\"cla_submit\" value=\"Update\" /></td></tr>";
		}

		$content .= '</tbody></table>';

		if ( current_user_can( 'wso_logistics' ) ) {
			$content .= "</form>";
		}

	}

	return $content;

}
add_filter( 'the_content', 'cla_render_order' );

if ( function_exists( 'genesis' ) ) {
	genesis();
}
