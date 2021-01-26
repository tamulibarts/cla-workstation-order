<?php
/**
 * Output current user's department's approved products for the current program year.
 *
 * @link       https://github.com/zachwatkins/wordpress-plugin/blob/master/src/class-products-in-form.php
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
class Products_In_Form {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// add_action( 'init', array( $this, 'add_fields' ) );
		// add_filter( 'gform_pre_render_1', array( $this, 'add_products' ) );
		// add_filter( 'gform_pre_validation_1', array( $this, 'add_products' ) );
		// add_filter( 'gform_pre_submission_filter_1', array( $this, 'add_products' ) );
	}

	/**
	 * Output for plugin_name_shortcode shortcode.
	 * https://docs.gravityforms.com/how-to-add-field-to-form-using-gfapi/
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function add_fields() {

		// Get order form.
		$form = \GFAPI::get_form( 1 );
		// Get next form field ID.
		$new_field_id = 0;
		foreach ( $form['fields'] as $field ) {
			if ( $field->id > $new_field_id ) {
				$new_field_id = $field->id;
			}
		}
		$new_field_id++;
		// create an array of field properties, this example creates a text field.
		// pass array to the create method.
		$properties         = array();
		$properties['type'] = 'product';
		$field              = \GF_Fields::create( $properties );
		$field->id          = $new_field_id;
		$field->label       = 'My New Field';
		$field->inputType   = 'singleproduct'; //phpcs:ignore
		// Add the new field to the form.
		$form['fields'][] = $field;
		// Save the modified form.
		\GFAPI::update_form( $form );

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
			'meta_key'   => 'program', // phpcs:ignore
			'meta_value' => $current_program_id, // phpcs:ignore
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

	/**
	 * Populate Order Form elements with current-year program's department's IT Rep and Business Admin users
	 * based on current user's department.
	 *
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function add_products( $form ) {

		$new_field_id = 0;
		foreach ( $form['fields'] as $field ) {
			if ( $field->id > $new_field_id ) {
				$new_field_id = $field->id;
			}
		}
		$new_field_id++;
		// echo '<pre>';
		// echo $new_field_id++;
		$properties = array(
			'type'       => 'product',
			'id'         => $new_field_id,
			'label'      => 'Product Field Label',
			'size'       => 'medium',
			'visibility' => 'visible',
			'inputs'     => array(
				array(
					'id'    => "{$new_field_id}.1",
					'label' => 'Name',
					'name'  => '',
				),
				array(
					'id'    => "{$new_field_id}.2",
					'label' => 'Price',
					'name'  => '',
				),
				array(
					'id'    => "{$new_field_id}.3",
					'label' => 'Quantity',
					'name'  => '',
				),
			),
			'inputType'  => 'singleproduct',
			'formId'     => 1,
			'basePrice'  => '$1.00',
			'pageNumber' => 1,
		);
		$new_field  = \GF_Fields::create( $properties );
		// Get product posts.
		// Get current user and user ID.
		$user    = wp_get_current_user();
		$user_id = $user->get( 'ID' );

		// Get user's department.
		$user_department_post    = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id = $user_department_post->ID;

		// Get current program meta.
		$current_program_post = get_field( 'current_program', 'option' );
		$current_program_id   = $current_program_post->ID;
		$args                 = array(
			'post_type'  => 'product',
			'nopaging'   => true,
			'meta_key'   => 'program', // phpcs:ignore
			'meta_value' => $current_program_id, // phpcs:ignore
		);
		$products             = new \WP_Query( $args );
		$product_posts        = $products->posts;
		// Add IT Reps form field.
		foreach ( $form['fields'] as $field ) {
			if ( 'product' === $field->type ) {
				// echo '<pre>';
				// print_r($field);
				// echo '</pre>';
			}
		}
		// echo '</pre>';
		$form['fields'][] = $new_field;

		return $form;
	}

}
