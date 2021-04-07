<?php

add_action( 'genesis_before_loop', 'cla_before_loop' );
function cla_before_loop(){

	echo '<div class="grid-x grid-margin-x">';
	echo '<div class="cell small-12 medium-2"><a class="button" href="/new-order/">New Order</a></div>';

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

add_action( 'the_content', 'cla_my_orders' );
function cla_my_orders() {

	$user    = wp_get_current_user();
	$user_id = $user->get( 'ID' );

	// Get accepted orders.
	$accepted_order_args = array(
		'post_type'      => 'wsorder',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	);
	$accepted_orders = get_posts( $accepted_order_args );
	echo '<div class="">';
	echo "<h3>Accepted orders</h3>";
	echo '<table class="table-bordered">';
	echo "<thead><tr><th style=\"width:60%\">Program Year</th><th style=\"width:40%\">Order</th></tr></thead>";
	echo "<tbody>";
	foreach ( $accepted_orders as $opost_id ) {
		$program_id   = get_field( 'program', $opost_id );
		$program_year = get_field( 'fiscal_year', $program_id );
		$order_title  = get_the_title( $opost_id );
		$order_link   = get_edit_post_link( $opost_id );
		echo "<tr><td>$program_year</td><td><a href=\"$order_link\">$order_title</a></td></tr>";
	}
	echo '</tbody></table>';

	// Get pending orders.
	$pending_order_args = array(
		'post_type'      => 'wsorder',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'post_status'    => array( 'action_required' ),
		'orderby'        => 'meta_value_num',
		'meta_key'       => 'order_id',
	);
	$pending_orders = get_posts( $pending_order_args );
	// Sort pending orders by fiscal year in descending order.
	$pending_orders_sorted = array();
	foreach ( $pending_orders as $opost_id ) {
		$program_id  = get_field( 'program', $opost_id );
		$fiscal_year = get_field( 'fiscal_year', $program_id );
		if ( ! array_key_exists( $fiscal_year, $pending_orders_sorted ) ) {
			$pending_orders_sorted[$fiscal_year] = array();
		}
		$pending_orders_sorted[$fiscal_year][] = $opost_id;
	}
	krsort( $pending_orders_sorted );
	// Display orders.
	echo '<div class="">';
	echo '<h3>Pending orders</h3>';
	echo '<table class="table-bordered">';
	echo '<thead><tr><th style="width:60%">Program Year</th><th style="width:40%">Order</th></tr></thead>';
	echo '<tbody>';
	foreach ( $pending_orders_sorted as $fiscal_year => $year_orders ) {
		foreach ( $year_orders as $opost_id ) {
			$order_title  = get_the_title( $opost_id );
			$order_link   = get_edit_post_link( $opost_id );
			echo "<tr><td>$fiscal_year</td><td><a href=\"$order_link\">$order_title</a></td></tr>";
		}
	}
	echo '</tbody></table>';

	// Get returned orders.
	$returned_order_args = array(
		'post_type'      => 'wsorder',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'post_status'    => 'returned',
	);
	$returned_orders = get_posts( $returned_order_args );
	echo '<div class="">';
	echo "<h3>Returned orders</h3>";
	echo '<table class="table-bordered">';
	echo "<thead><tr><th style=\"width:60%\">Program Year</th><th style=\"width:40%\">Order</th></tr></thead>";
	echo "<tbody>";
	foreach ( $returned_orders as $opost_id ) {
		$program_id   = get_field( 'program', $opost_id );
		$program_year = get_field( 'fiscal_year', $program_id );
		$order_title  = get_the_title( $opost_id );
		$order_link   = get_edit_post_link( $opost_id );
		echo "<tr><td>$program_year</td><td><a href=\"$order_link\">$order_title</a></td></tr>";
	}
	echo '</tbody></table>';

	echo '</div></div>';

}

// Remove site footer.
remove_action( 'genesis_footer', 'genesis_footer_markup_open', 5 );
remove_action( 'genesis_footer', 'genesis_do_footer' );
remove_action( 'genesis_footer', 'genesis_footer_markup_close', 15 );

// Customize site footer
add_action( 'genesis_footer', 'cla_custom_footer' );
function cla_custom_footer() { ?>

	<div class="site-footer"><div class="wrap"><p>Copyright &copy; 2021 &bull; <a href="<?php echo home_url(); ?>">CLA Workstation Ordering Application</a></p></div></div>

<?php
}

genesis();
