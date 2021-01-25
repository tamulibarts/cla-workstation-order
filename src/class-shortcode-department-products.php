<?php
/**
 * Output current user's department's approved products for the current program year.
 *
 * @link       https://github.com/zachwatkins/wordpress-plugin/blob/master/src/class-shortcode.php
 * @since      1.0.0
 * @package    wordpress-plugin
 * @subpackage wordpress-plugin/src
 */

namespace CLA_Workstation_Order;

/**
 * Create shortcode to display the faculty search form.
 *
 * @package wordpress-plugin
 * @since 1.0.0
 */
class Shortcode_Department_Products {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		add_shortcode( 'department_products', array( $this, 'shortcode' ) );

	}

	/**
	 * Output for plugin_name_shortcode shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function shortcode() {

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
			$output .= sprintf(
				'<div class="cell">%s%s</div>',
				get_the_post_thumbnail( $post ),
				$post->post_title
			);
		}

		$return = wp_kses_post( $output );

		return $return;

	}

}
