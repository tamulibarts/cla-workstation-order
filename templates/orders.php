<?php

add_action( 'genesis_before_loop', 'cla_before_loop' );
function cla_before_loop(){

	$output = '<div class="grid-x grid-margin-x">';
	$output .= '<div class="cell small-12 medium-2">';
	$output .= '<a class="button" href="/new-order/">New Order</a>';
	$output .= '<div><span class="status-color-key action_required"></span>Action Required</div>';
	$output .= '<div><span class="status-color-key returned"></span>Returned</div>';
	$output .= '<div><span class="status-color-key completed"></span>Completed</div>';
	$output .= '<div><span class="status-color-key awaiting_another"></span>Awaiting Another</div>';
	$output .= '</div>';

	echo $output;

}

add_action( 'genesis_after_loop', 'cla_after_loop' );
function cla_after_loop(){

	echo '</div>';

}

add_filter( 'genesis_attr_entry', 'cla_entry_atts' );
function cla_entry_atts( $attributes ){
	$attributes['class'] .= ' cell small-12 medium-10';
	return $attributes;
}

function get_order_output( $post_id ) {
	$post = get_post( $post_id );
	date_default_timezone_set('America/Chicago');
	$creation_time  = strtotime( $post->post_date_gmt.' UTC' );
	$creation_date  = date( 'M j, Y \a\t g:i a', $creation_time );
	$status         = get_post_status( $post_id );
	$permalink      = get_permalink( $post_id );
	$author_id      = (int) get_post_field( 'post_author', $post_id );
	$author         = get_user_by( 'ID', $author_id );
	$author_name    = $author->display_name;
	$author_dept    = get_the_author_meta( 'department', $author_id );
	$dept_name      = get_the_title( $author_dept );
	$subtotal       = get_field( 'products_subtotal', $post_id );
	$it_rep_fields  = get_field( 'it_rep_status', $post_id );
	$it_rep_name    = $it_rep_fields['it_rep']['display_name'];
	$business_admin_fields = get_field( 'business_staff_status', $post_id );
	$business_admin_name   = empty( $business_admin_fields['business_staff'] ) ? '' : $business_admin_fields['business_staff']['display_name'];
	$logistics_fields = get_field( 'it_logistics_status', $post_id );
	echo '<pre>';
	print_r($business_admin_fields);
	echo '</pre>';

	// Combined output.
	$output .= "<tr class=\"status-{$status}\">";
	$output .= "<td class=\"status-indicator status-{$status}\"></td>";
	$output .= "<td><a href=\"{$permalink}\">{$author_name}</a><br>{$dept_name}</td>";
	$output .= "<td>{$creation_date}</td>";
	$output .= "<td>{$subtotal}</td>";
	$output .= "<td>";
	if ( $it_rep_fields['confirmed'] === 1 ) {
		$output .= "<span class=\"badge badge-success\">Confirmed</span>";
	} else {
		$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
	}
	$output .= "<br><small>{$it_rep_name}</small></td>";
	$output .= "<td>";
	if ( empty ( $business_admin_fields['business_staff'] ) ) {
		$output .= "<span class=\"badge badge-light\">Not required</span>";
	} else if ( $business_admin_fields['confirmed'] !== 1 ) {
		$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
	} else {
		$output .= "<span class=\"badge badge-success\">Confirmed</span>";
	}
	$output .= "<br><small>{$business_admin_name}</small></td>";
	$output .= "<td>";
	if ( $logistics_fields['confirmed'] !== 1 ) {
		$output .= "<span class=\"badge badge-light\">Not yet confirmed</span>";
	} else {
		$output .= "<span class=\"badge badge-success\">Confirmed</span>";
	}
	$output .= "</td>";
	$output .= "</tr>";
	return $output;
}

add_action( 'the_content', 'cla_my_orders' );
function cla_my_orders() {

	$args = array(
		'post_type' => 'wsorder',
		'fields'    => 'ids',
	);
	$posts = get_posts( $args );
	$output = '<table><tbody>';
	foreach ($posts as $key => $post_id) {
		$output .= get_order_output( $post_id );
	}
	$output .= '</tbody></table>';
	echo wp_kses_post( $output );

}

genesis();
