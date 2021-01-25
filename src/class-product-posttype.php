<?php
/**
 * The file that defines the Product post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-product-posttype.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @package cla-workstation-order
 * @since 1.0.0
 */
class Product_PostType {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Register_post_types.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'acf/init', array( $this, 'register_custom_fields' ) );
		add_action( 'transition_post_status', array( $this, 'insert_into_order_form' ), 10, 3 );
		add_filter( 'manage_product_posts_columns', array( $this, 'product_filter_posts_columns' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'product_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'product_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'product_posts_orderby' ) );

	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-taxonomy.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Product',
				'plural'   => 'Products',
			),
			'product',
			array(),
			'dashicons-portfolio',
			array( 'title', 'thumbnail' ),
			array(
				'capability_type' => array( 'product', 'products' ),
			)
		);

	}

	/**
	 * Register custom fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_custom_fields() {
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/product-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/it-rep-status-order-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/business-staff-status-order-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/it-logistics-status-order-fields.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/order-department-comments-fields.php';
	}

	/**
	 * Add product a form field to the order form when a product is created; remove when deleted.
	 *
	 * @since 0.1.0
	 * @param string  $new_status The post's new status.
	 * @param string  $old_status The post's old status.
	 * @param WP_Post $post       The post object.
	 * @return void
	 */
	public function insert_into_order_form( $new_status, $old_status, $post ) {

		if ( 'product' !== $post->post_type ) {
			return;
		}

		if ( 'published' !== $old_status && 'published' === $new_status ) {

			/**
			 * Create the form field.
			 */
			// Get post meta.
			$post_meta = get_post_meta( $post->ID, '', true );

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

			// Create the new form field.
			$properties = array(
				'type'       => 'product',
				'id'         => $new_field_id,
				'label'      => $post->post_title,
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
				'basePrice'  => '$' . $post_meta['price'],
				'pageNumber' => 1,
			);
			$new_field  = \GF_Fields::create( $properties );

			// Add the new field to the form.
			$form['fields'][] = $new_field;

			// Save the modified form.
			\GFAPI::update_form( $form );

			// Add post meta for the field ID.
			update_post_meta( $post->ID, 'order_form_field_id', $new_field_id );

		} elseif ( 'published' === $old_status && 'published' !== $new_status ) {

			/**
			 * Delete the form field.
			 */

			$product_field_id = get_post_meta( $post->ID, 'order_form_field_id', $new_field_id );

			if ( empty( $field_id ) ) {
				return;
			}

			// Get order form.
			$form = \GFAPI::get_form( 1 );

			foreach ( $form['fields'] as $key => $field ) {
				if ( $field->id === $product_field_id ) {
					// Remove field from array.
					unset( $form['fields'][ $key ] );
					break;
				}
			}

			// Remove empty array member.
			$form['fields'] = array_values( $form['fields'] );

			// Save the modified form.
			\GFAPI::update_form( $form );

		}
	}

	/**
	 * Add new admin columns.
	 *
	 * @param array $columns The current admin columns.
	 *
	 * @return array
	 */
	public function product_filter_posts_columns( $columns ) {

		$thumbnail = array( 'image' => __( 'Image' ) );
		$date      = $columns['date'];
		unset( $columns['date'] );
		$columns['program'] = __( 'Program' );
		$columns['date']    = $date;

		$columns = $thumbnail + $columns;

		return $columns;
	}

	/**
	 * Output content for each post's new column.
	 *
	 * @param string $column  The column slug.
	 * @param int    $post_id The post ID.
	 *
	 * @return void
	 */
	public function product_column( $column, $post_id ) {
		// Image column.
		if ( 'program' === $column ) {
			$program_post_id = get_post_meta( $post_id, 'program', true );
			$program_prefix  = get_post_meta( $program_post_id, 'prefix', true );
			echo wp_kses_post( $program_prefix );
		} elseif ( 'image' === $column ) {
			echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 150, 150 ), 'style=max-height:150px' ) );
		}
	}

	/**
	 * Make a column sortable.
	 *
	 * @param array $columns The current columns.
	 *
	 * @return array
	 */
	public function product_sortable_columns( $columns ) {
		$columns['program'] = 'program';
		return $columns;
	}

	/**
	 * Change post query to provide sorting by program post ID.
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function product_posts_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'wso_program' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', 'program' );
			$query->set( 'meta_type', 'numeric' );
		}
	}
}
