<?php
/**
 * The file that defines Advanced Custom Fields for the program post type.
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/fields/program-fields.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		array(
			'key'                   => 'group_5fff77f0910ae',
			'title'                 => 'Program Fields',
			'fields'                => array(
				array(
					'key'               => 'field_5fff77faa4987',
					'label'             => 'Prefix',
					'name'              => 'prefix',
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
					'maxlength'         => 16,
				),
				array(
					'key'               => 'field_5fff7813a4988',
					'label'             => 'Allocation',
					'name'              => 'allocation',
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
					'key'               => 'field_5fff781da4989',
					'label'             => 'Threshold',
					'name'              => 'threshold',
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
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'program',
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
