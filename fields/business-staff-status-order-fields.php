<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's business staff status fields.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/business-staff-status-order-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_5fff6ec0e01ee',
			'title'                 => 'Business Staff Status',
			'fields'                => array(
				array(
					'key'               => 'field_5fff6ec0e2f7e',
					'label'             => '',
					'name'              => 'business_staff_status',
					'type'              => 'group',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'layout'            => 'block',
					'sub_fields'        => array(
						array(
							'key'               => 'field_5fff70b84ffe4',
							'label'             => 'Business Staff',
							'name'              => 'business_staff',
							'type'              => 'user',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'role'              => array(
								0 => 'wso_business_admin',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'array',
						),
						array(
							'key'               => 'field_5fff6ec0e4385',
							'label'             => 'Status',
							'name'              => 'status',
							'type'              => 'select',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'Not confirmed' => 'Not confirmed',
								'Confirmed'     => 'Confirmed',
							),
							'default_value'     => false,
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 0,
							'return_format'     => 'value',
							'ajax'              => 0,
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5fff6ec0e438b',
							'label'             => 'Date',
							'name'              => 'date',
							'type'              => 'date_time_picker',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5fff6ec0e4385',
										'operator' => '==',
										'value'    => 'Confirmed',
									),
								),
							),
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'F j, Y g:i a',
							'return_format'     => 'F j, Y g:i a',
							'first_day'         => 0,
						),
					),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'current_user_role',
						'operator' => '==',
						'value'    => 'wso_business_admin',
					),
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'wsorder',
					),
				),
			),
			'menu_order'            => 2,
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
