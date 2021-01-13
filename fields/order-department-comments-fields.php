<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's department comments field.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/order-department-comments-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_5fff71a842549',
			'title'                 => 'Order Department Comments',
			'fields'                => array(
				array(
					'key'               => 'field_5fff71b0097c3',
					'label'             => 'Department Comments',
					'name'              => 'department_comments',
					'type'              => 'textarea',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
					'maxlength'         => '',
					'rows'              => '',
					'new_lines'         => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'current_user_role',
						'operator' => '==',
						'value'    => 'wso_department_it_rep',
					),
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'wsorder',
					),
				),
				array(
					array(
						'param'    => 'current_user_role',
						'operator' => '==',
						'value'    => 'wso_department_business_admin',
					),
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'wsorder',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
		)
	);

endif;
