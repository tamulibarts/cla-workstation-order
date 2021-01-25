<?php
/**
 * The file that modifies the Gravity Forms Workstation Order form.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-order-form-mods.php
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
class Order_Form_Mods {

	/**
	 * File name
	 *
	 * @var file
	 */
	private static $file = __FILE__;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'gform_pre_render_1', array( $this, 'populate_posts' ) );
		add_filter( 'gform_pre_validation_1', array( $this, 'populate_posts' ) );
		add_filter( 'gform_pre_submission_filter_1', array( $this, 'populate_posts' ) );
	}

	/**
	 * Populate Order Form elements with current-year program's department's IT Rep and Business Admin users
	 * based on current user's department.
	 *
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function populate_posts( $form ) {

		// Add IT Reps form field.
		foreach ( $form['fields'] as $field ) {
			if ( 'select' !== $field->type ) {
				continue;
			} elseif (
			strpos( $field->cssClass, 'populate-it-reps' ) !== false // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			|| strpos( $field->cssClass, 'populate-business-admins' ) !== false // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			) {

				// Get current user and user ID.
				$user    = wp_get_current_user();
				$user_id = $user->get( 'ID' );

				// Get user's department.
				$user_department_post    = get_field( 'department', "user_{$user_id}" );
				$user_department_post_id = $user_department_post->ID;

				// Get current program meta.
				$current_program_post      = get_field( 'current_program', 'option' );
				$current_program_id        = $current_program_post->ID;
				$current_program_post_meta = get_post_meta( $current_program_id );

				// Get current program IT Reps and Business Admins.
				$department_ids = array();
				foreach ( $current_program_post_meta as $key => $value ) {
					$value = $value[0];
					if ( false !== strpos( $key, '_department_post_id' ) && false === strpos( $value, 'field' ) ) {
						$department_ids[ $key ] = (int) $value;
					}
				}
				$dept_key = '';
				foreach ( $department_ids as $key => $value ) {
					if ( $user_department_post_id === $value ) {
						$dept_key = str_replace( '_department_post_id', '', $key );
					}
				}
				if ( strpos( $field->cssClass, 'populate-it-reps' ) !== false ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$users_by_dept = get_post_meta( $current_program_id, "{$dept_key}_it_reps" )[0];
				} else {
					$users_by_dept = get_post_meta( $current_program_id, "{$dept_key}_business_admins" )[0];
				}

				// Get IT Rep user objects.
				$choices = array();
				foreach ( $users_by_dept as $key => $user_id ) {
					$user_id   = (int) $user_id;
					$user_meta = get_user_meta( $user_id );
					$user_name = $user_meta['first_name'][0] . ' ' . $user_meta['last_name'][0];
					$choices[] = array(
						'text'  => $user_name,
						'value' => $user_id,
					);
				}

				// update 'Select a Post' to whatever you'd like the instructive option to be.
				$field->placeholder = 'Select a User';
				$field->choices     = $choices;
			}
		}

		return $form;
	}

}
