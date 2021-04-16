<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's IT Logistics status.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/it-logistics-status-order-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5fff6f3cebfc8',
	'title' => 'IT Logistics Status',
	'fields' => array(
		array(
			'key' => 'field_5fff6f3cee555',
			'label' => 'IT Logistics Status',
			'name' => 'it_logistics_status',
			'type' => 'group',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'block',
			'sub_fields' => array(
				array(
					'key' => 'field_601d67ec53d63',
					'label' => 'Comments',
					'name' => 'comments',
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
				array(
					'key' => 'field_5fff6f3cef757',
					'label' => 'Confirm the order and notify the end user',
					'name' => 'confirmed',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 0,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key' => 'field_60074e2222cee',
					'label' => 'Ordered',
					'name' => 'ordered',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 0,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'current_user_role',
				'operator' => '==',
				'value' => 'wso_logistics',
			),
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'wsorder',
			),
		),
		array(
			array(
				'param' => 'current_user_role',
				'operator' => '==',
				'value' => 'wso_admin',
			),
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'wsorder',
			),
		),
	),
	'menu_order' => 6,
	'position' => 'normal',
	'style' => 'seamless',
	'label_placement' => 'left',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;
