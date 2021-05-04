<?php
/**
 * The file that defines the Order post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-dashboard.php
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
class Dashboard {

	function __construct() {
		// Remove widgets from dashboard welcome page.
		add_action( 'admin_init', array( $this, 'remove_dashboard_meta' ) );
		remove_action('welcome_panel', 'wp_welcome_panel');
		// Remove content from user profile page.
		remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
		add_action( 'admin_head', array( $this, 'remove_profile_sections_start' ) );
    add_action( 'admin_footer', array( $this, 'remove_profile_sections_end' ) );
		add_action('admin_head', array( $this, 'remove_help_tabs' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'wpexplorer_add_dashboard_widgets' ) );

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
			$link                    = get_edit_post_link( $post_id );
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
			$link                    = get_edit_post_link( $post_id );
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
		if (
			current_user_can( 'wso_logistics' )
			|| current_user_can( 'wso_it_rep' )
			|| current_user_can( 'wso_business_admin' )
		) {
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

	/**
	 * Remove help tabs from top of screen.
	 *
	 * @return void
	 */
	public function remove_help_tabs () {

    $screen = get_current_screen();

    //checking whether we are on dashboard main page or not
    if ( ! in_array( $screen->id, array('dashboard','profile') ) )
      return;

    //Adding tab with an id overview it gets replaced if tab is already available with same id
    $screen->remove_help_tabs();

	}

	/**
	 * Remove sections from the user profile page - start.
	 *
	 * @return void
	 */
	public function remove_profile_sections_start() {
		if ( ! current_user_can( 'administrator' ) ) {
			ob_start( function( $subject ) {
				$subject = preg_replace( '#<h[0-9]>'.__("Personal Options").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
				$subject = preg_replace( '#<h[0-9]>'.__("About Yourself").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
				$subject = preg_replace( '#<h[0-9]>'.__("Application Passwords").'</h[0-9]>.+?/table>#s', '', $subject, 1 );

				return $subject;
			});
		}

  }

	/**
	 * Remove sections from the user profile page - end.
	 *
	 * @return void
	 */
  public function remove_profile_sections_end () {

		if ( ! current_user_can( 'administrator' ) ) {
			ob_end_flush();
		}

  }
}
