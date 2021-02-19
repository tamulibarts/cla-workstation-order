<?php
/**
 * The file that defines Advanced Custom Fields for non-subscriber editing the order post type.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/wsorder-admin-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_601d4a5125382',
	'title' => 'Administrative Fields',
	'fields' => array(
		array(
			'key' => 'field_601d596738d58',
			'label' => 'Order ID',
			'name' => 'order_id',
			'type' => 'number',
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
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => 1,
		),
		array(
			'key' => 'field_60302052bab04',
			'label' => 'Author',
			'name' => 'order_author',
			'type' => 'user',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'role' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'return_format' => 'array',
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
