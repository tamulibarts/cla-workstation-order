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
 * Remove entry meta.
 */
remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );

/**
 * Modify post title.
 */
add_filter( 'the_title', function( $title ) {
	$post_id = get_the_ID();
	$post_type = get_post_type( $post_id );
	if ( 'wsorder' === $post_type ) {
		$title = 'Order ' . $title . ' Details';
	}
	return $title;
});

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
		true
	);

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cla-workstation-order-form-scripts' );
	// Identify if the page is an order.
	$post_id          = get_the_ID();
	$post_type        = get_post_type( $post_id );
	$is_order         = 'wsorder' === $post_type ? 'true' : 'false';
	$script_variables = "var cla_is_order = $is_order;";
	$script_variables .= "
";
	$script_variables .= 'var cla_status = "' . get_post_status( $post_id ) . '";';
	$script_variables .= "
";
	// Include admin ajax URL and nonce.
	$script_variables .= 'var WSOAjax = {"ajaxurl":"'.admin_url('admin-ajax.php').'","nonce":"'.wp_create_nonce('make_order').'"};';
	// Include products and prices.
	require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
	$cla_form_helper      = new \CLA_Workstation_Order\Order_Form_Helper();
	$products_and_bundles = $cla_form_helper->get_product_post_objects_for_program_by_user_dept();
	foreach ( $products_and_bundles as $post_id => $post_title ) {
		$products_and_bundles[$post_id] = get_post_meta( $post_id, 'price', true );
	}
	$script_variables .= "
";
	$script_variables .= 'var cla_product_prices = ' . json_encode($products_and_bundles);
	// Allocation data.
	$maybe_program_post = get_post_meta( get_the_ID(), 'program', true );
	if ( ! empty( $maybe_program_post ) ) {
		$program_id = (int) $maybe_program_post;
	} else {
		$program_post = get_field( 'current_program', 'option' );
		$program_id   = $program_post->ID;
	}
	$allocation           = (float) get_post_meta( $program_id, 'allocation', true );
	$allocation_threshold = (float) get_post_meta( $program_id, 'threshold', true );
	$script_variables .= "
";
	$script_variables .= "var cla_allocation = {$allocation};";
	$script_variables .= "
";
	$script_variables .= "var cla_threshold = {$allocation_threshold};";

	wp_add_inline_script( 'cla-workstation-order-form-scripts', $script_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_scripts', 1 );

/**
 * Render the order form.
 *
 * @return void
 */
function cla_render_order_form( $content ) {

	if ( ! is_user_logged_in() ) {

		echo 'You must be logged in to view this page.';

	} else {

		global $post;

		$maybe_order_author_id = (int) get_post_meta( $post->ID, 'order_author', true );
		if ( 'wsorder' === $post->post_type && ! empty( $maybe_order_author_id ) ) {
			$user = get_user_by( 'id', $maybe_order_author_id );
		} else {
			$user = wp_get_current_user();
		}
		$user_id   = $user->get( 'ID' );
		$user_meta = get_user_meta( $user_id );

		// Get user's department.
		$user_department_post    = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id = $user_department_post->ID;

		// Get current program meta.
		$maybe_program_post = get_post_meta( $post->ID, 'program', true );
		if ( 'wsorder' === $post->post_type && ! empty( $maybe_program_post ) ) {
			$program_post = get_post( $maybe_program_post );
		} else {
			$program_post = get_field( 'current_program', 'option' );
		}
		$program_id        = $program_post->ID;
		$program_post_meta = get_post_meta( $program_id, '', true );

		/**
		 * Get current user info
		 */
		$order_info  = '<div id="cla_order_info"><h2>Order Information</h2><p>Please verify your information below. If you need to update anything, please <a href="/my-account/">update your info</a>.</p><dl>';
		$order_info .= '<dt>First Name</dt><dd>' . $user_meta['first_name'][0] . '</dd>';
		$order_info .= '<dt>Last Name</dt><dd>' . $user_meta['last_name'][0] . '</dd>';
		$order_info .= '<dt>Email Address</dt><dd>' . $user->data->user_email . '</dd>';
		$order_info .= '<dt>Department</dt><dd>' . $user_department_post->post_title . '</dd>';
		$order_info .= '</dl></div>';

		/**
		 * Additional Funding
		 */
		$contribution_amount = get_post_meta( $post->ID, 'contribution_amount', true );
		if ( empty( $contribution_amount ) ) {
			$contribution_amount = '0.0';
		}
		$contribution_account = get_post_meta( $post->ID, 'contribution_account', true );
		if ( empty( $contribution_account ) ) {
			$contribution_account = '';
		}
		$additional_funding  = '<div id="cla_add_funding"><h3>Additional Funding</h3><p>Enter any additional funds that you would like to contribute on top of your base allowance.<br>Your cart calculations will include this amount. It\'s also required if your cart total exceeds the base allowance.</p>';
		$additional_funding .= '<div class="form-group"><label for="cla_contribution_amount">Contribution Amount</label> <div class="grid-x"><div class="cell shrink dollar-field">$</div><div class="cell auto"><input id="cla_contribution_amount" name="cla_contribution_amount" type="number" min="0" step="0.01" value="' . $contribution_amount . '" step="any" /></div></div></div>';
		$additional_funding .= '<div class="form-group"><label for="cla_account_number">Account</label> <input id="cla_account_number" name="cla_account_number" type="text" value="' . $contribution_account . '"/><small><br>Research, Bursary, etc. or the Acct #</small></div>';
		$additional_funding .= '</div>';

		/**
		 * Get dropdown of users
		 */
		$it_rep_ids = get_field( 'field_6048e8d2b575a', $post->ID );
		if ( empty( $affiliated_it_reps ) ) {
			// Get current program IT Reps assigned to current user's department.
			$department_ids = array();
			foreach ( $program_post_meta as $key => $value ) {
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

			$it_rep_ids = get_post_meta( $program_id, "{$dept_key}_it_reps", true );
		}

		$it_rep_args = array(
			'echo'    => false,
			'include' => $it_rep_ids,
			'name'    => 'cla_it_rep_id',
		);
		$selected_rep = (int) get_post_meta( $post->ID, 'it_rep_status_it_rep', true );
		if ( ! empty( $selected_rep ) ) {
			$it_rep_args['selected'] = $selected_rep;
			$it_rep_args['include_selected'] = true;
		}
		$it_rep_dropdown = wp_dropdown_users( $it_rep_args );
		// Handle when no IT Representatives are found.
		if ( false === strpos( $it_rep_dropdown, '<option' ) ) {
			$empty_option    = '<option value="-1">No IT Representatives are available</option>';
			$it_rep_dropdown = preg_replace( '/(<select[^>]*>)([^<]*)/', '$1$2' . $empty_option, $it_rep_dropdown );
		} else {
			// Add "Select a user" default option to it_rep_dropdown.
			$default_option  = '<option value="-1">Select a representative</option>';
			$it_rep_dropdown = preg_replace( '/(<select[^>]*>)([^<]*)<option/', '$1$2' . $default_option . '<option', $it_rep_dropdown );
		}

		/**
		 * Identify which products or bundles were selected during ordering.
		 */
		$selected_products_and_bundles = get_field( 'selected_products_and_bundles', $post->ID );

		/**
		 * Get the CLA Form Helper class.
		 */
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
		$cla_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();

		/**
		 * Get product categories.
		 */
		$selected_array = ! empty( $selected_products_and_bundles ) ? $selected_products_and_bundles : array();
		$apple_list  = $cla_form_helper->cla_get_products( 'apple', false, $selected_array );
		$pc_list     = $cla_form_helper->cla_get_products( 'pc', false, $selected_array );
		$addons_list = $cla_form_helper->cla_get_products( 'add-on', false, $selected_array );

		/**
		 * Add advanced quote button.
		 */
		$button_add_quote = '<button class="button" type="button" id="cla_add_quote">Add an Advanced Teaching/Research Quote</button>';

		/**
		 * Shopping cart items.
		 */
		$purchase_list_items = '';

		/**
		 * Purchased product IDs field.
		 */
		$selected_pab_value = '';
		if ( !empty( $selected_products_and_bundles ) ) {
			$selected_pab_value = implode( ',', $selected_products_and_bundles );
			foreach ( $selected_products_and_bundles as $key => $pob_post_id ) {
				$pob_price = get_field( 'price', $pob_post_id );
				$cart_price = floatval( $pob_price );
				$cart_price = '$' . number_format( $cart_price, 2, '.', ',' );
				$pob_thumb = get_the_post_thumbnail_url( $pob_post_id );
				$item = '<div class="cart-item shopping-cart-'.$pob_post_id.' grid-x">';
				if ( $pob_thumb ) {
					$item .= '<div class="cell shrink"><img width="50" src="' . $pob_thumb . '"></div>';
				}
				$item .= '<div class="cell auto">' . get_the_title( $pob_post_id ) . '</div>';
				$item .= '<div class="cell shrink align-right bold"><button class="trash trash-product" type="button" data-product-id="'.$pob_post_id.'">Remove product from cart</button>' . $cart_price . '</div>';
				$item .= '</div>';
				$purchase_list_items .= $item;
			}
		}
		$purchase_field = '<input type="hidden" id="cla_product_ids" name="cla_product_ids" value="' . $selected_pab_value . '" />';

		/**
		 * Store number of quotes.
		 */
		$custom_quotes = get_field( 'quotes', $post->ID );
		$quote_count   = 0;
		$quote_html    = '';
		if ( ! empty( $custom_quotes ) ) {
			$quote_count = count( $custom_quotes );
			foreach ( $custom_quotes as $key => $quote ) {
				$file_field = '';
				if ( ! empty( $quote['file'] ) ) {
					$file_field = '<a target="_blank" href="' . $quote['file']['url'] . '">' . $quote['file']['filename'] . '</a>';
				} else {
					$file_field = '<input name="cla_quote_' . $key . '_file" id="cla_quote_' . $key . '_file" class="cla-quote-file" type="file" accept=".pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"/>';
				}
				$quote_html .= '<div class="cla-quote-item grid-x grid-margin-x" data-quote-index="' . $key . '">';
				$quote_html .= '<div class="cell small-12 medium-4"><label for="cla_quote_' . $key . '_name">Name</label><input name="cla_quote_' . $key . '_name" id="cla_quote_' . $key . '_name" class="cla-quote-name" type="text" value="' . $quote['name'] . '" />';
				$quote_html .= '<label for="cla_quote_' . $key . '_price">Price</label><input name="cla_quote_' . $key . '_price" id="cla_quote_' . $key . '_price" class="cla-quote-price" type="number" min="0" step="0.01" value="' . $quote['price'] . '" /></div>';
				$quote_html .= '<div class="cell small-12 medium-4"><label for="cla_quote_' . $key . '_description">Description</label><textarea name="cla_quote_' . $key . '_description" id="cla_quote_' . $key . '_description" class="cla-quote-description" name="cla_quote_' . $key . '_description">' . $quote['description'] . '</textarea></div>';
				$quote_html .= '<div class="cell small-12 medium-auto"><label for="cla_quote_' . $key . '_file">File</label>' . $file_field . '</div>';
				$quote_html .= '<div class="cell small-12 medium-shrink"><button type="button" class="remove" data-quote-index="' . $key . '">Remove this quote item</button></div>';
				$quote_html .= '</div>';

				// Shopping cart item.
				$cart_price = floatval( $quote['price'] );
				$cart_price = '$' . number_format( $cart_price, 2, '.', ',' );
				$item = '<div class="cart-item quote-item quote-item-' . $key . ' grid-x">';
				$item .= '<div class="cell auto">Advanced Teaching/Research Item</div>';
				$item .= '<div class="cell shrink align-right bold"><button class="trash trash-quote" type="button" data-quote-index="' . $key . '">Remove product from cart</button><span class="price">' . $cart_price . '</span></div>';
				$item .= '</div>';
				$purchase_list_items .= $item;
			}
		}
		$count_quotes = '<input type="hidden" id="cla_quote_count" name="cla_quote_count" value="' . $quote_count . '" />';
		$list_quotes  = "<div id=\"list_quotes\">{$quote_html}</div>";

		/**
		 * Purchased product list view.
		 */
		$list_purchases = "<div id=\"list_purchases\">{$purchase_list_items}</div>";

		/**
		 * Order subtotal.
		 */
		$subtotal = '$0.00';
		$subtotal_field = get_field( 'products_subtotal', $post->ID );
		if ( ! empty( $subtotal_field ) ) {
			$subtotal_float = floatval( $subtotal_field );
			$subtotal       = '$' . number_format( $subtotal_float, 2, '.', ',' );
		}

		/**
		 * Submit button.
		 */
		if ( 'wsorder' === $post->post_type ) {
			if ( 'returned' === $post->post_status ) {
				$submit_text = 'Update and Resubmit Order';
			} else {
				$submit_text = 'Update Order';
			}
		} else {
			$submit_text = 'Place Order';
		}
		$submit_button = '<input type="submit" id="cla_submit" name="cla_submit" value="' . $submit_text . '">';

		/**
		 * Nonce field.
		 */
		$nonce_field = wp_nonce_field( 'verify_order_form_nonce8', 'the_superfluous_nonceity_n8me', true, false );

		/**
		 * Building field.
		 */
		$building = get_post_meta( $post->ID, 'building', true );
		if ( empty( $building ) ) {
			$building = '';
		}
		$building_field = "<input id=\"cla_building_name\" name=\"cla_building_name\" type=\"text\" value=\"{$building}\"/>";

		/**
		 * Room number.
		 */
		$room_number = get_post_meta( $post->ID, 'office_location', true );
		if ( empty( $room_number ) ) {
			$room_number = '';
		}
		$room_number_field = "<input id=\"cla_room_number\" name=\"cla_room_number\" type=\"text\" value=\"{$room_number}\"/>";

		/**
		 * Asset number.
		 */
		$asset_number = get_post_meta( $post->ID, 'current_asset', true );
		if ( empty( $asset_number ) ) {
			$asset_number = '';
		}
		$asset_number_field = "<input id=\"cla_current_asset_number\" name=\"cla_current_asset_number\" type=\"text\" value=\"{$asset_number}\"/>";

		/**
		 * No computer yet.
		 */
		$no_computer = (int) get_post_meta( $post->ID, 'i_dont_have_a_computer_yet', true );
		if ( 1 === $no_computer ) {
			$no_computer = ' checked';
		} else {
			$no_computer = '';
		}
		$no_computer_field = "<input id=\"cla_no_computer_yet\" name=\"cla_no_computer_yet\" type=\"checkbox\"$no_computer />";

		/**
		 * Order comment.
		 */
		$order_comment = get_post_meta( $post->ID, 'order_comment', true );
		if ( empty( $order_comment ) ) {
			$order_comment = '';
		}
		$order_comment_field = "<textarea id=\"cla_order_comments\" name=\"cla_order_comments\" rows=\"5\">{$order_comment}</textarea>";

		/**
		 * Form
		 */
		$permalink = get_permalink();
		$order_form = "<div id=\"cla_order_form_wrap\">
<form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_form\" action=\"{$permalink}\">
<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">{$order_info}</div><div class=\"cell medium-6\">{$additional_funding}</div></div><div class=\"grid-x grid-margin-x\"><div class=\"cell small-12\"><hr /></div></div>
<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">
<div><label for=\"cla_it_rep_id\">IT Representative *</label> {$it_rep_dropdown}<br><small>To whom in IT should your order be sent to for confirmation?</small></div>
<div class=\"grid-x grid-margin-x\"><div class=\"building-name cell medium-6\"><label for=\"cla_building_name\">Building *</label> {$building_field}<br><small>What building is your primary office located in?</small></div><div class=\"room-number cell medium-6\">
<label for=\"cla_room_number\">Room Number *</label> {$room_number_field}<br><small>What is the room number of your primary office?</small></div>
</div>
<div><label for=\"cla_current_asset_number\">Current Workstation Asset Number *</label> {$asset_number_field}<br><small>What is the TAMU asset number of your current workstation computer? Example: 021500123456</small></div>
<div class=\"nobreak\">{$no_computer_field}<label for=\"cla_no_computer_yet\">I don't have a computer yet.</label></div>
</div><div class=\"cell medium-6\">
<div><label for=\"cla_order_comments\">Order Comment</label> {$order_comment_field}<br><small>Any additional information that would be helpful to pass along.
</small></div>
</div>
</div>
<div class=\"grid-x grid-margin-x\">
<div id=\"products\" class=\"cell small-12 medium-auto\">
<div class=\"products-apple toggle\"><h3><a class=\"btn\" href=\"#\">Apple</a></h3>{$apple_list}</div>
<div class=\"products-pc toggle\"><h3><a class=\"btn\" href=\"#\">PC</a></h3>{$pc_list}</div>
<div class=\"products-addons toggle\"><h3><a class=\"btn\" href=\"#\">Add Ons</a></h3>{$addons_list}</div>
<div class=\"products-custom-quote toggle\"><h3><a class=\"btn\" href=\"#\">Advanced Teaching/Research Quote</a></h3><div class=\"products\">{$button_add_quote}{$list_quotes}</div></div>
</div>
<div id=\"shopping_cart\" class=\"cell small-12 medium-3\"><h3>Shopping Cart</h3>
{$count_quotes}{$purchase_field}{$list_purchases}<hr />
<div class=\"grid-x\">
<div class=\"cell shrink\">Products Total:</div>
<div id=\"products_total\" class=\"cell auto align-right\">{$subtotal}</div>
</div>
<div id=\"allocation-data\" class=\"hidden\">
<div class=\"grid-x\">
	<div id=\"contribution_needed_label\" class=\"cell shrink\">Contribution Needed:</div>
	<div id=\"contribution_needed\" class=\"cell auto align-right\">$0.00</div>
</div>
</div>
<hr />
<div id=\"order-message\"></div>
<div>{$submit_button}{$nonce_field}</div>
<div class=\"flagged-instructions hidden\">Please address the flagged items.</div>
</div>
</div>
</form>
</div>";

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
				'selected' => array(),
			),
			'label'    => array(
				'for' => array(),
			),
			'input'    => array(
				'id'       => array(),
				'name'     => array(),
				'type'     => array(),
				'value'    => array(),
				'disabled' => array(),
				'checked'  => array(),
				'files'    => array(),
				'class'    => array(),
				'min'      => array(),
				'step'     => array(),
				'accept'   => array(),
			),
			'div'      => array(
				'class'            => array(),
				'id'               => array(),
				'data-quote-index' => array(),
			),
			'textarea' => array(
				'id'    => array(),
				'name'  => array(),
				'rows'  => array(),
				'class' => array(),
			),
			'button'   => array(
				'type'               => array(),
				'id'                 => array(),
				'class'              => array(),
				'disabled'           => array(),
				'data-product-id'    => array(),
				'data-product-name'  => array(),
				'data-quote-index'   => array(),
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
				'class'  => array(),
				'href'   => array(),
				'target' => array(),
			),
			'img'      => array(
				'src'     => array(),
				'alt'     => array(),
				'width'   => array(),
				'height'  => array(),
				'class'   => array(),
				'loading' => array(),
			),
			'ul'       => array(),
			'li'       => array(),
			'p'        => array(),
		);
		return wp_kses( $order_form, $allowed_html );

	}
}
add_filter( 'the_content', 'cla_render_order_form' );

if ( function_exists( 'genesis' ) ) {
	genesis();
}
