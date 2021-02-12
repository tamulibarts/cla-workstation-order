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
	public function dashboard_widget_function() {
		$url       = get_site_url();
		$admin_url = get_admin_url();
		$user_id   = get_current_user_id();
		$all_query = new \WP_Query(
			array(
				'post_type' => 'wsorder',
				'author' => $user_id,
			)
		);
		$returned_query = new \WP_Query(
			array(
				'post_type' => 'wsorder',
				'author' => $user_id,
				'post_status' => 'returned',
			)
		);
		$action_required_query = new \WP_Query(
			array(
				'post_type' => 'wsorder',
				'author' => $user_id,
				'post_status' => 'action_required',
			)
		);
		$completed_query = new \WP_Query(
			array(
				'post_type' => 'wsorder',
				'author' => $user_id,
				'post_status' => 'completed',
			)
		);
		echo "<ul><li><a href=\"{$url}/order-form/\">+ Place a New Order</a></li><li><a href=\"{$admin_url}edit.php?post_type=wsorder&author={$user_id}\">All Orders ({$all_query->post_count})</a></li><li><a href=\"{$admin_url}edit.php?post_type=wsorder&author={$user_id}&post_status=action_required\">Action Required ({$action_required_query->post_count})</a></li><li><a href=\"{$admin_url}edit.php?post_type=wsorder&author={$user_id}&post_status=returned\">Returned ({$returned_query->post_count})</a></li><li><a href=\"{$admin_url}edit.php?post_type=wsorder&author={$user_id}&post_status=completed\">Completed ($completed_query->post_count)</a></li></ul>";
	}

	/**
	 * Register custom dashboard widget for custom links.
	 *
	 * @return void
	 */
	public function wpexplorer_add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'cla_dashboard_widget', // Widget slug.
			'My Orders', // Title.
			array( $this, 'dashboard_widget_function' ) // Display function.
		);
	}

	/**
	 * Remove dashboard widgets.
	 *
	 * @return void
	 */
	public function remove_dashboard_meta() {
		add_filter( 'screen_options_show_help', function(){ return false; } );
		if ( ! current_user_can( 'manage_options' ) ) {
			add_filter( 'screen_options_show_screen', function(){ return false; } );

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

    ob_start( function( $subject ) {
      $subject = preg_replace( '#<h[0-9]>'.__("Personal Options").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
      $subject = preg_replace( '#<h[0-9]>'.__("About Yourself").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
      $subject = preg_replace( '#<h[0-9]>'.__("Application Passwords").'</h[0-9]>.+?/table>#s', '', $subject, 1 );

      return $subject;
    });

  }

	/**
	 * Remove sections from the user profile page - end.
	 *
	 * @return void
	 */
  public function remove_profile_sections_end () {

    ob_end_flush();

  }
}
