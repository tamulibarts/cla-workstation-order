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
			'return_format' => 'id',
		),
		array(
			'key' => 'field_6048dfb535163',
			'label' => 'Author\'s Department',
			'name' => 'author_department',
			'type' => 'post_object',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'post_type' => array(
				0 => 'department',
			),
			'taxonomy' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'return_format' => 'id',
			'ui' => 1,
		),
		array(
			'key' => 'field_6048e8d2b575a',
			'label' => 'Affiliated IT Reps',
			'name' => 'affiliated_it_reps',
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
			'multiple' => 1,
			'return_format' => 'id',
		),
		array(
			'key' => 'field_6048e9f6b575b',
			'label' => 'Affiliated Business Staff',
			'name' => 'affiliated_business_staff',
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
				0 => 'wso_business_admin',
			),
			'allow_null' => 0,
			'multiple' => 1,
			'return_format' => 'id',
		),
		array(
			'key' => 'field_60994a3b0425c',
			'label' => 'Selected Products and Bundles',
			'name' => 'selected_products_and_bundles',
			'type' => 'post_object',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'post_type' => array(
				0 => 'product',
				1 => 'bundle',
			),
			'taxonomy' => '',
			'allow_null' => 1,
			'multiple' => 1,
			'return_format' => 'id',
			'ui' => 1,
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
