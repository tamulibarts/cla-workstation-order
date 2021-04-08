<?php
/**
 * The file that renders the catalog template
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/templates/catalog.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/templates
 */

/**
 * Registers and enqueues template styles.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_form_styles() {

	wp_register_style(
		'cla-workstation-order-form-template',
		CLA_WORKSTATION_ORDER_DIR_URL . 'css/order-form-template.css',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'css/order-form-template.css' ),
		'screen'
	);

	wp_enqueue_style( 'cla-workstation-order-form-template' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_styles', 1 );

/**
 * Registers and enqueues template scripts.
 *
 * @since 1.0.0
 * @return void
 */
function cla_workstation_order_form_scripts() {

	wp_register_script(
		'cla-workstation-order-form-scripts',
		CLA_WORKSTATION_ORDER_DIR_URL . 'js/order-form.js',
		false,
		filemtime( CLA_WORKSTATION_ORDER_DIR_PATH . 'js/order-form.js' ),
		'screen'
	);

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cla-workstation-order-form-scripts' );

}
add_action( 'wp_enqueue_scripts', 'cla_workstation_order_form_scripts', 1 );

add_action( 'the_content', function(){
	/**
	 * Get the CLA Form Helper class.
	 */
	require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-order-form-helper.php';
	$cla_form_helper = new \CLA_Workstation_Order\Order_Form_Helper();

	/**
	 * Get product categories.
	 */
	$apple_list  = $cla_form_helper->cla_get_products( 'apple', true );
	$pc_list     = $cla_form_helper->cla_get_products( 'pc', true );
	$addons_list = $cla_form_helper->cla_get_products( 'add-on', true );
	$output = "<div id=\"cla-order-form\"><div id=\"products\" class=\"cell small-12 medium-auto\">
	<div class=\"products-apple toggle\"><h3><a class=\"btn\" href=\"#\">Apple</a></h3>{$apple_list}</div>
	<div class=\"products-pc toggle\"><h3><a class=\"btn\" href=\"#\">PC</a></h3>{$pc_list}</div>
	<div class=\"products-addons toggle\"><h3><a class=\"btn\" href=\"#\">Add Ons</a></h3>{$addons_list}</div>
	</div></div>";
	echo $output;
});

genesis();
