<?php
/**
 * The file that defines Advanced Custom Fields for the order post type's department comments field.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/fields/order-department-comments-fields.php
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/fields
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5fff71a842549',
	'title' => 'Department Comments',
	'fields' => array(
		array(
			'key' => 'field_5fff71b0097c3',
			'label' => ' ',
			'name' => 'department_comments',
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
	),
	'location' => array(
		array(
			array(
				'param' => 'current_user_role',
				'operator' => '==',
				'value' => 'wso_department_it_rep',
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
				'value' => 'wso_department_business_admin',
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
	),
	'menu_order' => 3,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'left',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;
