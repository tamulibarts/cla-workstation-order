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
function cla_empty_edit_link() {
	return '';
}
add_filter( 'edit_post_link', 'cla_empty_edit_link' );

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

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cla-workstation-order-form-scripts' );
	$ajax_params = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'make_order' ),
	);
	wp_localize_script( 'cla-workstation-order-form-scripts', 'WSOAjax', $ajax_params );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_scripts', 1 );

/**
 * Render the order form.
 *
 * @return void
 */
function cla_render_order_form() {

	if ( ! is_user_logged_in() ) {

		echo 'You must be logged in to view this page.';

	} else {

		/**
		 * Build and output the form.
		 */
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
		$additional_funding  = '<div id="cla_add_funding"><h3>Additional Funding</h3><p>Enter any additional funds that you would like to contribute on top of your base allowance.<br>Your cart calculations will include this amount. It\'s also required if your cart total exceeds the base allowance.</p>';
		$additional_funding .= '<div class="form-group"><label for="cla_contribution_amount">Contribution Amount</label> <div class="grid-x"><div class="cell shrink dollar-field">$</div><div class="cell auto"><input id="cla_contribution_amount" name="cla_contribution_amount" type="number" min="0" value="0.0" step="any" /></div></div></div>';
		$additional_funding .= '<div class="form-group"><label for="cla_account_number">Account</label> <input id="cla_account_number" name="cla_account_number" type="text" /><small><br>Research, Bursary, etc. or the Acct #</small></div>';
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
		 * Get the CLA Form Helper class.
		 */
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
		$cla_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();

		/**
		 * Get product categories.
		 */
		$apple_list  = $cla_form_helper->cla_get_products( 'apple' );
		$pc_list     = $cla_form_helper->cla_get_products( 'pc' );
		$addons_list = $cla_form_helper->cla_get_products( 'add-on' );

		/**
		 * Purchased product IDs field.
		 */
		$purchase_field = '<input type="hidden" id="cla_product_ids" name="cla_product_ids" />';

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
		$allocation           = $current_program_post_meta['allocation'][0];
		$allocation_threshold = $current_program_post_meta['threshold'][0];

		/**
		 * Add advanced quote button.
		 */
		$button_add_quote = '<div class="products"><button class="button" type="button" id="cla_add_quote">Add an Advanced Teaching/Research Quote</button></div>';

		/**
		 * Store number of quotes.
		 */
		$count_quotes = '<input type="hidden" id="cla_quote_count" name="cla_quote_count" value="0" />';

		/**
		 * Submit button.
		 */
		$submit_button = '<input type="submit" id="cla_submit" name="cla_submit" value="Place Order">';

		/**
		 * Nonce field.
		 */
		$nonce_field = wp_nonce_field( 'verify_order_form_nonce8', 'the_superfluous_nonceity_n8me', true, false );

		/**
		 * Form
		 */
		$permalink = get_permalink();
		// $search_form = "{$validation_message}<div id=\"cla_order_form_wrap\">
		$search_form = "<div id=\"cla_order_form_wrap\">
<form method=\"post\" enctype=\"multipart/form-data\" id=\"cla_order_form\" action=\"{$permalink}\">
<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">{$order_info}</div><div class=\"cell medium-6\">{$additional_funding}</div></div><div class=\"grid-x grid-margin-x\"><div class=\"cell small-12\"><hr /></div></div>
<div class=\"grid-x grid-margin-x\"><div class=\"cell medium-6\">
<div><label for=\"cla_it_rep_id\">IT Representative *</label> {$it_rep_dropdown}<br><small>To whom in IT should your order be sent to for confirmation?</small></div>
<div class=\"grid-x grid-margin-x\"><div class=\"building-name cell medium-6\"><label for=\"cla_building_name\">Building *</label> <input id=\"cla_building_name\" name=\"cla_building_name\" type=\"text\" /><br><small>What building is your primary office located in?</small></div><div class=\"room-number cell medium-6\">
<label for=\"cla_room_number\">Room Number *</label> <input id=\"cla_room_number\" name=\"cla_room_number\" type=\"text\" /><br><small>What is the room number of your primary office?</small></div>
</div>
<div><label for=\"cla_current_asset_number\">Current Workstation Asset Number *</label> <input id=\"cla_current_asset_number\" name=\"cla_current_asset_number\" type=\"text\" /><br><small>What is the TAMU asset number of your current workstation computer? Example: 021500123456</small></div>
<div class=\"nobreak\"><input id=\"cla_no_computer_yet\" name=\"cla_no_computer_yet\" type=\"checkbox\" /><label for=\"cla_no_computer_yet\">I don't have a computer yet.</label></div>
</div><div class=\"cell medium-6\">
<div><label for=\"cla_order_comments\">Order Comment *</label> <textarea id=\"cla_order_comments\" name=\"cla_order_comments\" rows=\"5\"></textarea><br><small>Any additional information that would be helpful to pass along.
</small></div>
</div>
</div>
<div class=\"grid-x grid-margin-x\">
<div id=\"products\" class=\"cell small-12 medium-auto\">
<div class=\"products-apple toggle\"><h3><a class=\"btn\" href=\"#\">Apple</a></h3>{$apple_list}</div>
<div class=\"products-pc toggle\"><h3><a class=\"btn\" href=\"#\">PC</a></h3>{$pc_list}</div>
<div class=\"products-addons toggle\"><h3><a class=\"btn\" href=\"#\">Add Ons</a></h3>{$addons_list}</div>
<div class=\"products-custom-quote toggle\"><h3><a class=\"btn\" href=\"#\">Advanced Teaching/Research Quote</a></h3>{$button_add_quote}</div>
</div>
<div id=\"shopping_cart\" class=\"cell small-12 medium-3\"><h3>Shopping Cart</h3>
{$count_quotes}{$purchase_field}{$total_purchase_field}{$list_purchases}<hr />
<div class=\"grid-x\">
<div class=\"cell shrink\">Products Total:</div>
<div id=\"products_total\" class=\"cell auto align-right\">$0.00</div>
</div>
<div id=\"allocation-data\" class=\"hidden\" data-allocation=\"{$allocation}\" data-allocation-threshold=\"{$allocation_threshold}\">
<div class=\"grid-x\">
	<div class=\"cell shrink\">Contribution Needed:</div>
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
			),
			'div'      => array(
				'class'                     => array(),
				'id'                        => array(),
				'data-allocation'           => array(),
				'data-allocation-threshold' => array(),
			),
			'textarea' => array(
				'id'   => array(),
				'name' => array(),
				'rows' => array(),
			),
			'button'   => array(
				'type'               => array(),
				'id'                 => array(),
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
				'href'  => array(),
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
		echo wp_kses( $search_form, $allowed_html );

	}
}
add_action( 'the_content', 'cla_render_order_form' );

genesis();
