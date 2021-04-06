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
		add_action( 'transition_post_status', array( $this, 'order_rep_confirmed_bus_approval_needed' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'order_rep_confirmed_bus_approval_not_needed' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'order_bus_confirmed_notify_logistics' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'order_logistics_confirmed_notify_end_user' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'handle_returned_order_emails' ), 10, 3 );

	}

	/**
	 * Get users assigned to active user's department for current program, as array.
	 */
	private function get_program_business_admin_user_id( $program_id, $user_department_post_id ) {

		$program_meta_keys_departments = array(
			'assign_political_science_department_post_id',
			'assign_sociology_department_post_id',
			'assign_philosophy_humanities_department_post_id',
			'assign_performance_studies_department_post_id',
			'assign_international_studies_department_post_id',
			'assign_history_department_post_id',
			'assign_hispanic_studies_department_post_id',
			'assign_english_department_post_id',
			'assign_economics_department_post_id',
			'assign_communication_department_post_id',
			'assign_anthropology_department_post_id',
			'assign_psychology_department_post_id',
			'assign_dean_department_post_id',
		);
		$current_program_post_meta     = get_post_meta( $program_id );
		$value                         = 0;

		foreach ( $program_meta_keys_departments as $meta_key ) {
			$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
			if ( $user_department_post_id === $assigned_dept ) {
				$base_key                     = preg_replace( '/_department_post_id$/', '', $meta_key );
				$dept_assigned_business_admin = unserialize( $current_program_post_meta[ "{$base_key}_business_admins" ][0] );
				$value                        = $dept_assigned_business_admin[0];
				break;
			}
		}

		return $value;

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
	public function order_rep_confirmed_bus_approval_needed( $new_status, $old_status, $post ) {

		if (
		  'wsorder' !== $post->post_type
			|| $new_status === 'auto-draft'
			|| ! array_key_exists( 'acf', $_POST )
		) {
			return;
		}

		if (
			current_user_can( 'wso_it_rep' )
			|| current_user_can( 'wso_admin' )
		) {

			// Get confirmation statuses.
			$old_post_it_confirm = (int) get_post_meta( $post->ID, 'it_rep_status_confirmed', true );
			$new_post_it_confirm = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff6b71a22b0'];
			// If business admin is assigned, continue.
			$business_admin_id = isset( $_POST['acf']['field_5fff70b84ffe4'] ) ? $_POST['acf']['field_5fff70b84ffe4'] : '';

			if (
				0 === $old_post_it_confirm
				&& 1 === $new_post_it_confirm
				&& ! empty( $business_admin_id )
			) {

				// Get the order name.
				$order_name = get_the_title( $post->ID );
				// Declare end user variables.
				$user_id                 = $post->post_author;
				$end_user                = get_user_by( 'id', $user_id );
				$end_user_name           = $end_user->user_name;
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
				// Declare business admin variables.
				$business_admin_obj    = get_userdata( $business_admin_id );
				$business_admin_email  = $business_admin_obj->user_email;
				$business_admins       = get_field( 'affiliated_business_staff', $post_id );
				$business_admin_emails = array();
				foreach ( $business_admins as $bus_user_id ) {
					$user_data               = get_userdata( $bus_user_id );
					$business_admin_emails[] = $user_data->user_email;
				}
				$business_admin_emails = implode( ',', $business_admin_emails );
				// Send email.
				$to      = $business_admin_emails;
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_it_rep_to_business( $post->ID, $_POST['acf'], $end_user_name );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				wp_mail( $to, $title, $message, $headers );

			}
		}
	}

	/**
	 * Once IT Rep has confirmed, if business approval NOT needed then
	 * send an email to the logistics address.
	 *
	 * @param string $new_status The new status of the post.
	 * @param string $old_status The old status of the post.
	 * @param object $post       The WP_Post object.
	 *
	 * @return void
	 */
	public function order_rep_confirmed_bus_approval_not_needed( $new_status, $old_status, $post ) {

		if (
		  'wsorder' !== $post->post_type
			|| $new_status === 'auto-draft'
			|| ! array_key_exists( 'acf', $_POST )
		) {
			return;
		}

		if (
			current_user_can( 'wso_it_rep' )
			|| current_user_can( 'wso_admin' )
		) {

			// Get confirmation statuses.
			$old_post_it_confirm = (int) get_post_meta( $post->ID, 'it_rep_status_confirmed', true );
			$new_post_it_confirm = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff6b71a22b0'];
			// If business admin is assigned, continue.
			$business_admin_id = isset( $_POST['acf']['field_5fff70b84ffe4'] ) ? (int) $_POST['acf']['field_5fff70b84ffe4'] : '';
			// Get logistics email setting.
			$enable_logistics_email = (int) get_field( 'enable_emails_to_logistics', 'option' );

			if (
				0 === $old_post_it_confirm
				&& 1 === $new_post_it_confirm
				&& empty( $business_admin_id )
				&& 1 === $enable_logistics_email
			) {

				// Get the order name.
				$order_name = get_the_title( $post->ID );
				// Declare end user variables.
				$user_id                 = $post->post_author;
				$end_user                = get_user_by( 'id', $user_id );
				$end_user_name           = $end_user->user_name;
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
				// Get logistics email.
				$logistics_email = get_field( 'logistics_email', 'option' );
				// Send email.
				$to      = $logistics_email;
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_to_logistics( $post->ID, $_POST['acf'] );
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
	public function order_bus_confirmed_notify_logistics( $new_status, $old_status, $post ) {

		if (
		  'wsorder' !== $post->post_type
			|| $new_status === 'auto-draft'
			|| ! array_key_exists( 'acf', $_POST )
		) {
			return;
		}

		if (
			current_user_can( 'wso_business_admin' )
			|| current_user_can( 'wso_admin' )
		) {

			// Get confirmation statuses.
			$old_post_bus_confirm = (int) get_post_meta( $post->ID, 'business_staff_status_confirmed', true );
			$new_post_bus_confirm = 0;
			// Logistics email enabled.
			$enable_logistics_email = get_field( 'enable_emails_to_logistics', 'option' );
			if ( array_key_exists( 'field_5fff6ec0e4385', $_POST['acf']['field_5fff6ec0e2f7e'] ) ) {
				$new_post_bus_confirm = (int) $_POST['acf']['field_5fff6ec0e2f7e']['field_5fff6ec0e4385'];
			}

			if (
				0 === $old_post_bus_confirm
				&& 1 === $new_post_bus_confirm
				&& 1 === $enable_logistics_email
			) {

				// Get the order name.
				$order_name = get_the_title( $post->ID );
				// Declare end user variables.
				$user_id                 = $post->post_author;
				$end_user                = get_user_by( 'id', $user_id );
				$end_user_name           = $end_user->user_name;
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
				// Get logistics email.
				$logistics_email = get_field( 'logistics_email', 'option' );
				// Send email.
				$to      = $logistics_email;
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_to_logistics( $post_id, $_POST['acf'] );
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
	public function order_logistics_confirmed_notify_end_user( $new_status, $old_status, $post ) {

		if (
		  'wsorder' !== $post->post_type
			|| $new_status === 'auto-draft'
			|| ! array_key_exists( 'acf', $_POST )
		) {
			return;
		}

		if (
			current_user_can( 'wso_logistics' )
			|| current_user_can( 'wso_admin' )
		) {

			// Get confirmation statuses.
			$old_post_log_confirm = (int) get_post_meta( $post->ID, 'it_logistics_status_confirmed', true );
			$new_post_log_confirm = (int) $_POST['acf']['field_5fff6f3cee555']['field_5fff6f3cef757'];

			if (
				0 === $old_post_log_confirm
				&& 1 === $new_post_log_confirm
			) {

				// Get the order name.
				$order_name = get_the_title( $post->ID );
				// Declare end user variables.
				$user_id                 = $post->post_author;
				$end_user                = get_user_by( 'id', $user_id );
				$end_user_email          = $end_user->user_email;
				$end_user_name           = $end_user->user_name;
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$department_abbreviation = get_field( 'abbreviation', $user_department_post->ID );
				// Send email.
				$to      = $end_user_email;
				$title   = "[{$order_name}] Workstation Order Approval - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_order_approved( $post->ID, $_POST['acf'] );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				wp_mail( $to, $title, $message, $headers );

			}
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
			|| $new_status === 'auto-draft'
			|| ! array_key_exists( 'acf', $_POST )
		) {
			return;
		}

		// Get email headers.
		$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
		$post_id    = $post->ID;
		$order_name = get_the_title( $post_id );
		// Declare current user variables.
		$current_user      = wp_get_current_user();
		$current_user_id   = $current_user->ID;
		$current_user_name = $current_user->display_name;
		// Declare end user variables.
		$user_id                 = $post->post_author;
		$end_user                = get_user_by( 'id', $user_id );
		$end_user_email          = $end_user->user_email;
		$end_user_name           = $end_user->user_name;
		$user_department_post    = get_field( 'department', "user_{$user_id}" );
		$user_department_post_id = $user_department_post->ID;
		$department_abbreviation = get_field( 'abbreviation', $user_department_post_id );
		// Declare IT Rep user variables.
		$it_rep_user_id = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff703a5289f'];
		$it_reps        = get_field( 'affiliated_it_reps', $post_id );
		$it_rep_emails  = array();
		foreach ( $it_reps as $rep_user_id ) {
			$user_data       = get_userdata( $rep_user_id );
			$it_rep_emails[] = $user_data->user_email;
		}
		$it_rep_emails = implode( ',', $it_rep_emails );
		// Declare business approval variables.
		$contribution_amount   = $_POST['acf']['field_5ffcc10806825'];
		$order_program_id      = get_field( 'program', $post_id );
		$business_admin_id     = $_POST['acf']['field_5fff70b84ffe4'];
		$business_admin_emails = '';
		if ( ! empty( $business_admin_id ) ) {
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
		// Get confirmation statuses.
		$old_post_it_confirm  = (int) get_post_meta( $post_id, 'it_rep_status_confirmed', true );
		$new_post_it_confirm  = (int) $_POST['acf']['field_5fff6b46a22af']['field_5fff6b71a22b0'];
		$old_post_log_confirm = (int) get_post_meta( $post_id, 'it_logistics_status_confirmed', true );
		$new_post_log_confirm = (int) $_POST['acf']['field_5fff6f3cee555']['field_5fff6f3cef757'];
		$old_post_bus_confirm = (int) get_post_meta( $post_id, 'business_staff_status_confirmed', true );
		if ( array_key_exists( 'field_5fff6ec0e4385', $_POST['acf']['field_5fff6ec0e2f7e'] ) ) {
			$new_post_bus_confirm = (int) $_POST['acf']['field_5fff6ec0e2f7e']['field_5fff6ec0e4385'];
		} else {
			$new_post_bus_confirm = 0;
		}

		/**
		 * Handle returned order emails.
		 */
		if (
			$new_status === 'returned'
			&& $old_status !== 'returned'
		) {

			// Store user ID who returned the order.
			update_post_meta( $post_id, 'returned_by', $current_user_id );

			/**
			 * If status changed to "Returned" ->
			 * subject: [{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}
			 * to: end user
			 * cc: whoever set it to return
			 * body: email_body_return_to_user( $post->ID, $_POST['acf'] );
			 */
			$to      = $end_user_email;
			$to_cc   = $current_user->user_email;
			$title   = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
			$message = $this->email_body_return_to_user( $post_id, $_POST['acf'] );
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
			if ( ! empty( $to ) ) {
				$title   = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
				$message = $this->email_body_return_to_user_forward( $post_id, $_POST['acf'], $end_user_name );
				wp_mail( $to, $title, $message, $headers );
			}
		}

		/**
		 * When end user addresses the work order after it was returned to them.
		 */
		if (
			'returned' === $old_status
			&& 'action_required' === $new_status
		) {

			// Notify the person who returned the request.
			$returner_id          = get_post_meta( $post_id, 'returned_by', true );
			$returner_data        = get_userdata( $returner_id );
			$returner_roles       = $returner_data->roles;
			$returner_email       = $returner_data->user_email;
			$returner_role_emails = array();
			// Figure out who returned it.
			if ( in_array( 'wso_admin', $returner_roles ) ) {
				$returner_role_emails = $returner_email;
			} elseif ( in_array( 'wso_it_rep', $returner_roles ) ) {
				// IT Rep returned it.
				$returner_role_emails = $it_rep_emails;
			} elseif ( in_array( 'wso_business_admin', $returner_roles ) ) {
				// Business admin returned it.
				$returner_role_emails = $business_admin_emails;
			}
			$to              = $returner_role_emails;
			$title           = "[{$order_name}] Returned Workstation Order - {$department_abbreviation} - {$end_user_name}";
			$admin_order_url = admin_url() . "post.php?post={$post_id}&action=edit";
			$message         = 'Please check on this work order as the end user has passed it on: $admin_order_url';
			wp_mail( $to, $title, $message, $headers );
			// Empty the "returned by" post meta.
			update_post_meta( $post_id, 'returned_by', '' );

		}

	}

	private function email_body_it_rep_to_business( $order_post_id, $acf_data, $end_user_name ) {
		error_log( 'end user: (' . gettype( $end_user_name ) . ') ' . $end_user_name );
		$program_name    = get_the_title( $acf_data['field_5ffcc2590682b'] );
		$addfund_amount  = $acf_data['field_5ffcc10806825'];
		$addfund_account = $acf_data['field_5ffcc16306826'];
		$admin_order_url = admin_url() . "post.php?post={$order_post_id}&action=edit";
		$message         = "<p>
  Howdy<br />
  <strong>There is a new {$program_name} order that requires your attention for financial resolution.</strong></p>
<p>
  {$end_user_name} elected to contribute additional funds toward their order in the amount of {$addfund_amount}. An account reference of \"{$addfund_account}\" needs to be confirmed or replaced with the correct account number that will be used on the official requisition.
</p>
<p>
  You can view the order at this link: {$admin_order_url}.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	private function email_body_to_logistics( $order_post_id, $acf_data ) {

		$program_name    = get_the_title( $acf_data['field_5ffcc2590682b'] );
		$admin_order_url = admin_url() . "post.php?post={$order_post_id}&action=edit";
		$message         = "<p><strong>There is a new {$program_name} order that requires your approval.</strong></p>
<p>
  Please review this order carefully for any errors or omissions, then approve order for purchasing.
</p>
<p>
  You can view the order at this link: {$admin_order_url}.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	private function email_body_return_to_user( $order_post_id, $acf_data ) {

		$program_name     = get_the_title( $acf_data['field_5ffcc2590682b'] );
		$actor_user       = wp_get_current_user();
		$actor_name       = $actor_user->display_name;
		$returned_comment = $acf_data['field_601d52f2e5418'];
		$admin_order_url  = admin_url() . "post.php?post={$order_post_id}&action=edit";
		$message          = "<p>
  Howdy,
</p>
<p>
  Your {$program_name} order has been returned by {$actor_name}. This could be because it was missing some required information, missing a necessary part, or could not be fulfilled as is. An explanation should appear below in the comments.
</p>
<p>
  Comments from {$actor_name}: {$returned_comment}
</p>
<p>
  Next step is to resolve your order's issue with the person who returned it (who has been copied on this email for your convenience), then correct the existing order. You may access your order online at any time using this link: {$admin_order_url}.
</p>

<p>
	Have a great day!<br />
	<em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	private function email_body_return_to_user_forward( $order_post_id, $acf_data, $end_user_name ) {

		$program_name     = get_the_title( $acf_data['field_5ffcc2590682b'] );
		$actor_user       = wp_get_current_user();
		$actor_name       = $actor_user->display_name;
		$returned_comment = $acf_data['field_601d52f2e5418'];
		$admin_order_url  = admin_url() . "post.php?post={$order_post_id}&action=edit";
		$message          = "<p>
  Howdy,
</p>
<p>
  The {$program_name} order for {$user_name} has been returned by {$actor_name}. An explanation should appear below in the comments.
</p>
<p>
  Comments from {$actor_name}: {$returned_comment}
</p>
<p>
  {$user_name} will correct the order and resubmit.
</p>
<p>
  You can view the order at this link: {$admin_order_url}.
</p>
<p>
  Have a great day!<br />
  <em>- Liberal Arts IT</em>
</p>
<p><em>This email was sent from an unmonitored email address. Please do not reply to this email.</em></p>";

		return $message;

	}

	private function email_body_order_approved( $order_post_id, $acf_data ) {

		$program_name = get_the_title( $acf_data['field_5ffcc2590682b'] );
		$user         = get_userdata( $acf_data['field_60302052bab04'] );
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
