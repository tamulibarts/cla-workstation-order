<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's IT Rep status.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/it-rep-status-order-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_5ffddda6eaa7a',
			'title'                 => 'IT Rep Status',
			'fields'                => array(
				array(
					'key'               => 'field_5fff6b46a22af',
					'label'             => 'IT Rep Status',
					'name'              => 'it_rep_status',
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
							'key'               => 'field_5fff703a5289f',
							'label'             => 'IT Rep',
							'name'              => 'it_rep',
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
								0 => 'wso_it_rep',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'array',
						),
						array(
							'key'               => 'field_5fff6b71a22b0',
							'label'             => 'Confirmed',
							'name'              => 'confirmed',
							'type'              => 'true_false',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 0,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5fff6b86a22b1',
							'label'             => 'Date',
							'name'              => 'date',
							'type'              => 'date_time_picker',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5fff6b71a22b0',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'F j, Y g:i a',
							'return_format'     => 'Y-m-d H:i:s',
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
						'value'    => 'wso_it_rep',
					),
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'wsorder',
					),
				),
			),
			'menu_order'            => 1,
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
