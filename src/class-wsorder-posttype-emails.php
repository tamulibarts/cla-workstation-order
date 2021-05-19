<?php
/**
 * The file that defines the Order post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-wsorder-posttype-emails.php
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
class WSOrder_PostType_Emails {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Notify parties of changes to order status.
		add_filter( 'acf/update_value/key=field_5fff6b71a22b0', array( $this, 'it_rep_confirmed' ), 12, 2 );
		add_filter( 'acf/update_value/key=field_5fff6ec0e4385', array( $this, 'bus_staff_confirmed' ), 12, 2 );
		add_filter( 'acf/update_value/key=field_5fff6f3cef757', array( $this, 'logistics_confirmed' ), 12, 2 );
		add_action( 'transition_post_status', array( $this, 'handle_returned_order_emails' ), 12, 3 );

	}

	/**
	 * Determine if the order requires business approval.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return boolean
	 */
	private function order_requires_business_approval( $post_id ) {
		$business_admin = (int) get_post_meta( $post_id, 'business_staff_status_business_staff', true );
		if ( ! empty( $business_admin ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Once IT Rep has confirmed, if business approval is needed then
	 * send an email to the business admin.
	 *
	 * @param string $new_status The new status of the post.
	 * @param string $old_status The old status of the post.
	 * @param object $post       The WP_Post object.
	 *
	 * @return void
	 */
	public function it_rep_confirmed( $value, $post_id ) {

		$old_value = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
		if ( 1 === intval( $value ) && 0 === $old_value ) {
			// IT Rep confirmed the order.
			$post                    = get_post( $post_id );
			$order_name              = get_the_title( $post_id );
			$user_id                 = $post->post_author;
			$end_user                = get_user_by( 'id', $user_id );
			$end_user_name           = $end_user->display_name;
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
			$to                      = '';

			// Check if business approval is needed.
			$requires_bus_approval = $this->order_requires_business_approval( $post_id );

			if ( $requires_bus_approval ) {

				// Declare business admin variables.
				$business_admins       = get_field( 'affiliated_business_staff', $post_id );
				$business_admin_emails = array();
				foreach ( $business_admins as $bus_user_id ) {
					$user_data               = get_userdata( $bus_user_id );
					$business_admin_emails[] = $user_data->user_email;
				}
				$business_admin_emails = implode( ',', $business_admin_emails );
				// Send email.
				$to      = $business_admin_emails;
				$message = $this->email_body_it_rep_to_business( $post_id, $end_user_name );

			} else {

				// Get logistics email setting.
				$enable_logistics_email = (int) get_field( 'enable_emails_to_logistics', 'option' );
				if ( 1 === $enable_logistics_email ) {

					// Get logistics email.
					$logistics_email = get_field( 'logistics_email', 'option' );

					// Send email.
					$to      = $logistics_email;
					$message = $this->email_body_to_logistics( $post_id );

				}
			}

			// Send email.
			if ( ! empty( $to ) ) {
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				wp_mail( $to, $title, $message, $headers );
			}

		}
	}

	/**
	 * Once Business Staff has confirmed, then
	 * send an email to the logistics address.
	 *
	 * @param string $new_status The new status of the post.
	 * @param string $old_status The old status of the post.
	 * @param object $post       The WP_Post object.
	 *
	 * @return void
	 */
	public function bus_staff_confirmed( $value, $post_id ) {

		$old_value = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );
		if ( 1 === intval( $value ) && 0 === $old_value ) {

			// Logistics email enabled.
			$enable_logistics_email = (int) get_field( 'enable_emails_to_logistics', 'option' );

			if ( 1 === $enable_logistics_email ) {

				$post = get_post( $post_id );
				// Get the order name.
				$order_name = get_the_title( $post_id );
				// Declare end user variables.
				$user_id                 = $post->post_author;
				$end_user                = get_user_by( 'id', $user_id );
				$end_user_name           = $end_user->display_name;
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
				// Get logistics email.
				$logistics_email = get_field( 'logistics_email', 'option' );
				// Send email.
				$to      = $logistics_email;
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_to_logistics( $post_id );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				wp_mail( $to, $title, $message, $headers );

			}
		}
	}

	/**
	 * IT Logistics checks their "Confirmed" checkbox, then
	 * end user is emailed with "order approval completed email".
	 *
	 * @param string $new_status The new status of the post.
	 * @param string $old_status The old status of the post.
	 * @param object $post       The WP_Post object.
	 *
	 * @return void
	 */
	public function logistics_confirmed( $value, $post_id ) {

		$old_value = (int) get_post_meta( $post_id, 'it_logistics_status_confirmed', true );
		if ( 1 === intval( $value ) && 0 === $old_value ) {

			$post = get_post( $post_id );
			// Get the order name.
			$order_name = get_the_title( $post_id );
			// Declare end user variables.
			$user_id                 = $post->post_author;
			$end_user                = get_user_by( 'id', $user_id );
			$end_user_email          = $end_user->user_email;
			$end_user_name           = $end_user->display_name;
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
			// Send email.
			$to      = $end_user_email;
			$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
			$message = $this->email_body_order_approved( $post_id );
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $to, $title, $message, $headers );

		}
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
	public function handle_returned_order_emails( $new_status, $old_status, $post ) {

		if (
			$old_status === $new_status
			|| 'wsorder' !== $post->post_type
			|| 'auto-draft' === $new_status
		) {
			return;
		}

		// Verify either nonce for admin or ajax updates.
		if (
			(
				! isset( $_POST['_wpnonce'] )
				|| false === wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-post_' . $post->ID )
			)
			&& (
				! isset( $_POST['_ajax_nonce'] )
				|| false === wp_verify_nonce( sanitize_key( $_POST['_ajax_nonce'] ), 'confirm_order' )
			)
		) {
			return;
		}

		// Get email headers.
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
		$post_id    = $post->ID;
		$order_name = get_the_title( $post_id );
		// Declare current user variables.
		$current_user       = wp_get_current_user();
		$current_user_id    = $current_user->ID;
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;
		// Declare end user variables.
		$user_id                   = $post->post_author;
		$end_user                  = get_user_by( 'id', $user_id );
		$end_user_email            = $end_user->user_email;
		$end_user_name             = $end_user->display_name;
		$primary_it_rep_user_id    = get_post_meta( $post_id, 'it_rep_status_it_rep', true );
		$primary_it_rep_user       = get_user_by( 'id', $primary_it_rep_user_id );
		$primary_it_rep_user_email = $primary_it_rep_user->user_email;
		$user_department_post      = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id   = $user_department_post->ID;
		$department_abbreviation   = get_field( 'abbreviation', $user_department_post_id );
		// Declare IT Rep user variables.
		$it_reps        = get_field( 'affiliated_it_reps', $post_id );
		$it_rep_emails  = array();
		foreach ( $it_reps as $rep_user_id ) {
			$user_data       = get_userdata( $rep_user_id );
			$it_rep_emails[] = $user_data->user_email;
		}
		$it_rep_emails = implode( ',', $it_rep_emails );
		// Declare business approval variables.
		$order_program_id           = get_field( 'program', $post_id );
		$requires_business_approval = $this->order_requires_business_approval( $post_id );
		$business_admin_emails      = '';
		if ( $requires_business_approval ) {
			$business_admins       = get_field( 'affiliated_business_staff', $post_id );
			$business_admin_emails = array();
			foreach ( $business_admins as $bus_user_id ) {
				$user_data               = get_userdata( $bus_user_id );
				$business_admin_emails[] = $user_data->user_email;
			}
			$business_admin_emails = implode( ',', $business_admin_emails );
		}
		// Get logistics email settings.
		$logistics_email        = get_field( 'logistics_email', 'option' );
		$enable_logistics_email = get_field( 'enable_emails_to_logistics', 'option' );

		/**
		 * Handle returned order emails.
		 */
		if (
			'returned' === $new_status
			&& 'returned' !== $old_status
		) {

			// Store user ID who returned the order.
			update_post_meta( $post_id, 'returned_by', $current_user_id );

			// Find comment field updated by current user.
			$affiliated_it_reps        = get_field( 'affiliated_it_reps', $post_id );
			$affiliated_business_staff = get_field( 'affiliated_business_staff', $post_id );
			$returned_comment          = '';
			// Decide what kind of user this is.
			if ( is_array( $affiliated_it_reps ) && in_array( $current_user_id, $affiliated_it_reps ) ) {
				// Current user is an IT rep.
				$fields = get_field( 'it_rep_status' );
				$returned_comment = $fields['comments'];
			} elseif ( is_array( $affiliated_business_staff ) && in_array( $current_user_id, $affiliated_business_staff ) ) {
				// Current user is a business admin.
				$fields = get_field( 'business_staff_status' );
				$returned_comment = $fields['comments'];
			} elseif ( current_user_can( 'wso_logistics' ) ) {
				$fields = get_field( 'it_logistics_status' );
				$returned_comment = $fields['comments'];
			}

			/**
			 * If status changed to "Returned" ->
			 * subject: [{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}
			 * to: end user
			 * cc: whoever set it to return
			 * body: email_body_return_to_user( $post->ID, $_POST['acf'] );
			 */
			$to      = $end_user_email;
			$to_cc   = $current_user_email;
			$title   = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
			$message = $this->email_body_return_to_user( $post_id, $returned_comment );
			array_push( $headers, 'CC:' . $to_cc );
			wp_mail( $to, $title, $message, $headers );

			/**
			 * If status changed to "Returned" ->
			 * subject: [{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$order.user_name}
			 * to: if it_rep is assigned and approved, email them; if business_admin is assigned, email them
		   * body: email_body_return_to_user_forward( $post->ID, $_POST['acf'] );
		   */
			$to = array();
			if ( ! empty( $it_rep_emails ) ) {
				$to[] = $it_rep_emails;
			}
			if ( ! empty( $business_admin_emails ) ) {
				$to[] = $business_admin_emails;
			}
			$to = implode( ',', $to );
			// Remove the returning user from this email list.
			$pattern = "/$current_user_email,?/";
			$to      = preg_replace( $pattern, '', $to );
			$to      = preg_replace( '/,$/', '', $to );
			if ( ! empty( $to ) ) {
				$title   = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_return_to_user_forward( $post_id, $returned_comment, $end_user_name );
				wp_mail( $to, $title, $message, $headers );
			}
		}

		/**
		 * When end user addresses the work order after it was returned to them.
		 */
		if (
			'returned' === $old_status
			&& 'returned' !== $new_status
		) {

			// Notify the person who returned the request.
			$returner_id          = get_post_meta( $post_id, 'returned_by', true );
			$returner_data        = get_userdata( $returner_id );
			$returner_roles       = $returner_data->roles;
			$returner_email       = $returner_data->user_email;
			$to                   = $returner_email;
			// Figure out who returned it.
			if ( in_array( 'wso_it_rep', $returner_roles, true ) ) {
				// IT Rep returned it. Notify all IT reps.
				$emails = explode( ',', $it_rep_emails );
				if ( 1 < count( $emails ) ) {
					// More than one IT rep.
					$pattern      = "/$returner_email,?/";
					$other_emails = preg_replace( $pattern, '', $emails );
					$other_emails = preg_replace( '/,$/', '', $other_emails );
					$headers[]    = 'CC:' . $other_emails;
				}
			} elseif ( in_array( 'wso_business_admin', $returner_roles, true ) ) {
				// Business admin returned it. Notify all business admins.
				$emails = explode( ',', $business_admin_emails );
				if ( 1 < count( $emails ) ) {
					// More than one IT rep.
					$pattern      = "/$returner_email,?/";
					$other_emails = preg_replace( $pattern, '', $emails );
					$other_emails = preg_replace( '/,$/', '', $other_emails );
					$headers[]    = 'CC:' . $other_emails;
				}
			}
			$to              = $returner_email;
			$title           = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
			$admin_order_url = admin_url() . "post.php?post={$post_id}&action=edit";
			$message         = "Please check on this work order as the end user has passed it on: $admin_order_url";
			wp_mail( $to, $title, $message, $headers );

			// Empty user ID who returned the order.
			update_post_meta( $post_id, 'returned_by', '' );

		}

	}

	/**
	 * The email body which is sent to the business admin when the IT rep confirms the order.
	 *
	 * @param int    $order_post_id The order post ID.
	 * @param array  $acf_data      The array of Advanced Custom Field data.
	 * @param string $end_user_name The name of the end user who created the order.
	 *
	 * @return string
	 */
	private function email_body_it_rep_to_business( $post_id, $end_user_name ) {

		$program_id      = get_field( 'program', $post_id );
		$program_name    = get_the_title( $program_id );
		$contribution    = get_field( 'contribution_amount', $post_id );
		$addfund_amount  = '$' . number_format( $contribution, 2, '.', ',' );
		$addfund_account = get_field( 'contribution_account', $post_id );
		$order_url       = get_permalink( $post_id );
		$message         = "<p>
  Howdy<br />
  <strong>There is a new {$program_name} order that requires your attention for financial resolution.</strong></p>
<p>
  {$end_user_name} elected to contribute additional funds toward their order in the amount of {$addfund_amount}. An account reference of \"{$addfund_account}\" needs to be confirmed or replaced with the correct account number that will be used on the official requisition.
</p>
<p>
  You can view the order at this link: <a href=\"{$order_url}\">{$order_url}</a>.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	/**
	 * The email body which is sent to logistics.
	 *
	 * @param int   $order_post_id The order post ID.
	 * @param array $acf_data      The array of Advanced Custom Field data.
	 *
	 * @return string
	 */
	private function email_body_to_logistics( $post_id ) {

		$program_id      = get_field( 'program', $post_id );
		$program_name    = get_the_title( $program_id );
		$admin_order_url = admin_url() . "post.php?post={$post_id}&action=edit";
		$message         = "<p><strong>There is a new {$program_name} order that requires your approval.</strong></p>
<p>
  Please review this order carefully for any errors or omissions, then approve order for purchasing.
</p>
<p>
  You can view the order at this link: <a href=\"{$admin_order_url}\">{$admin_order_url}</a>.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	/**
	 * The email body which is sent to the end user when the order is returned.
	 *
	 * @param int   $order_post_id The order post ID.
	 * @param array $acf_data      The array of Advanced Custom Field data.
	 *
	 * @return string
	 */
	private function email_body_return_to_user( $post_id, $returned_comment ) {

		$order_url    = get_permalink( $post_id );
		$program_id   = get_field( 'program', $post_id );
		$program_name = get_the_title( $program_id );
		$actor_user   = wp_get_current_user();
		$actor_id     = $actor_user->ID;
		$actor_name   = $actor_user->display_name;
		$message      = "<p>
  Howdy,
</p>
<p>
  Your {$program_name} order has been returned by {$actor_name}. This could be because it was missing some required information, missing a necessary part, or could not be fulfilled as is. An explanation should appear below in the comments.
</p>
<p>
  Comments from {$actor_name}: {$returned_comment}
</p>
<p>
  Next step is to resolve your order's issue with the person who returned it (who has been copied on this email for your convenience), then correct the existing order. You may access your order online at any time using this link: <a href=\"{$order_url}\">{$order_url}</a>.
</p>

<p>
	Have a great day!<br />
	<em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	/**
	 * The email body which is sent to the IT rep and business admin when the order is returned to the end user.
	 *
	 * @param int    $order_post_id The order post ID.
	 * @param array  $acf_data      The array of Advanced Custom Field data.
	 * @param string $end_user_name The name of the end user who created the order.
	 *
	 * @return string
	 */
	private function email_body_return_to_user_forward( $post_id, $returned_comment, $end_user_name ) {

		$program_id   = get_field( 'program', $post_id );
		$program_name = get_the_title( $program_id );
		$actor_user   = wp_get_current_user();
		$actor_name   = $actor_user->display_name;
		$order_url    = get_permalink( $post_id );
		$message      = "<p>
  Howdy,
</p>
<p>
  The {$program_name} order for {$end_user_name} has been returned by {$actor_name}. An explanation should appear below in the comments.
</p>
<p>
  Comments from {$actor_name}: {$returned_comment}
</p>
<p>
  {$end_user_name} will correct the order and resubmit.
</p>
<p>
  You can view the order at this link: <a href=\"{$order_url}\">{$order_url}</a>.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	private function email_body_order_approved( $post_id ) {

		$program_id   = get_field( 'program', $post_id );
		$program_name = get_the_title( $program_id );
		$end_user_id  = get_field( 'order_author', $post_id );
		$user         = get_userdata( $end_user_id );
		$user_name    = $user->display_name;
		$message      = "<p>
	Howdy,
</p>
<p>
	The {$program_name} order for {$user_name} has been approved.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}
}
