<?php
/**
 * The file that defines the Program post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-program-posttype.php
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
class Program_PostType {

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

	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Program',
				'plural'   => 'Programs',
			),
			'program',
			array(),
			'dashicons-portfolio',
			array( 'title' ),
			array(
				'capability_type' => array( 'program', 'programs' ),
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
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/program-fields.php';
	}
}
