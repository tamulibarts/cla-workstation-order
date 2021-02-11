<?php
/**
 * The file that defines helper functions for order forms.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-order-form-helper.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * The core plugin class
 *
 * @since 1.0.0
 * @return void
 */
class Order_Form_Helper {

	/**
	 * File name
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Validate file upload.
	 */
	public function validate_file_field( $files ) {

		$return = array(
			'passed' => true,
			'message' => '',
		);

		// No files to validate.
		if ( empty( $files ) ) {
			return $return;
		}

		// Throws a message if no file is selected
		if ( ! $files['cla_quote_0_file']['name'] ) {
			$return['passed'] = false;
			$return['message'] = esc_html__( 'Please choose a file', 'cla-workstation-order-textdomain' );
		}

		// Validate file extension.
		$allowed_extensions = array( 'pdf', 'doc', 'docx' );
		$file_type = wp_check_filetype( $files['cla_quote_0_file']['name'] );
		$file_extension = $file_type['ext'];
		if ( ! in_array( $file_extension, $allowed_extensions ) ) {
			$return['passed'] = false;
			$return['message'] = sprintf(  esc_html__( 'Invalid file extension, only allowed: %s', 'cla-workstation-order-textdomain' ), implode( ', ', $allowed_extensions ) );
		}

		// Validate file size.
		$file_size = $files['cla_quote_0_file']['size'];
		$allowed_file_size = 1024000; // Here we are setting the file size limit
		if ( $file_size >= $allowed_file_size ) {
			$return['passed'] = false;
			$return['message'] = sprintf( esc_html__( 'File size limit exceeded, file size should be smaller than %d KB', 'cla-workstation-order-textdomain' ), $allowed_file_size / 1000 );
		}

		return $return;

	}

	private function get_last_order_id( $program_post_id ) {

		$args              = array(
			'post_type'      => 'wsorder',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'order_id',
					'compare' => 'EXISTS'
				),
				array(
					'key'     => 'program',
					'compare' => '=',
					'value'   => $program_post_id,
				)
			),
		);
		$the_query = new \WP_Query( $args );
		$posts = $the_query->posts;
		if ( ! empty( $posts ) ) {
			$last_wsorder_id = (int) get_post_meta( $posts[0], 'order_id', true );
		} else {
			$last_wsorder_id = 0;
		}

		return $last_wsorder_id;

	}

	private function get_program_business_admin_user_id( $program_id, $user_department_post_id ) {

		// Get users assigned to active user's department for current program, as array.
		$program_meta_keys_departments = array(
			'assign_political_science_department_post_id',
			'assign_sociology_department_post_id',
			'assign_philosophy_humanities_department_post_id',
			'assign_performance_studies_department_post_id',
			'assign_international_studies_department_post_id',
			'assign_history_department_post_id',
			'assign_hispanic_studies_department_post_id',
			'assign_english_department_post_id',
			'assign_economics_department_post_id',
			'assign_communication_department_post_id',
			'assign_anthropology_department_post_id',
			'assign_psychology_department_post_id',
			'assign_dean_department_post_id',
		);
		$current_program_post_meta     = get_post_meta( $program_id );
		$value                         = 0;

		foreach ( $program_meta_keys_departments as $meta_key ) {
			$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
			if ( $user_department_post_id === $assigned_dept ) {
				$base_key   = preg_replace( '/_department_post_id$/', '', $meta_key );
				$meta_value = $current_program_post_meta[ "{$base_key}_business_admins" ];
				if ( gettype( $meta_value ) === 'boolean' ) {
					$value = 0;
				} else {
					$meta_value                   = unserialize( $meta_value[0] );
					$dept_assigned_business_admin = $meta_value[0];
					$value                        = $dept_assigned_business_admin[0];
				}
				break;
			}
		}

		return $value;

	}

	/**
	 * After submission action hook
	 *
	 * @param object $entry The Entry Object that was just created.
	 * @param object $form  The current Form Object.
	 *
	 * @return void;
	 */
	public function create_post( $data ) {

		// Get current user and user ID.
		$user    = wp_get_current_user();
		$user_id = $user->get( 'ID' );

		// Get current program meta.
		$current_program_post      = get_field( 'current_program', 'option' );
		$current_program_id        = $current_program_post->ID;
		$current_program_post_meta = get_post_meta( $current_program_id );
		$current_program_prefix    = $current_program_post_meta['prefix'][0];

		// Get new order post's order ID meta.
		$last_wsorder_id = $this->get_last_order_id( $current_program_id );
		$new_wsorder_id  = $last_wsorder_id + 1;

		// Insert post.
		$postarr = array(
			'post_author'    => $user_id,
			'post_status'    => 'action_required',
			'post_type'      => 'wsorder',
			'comment_status' => 'closed',
			'post_title'     => "{$current_program_prefix}-{$new_wsorder_id}",
			'post_content'   => '',
			'meta_input'     => array(
				'order_id' => $new_wsorder_id,
			),
		);
		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {

			// Failed to generate a new post.
			error_log( $post_id );
			return 0;

		} else {

			// Get user's department.
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$user_department_post_id = $user_department_post->ID;

			// Get users assigned to active user's department for current program, as array.
			$dept_assigned_business_admin = $this->get_program_business_admin_user_id( $current_program_id, $user_department_post_id );

			/**
			 * Save ACF field values.
			 * https://www.advancedcustomfields.com/resources/update_field/
			 */

			// Save program.
			$value = $current_program_id;
			update_field( 'program', $value, $post_id );

			// Save building location.
			$value = $data['cla_building_name'];
			update_field( 'building', $value, $post_id );

			// Save office location.
			$value = $data['cla_room_number'];
			update_field( 'office_location', $value, $post_id );

			// Save contribution amount.
			$value = $data['cla_contribution_amount'];
			update_field( 'contribution_amount', $value, $post_id );

			// Save account number.
			$value = $data['cla_account_number'];
			update_field( 'contribution_account', $value, $post_id );

			// Save order comment.
			$value = $data['cla_order_comments'];
			update_field( 'order_comment', $value, $post_id );

			// Save current asset.
			$value = $data['cla_current_asset_number'];
			update_field( 'current_asset', $value, $post_id );

			// Save product subtotal.
			$value = $data['cla_total_purchase'];
			update_field( 'products_subtotal', $value, $post_id );

			// Save no computer yet field.
			if ( array_key_exists( 'cla_no_computer_yet', $data ) ) {
				$value = $data['cla_no_computer_yet'];
				update_field( 'i_dont_have_a_computer_yet', $value, $post_id );
			}

			// Save department IT Rep.
			// $value = $dept_assigned_users['it_rep'];
			$value = $data['cla_it_rep_id'];
			update_field( 'it_rep_status', array( 'it_rep' => $value ), $post_id );

			// Save department Business Admin.
			$value = $dept_assigned_business_admin === 0 ? '' : $dept_assigned_business_admin;
			update_field( 'business_staff_status', array( 'business_staff' => $value ), $post_id );

			/**
			 * Handle quote fields and file uploads.
			 */
			// These files need to be included as dependencies when on the front end.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			// Let WordPress handle the upload.
			// Remember, 'cla_quote_0_file' is the name of our file input in our form above.
			// Here post_id is 0 because we are not going to attach the media to any post.
			$quote_count  = $data['cla_quote_count'];
			if ( $quote_count > 0 ) {

				$quote_fields = array();
				for ($i=0; $i < $quote_count; $i++) {

					$quote_fields[$i] = array(
						'name'        => $data["cla_quote_{$i}_name"],
						'price'       => $data["cla_quote_{$i}_price"],
						'description' => $data["cla_quote_{$i}_description"],
					);

					// Handle uploading quote file.
					$attachment_id = media_handle_upload( "cla_quote_{$i}_file", 0 );
					if ( is_wp_error( $attachment_id ) ) {
						// There was an error uploading the image.
						error_log($attachment_id->get_error_message());
					} else {
						// Attach file.
						$quote_fields[$i]['file'] = $attachment_id;
					}

				}
				update_field( 'quotes', $quote_fields, $post_id );

			}

			/**
			 * Save product information.
			 */
			$product_post_ids = preg_replace('/^,|,$/', '', $data['cla_product_ids']);
			$product_post_ids = explode( ',', $product_post_ids );
			$product_count    = count( $product_post_ids );
			if ( $product_count > 0 ) {

				$product_fields   = array();
				for ($i=0; $i < $product_count; $i++) {
					$product_fields[$i] = array(
						'sku' => get_field( 'sku', $product_post_ids[$i] ),
						'item' => get_the_title( $product_post_ids[$i] ),
						'price' => get_field( 'price', $product_post_ids[$i] ),
					);
				}
				update_field( 'order_items', $product_fields, $post_id );

			}

		}

		$this->send_confirmation_email( "{$current_program_prefix}-{$new_wsorder_id}", $user, $data['cla_it_rep_id'], $post_id, $data );

		return $post_id;

	}

	private function send_confirmation_email( $order_name, $current_user, $it_rep_id, $post_id, $data ) {

		// Get user information.
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;
		$it_rep_user        = get_userdata( $it_rep_id );
		$it_rep_email       = $it_rep_user->user_email;

		// Email settings.
		$headers = array('Content-Type: text/html; charset=UTF-8');

		// Get current program meta.
		$current_program_post = get_field( 'current_program', 'option' );
		$current_program_id   = $current_program_post->ID;
		$program_name         = get_the_title( $current_program_id );

		// Get order information.
		$order_url = admin_url() . "post.php?post={$post_id}&action=edit";

		// Email end user.
		$message = "<p>Howdy,</p>
<p>Liberal Arts IT has received your order.</p>

<p>Your {$program_name} order will be reviewed to ensure all necessary information and funding is in place.</p>
<p>
  Following review, your workstation request will be combined with others from your department to create a consolidated {$program_name} purchase. Consolidated orders are placed to maximize efficiency. Your order will be processed and received by IT Logistics in 4-6 weeks, depending on how early in the order cycle you make your selection. Once received, your workstation will be released to departmental IT staff who will then image your workstation, install software and prepare the device for delivery. These final steps generally take one to two days.
</p>
<p>You may view your order online at any time using this link: {$order_url}.</p>

<p>
  Have a great day!
  <em>-Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";
		wp_mail( $current_user_email, 'Workstation Order Received', $message, $headers );

		// Email IT Rep.
		$admin_url = admin_url() . "post.php?post={$post_id}&action=edit";
		$message = "<p>
  <strong>There is a new {$program_name} order that requires your attention.</strong>
</p>
<p>
  Please review this order carefully for any errors or omissions, then confirm it to pass along in the ordering workflow, or return it to the customer with your feedback and ask that they correct the order.
</p>
<p>
  You can view the order at this link: {$order_url}.
</p>
<p>
  Have a great day!
  <em>-Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";
		wp_mail( $it_rep_email, 'Workstation Order Received', $message, $headers );

	}

	public function get_product_post_objects_for_program_by_user_dept( $category = false ) {

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
			'post__not_in' => $hidden_products,
			'meta_query' => array( //phpcs:ignore
				'relation' => 'AND',
				array(
					'key'   => 'program', //phpcs:ignore
					'value' => $current_program_id, //phpcs:ignore
					'compare' => '=',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'visibility',
						'compare' => 'NOT EXISTS',
					),
					array(
						'relation' => 'AND',
						array(
							'key'     => 'visibility_archived',
							'value'   => '1',
							'compare' => '!='
						),
						array(
							'key'     => 'visibility_bundle_only',
							'value'   => '1',
							'compare' => '!='
						),
					),
				),
			),
		);

		// Return product category only, if chosen.
		if ( false !== $category ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product-category',
					'field'    => 'slug',
					'terms'    => $category,
				)
			);
		}
		$products      = new \WP_Query( $args );
		$product_posts = $products->posts;

		return $product_posts;

	}

	public function cla_get_products( $category = false ) {

		/**
		 * Display products.
		 */
		$product_posts = $this->get_product_post_objects_for_program_by_user_dept( $category );

		// Output posts.
		$output = '<div class="products grid-x grid-margin-x grid-margin-y">';
		foreach ( $product_posts as $key => $post ) {

			// Define the card variables.
			$post_id     = $post->ID;
			$permalink   = get_permalink($post->ID);
			$post_title  = $post->post_title;
			$price       = (int) get_post_meta( $post->ID, 'price', true );
			$price       = number_format( $price, 2, '.', ',' );
			$thumbnail   = get_the_post_thumbnail( $post, 'post-thumbnail', 'style=""' );
			$thumbnail   = preg_replace( '/ style="[^"]*"/', '', $thumbnail );
			$description = get_post_meta( $post->ID, 'description', true );
			$more_info   = get_post_meta( $post->ID, 'descriptors', true );


			// Build the card output.
			$output .= "<div id=\"product-{$post_id}\" class=\"card cell small-12 medium-3\">";
			$output .= "<h5 class=\"card-header\"><span class=\"post-title post-title-{$post_id}\">{$post_title}</span></h5>";
			$output .= "<div class=\"card-body\">{$thumbnail}<p>$description</p></div>";
			$output .= "<div class=\"card-footer\"><div class=\"grid-x grid-padding-x grid-padding-y\">";
			$output .= "<div class=\"more-details-wrap align-left cell shrink\"><button class=\"more-details link\" type=\"button\">More Details<div class=\"info\">$more_info<a href=\"#\" class=\"close\">Close</a></div></button></div>";
			$output .= "<div class=\"cell auto align-right display-price price-{$post_id}\">\${$price}</div>";
			$output .= "<div class=\"cart-cell cell small-12 align-left\"><button id=\"cart-btn-{$post_id}\" data-product-id=\"{$post_id}\" data-product-price=\"\${$price}\" type=\"button\" class=\"add-product\">Add</button></div>";
			$output .= "</div></div>";
			$output .= "</div>";

		}
		$output .= '</div>';

		$return = wp_kses_post( $output );
		return $output;

	}

}
