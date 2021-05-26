<?php
if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'wso_admin' ) && ! current_user_can( 'wso_logistics' ) && ! current_user_can( 'wso_business_admin' ) && ! current_user_can( 'wso_it_rep' ) ) {
	$blog_id = get_current_blog_id();
	$url     = get_site_url( $blog_id, '/my-orders/' );
	wp_redirect( $url );
}

function cla_get_selected_program_id() {

	$program_id = false;
	if ( isset( $_REQUEST['program_id'] ) ) {
		$try_id = (int) $_REQUEST['program_id'];
		if ( is_int( $try_id ) ) {
			if ( 0 === $try_id ) {
				$program_id = 0;
			} else {
				$program_post_status = get_post_status( $try_id );
				$program_post_type   = get_post_type( $try_id );
				if ( 'publish' === $program_post_status && 'program' === $program_post_type ) {
					$program_id = $try_id;
				} else {
					$program_id = 0;
				}
			}
		} else {
			$program_id = 0;
		}
	}
	if ( false === $program_id ) {
		$program_post = get_field( 'current_program', 'option' );
		if ( is_object( $program_post ) ) {
			$program_id = $program_post->ID;
		} else {
			$program_id = 0;
		}
	}
	return $program_id;
}

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
function program_title( $title, $post_id ) {

	$post_type = get_post_type( $post_id );

	if ( 'page' === $post_type ) {
		$program_prefix = '';
		$program_id     = cla_get_selected_program_id();

		if ( 0 !== $program_id ) {
			$program_prefix = get_post_meta( $program_id, 'prefix', true );
			if ( empty( $program_prefix ) || false === $program_prefix ) {
				$program_prefix = 'no prefix';
			}
			$title .= ' for <span id="heading_program_prefix">' . $program_prefix . '</span>';
		} else {
			$title = 'All Orders';
		}
	}

	return $title;

}
add_filter( 'the_title', 'program_title', 11, 2 );

/**
 * Registers and enqueues order deletion scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_order_search_scripts() {

	wp_register_script(
		'cla-workstation-order-search-script',
		CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-search.js',
		array('jquery'),
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-search.js' ),
		true
	);

	wp_enqueue_script( 'cla-workstation-order-search-script' );
	// Include admin ajax URL and nonce.
	$script_variables = 'var WSOSearchOrderAJAX = {"ajaxurl":"'.admin_url('admin-ajax.php').'","nonce":"'.wp_create_nonce('search_order').'"};';

	wp_add_inline_script( 'cla-workstation-order-search-script', $script_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'cla_order_search_scripts', 1 );

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
 * Get the dropdown for program posts.
 */
function get_program_dropdown() {

	$program_id = cla_get_selected_program_id();
	$selected   = $program_id;

	// Get values to filter by.
	$args    = array(
		'post_type'      => 'program',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	);
	$results = get_posts( $args );

	// Determine the index of the default option.
	if ( 0 === $selected || ! in_array( $selected, $results ) ) {
		$default_index = 0;
	} else {
		$default_index = 1 + array_search( $selected, $results );
	}

	// Build a custom dropdown list of values to filter by.
	$output = '<div class="search-filter-wrap"><select id="search-program" class="btn-secondary" name="search-program" data-default-value="' . $default_index . '">';
	$output .= '<option value="0">' . __( 'All Programs', 'cla-workstation-order' ) . '</option>';
	foreach( $results as $program ) {
		if ( ! empty( $program ) ) {
			$select = ($program === $selected) ? ' selected="selected"':'';
			$label = get_post_meta( $program, 'prefix', true );
			if ( empty( $label ) ) {
				$label = get_the_title( $program );
			}
			$output .= '<option value="' . $program . '"' . $select . '>' . $label . '</option>';
		}
	}
	$output .= '</select></div>';

	return $output;

}

/**
 * Get post status dropdown.
 */
function get_status_dropdown() {

	if ( isset( $_REQUEST['status'] ) ) {
	  $selected = $_REQUEST['status'];
	} else {
		$selected = 'any';
	}

	$options = array(
		'any'             => 'All Status',
		'action_required' => 'Action Required',
		'returned'        => 'Returned',
		'publish'         => 'Completed',
	);

	$output = '<div class="search-filter-wrap"><select id="search-status" class="btn-secondary" name="search-status">';

	foreach ($options as $key => $value) {
		$select = ($key === $selected) ? ' selected="selected"':'';
		$output .= "<option value=\"{$key}\"{$select}>{$value}</option>";
	}

	$output .= '</select></div>';

	return $output;
}

add_action( 'genesis_before_loop', 'cla_before_loop' );
function cla_before_loop(){

	$permalink        = get_permalink();
	$program_dropdown = get_program_dropdown();
	$status_dropdown  = get_status_dropdown();

	$output = '<div class="grid-x grid-margin-x">';
	$output .= '<div class="order-sidebar cell small-12 medium-2">';
	$output .= '<a class="btn btn-outline-success" href="/new-order/">New Order</a>';
	$output .= '<div id="order-search-filters" class="search-filters"><form method="post" enctype="multipart/form-data" id="cla_search_order_form" action="' . $permalink . '">';
	$output .= $program_dropdown;
	$output .= $status_dropdown;
	$output .= '<button type="button" class="btn btn-primary" id="reset-button">Reset Filter</button>';
	$output .= '<div class="ajax-response"></div></form></div>';
	$output .= '<div class="card card-body">';
	$output .= '<div class="grid-x grid-margin-x align-middle">';
	$output .= '<div class="cell shrink"><span class="status-color-key action_required"></span></div><div class="cell auto">Action Required</div>';
	$output .= '</div>';
	$output .= '<div class="grid-x grid-margin-x align-middle">';
	$output .= '<div class="cell shrink"><span class="status-color-key returned"></span></div><div class="cell auto">Returned</div>';
	$output .= '</div>';
	$output .= '<div class="grid-x grid-margin-x align-middle">';
	$output .= '<div class="cell shrink"><span class="status-color-key completed"></span></div><div class="cell auto">Completed</div>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '</div>';

	echo $output;

}

add_action( 'genesis_after_loop', 'cla_after_loop' );
function cla_after_loop(){

	echo '</div>';

}

add_filter( 'genesis_attr_entry', 'cla_entry_atts' );
function cla_entry_atts( $attributes ){
	$attributes['class'] .= ' order-search-results cell small-12 medium-10';
	return $attributes;
}

function get_order_output( $post_id ) {
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
	if ( current_user_can( 'administrator' ) || current_user_can( 'wso_admin' ) || current_user_can( 'wso_logistics' ) ) {
		if ( 'publish' !== $status ) {
			$output .= '<a class="btn btn-sm btn-outline-yellow" title="Edit this order" href="' . $permalink . '"><span class="dashicons dashicons-welcome-write-blog"></span></a>';
		}
		$output .= '<button class="cla-delete-order btn btn-sm btn-outline-red" data-post-id="' . $post_id . '" data-clear-container="true" type="button" title="Delete this order"><span class="dashicons dashicons-trash"></span></button>';
	}
	$output .= "</td>";
	$output .= "</tr>";
	return $output;
}

add_action( 'the_content', 'cla_my_orders' );
function cla_my_orders() {

	$order_args = array(
		'post_type'      => 'wsorder',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	// Determine the program ID.
	$program_id = cla_get_selected_program_id();

	if ( 0 !== $program_id ) {
		$order_args['meta_key']   = 'program';
		$order_args['meta_value'] = $program_id;
	}

	// Determine the post status.
	if ( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'publish', 'returned', 'action_required' ) ) ) {
		$order_args['post_status'] = $_REQUEST['status'];
	} else {
		$order_args['post_status'] = array( 'publish', 'returned', 'action_required' );
	}

	// Output post information.
	$posts  = get_posts( $order_args );
	$output = '<table><thead class="thead-light"><tr><th style="width: 50px"></th><th scope="col">Ordered By</th><th scope="col">Ordered At</th><th scope="col">Amount</th><th scope="col">IT</th><th scope="col">Business</th><th scope="col">Logistics</th><th></th></tr></thead><tbody id="ajax-results">';
	foreach ( $posts as $key => $post_id ) {
		$output .= get_order_output( $post_id );
	}
	$output .= '</tbody></table>';
	echo wp_kses_post( $output );

}

genesis();
