<?php
/**
 * The file that defines the Gravity Form leads helper class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-leads-into-orders.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * The core plugin class
 *
 * @since 1.0.0
 * @return void
 */
class Leads_Into_Orders {

	/**
	 * File name
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * Array of post meta keys pointing to department post ids in program posts.
	 *
	 * @var program_meta_keys_departments
	 */
	private $program_meta_keys_departments = array(
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

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'gform_after_submission_1', array( $this, 'create_wsorder_post' ), 10, 2 );
	}
	/**
	 * After submission action hook
	 *
	 * @param object $entry The Entry Object that was just created.
	 * @param object $form  The current Form Object.
	 *
	 * @return void;
	 */
	public function create_wsorder_post( $entry, $form ) {
		echo '<pre>';
		print_r( $entry );
		echo '</pre>';
		// Get current user and user ID.
		$user    = wp_get_current_user();
		$user_id = $user->get( 'ID' );

		// Make post title using current program ID and incremented order ID from last order.
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
		// Insert post.
		$postarr = array(
			'post_author'    => $user_id,
			'post_status'    => 'draft',
			'post_type'      => 'wsorder',
			'comment_status' => 'closed',
			'post_title'     => "{$current_program_prefix}-{$wsorder_id}",
			'post_content'   => '',
			'post_date'      => $entry['date_created'],
			'post_date_gmt'  => $entry['date_created'],
			'meta_input'     => array(
				'order_id' => $wsorder_id,
			),
		);
		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {

			// Failed to generate a new post.
			// Send email to developer.
			$link     = get_bloginfo( 'url' );
			$form_id  = $form['id'];
			$entry_id = $entry['id'];
			$link    .= '/wp-admin/admin.php?page=gf_entries&view=entry&id=' . $form_id . '&lid=' . $entry_id . '&order=ASC&filter&paged=1&pos=0&field_id&operator';
			$message  = 'Gravity Form for new orders failed to generate a new order post from <a href="$link">entry #$entry_id</a>';

			wp_mail( 'zwatkins2@tamu.edu', 'Failed: Gravity Form Lead to Order Conversion Failed', $message );

		} else {

			// Get user's department.
			$user_department_post    = get_field( 'department', "user_{$user_id}" );
			$user_department_post_id = $user_department_post->ID;

			// Get users assigned to active user's department for current program, as array.
			$dept_assigned_users = array();
			foreach ( $this->program_meta_keys_departments as $meta_key ) {
				$assigned_dept = (int) $current_program_post_meta[ $meta_key ][0];
				if ( $user_department_post_id === $assigned_dept ) {
					$base_key                              = preg_replace( '/_department_post_id$/', '', $meta_key );
					$dept_assigned_users['it_rep']         = $current_program_post_meta[ "{$base_key}_it_reps" ][0];
					$dept_assigned_users['business_admin'] = $current_program_post_meta[ "{$base_key}_business_admins" ][0];
					break;
				}
			}

			/**
			 * Save ACF field values.
			 * https://www.advancedcustomfields.com/resources/update_field/
			 */

			// Save customer user ID.
			$field_key = 'field_5ffcc0a806823';
			$value     = $user->get( 'ID' );
			update_field( $field_key, $value, $post_id );

			// Save form entry ID.
			$field_key = 'field_5ffcc19d06827';
			$value     = $entry['id'];
			update_field( $field_key, $value, $post_id );

			// Save contribution amount.
			$field_key = 'field_5ffcc10806825';
			$value     = $entry['10'];
			update_field( $field_key, $value, $post_id );

			// Save account number.
			$field_key = 'field_5ffcc16306826';
			$value     = $entry['12'];
			update_field( $field_key, $value, $post_id );

			// Save building location.
			$field_key = 'field_6009bcb19bba2';
			$value     = $entry['2'];
			update_field( $field_key, $value, $post_id );

			// Save office location.
			$field_key = 'field_5ffcc21406828';
			$value     = $entry['3'];
			update_field( $field_key, $value, $post_id );

			// Save current asset.
			$field_key = 'field_5ffcc22006829';
			$value     = $entry['4'];
			update_field( $field_key, $value, $post_id );

			// Save order comment.
			$field_key = 'field_5ffcc22d0682a';
			$value     = $entry['13'];
			update_field( $field_key, $value, $post_id );

			// Save program.
			$field_key = 'field_5ffcc2590682b';
			$value     = $current_program_id;
			update_field( $field_key, $value, $post_id );

			// Save department IT Rep.
			$field_key = 'field_5fff703a5289f';
			$value     = $dept_assigned_users['it_rep'];
			update_field( $field_key, $value, $post_id );

			// Save department Business Admin.
			$field_key = 'field_5fff70b84ffe4';
			$value     = $dept_assigned_users['business_admin'];
			update_field( $field_key, $value, $post_id );

			// Save order items.
			$field_key = 'field_5ffcc2b90682c';

			/**
			// Save order status.
			$field_key = 'field_5ffe12e5d0bcd';
			$value     = array( 'Action Required' );
			update_field( $field_key, $value, $post_id );
			// Save IT staff status.
			$field_key = 'field_5ffcc41c0682f';
			$value     = $entry['1'];
			update_field( $field_key, $value, $post_id );
			// Save processing staff status.
			// Processing staff is determined by a "program".
			// A "program" determines people per department and role.
			$field_key = 'field_5ffcc3cc0682e';
			*/
		}
	}

}
