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
		add_filter( 'manage_product_posts_columns', array( $this, 'product_filter_posts_columns' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'product_column' ), 10, 2 );
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
			'dashicons-desktop',
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
		$columns['price'] = __( 'Price' );
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
		if ( 'price' === $column ) {
			$price = get_post_meta( $post_id, 'price', true );
			echo wp_kses_post( $price );
		} elseif ( 'program' === $column ) {
			$program_post_id = get_post_meta( $post_id, 'program', true );
			$program_prefix  = get_post_meta( $program_post_id, 'prefix', true );
			echo wp_kses_post( $program_prefix );
		} elseif ( 'image' === $column ) {
			echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 150, 150 ), 'style=max-height:150px' ) );
		}
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
