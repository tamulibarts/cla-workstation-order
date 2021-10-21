<?php
/**
 * The file that defines the Order post type
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-dashboard.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @package cla-workstation-order
 * @since 1.0.0
 */
class Dashboard {

	function __construct() {
		// Remove widgets from dashboard welcome page.
		add_action( 'admin_init', array( $this, 'remove_dashboard_meta' ) );
		remove_action('welcome_panel', 'wp_welcome_panel');
		add_action( 'wp_dashboard_setup', array( $this, 'wpexplorer_add_dashboard_widgets' ) );

		// Update account information via ajax action hook.
		add_action( 'wp_ajax_update_acount', array( $this, 'update_acount' ) );

	}

	/**
	 * AJAX action to update User data from a front-end page template.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function update_acount() {

		// Ensure nonce is valid.
		check_ajax_referer( 'update_account' );

		// Get referring post properties.
		$url = wp_get_referer();
		if ( false === strpos( $url, '/my-account/' ) || ! is_user_logged_in() ) {
			return;
		}

		$post_id   = url_to_postid( $url );
		$post_type = get_post_type( $post_id );

		if ( 'page' !== $post_type ) {
			return;
		}

		$json_out           = array(
			'status' => 'Your account could not be updated.',
			'errors' => array(),
		);
		$current_user       = wp_get_current_user();
		$current_user_id    = get_current_user_id();
		$current_user_meta  = get_user_meta( $current_user_id );
		$current_department = (int) get_user_meta( $current_user_id, 'department', true );
		$first_name         = false;
		$last_name          = false;
		$email              = false;
		$department         = false;

		// Validate input.
		if ( isset( $_POST['first_name'] ) ) {
			$maybe_firstname = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
			if ( ! empty( $maybe_firstname ) ) {
				if ( $maybe_firstname !== $current_user_meta['first_name'][0] ) {
					$first_name = $maybe_firstname;
				}
			} else {
				$json_out['errors']['first_name'] = 'The first name you provided is not valid.';
			}
		}
		if ( isset( $_POST['last_name'] ) ) {
			$maybe_lastname = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
			if ( ! empty( $maybe_lastname ) ) {
				if ( $maybe_lastname !== $current_user_meta['last_name'][0] ) {
					$last_name = $maybe_lastname;
				}
			} else {
				$json_out['errors']['last_name'] = 'The last name you provided is not valid.';
			}
		}
		if ( isset( $_POST['email'] ) ) {
			$maybe_email = sanitize_text_field( wp_unslash( $_POST['email'] ) );
			if ( ! empty( $maybe_email ) && is_email( $maybe_email ) ) {
				if ( $maybe_email !== $current_user->user_email ) {
					$email = $maybe_email;
				}
			} else {
				$json_out['errors']['email'] = 'The email address you provided is not valid.';
			}
		}
		if ( isset( $_POST['department'] ) ) {
			$maybe_department = sanitize_text_field( wp_unslash( $_POST['department'] ) );
			$maybe_department = intval( $maybe_department );
			if ( 0 !== $maybe_department ) {
				$maybe_post = get_post( $maybe_department );
				if ( 'department' === $maybe_post->post_type && 'publish' === $maybe_post->post_status ) {
					if ( $maybe_department !== $current_department ) {
						$department = $maybe_department;
					}
				} else {
					$json_out['errors']['department'] = 'This is not a valid department choice.';
				}
			}
		}

		// Apply changes.
		if ( $first_name || $last_name || $email || $department ) {
			// Update user data.
			if ( $first_name || $last_name || $email ) {
				$user_data_params = array(
					'ID' => $current_user_id,
				);
				// Add first name.
				if ( $first_name ) {
					$user_data_params['first_name'] = $first_name;
				}
				// Add last name.
				if ( $last_name ) {
					$user_data_params['last_name'] = $last_name;
				}
				// Add display name as first_name last_name.
				if ( $first_name || $last_name ) {
					$new_display_name = $current_user->display_name;
					if ( $first_name && $last_name ) {
						$new_display_name = "$first_name $last_name";
					} elseif ( $first_name ) {
						$new_display_name = $first_name . ' ' . $current_user_meta['last_name'][0];
					} elseif ( $last_name ) {
						$new_display_name = $current_user_meta['first_name'][0] . ' ' . $last_name;
					}
					$user_data_params['display_name'] = $new_display_name;
				}
				// Add email.
				if ( $email ) {
					$user_data_params['user_email'] = $email;
				}
				// Update the user data.
				$user_data = wp_update_user( $user_data_params );
				// Validate results.
				if ( is_wp_error( $user_data ) ) {
					$json_out['errors']['user_data'] = 'The first name, last name, and/or email address could not be updated.';
				} else {
					$json_out['status'] = 'success';
				}
			}
			// Update user meta.
			if ( $department ) {
				$user_meta = update_user_meta( $current_user_id, 'department', $department );
				if ( false === $user_meta ) {
					$json_out['errors']['department'] = 'The department could not be updated.';
				} else {
					$json_out['status'] = 'success';
				}
			}
		} else {
			$json_out['status']   = 'No changes were made.';
			$json_out['errors'][] = 'No changes were made.';
		}

		echo wp_json_encode( $json_out );
		die();

	}

	/**
	 * Add a custom widget to the main dashboard page including order links.
	 *
	 * @return void
	 */
	public function dashboard_widget_subscribers() {
		$url             = get_site_url();
		$current_user    = wp_get_current_user();
		$current_user_id = $current_user->ID;
		// Output all links.
		$output = '<ul>';
		$output .= "<li><a href=\"{$url}/new-order/\">+ Place a New Order</a></li><li><a href=\"{$url}/my-orders/\">My Orders</a></li>";
		$output .= '</ul>';
		$returned_query_args = array(
			'post_type' => 'wsorder',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'post_status' => 'returned',
			'order' => 'ASC',
			'author' => $current_user_id,
		);
		$returned_posts = get_posts( $returned_query_args );
		if ( count( $returned_posts ) > 0 ) {
			$output .= '<div><strong>Returned Orders</strong></div>';
		}
		foreach ( $returned_posts as $post_id ) {
			$title                   = get_the_title( $post_id );
			$link                    = get_permalink( $post_id );
			// Output links.
			$output .= "<div><a href=\"{$link}\">{$title} (click to edit)</a></div>";
		}
		echo $output;
	}

	/**
	 * Add a custom widget to the main dashboard page including order links.
	 *
	 * @return void
	 */
	public function dashboard_widget_todo() {

		$admin_url = get_admin_url();
		$user      = wp_get_current_user();
		$user_id   = $user->ID;
		$output    = '<ul>';

		/**
		 * Get orders the user is actively responsible for.
		 */
		if ( current_user_can( 'wso_it_rep' ) ) {
			$todo_query_args = array(
				'post_type'      => 'wsorder',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => array( 'draft', 'action_required' ),
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => 'it_rep_status_it_rep',
						'value' => $user_id,
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'it_rep_status_confirmed',
							'value'   => '1',
							'compare' => '!=',
						),
						array(
							'key'     => 'it_rep_status_confirmed',
							'compare' => 'NOT EXISTS',
						),
					),
				),
			);
		} elseif ( current_user_can( 'wso_business_admin' ) ) {
			$todo_query_args = array(
				'post_type'      => 'wsorder',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => array( 'draft', 'action_required' ),
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => 'business_staff_status_business_staff',
						'value' => $user_id,
					),
					array(
						'key'   => 'it_rep_status_confirmed',
						'value' => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'business_staff_status_confirmed',
							'value'   => '1',
							'compare' => '!=',
						),
						array(
							'key'     => 'business_staff_status_confirmed',
							'compare' => 'NOT EXISTS',
						),
					),
				),
			);
		} else {
			$todo_query_args = array(
				'post_type'      => 'wsorder',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => array( 'draft', 'action_required' ),
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'it_rep_status_confirmed',
						'value'   => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => 'business_staff_status_business_staff',
							'value' => '',
						),
						array(
							'key'     => 'business_staff_status_business_staff',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => 'business_staff_status_confirmed',
							'value' => '1',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'it_logistics_status_confirmed',
							'value'   => '0',
						),
						array(
							'key'     => 'it_logistics_status_confirmed',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'it_logistics_status_ordered',
							'value'   => '0',
						),
						array(
							'key'     => 'it_logistics_status_ordered',
							'compare' => 'NOT EXISTS',
						),
					),
				),
			);
		}
		$todo_posts = get_posts( $todo_query_args );
		// Output links.
		foreach ($todo_posts as $post_id) {
			$title                   = get_the_title( $post_id );
			$link                    = get_permalink( $post_id );
			$author_id               = (int) get_post_field( 'post_author', $post_id );
			$author                  = get_user_by( 'ID', $author_id );
			$author_name             = $author->display_name;
			$user_department_post    = get_field( 'department', "user_{$author_id}" );
			$user_department_post_id = $user_department_post->ID;
			$department_title        = get_the_title( $user_department_post_id );
			$date                    = new \DateTime( get_the_time( 'c', $post_id ) );
			$date->setTimezone( new \DateTimeZone( 'America/Chicago' ) );
			$submit_date = $date->format( 'M j, Y \a\t g:i a' );
			// Output links.
			$output .= "<li><a href=\"{$link}\">{$title} for {$author_name} ({$department_title})</a><br>{$submit_date}</li>";
		}
		$output .= '</ul>';
		echo $output;

	}

	/**
	 * Register custom dashboard widget for custom links.
	 *
	 * @return void
	 */
	public function wpexplorer_add_dashboard_widgets() {
		$program_id = (int) get_site_option( 'options_current_program' );
		$program_post = get_post( $program_id );
		wp_add_dashboard_widget(
			'cla_dashboard_subscribers', // Widget slug.
			'Quick Links', // Title.
			array( $this, 'dashboard_widget_subscribers' ) // Display function.
		);
		if ( ! current_user_can( 'subscriber' ) ) {
			wp_add_dashboard_widget(
				'cla_dashboard_todo', // Widget slug.
				'Orders requiring your attention', // Title.
				array( $this, 'dashboard_widget_todo' ) // Display function.
			);
		}
	}

	/**
	 * Remove dashboard widgets.
	 *
	 * @return void
	 */
	public function remove_dashboard_meta() {

		// Remove help and screen options tabs
		add_filter( 'screen_options_show_help', function(){ return false; } );
		add_filter( 'screen_options_show_screen', function(){ return false; } );

		// Remove every widget from the main dashboard page
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal');
		remove_meta_box( 'dashboard_welcome', 'dashboard', 'normal');

		// Remove WP Engine dashboard widget.
		remove_meta_box( 'wpe_dify_news_feed', 'dashboard', 'normal');
	}
}
