<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's IT Rep status.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/it-rep-status-order-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5ffddda6eaa7a',
	'title' => 'IT Rep Status',
	'fields' => array(
		array(
			'key' => 'field_5fff6b46a22af',
			'label' => 'IT Rep Status',
			'name' => 'it_rep_status',
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
					'key' => 'field_5fff703a5289f',
					'label' => 'IT Rep',
					'name' => 'it_rep',
					'type' => 'user',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'role' => array(
						0 => 'wso_it_rep',
					),
					'allow_null' => 0,
					'multiple' => 0,
					'return_format' => 'array',
				),
				array(
					'key' => 'field_601d66373860d',
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
					'key' => 'field_5fff6b71a22b0',
					'label' => 'Confirm your approval of the work order and notify the next staff member for their approval',
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
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'current_user_role',
				'operator' => '==',
				'value' => 'wso_it_rep',
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
				'value' => 'wso_logistics_admin',
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
	'menu_order' => 4,
	'position' => 'normal',
	'style' => 'seamless',
	'label_placement' => 'left',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;
