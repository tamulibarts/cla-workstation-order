<?php
/**
 * The file that defines Advanced Custom Fields for the settings page.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/settings-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_60099bc1ded11',
			'title'                 => 'Workstation Order Settings',
			'fields'                => array(
				array(
					'key'               => 'field_60099c44333b3',
					'label'             => 'Current Program',
					'name'              => 'current_program',
					'type'              => 'post_object',
					'instructions'      => '',
					'required'          => 1,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'post_type'         => array(
						0 => 'program',
					),
					'taxonomy'          => '',
					'allow_null'        => 0,
					'multiple'          => 0,
					'return_format'     => 'object',
					'ui'                => 1,
				),
				array(
					'key'               => 'field_60bf90684b119',
					'label'             => 'Unfunded Program',
					'name'              => 'unfunded_program',
					'type'              => 'post_object',
					'instructions'      => 'This is the program users can select when they want to submit an order their unit will fund 100% outside of any ordering program.',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'post_type'         => array(
						0 => 'program',
					),
					'taxonomy'          => '',
					'allow_null'        => 0,
					'multiple'          => 0,
					'return_format'     => 'object',
					'ui'                => 1,
				),
				array(
					'key'               => 'field_601da56c35bfe',
					'label'             => 'Logistics Email',
					'name'              => 'logistics_email',
					'type'              => 'email',
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
				),
				array(
					'key'               => 'field_601da63a35bff',
					'label'             => 'Enable Emails to Logistics',
					'name'              => 'enable_emails_to_logistics',
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
					'default_value'     => 1,
					'ui'                => 0,
					'ui_on_text'        => '',
					'ui_off_text'       => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'wsorder-settings',
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
