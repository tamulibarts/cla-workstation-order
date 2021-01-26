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

get_header();

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
							$order_info .= '<dt>First Name:</dt><dd>' . $user_meta['first_name'][0] . '</dd>';
							$order_info .= '<dt>Last Name:</dt><dd>' . $user_meta['last_name'][0] . '</dd>';
							$order_info .= '<dt>Email Address:</dt><dd>' . $user->data->user_email . '</dd>';
							$order_info .= '<dt>Department:</dt><dd>' . $user_department_post->post_title . '</dd>';
							$order_info .= '</dl></div>';

							/**
							 * Additional Funding
							 */
							$additional_funding  = "<div id=\"cla_add_funding\"><h2>Additional Funding</h2><p>Enter any additional funds that you would like to contribute on top of your base allowance.
Your cart calculations will include this amount. It's also required if your cart total exceeds the base allowance.</p>";
							$additional_funding .= '<div><label for="cla_contribution_amount">Contribution Amount</label> <input id="cla_contribution_amount" name="cla_contribution_amount" type="number" min="0" step="any" /></div>';
							$additional_funding .= '<div><label for="cla_account_number">Account</label> <input id="cla_account_number" name="cla_account_number" type="text" /><small><br>Research, Bursary, etc. or the Acct #</small></div>';
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
		<div class=\"row\"><div class=\"col\">%s</div><div class=\"col\">%s</div></div><hr />
		<div class=\"row\"><div class=\"col\">
				<div><label for=\"cla_it_rep_id\">IT Representative</label> %s<br><small>To whom in IT should your order be sent to for confirmation?</small></div>
				<div class=\"row\"><div class=\"col\"><div><label for=\"cla_building_name\">Building</label> <input id=\"cla_building_name\" name=\"cla_building_name\" type=\"text\" /><br><small>What building is your primary office located in?</small></div></div><div class=\"col\">
					<div><label for=\"cla_room_number\">Room Number</label> <input id=\"cla_room_number\" name=\"cla_room_number\" type=\"text\" /><br><small>What is the room number of your primary office?</small></div></div>
				</div>
				<div><label for=\"cla_current_asset_number\">Current Workstation Asset Number</label> <input id=\"cla_current_asset_number\" name=\"cla_current_asset_number\" type=\"text\" /><br><small>What is the TAMU asset number of your current workstation computer? Example: 021500123456</small></div>
				<div class=\"nobreak\"><input id=\"cla_no_computer_yet\" name=\"cla_no_computer_yet\" type=\"checkbox\" /><label for=\"cla_no_computer_yet\">I don't have a computer yet.</label></div>
				</div><div class=\"col\">
				<div><label for=\"cla_order_comments\">Order Comment</label> <textarea id=\"cla_contribution_amount\" name=\"cla_contribution_amount\" rows=\"5\"></textarea><br><small>Any additional information that would be helpful to pass along.
</small></div>%s%s
			</div>
		</div>
	</form>
</div>";
							$search_form  = sprintf( $search_form, get_permalink(), $order_info, $additional_funding, $it_rep_dropdown, $submit_button, $nonce_field );
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
									'id'   => array(),
									'name' => array(),
									'type' => array(),
								),
								'div'      => array(
									'class' => array(),
									'id'    => array(),
								),
								'textarea' => array(
									'id'   => array(),
									'name' => array(),
									'rows' => array(),
								),
								'small'    => array(),
								'dl'       => array(),
								'dt'       => array(),
								'dd'       => array(),
								'h2'       => array(),
								'hr'       => array(),
							);
							echo wp_kses( $search_form, $allowed_html );
						}

						/**
						 * Display products.
						 */

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

						$args          = array(
							'post_type'  => 'product',
							'nopaging'   => true,
							'meta_key'   => 'program', //phpcs:ignore
							'meta_value' => $current_program_id, //phpcs:ignore
						);
						$products      = new \WP_Query( $args );
						$product_posts = $products->posts;

						// Filter out hidden products for department.
						$hidden_products = get_post_meta( $user_department_post_id, 'hidden_products', true );
						foreach ( $product_posts as $key => $post ) {
							// unset posts.
							if ( in_array( $post->ID, $hidden_products, true ) ) {
								unset( $product_posts[ $key ] );
							}
						}
						$product_posts = array_values( $product_posts );

						// Output posts.
						$output = '';
						foreach ( $product_posts as $key => $post ) {
							$price = (int) get_post_meta( $post->ID, 'price', true );
							$price = number_format( $price, 2, '.', ',' );
							$output .= sprintf(
								'<div class="card"><a title="%s" href="%s">%s</a>%s<p>%s</p><div>$%s</div></div>',
								$post->ID,
								get_permalink($post->ID),
								$post->post_title,
								get_the_post_thumbnail( $post ),
								get_post_meta( $post->ID, 'description', true ),
								$price
							);
						}

						$return = wp_kses_post( $output );
						echo $return;
						?>
</main></div>
<?php

get_footer();
