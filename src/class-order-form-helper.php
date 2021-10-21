<?php
/**
 * The file that defines helper functions for order forms.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-order-form-helper.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
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
	 * Validate a Custom Quote file upload.
	 *
	 * @param array $file  The file upload data set from AJAX.
	 * @param int   $index The index of the Custom Quote Item that this file is being uploaded for.
	 *
	 * @return array
	 */
	public function validate_file_field( $file, $index ) {

		$return   = array(
			'passed'  => true,
			'message' => '',
		);
		$messages = array();

		// No files to validate.
		if ( empty( $file ) || ! $file['name'] ) {
			$return['passed'] = false;
			$messages[]       = esc_html__( 'Please choose a file.', 'cla-workstation-order-textdomain' );
		}

		// Validate file extension.
		$allowed_extensions = array( 'pdf', 'doc', 'docx' );
		$file_type          = wp_check_filetype( $file['name'] );
		$file_extension     = $file_type['ext'];
		if ( ! in_array( $file_extension, $allowed_extensions, true ) ) {
			$return['passed'] = false;
			$messages[]       = sprintf( esc_html__( 'Invalid file extension, only allowed: %s.', 'cla-workstation-order-textdomain' ), implode( ', ', $allowed_extensions ) );
		}

		// Validate file size.
		$file_size         = $file['size'];
		$allowed_file_size = 1024000; // Here we are setting the file size limit.
		if ( $file_size >= $allowed_file_size ) {
			$return['passed'] = false;
			$messages[]       = esc_html__( 'File size limit exceeded, file size should be smaller than 1 MB.', 'cla-workstation-order-textdomain' );
		}

		if ( $messages ) {
			$filename          = empty( $file ) || ! $file['name'] ? ($index + 1) : $file['name'];
			$return['message'] = "There was an error with file $filename: " . implode(' ', $messages);
		}

		return $return;

	}

	/**
	 * Get the most recent order's ID within a certain program
	 */
	private function get_last_order_id( $program_post_id ) {

		$args  = array(
			'post_type'      => 'wsorder',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'order_id',
			'order'          => 'DESC',
			'meta_query'     => array( //phpcs:ignore
				array(
					'key'     => 'order_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'program',
					'compare' => '=',
					'value'   => $program_post_id,
				),
			),
		);
		$posts = get_posts( $args );
		if ( ! empty( $posts ) ) {
			$last_wsorder_id = (int) get_post_meta( $posts[0], 'order_id', true );
		} else {
			$last_wsorder_id = 0;
		}

		return $last_wsorder_id;

	}

	/**
	 * Get the department fields within a program based from the department associated with the given user ID.
	 * Todo
	 */
	private function get_program_department_fields( $department_id, $program_id ) {

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
		$value                         = array();

		foreach ( $program_meta_keys_departments as $meta_key ) {
			$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
			if ( $department_id === $assigned_dept ) {
				$base_key        = preg_replace( '/_department_post_id$/', '', $meta_key );
				$it_reps         = $current_program_post_meta[ "{$base_key}_it_reps" ];
				$value['business_admins'] = unserialize( $current_program_post_meta[ "{$base_key}_business_admins" ][0] );
				$value['it_reps']         = unserialize( $current_program_post_meta[ "{$base_key}_it_reps" ][0] );
				break;
			}
		}

		return $value;

	}

	/**
	 * Get Products as objects for current program not hidden by current user's department
	 * and within a certain category.
	 *
	 * @param string|false $category The category taxonomy term to filter by.
	 *
	 * @return array
	 */
	public function get_product_post_objects_for_program_by_user_dept( $program_id = false, $category = false ) {

		// Get current user and user ID.
		$user    = wp_get_current_user();
		$user_id = $user->get( 'ID' );

		// Get user's department.
		$user_department_post    = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id = $user_department_post->ID;

		// Retrieve products for the current program year.
		if ( ! $program_id ) {
			$program_post = get_field( 'current_program', 'option' );
			$program_id   = $program_post->ID;
		}

		// Filter out hidden products for department.
		$hidden_products = get_field( 'hidden_products', $user_department_post_id );
		$hidden_bundles = get_field( 'hidden_products', $user_department_post_id );
		$hidden_products_and_bundles = array();
		if ( is_array( $hidden_products ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_products );
		}
		if ( is_array( $hidden_bundles ) ) {
			$hidden_products_and_bundles = array_merge( $hidden_products_and_bundles, $hidden_bundles );
		}

		// Find the posts.
		$product_args = array(
			'posts_per_page' => -1,
			'post_type'      => 'product',
			'nopaging'       => true,
			'post__not_in'   => $hidden_products_and_bundles,
			'fields'         => 'ids',
			'meta_query'     => array( //phpcs:ignore
				'relation' => 'AND',
				array(
					'key'     => 'program', //phpcs:ignore
					'value'   => $program_id, //phpcs:ignore
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
			$product_args['tax_query'] = array(
				array(
					'taxonomy' => 'product-category',
					'field'    => 'slug',
					'terms'    => $category,
				)
			);
		}
		$products      = new \WP_Query( $product_args );
		$product_posts = $products->posts;

		// Get bundles.
		// Filter out hidden products for department.
		$hidden_bundles = get_post_meta( $user_department_post_id, 'hidden_bundles', true );
		$bundle_args = array(
			'post_type'  => 'bundle',
			'nopaging'   => true,
			'post__not_in' => $hidden_bundles,
			'fields'       => 'ids',
			'meta_query' => array( //phpcs:ignore
				'relation' => 'AND',
				array(
					'key'   => 'program', //phpcs:ignore
					'value' => $program_id, //phpcs:ignore
					'compare' => '=',
				),
				array(
					'key'     => 'visibility_archived',
					'value'   => '1',
					'compare' => '!='
				),
			),
		);
		if ( false !== $category ) {
			$bundle_args['tax_query'] = array(
				array(
					'taxonomy' => 'product-category',
					'field'    => 'slug',
					'terms'    => $category,
				)
			);
		}
		$bundles      = new \WP_Query( $bundle_args );
		$bundle_posts = $bundles->posts;

		// Merge posts.
		$posts = array_merge( $product_posts, $bundle_posts );

		// Alphabetize posts.
		$posts_sorted = array();
		foreach ( $posts as $post_id ) {
			$posts_sorted[$post_id] = get_the_title( $post_id );
		}
		asort($posts_sorted);

		return $posts_sorted;

	}

	/**
	 * Get the HTML for products within the current program and the current user's department
	 * and an optional category taconomy term.
	 *
	 * @param string|false $category The category taxonomy term to filter by.
	 * @param boolean      $preview  Whether or not these products are shown on a Preview page.
	 * @param array        $selected Which post IDs should be rendered as selected.
	 *
	 * @return string
	 */
	public function cla_get_products( $category = false, $program_id = false, $preview = false, $selected = array() ) {

		/**
		 * Display products.
		 */
		$product_posts = $this->get_product_post_objects_for_program_by_user_dept( $program_id, $category );

		// Output posts.
		$output = '';
		foreach ( $product_posts as $post_id => $post_title ) {

			// Define the card variables.
			$permalink   = get_permalink( $post_id );
			$price       = (float) get_post_meta( $post_id, 'price', true );
			$price       = number_format( $price, 2, '.', ',' );
			$thumbnail   = get_the_post_thumbnail( $post_id, 'post-thumbnail', 'style=""' );
			$thumbnail   = preg_replace( '/ style="[^"]*"/', '', $thumbnail );
			$description = get_post_meta( $post_id, 'description', true );
			$more_info   = get_post_meta( $post_id, 'descriptors', true );

			// Build the card output.
			$output .= "<div id=\"product-{$post_id}\" class=\"card cell small-12 medium-3\">";
			$output .= "<h5 class=\"card-header\"><span class=\"post-title post-title-{$post_id}\">{$post_title}</span></h5>";
			$output .= "<div class=\"card-body\">{$thumbnail}<p>$description</p></div>";
			$output .= "<div class=\"card-footer\"><div class=\"grid-x grid-padding-x grid-padding-y\">";
			if ( ! empty( $more_info ) ) {
				$output .= "<div class=\"more-details-wrap align-left cell shrink\"><button class=\"more-details link\" type=\"button\">More Details<div class=\"info\">$more_info<a href=\"#\" class=\"close\">Close</a></div></button></div>";
			}
			$output .= "<div class=\"cell auto align-right display-price\">\${$price}</div>";
			if ( false === $preview ) {
				$disabled    = in_array( $post_id, $selected, true ) ? ' disabled="disabled"' : '';
				$button_text = in_array( $post_id, $selected, true ) ? 'In cart' : 'Add';
				$output      .= "<div class=\"cart-cell cell small-12 align-left\"><button id=\"cart-btn-{$post_id}\" data-product-id=\"{$post_id}\" type=\"button\" class=\"add-product\"{$disabled}>{$button_text}</button></div>";
			}
			$output .= "</div></div>";
			$output .= "</div>";

		}
		if ( $output ) {
			$term = get_term_by( 'slug', $category, 'product-category' );
			$output = '<div class="products-' . $category . ' toggle"><h3><a class="btn" href="#">' . $term->name . '</a></h3><div class="products grid-x grid-margin-x grid-margin-y">' . $output . '</div></div>';
		}

		$return = wp_kses_post( $output );
		return $output;

	}

}
