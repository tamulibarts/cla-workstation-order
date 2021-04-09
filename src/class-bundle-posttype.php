<?php
/**
 * The file that defines the Bundle post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-bundle-posttype.php
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
class Bundle_PostType {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Register_post_types.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'acf/init', array( $this, 'register_custom_fields' ) );

	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Bundle',
				'plural'   => 'Bundles',
			),
			'bundle',
			array(),
			'dashicons-database',
			array( 'title', 'thumbnail' ),
			array(
				'capability_type'    => array( 'bundle', 'bundles' ),
				'publicly_queryable' => false,
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
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/bundle-fields.php';
	}
}
