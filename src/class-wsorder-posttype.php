<?php
/**
 * The file that defines the Order post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-wsorder-posttype.php
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
class WSOrder_PostType {

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
		add_action( 'transition_post_status', array( $this, 'notify_published' ), 10, 3 );
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-taxonomy.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Order',
				'plural'   => 'Orders',
			),
			'wsorder',
			array(),
			'dashicons-portfolio',
			array( 'title' ),
			array(
				'capability_type' => array( 'wsorder', 'wsorders' ),
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
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/wsorder-fields.php';
	}

	/**
	 * Notify users based on their association to the wsorder post and the new status of the post.
	 *
	 * @since 0.1.0
	 * @param string  $new_status The post's new status.
	 * @param string  $old_status The post's old status.
	 * @param WP_Post $post       The post object.
	 * @return void
	 */
	public function notify_published( $new_status, $old_status, $post ) {

		if ( 'wsorder' !== $post->post_type ) {
			return;
		}
		$message              = '';
		$message .= serialize( $_POST ); //phpcs:ignore
		$message .= serialize( $post ); //phpcs:ignore
		$it_rep_user_id       = $_POST['acf']['field_5fff6b46a22af']['field_5fff703a5289f']; //phpcs:ignore
		$it_rep_user_id_saved = get_post_meta( $post->ID, 'it_rep_status_it_rep' );
		$message             .= $it_rep_user_id . ' : ' . $it_rep_user_id_saved;
		wp_mail( 'zwatkins2@tamu.edu', 'order published', $message );
		if (
			( 'publish' === $new_status && 'publish' !== $old_status )
			&& 'wsorder' === $post->post_type
		) {
			$message  = serialize( $_GET ); //phpcs:ignore
			$message .= serialize( $_POST ); //phpcs:ignore
			$message .= serialize( $post ); //phpcs:ignore
			wp_mail( 'zwatkins2@tamu.edu', 'order published', $message );
		}
	}
}
