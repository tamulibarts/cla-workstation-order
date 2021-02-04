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
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_status_add_to_dropdown' ) );
		add_filter( 'display_post_states', array( $this, 'display_status_state' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'admin_script' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_script' ), 11 );
		// Add columns to dashboard post list screen.
		add_filter( 'manage_wsorder_posts_columns', function( $columns ){

  	  $columns['amount']    = 'Amount';
  	  $columns['it_status'] = 'IT';
  	  $columns['business_status'] = 'Business';
  	  $columns['logistics_status'] = 'Logistics';
	    return $columns;

		});
		add_action( 'manage_wsorder_posts_custom_column', function( $column_name, $post_id ) {
	    if( 'amount' === $column_name ) {
        $number = get_post_meta( $post_id, 'products_subtotal', true );
        if ( class_exists('NumberFormatter') ) {
					$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
					echo $formatter->formatCurrency($number, 'USD');
				} else {
					echo '$' . number_format($number, 2,'.', ',');
				}
	    } else if ( 'it_status' === $column_name ) {
	    	$status = get_field( 'it_rep_status', $post_id );
	    	if ( empty( $status['confirmed'] ) ) {
	    		echo '<span class="not-confirmed">Not yet confirmed</span>';
	    	} else {
	    		echo '<span class="confirmed">Confirmed</span><br>';
	    		echo $status['it_rep']['display_name'];
	    	}
	    } else if ( 'business_status' === $column_name ) {
	    	$status = get_field( 'business_staff_status', $post_id );
	    	if ( empty( $status['confirmed'] ) ) {
	    		echo '<span class="not-confirmed">Not yet confirmed</span>';
	    	} else {
	    		echo '<span class="confirmed">Confirmed</span><br>';
	    		echo $status['business_staff']['display_name'];
	    	}
	    } else if ( 'logistics_status' === $column_name ) {
	    	$status = get_field( 'it_logistics_status', $post_id );
	    	if ( empty( $status['confirmed'] ) ) {
	    		echo '<span class="not-confirmed">Not yet confirmed</span>';
	    	} else {
	    		echo '<span class="confirmed">Confirmed</span> ';
		    	if ( empty( $status['ordered'] ) ) {
		    		echo '<span class="not-fully-ordered">Not fully ordered</span>';
		    	} else {
		    		echo '<span class="ordered">Ordered</span>';
		    	}
	    	}
	    }
		}, 10, 2 );
		// Manipulate post title to force it to a certain format.
		// add_filter( 'default_title', array( $this, 'default_post_title' ) );
		// add_action( 'new_wsorder', array( $this, 'new_wsorder' ) );
		// add_filter( 'wp_insert_post_data', array( $this, 'insert_post_data' ), 11, 2);

		// Notify parties of changes to order status.
		// add_action( 'transition_post_status', array( $this, 'notify_published' ), 10, 3 );

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

	public function post_status_add_to_dropdown() {

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
	 * Create new wsorder post type for the very first time.
	 * We will create the "order_id" post meta.
	 */
	function new_wsorder( $post_id, $post ){

		// Get current program meta.
		$current_program_post      = get_field( 'current_program', 'option' );
		$current_program_id        = $current_program_post->ID;
		$current_program_post_meta = get_post_meta( $current_program_id );
		$current_program_prefix    = $current_program_post_meta['prefix'][0];

		// Make post title using current program ID and incremented order ID from last order.
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
					'value'   => $current_program_id,
				)
			),
		);
		$the_query = new \WP_Query( $args, OBJECT );
		$last_wsorder_posts = $the_query->posts;

		// Figure out order ID.
		$wsorder_id = 1;
		if ( ! empty( $last_wsorder_posts ) ) {
			$last_wsorder_id = (int) get_post_meta( $last_wsorder_posts[0], 'order_id', true );
			$test = get_post_meta( $last_wsorder_posts[0], 'order_id', true );
			echo '<script>console.log("' . gettype($test) . '");</script>';
			$wsorder_id      = $last_wsorder_id + 1;
		}

		// Push order ID value to post details.
		update_post_meta( $post_id, 'order_id', $wsorder_id );
		$args = array(
			'ID' => $post->ID,
			'post_title' => "{$current_program_prefix}-{$wsorder_id}",
		);
		wp_update_post( $args );

	}

	/**
	 * Make post title using current program ID and incremented order ID from last order.
	 */
	public function default_post_title() {

		global $post_type;

		if ('wsorder' === $post_type) {

			$args              = array(
				'post_type'      => 'wsorder',
				'posts_per_page' => 1,
			);
			$last_wsorder_post = wp_get_recent_posts( $args, OBJECT );
			$wsorder_id        = 1;
			if ( ! empty( $last_wsorder_post ) ) {
				$last_wsorder_id = (int) get_post_meta( $last_wsorder_post[0]->ID, 'order_id' );
				$wsorder_id      = $last_wsorder_id + 1;
			}

			// Get current program meta.
			$current_program_post      = get_field( 'current_program', 'option' );
			$current_program_id        = $current_program_post->ID;
			$current_program_post_meta = get_post_meta( $current_program_id );
			$current_program_prefix    = $current_program_post_meta['prefix'][0];

			// Push order ID value to post details.
			return "{$current_program_prefix}-{$wsorder_id}";
		}
	}

	public function insert_post_data ( $data, $postarr ) {

		if ( $data['post_type'] !== 'wsorder' ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

		// Get current program meta.
		$current_program_post      = get_field( 'current_program', 'option' );
		$current_program_id        = $current_program_post->ID;
		$current_program_post_meta = get_post_meta( $current_program_id );
		$current_program_prefix    = $current_program_post_meta['prefix'][0];

    $post_id = $postarr['ID'];

    if ( $post_id === 0 ) {

    	$program_id = $current_program_id;

    	// Post doesn't exist yet, so get order ID from last saved order.
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
						'value'   => $current_program_id,
					)
				),
			);
			$the_query = new \WP_Query( $args );
			// echo '<pre style="padding-left:250px;">';
			// print_r($the_query);
			// echo '</pre>';
			$last_wsorder_posts = $the_query->posts;
			$wsorder_id = 1;
			if ( ! empty( $last_wsorder_posts ) ) {
				$last_wsorder_id = (int) get_post_meta( $last_wsorder_posts[0], 'order_id' );
				$wsorder_id      = $last_wsorder_id + 1;
			}

    } else {

    	// Update post meta for order ID.
    	$meta_exists = metadata_exists( 'post', $post_id, 'order_id' );
    	if ( ! $meta_exists ) {
    		update_post_meta( $post_id, 'order_id', $wsorder_id );
    	} else {
	    	// Post exists, get order ID from post meta.
		    $wsorder_id = get_post_meta( $post_id, 'order_id', true );
		  }

	  }

		// Set the post title value.
    $data['post_title'] = "{$current_program_prefix}-{$wsorder_id}";

    return $data;

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
