<?php
/**
 * The file that renders the single page template
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/templates/order-form-template.php
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
 * Render the order form.
 *
 * @return void
 */
function cla_render_order_form( $content ) {

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
		$creation_time       = strtotime( $post->post_date_gmt.' UTC' );
		$it_rep_time         = strtotime( $post_meta['it_rep_status_date'][0].' UTC' );
		$business_admin_time = array_key_exists( 'business_staff_status_date', $post_meta ) ? strtotime( $post_meta['business_staff_status_date'][0].' UTC' ) : '';
		$logistics_time      = strtotime( $post_meta['it_logistics_status_date'][0].' UTC' );
		$logistics_ordered_time = strtotime( $post_meta['it_logistics_status_ordered_at'][0].' UTC' );
		$creation_date       = date( 'M j, Y \a\t g:i a', $creation_time );
		$it_rep_date         = date( 'M j, Y \a\t g:i a', $it_rep_time );
		$business_admin_date = ! empty( $business_admin_time ) ? date( 'M j, Y \a\t g:i a', $business_admin_time ) : '';
		$logistics_date      = date( 'M j, Y \a\t g:i a', $logistics_time );
		$logistics_ordered_date = date( 'M j, Y \a\t g:i a', $logistics_ordered_time );
		$program             = get_post( $post_meta['program'][0] );
		$program_fiscal_year = get_post_meta( $post_meta['program'][0], 'fiscal_year', true );
		$it_rep              = get_user_by( 'id', $post_meta['it_rep_status_it_rep'][0] );
		$it_rep_comments     = $post_meta['it_rep_status_comments'][0];
		$business_admin      = get_user_by( 'id', $post_meta['business_staff_status_business_staff'][0] );
		echo '<pre>';
		print_r($post_meta);
		echo '</pre>';

		/**
		 * User Details
		 */
		$content .= '<div class="grid-x grid-padding-x"><div class="cell small-12 medium-6"><h2>User Details</h2><dl class="row horizontal">';
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
		$content .= '<div class="cell small-12 medium-6"><h2>Processing</h2><dl class="row horizontal">';
		$content .= "<dt>IT Staff ({$it_rep->data->display_name})</dt><dd><span class=\"badge badge-success\">Confirmed</span> {$it_rep_date}</dd>";
		if ( $business_admin ) {
			$content .= "<dt>Business Staff ({$business_admin->data->display_name})</dt><dd><span class=\"badge badge-success\">Confirmed</span> {$business_admin_date}</dd>";
		} else {
			$content .= '<dt>Business Staff</dt><dd>Not required</dd>';
		}
		$content .= "<dt>IT Logistics</dt><dd><span class=\"badge badge-success\">Confirmed</span> {$logistics_date}<br><span class=\"badge badge-success\">Ordered</span> {$logistics_ordered_date}</dd>";
		$content .= "<dt>IT Staff Comments</dt><dd>{$it_rep_comments}</dd>";
		$content .= "<dt>Department Comments</dt><dd>{$post_meta['department_comments'][0]}</dd>";
		$content .= '</dl></div></div>';

		/**
		 * Order Items.
		 */
		$content .= '<h2>Order Items</h2><p>Note: some items in the catalog are bundles, which are a collection of products. Any bundles that you selected will be expanded as their products below.</p>';

		// Products.
		if ( array_key_exists( 'order_items', $post_meta ) && ! empty( $post_meta['order_items'][0] ) ) {
			$content .= '<h3>Products</h3>';
			$content .= '<table><thead class="thead-light"><tr><th>SKU</th><th>Item</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
			$order_items = get_field( 'order_items', $post_id );
			foreach ( $order_items as $item ) {
				$content .= "<td>{$item['sku']}</td>";
				$content .= "<td>{$item['item']}</td>";
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
				$content .= "<td>{$price}</td>";
			}
			$content .= '</tbody></table>';
		}

		// Quotes.
		if ( array_key_exists( 'quotes', $post_meta ) && ! empty( $post_meta['quotes'][0] ) ) {
			$content .= '<h3>External Items</h3>';
			$content .= '<table><thead class="thead-light"><tr><th>Name</th><th>Description</th><th>Quote</th><th>Req #</th><th>Req Date</th><th>Asset #</th><th>Price</th></tr></thead><tbody>';
			$quotes = get_field( 'quotes', $post_id );
			echo '<pre>';
			print_r($quotes);
			echo '</pre>';
			foreach ( $quotes as $item ) {
				$content .= "<td>{$item['name']}</td>";
				$content .= "<td>{$item['description']}</td>";
				$content .= "<td>{$item['file']['url']}</td>";
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
				$content .= "<td>{$price}</td>";
			}
			$content .= '</tbody></table>';
		}

	}

	return $content;

}
add_filter( 'the_content', 'cla_render_order_form' );

if ( function_exists( 'genesis' ) ) {
	genesis();
}
