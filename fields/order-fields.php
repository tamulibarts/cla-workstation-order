<?php
/**
 * The file that defines Advanced Custom Fields for the order post type.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/order-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	$user_id    = get_current_user_id();
	$user_meta  = get_userdata( $user_id );
	$user_roles = $user_meta->roles;
	if ( isset( $_GET['post'] ) && check_admin_referer() ) {
		$postid = sanitize_text_field( wp_unslash( $_GET['post'] ) );
	} else {
		$postid = 0;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_5ffcc0953abde',
			'title'                 => 'Order Fields',
			'fields'                => array(
				array(
					'key'               => 'field_5ffcc0a806823',
					'label'             => 'User',
					'name'              => 'user',
					'type'              => 'user',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'role'              => '',
					'allow_null'        => 0,
					'multiple'          => 0,
					'return_format'     => 'array',
				),
				array(
					'key'               => 'field_5ffcc19d06827',
					'label'             => 'Form Entry ID',
					'name'              => 'form_entry_id',
					'type'              => 'number',
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
					'prepend'           => '',
					'append'            => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
				),
				array(
					'key'               => 'field_5ffcc10806825',
					'label'             => 'Contribution Amount',
					'name'              => 'contribution_amount',
					'type'              => 'number',
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
					'prepend'           => '$',
					'append'            => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
				),
				array(
					'key'               => 'field_5ffcc16306826',
					'label'             => 'Account Number',
					'name'              => 'account_number',
					'type'              => 'text',
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
					'prepend'           => '',
					'append'            => '',
					'maxlength'         => '',
				),
				array(
					'key'               => 'field_5ffcc21406828',
					'label'             => 'Office Location',
					'name'              => 'office_location',
					'type'              => 'text',
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
					'prepend'           => '',
					'append'            => '',
					'maxlength'         => '',
				),
				array(
					'key'               => 'field_5ffcc22006829',
					'label'             => 'Current Asset',
					'name'              => 'current_asset',
					'type'              => 'number',
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
					'prepend'           => '',
					'append'            => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
				),
				array(
					'key'               => 'field_5ffcc22d0682a',
					'label'             => 'Order Comment',
					'name'              => 'order_comment',
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
				array(
					'key'               => 'field_5ffcc2590682b',
					'label'             => 'Program',
					'name'              => 'program',
					'type'              => 'select',
					'instructions'      => '',
					'required'          => 1,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'choices'           => array(
						'FY21' => 'FY21',
						'FY19' => 'FY19',
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
					'key'               => 'field_5ffcc2b90682c',
					'label'             => 'Order Items',
					'name'              => 'order_items',
					'type'              => 'repeater',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'collapsed'         => '',
					'min'               => 0,
					'max'               => 0,
					'layout'            => 'table',
					'button_label'      => '',
					'sub_fields'        => array(
						array(
							'key'               => 'field_5ffdfc23d5e87',
							'label'             => 'SKU',
							'name'              => 'sku',
							'type'              => 'number',
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
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5ffdfcbcbaaa3',
							'label'             => 'Item',
							'name'              => 'item',
							'type'              => 'text',
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
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5ffdfcfabaaa4',
							'label'             => 'Requisition Number',
							'name'              => 'requisition_number',
							'type'              => 'number',
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
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5ffdfd04baaa5',
							'label'             => 'Requisition Date',
							'name'              => 'requisition_date',
							'type'              => 'date_picker',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'd/m/Y',
							'return_format'     => 'd/m/Y',
							'first_day'         => 1,
						),
						array(
							'key'               => 'field_5ffdfd0ebaaa6',
							'label'             => 'Asset #',
							'name'              => 'asset_number',
							'type'              => 'number',
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
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5ffdfd1abaaa7',
							'label'             => 'Price',
							'name'              => 'price',
							'type'              => 'number',
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
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
					),
				),
				array(
					'key'               => 'field_5ffe12e5d0bcd',
					'label'             => 'Approval Status',
					'name'              => 'approval_status',
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
						'Action Required'  => 'Action Required',
						'Returned'         => 'Returned',
						'Completed'        => 'Completed',
						'Awaiting Another' => 'Awaiting Another',
					),
					'default_value'     => 'Action Required',
					'allow_null'        => 0,
					'multiple'          => 0,
					'ui'                => 0,
					'return_format'     => 'value',
					'ajax'              => 0,
					'placeholder'       => '',
				),
			),
			'location'              => array(
				array(
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

	acf_add_local_field(
		array(
			'key'               => 'field_5ffcc41c0682f',
			'label'             => 'IT Staff',
			'name'              => 'it_staff',
			'type'              => 'user',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'role'              => '',
			'allow_null'        => 1,
			'multiple'          => 0,
			'return_format'     => 'array',
			'parent'            => 'group_5ffcc0953abde',
		)
	);

	$post_it_rep    = get_field( 'it_staff', $postid );
	$post_it_rep_id = $post_it_rep['ID'];
	if ( $user_id === $post_it_rep_id ) {
		acf_add_local_field(
			array(
				'key'               => 'field_5ffcc46406830',
				'label'             => 'IT Staff Confirmed',
				'name'              => 'it_staff_confirmed',
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
				'parent'            => 'group_5ffcc0953abde',
			)
		);
	}

	acf_add_local_field(
		array(
			'key'               => 'field_5ffcc49606831',
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
			'role'              => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'return_format'     => 'array',
			'parent'            => 'group_5ffcc0953abde',
		)
	);

	$post_business_staff    = get_field( 'business_staff', $postid );
	$post_business_staff_id = $post_business_staff['ID'];
	if ( $post_business_staff_id === $user_id ) {
		acf_add_local_field(
			array(
				'key'               => 'field_5ffcc4a444a8d',
				'label'             => 'Business Staff Confirmed',
				'name'              => 'business_staff_confirmed',
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
				'parent'            => 'group_5ffcc0953abde',
			)
		);
	}

	acf_add_local_field(
		array(
			'key'               => 'field_5ffcc4cd44a8e',
			'label'             => 'IT Logistics',
			'name'              => 'it_logistics',
			'type'              => 'user',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'role'              => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'return_format'     => 'array',
			'parent'            => 'group_5ffcc0953abde',
		)
	);

	if ( $user ) {
		acf_add_local_field(
			array(
				'key'               => 'field_5ffcc4db44a8f',
				'label'             => 'IT Logistics Confirmed',
				'name'              => 'it_logistics_confirmed',
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
				'parent'            => 'group_5ffcc0953abde',
			)
		);
	}

	if ( current_user_can( 'edit_department' ) ) {
		acf_add_local_field(
			array(
				'key'    => 'field_5ffcc4db44a8g',
				'label'  => 'Department Comments',
				'name'   => 'department_comments',
				'type'   => 'textarea',
				'parent' => 'group_5ffcc0953abde',
			)
		);
	}

endif;
