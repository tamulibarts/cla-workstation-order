<?php
/**
 * The file that defines Advanced Custom Fields for returning the order to the end user.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/wsorder-return-to-user-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_601d52d563efc',
	'title' => 'Return to User',
	'fields' => array(
		array(
			'key' => 'field_601d52f2e5418',
			'label' => 'Comments',
			'name' => 'returned_comments',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'maxlength' => '',
			'rows' => '',
			'new_lines' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'wsorder',
			),
			array(
				'param' => 'current_user_role',
				'operator' => '!=',
				'value' => 'subscriber',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'left',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;
