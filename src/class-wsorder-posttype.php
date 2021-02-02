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
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_to_post_status_dropdown' ) );
		add_filter( 'display_post_states', array( $this, 'display_status_state' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'admin_script' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_script' ), 11 );

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

		register_post_status( 'action_required', array(
			'label'                     => _x( 'Action Required', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Action Required <span class="count">(%s)</span>', 'Action Required <span class="count">(%s)</span>' )
		) );

		register_post_status( 'returned', array(
			'label'                     => _x( 'Returned', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>' )
		) );

		register_post_status( 'completed', array(
			'label'                     => _x( 'Completed', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' )
		) );

		register_post_status( 'awaiting_another', array(
			'label'                     => _x( 'Awaiting Another', 'post' ),
			'public'                    => true,
      'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Awaiting Another <span class="count">(%s)</span>', 'Awaiting Another <span class="count">(%s)</span>' )
		) );

	}

	public function admin_script() {
		global $post_type;
		if( 'wsorder' == $post_type ) {
			wp_enqueue_script( 'cla-workstation-order-admin-script' );
		}
	}

	public function add_to_post_status_dropdown() {

		global $post;
		if( $post->post_type != 'wsorder' ) {
			return false;
		}
		$status = '';
		switch ( $post->post_status ) {
			case 'action_required':
				$status = "jQuery( '#post-status-display' ).text( 'Action Required' ); jQuery(
						'select[name=\"post_status\"]' ).val('action_required')";
				break;

			case 'returned':
				$status = "jQuery( '#post-status-display' ).text( 'Returned' ); jQuery(
						'select[name=\"post_status\"]' ).val('returned')";
				break;

			case 'completed':
				$status = "jQuery( '#post-status-display' ).text( 'Completed' ); jQuery(
						'select[name=\"post_status\"]' ).val('completed')";
				break;

			case 'awaiting_another':
				$status = "jQuery( '#post-status-display' ).text( 'Awaiting Another' ); jQuery(
						'select[name=\"post_status\"]' ).val('awaiting_another')";
				break;

			default:
				break;
		}
		echo "<script>
			jQuery(document).ready( function() {
				jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"action_required\">Action Required</option><option value=\"returned\">Returned</option><option value=\"completed\">Completed</option><option value=\"awaiting_another\">Awaiting Another</option>' );
				".$status."
			});
		</script>";

	}

	public function display_status_state( $states ) {

		global $post;
		$arg = get_query_var( 'post_status' );
		switch ( $post->post_status ) {
			case 'action_required':
				if ( $arg != $post->post_status ) {
					return array( 'Action Required' );
				}
				break;

			case 'returned':
				if ( $arg != $post->post_status ) {
					return array( 'Returned' );
				}
				break;

			case 'completed':
				if ( $arg != $post->post_status ) {
					return array( 'Completed' );
				}
				break;

			case 'awaiting_another':
				if ( $arg != $post->post_status ) {
					return array( 'Awaiting Another' );
				}
				break;

			default:
				return $states;
				break;
		}

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
		if ( isset( $_POST['acf'] ) ) {
			$it_rep_user_id       = $_POST['acf']['field_5fff6b46a22af']['field_5fff703a5289f']; //phpcs:ignore
			$it_rep_user_id_saved = get_post_meta( $post->ID, 'it_rep_status_it_rep' );
			$message             .= $it_rep_user_id . ' : ' . $it_rep_user_id_saved;
		}
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
