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
function cla_workstation_order_form_styles() {

	wp_register_style(
		'cla-workstation-order-form-template',
		CLA_WORKSTATION_ORDER_DIR_URL . 'css/order-form-template.css',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'css/order-form-template.css' ),
		'screen'
	);

	wp_enqueue_style( 'cla-workstation-order-form-template' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_styles', 1 );

/**
 * Registers and enqueues template scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_form_scripts() {

	wp_register_script(
		'cla-workstation-order-form-scripts',
		CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-form.js',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-form.js' ),
		'screen'
	);

	wp_enqueue_script( 'cla-workstation-order-form-scripts' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_scripts', 1 );

get_header();

function get_product_post_objects_for_program_by_user_dept() {

	// Get current user and user ID.
	$user    = wp_get_current_user();
	$user_id = $user->get( 'ID' );

	// Get user's department.
	$user_department_post    = get_field( 'department', "user_{$user_id}" );
	$user_department_post_id = $user_department_post->ID;

	// Retrieve products for the current program year.
	$current_program_post      = get_field( 'current_program', 'option' );
	$current_program_id        = $current_program_post->ID;
	$current_program_post_meta = get_post_meta( $current_program_id );

	// Filter out hidden products for department.
	$hidden_products = get_post_meta( $user_department_post_id, 'hidden_products', true );

	// Find the posts.
	$args          = array(
		'post_type'  => 'product',
		'nopaging'   => true,
		'meta_key'   => 'program', //phpcs:ignore
		'meta_value' => $current_program_id, //phpcs:ignore
		'post__not_in' => $hidden_products
	);
	$products      = new \WP_Query( $args );
	$product_posts = $products->posts;

	// echo '<pre>';
	// print_r($hidden_products);
	// echo '</pre>';
	// foreach ( $product_posts as $key => $post ) {
	// 	// unset posts.
	// 	if ( in_array( $post->ID, $hidden_products, true ) ) {
	// 		unset( $product_posts[ $key ] );
	// 	}
	// }
	// $product_posts = array_values( $product_posts );

	return $product_posts;

}

function cla_get_products() {

	/**
	 * Display products.
	 */
	$product_posts = get_product_post_objects_for_program_by_user_dept();

	// Output posts.
	$output = '<div id="products"><div class="grid-x grid-margin-x grid-margin-y">';
	foreach ( $product_posts as $key => $post ) {
		$price = (int) get_post_meta( $post->ID, 'price', true );
		$price = number_format( $price, 2, '.', ',' );
		$thumbnail = get_the_post_thumbnail( $post, 'post-thumbnail', 'style=""' );
		$thumbnail = preg_replace( '/ style="[^"]*"/', '', $thumbnail );
		$output .= sprintf(
			'<div id="product-%s" class="card cell small-12 medium-3"><h5 class="card-header"><a href="%s">%s</a></h5><div class="card-body">%s<p>%s</p></div><div class="card-footer"><div class="grid-x grid-padding-x grid-padding-y"><div class="cell shrink align-left"><button data-product-id="%s" data-product-name="%s" data-product-price="$%s" type="button" class="add-product">Add</button></div><div class="cell auto align-right price price-%s">$%s</div></div></div></div>',
			$post->ID,
			get_permalink($post->ID),
			$post->post_title,
			$thumbnail,
			get_post_meta( $post->ID, 'description', true ),
			$post->ID,
			$post->post_title,
			$price,
			$post->ID,
			$price
		);
	}
	$output .= '</div></div>';

	$return = wp_kses_post( $output );
	return $output;

}

?><div id="primary" class="content-area"><main id="main" class="site-main">
<h1><?php the_title(); ?></h1>
					   <?php
						the_content();

						if ( isset( $_POST['cla_submit'] ) ) {

							$output_form = false;

							if (
								isset( $_POST['the_superfluous_nonceity_n8me'] )
								&& wp_verify_nonce( $_POST['the_superfluous_nonceity_n8me'], 'verify_order_form_nonce8' )
							) {

								echo '<pre>';
								print_r( $_POST );
								echo '</pre>';

								?>
		<div id="respond">Validated.</div>
								<?php

							}
						} else {

							$output_form = true;

						}

						if ( $output_form ) {

							// Get current user and user ID.
							$user      = wp_get_current_user();
							$user_id   = $user->get( 'ID' );
							$user_meta = get_user_meta( $user_id );

							// Get user's department.
							$user_department_post    = get_field( 'department', "user_{$user_id}" );
							$user_department_post_id = $user_department_post->ID;

							// Get current program meta.
							$current_program_post      = get_field( 'current_program', 'option' );
							$current_program_id        = $current_program_post->ID;
							$current_program_post_meta = get_post_meta( $current_program_id, '', true );

							/**
							 * Get current user info
							 */
							$order_info  = sprintf(
								'<div id="cla_order_info"><h2>Order Information</h2><p>Please verify your information below. If you need to update anything, please <a href="%s">update your info</a>.</p><dl>',
								get_edit_profile_url()
							);
							$order_info .= '<dt>First Name</dt><dd>' . $user_meta['first_name'][0] . '</dd>';
							$order_info .= '<dt>Last Name</dt><dd>' . $user_meta['last_name'][0] . '</dd>';
							$order_info .= '<dt>Email Address</dt><dd>' . $user->data->user_email . '</dd>';
							$order_info .= '<dt>Department</dt><dd>' . $user_department_post->post_title . '</dd>';
							$order_info .= '</dl></div>';

							/**
							 * Additional Funding
							 */
							$additional_funding  = "<div id=\"cla_add_funding\"><h2>Additional Funding</h2><p>Enter any additional funds that you would like to contribute on top of your base allowance.
Your cart calculations will include this amount. It's also required if your cart total exceeds the base allowance.</p>";
							$additional_funding .= '<div><label for="cla_contribution_amount">Contribution Amount</label> <div class="grid-x"><div class="cell shrink dollar-field">$</div><div class="cell auto"><input id="cla_contribution_amount" name="cla_contribution_amount" type="number" min="0" step="any" required /></div></div></div>';
							$additional_funding .= '<div><label for="cla_account_number">Account</label> <input id="cla_account_number" name="cla_account_number" type="text" required /><small><br>Research, Bursary, etc. or the Acct #</small></div>';
							$additional_funding .= '</div>';

							/**
							 * Get dropdown of users
							 */

							// Get current program IT Reps and Business Admins assigned to current user's department.
							$department_ids = array();
							foreach ( $current_program_post_meta as $key => $value ) {
								$value = $value[0];
								if ( false !== strpos( $key, '_department_post_id' ) && false === strpos( $value, 'field_' ) ) {
									$department_ids[ $key ] = (int) $value;
								}
							}
							$dept_key = '';
							foreach ( $department_ids as $key => $value ) {
								if ( $user_department_post_id === $value ) {
									$dept_key = str_replace( '_department_post_id', '', $key );
								}
							}

							$it_rep_ids         = get_post_meta( $current_program_id, "{$dept_key}_it_reps", true );
							$business_admin_ids = get_post_meta( $current_program_id, "{$dept_key}_business_admins", true );

							$it_rep_args     = array(
								'echo'    => false,
								'include' => $it_rep_ids,
								'name'    => 'cla_it_rep_id',
							);
							$it_rep_dropdown = wp_dropdown_users( $it_rep_args );

							/**
							 * Purchased product IDs field.
							 */
							$purchase_field = '<input type="hidden" id="cla_product_ids" name="cla_product_ids" required />';

							/**
							 * Total Purchase price field.
							 */
							$total_purchase_field = '<input type="hidden" id="cla_total_purchase" name="cla_total_purchase" value="0" />';

							/**
							 * Purchased product list view.
							 */
							$list_purchases = '<div id="list_purchases"></div>';

							/**
							 * Allocation and allocation threshold.
							 */
							$allocation = $current_program_post_meta['allocation'][0];
							$allocation_threshold = $current_program_post_meta['threshold'][0];

							/**
							 * Submit button.
							 */
							$submit_button = '<input type="submit" name="cla_submit" value="Submit Order">';

							/**
							 * Nonce field.
							 */
							$nonce_field = wp_nonce_field( 'verify_order_form_nonce8', 'the_superfluous_nonceity_n8me', true, false );

							/**
							 * Form
							 */
							$search_form  = "<div id=\"cla_order_form_wrap\">
	<form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_form\" action=\"%s\">
		<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">%s</div><div class=\"cell medium-6\">%s</div></div><hr />
		<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">
				<div><label for=\"cla_it_rep_id\">IT Representative</label> %s<br><small>To whom in IT should your order be sent to for confirmation?</small></div>
				<div class=\"grid-x\"><div class=\"building-name cell medium-6\"><label for=\"cla_building_name\">Building</label> <input id=\"cla_building_name\" name=\"cla_building_name\" type=\"text\" required /><br><small>What building is your primary office located in?</small></div><div class=\"room-number cell medium-6\">
					<label for=\"cla_room_number\">Room Number</label> <input id=\"cla_room_number\" name=\"cla_room_number\" type=\"text\" required /><br><small>What is the room number of your primary office?</small></div>
				</div>
				<div><label for=\"cla_current_asset_number\">Current Workstation Asset Number</label> <input id=\"cla_current_asset_number\" name=\"cla_current_asset_number\" type=\"text\" /><br><small>What is the TAMU asset number of your current workstation computer? Example: 021500123456</small></div>
				<div class=\"nobreak\"><input id=\"cla_no_computer_yet\" name=\"cla_no_computer_yet\" type=\"checkbox\" /><label for=\"cla_no_computer_yet\">I don't have a computer yet.</label></div>
				</div><div class=\"cell medium-6\">
				<div><label for=\"cla_order_comments\">Order Comment</label> <textarea id=\"cla_order_comment\" name=\"cla_order_comment\" rows=\"5\" required></textarea><br><small>Any additional information that would be helpful to pass along.
</small></div>
			</div>
		</div>
		<div class=\"grid-x grid-margin-x\">
			<div class=\"cell small-12 medium-auto\">%s</div>
			<div id=\"shopping_cart\" class=\"cell small-12 medium-3\"><h3>Shopping Cart</h3>
				%s%s%s<hr />
				<div class=\"grid-x\">
					<div class=\"cell shrink\">Products Total:</div>
					<div id=\"products_total\" class=\"cell auto align-right\">$0.00</div>
				</div>
				<div id=\"allocation-data\" class=\"grid-x hidden\" data-allocation=\"%s\" data-allocation-threshold=\"%s\">
					<div class=\"cell shrink\">Contribution Needed:</div>
					<div id=\"contribution_needed\" class=\"cell auto align-right\">$0.00</div>
				</div>
				<hr />
				<div>%s%s</div>
			</div>
		</div>
	</form>
</div>";

							$product_list = cla_get_products();
							$search_form  = sprintf( $search_form, get_permalink(), $order_info, $additional_funding, $it_rep_dropdown, $product_list, $purchase_field, $total_purchase_field, $list_purchases, $allocation, $allocation_threshold, $submit_button, $nonce_field );
							$allowed_html = array(
								'form'     => array(
									'method'  => array(),
									'enctype' => array(),
									'id'      => array(),
									'action'  => array(),
								),
								'select'   => array(
									'name'  => array(),
									'id'    => array(),
									'class' => array(),
								),
								'option'   => array(
									'value' => array(),
									'id'    => array(),
									'name'  => array(),
								),
								'label'    => array(
									'for' => array(),
								),
								'input'    => array(
									'id'       => array(),
									'name'     => array(),
									'type'     => array(),
									'value'    => array(),
									'required' => array(),
								),
								'div'      => array(
									'class'                     => array(),
									'id'                        => array(),
									'data-allocation'           => array(),
									'data-allocation-threshold' => array(),
								),
								'textarea' => array(
									'id'       => array(),
									'name'     => array(),
									'rows'     => array(),
									'required' => array(),
								),
								'button'   => array(
									'type'               => array(),
									'class'              => array(),
									'data-product-id'    => array(),
									'data-product-name'  => array(),
									'data-product-price' => array(),
								),
								'small'    => array(),
								'dl'       => array(),
								'dt'       => array(),
								'dd'       => array(),
								'h2'       => array(),
								'h3'       => array(),
								'h5'       => array(
									'class' => array(),
								),
								'hr'       => array(),
								'a'        => array(
									'class' => array(),
									'href' => array(),
								),
								'img'      => array(
									'src'     => array(),
									'alt'     => array(),
									'width'   => array(),
									'height'  => array(),
									'class'   => array(),
									'loading' => array(),
								),
							);
							echo wp_kses( $search_form, $allowed_html );
							// echo $search_form;
						}
						?>
</main></div>
<?php

get_footer();
