<?php
/**
 * The file that defines the Program post type
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-program-posttype.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @package cla-workstation-order
 * @since 1.0.0
 */
class Program_PostType {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {

		// Register_post_types.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'acf/init', array( $this, 'register_custom_fields' ) );
		add_filter( 'manage_program_posts_columns', array( $this, 'add_list_view_columns' ) );
		add_action( 'manage_program_posts_custom_column', array( $this, 'output_list_view_columns' ), 10, 2 );

		add_filter( 'acf/load_value/name=product_category_order', array( $this, 'load_product_categories' ), 10, 3 );

	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Program',
				'plural'   => 'Programs',
			),
			'program',
			array(),
			'dashicons-id',
			array( 'title' ),
			array(
				'capability_type'    => array( 'program', 'programs' ),
				'publicly_queryable' => false,
				'has_archive'        => false,
				'rewrite'            => false,
				'public'             => false,
			)
		);

	}

	/**
	 * Register custom fields
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_custom_fields() {
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/program-fields.php';
	}

	public function add_list_view_columns( $columns ){

		array_pop( $columns );
		$status  = array( 'current' => '' );
		$columns = array_merge( $status, $columns );

		$columns['prefix']      = 'Prefix';
		$columns['allocation']  = 'Allocation';
		$columns['threshold']   = 'Threshold';
		$columns['fiscal_year'] = 'Fiscal Year';
		$columns['orders']      = 'Orders';
		return $columns;

	}

	public function output_list_view_columns( $column_name, $post_id ) {
		if ( 'current' === $column_name ) {
			$current_program_post = get_field( 'current_program', 'option' );
			$current_program_id   = $current_program_post->ID;
			if ( $post_id === $current_program_id ) {
				echo '<span class="current-program-marker">*</span>';
			}
		} elseif ( 'prefix' === $column_name ) {
			$prefix = get_field( 'prefix', $post_id );
			echo $prefix;
		} elseif ( 'allocation' === $column_name ) {
			$number = (float) get_field( 'allocation', $post_id );
			if ( class_exists( 'NumberFormatter' ) ) {
				$formatter = new \NumberFormatter( 'en_US', \NumberFormatter::CURRENCY );
				echo $formatter->formatCurrency($number, 'USD');
			} else {
				echo '$' . number_format( $number, 2, '.', ',' );
			}
		} elseif ( 'threshold' === $column_name ) {
			$number = (float) get_field( 'threshold', $post_id );
			if ( class_exists( 'NumberFormatter' ) ) {
				$formatter = new \NumberFormatter( 'en_US', \NumberFormatter::CURRENCY );
				echo $formatter->formatCurrency( $number, 'USD' );
			} else {
				echo '$' . number_format( $number, 2, '.', ',' );
			}
		} elseif ( 'fiscal_year' === $column_name ) {
			$field = get_field( 'fiscal_year', $post_id );
			echo $field;
		} elseif ( 'orders' === $column_name ) {
			$admin_url = admin_url();
			echo "<a href=\"{$admin_url}edit.php?post_type=wsorder&program={$post_id}\">Orders</a>";
		}
	}

	public function load_product_categories( $value, $post_id, $field ) {

		/**
		 * If the value is exactly NULL it means the field has never been updated.
		 * We don't want to change fields that have already been editied.
		 */
		// if ( NULL !== $value ) {
		// 	return $value;
		// }

		$new_field_key = $field['sub_fields'][0]['key'];
		$args          = array(
			'taxonomy' => 'product-category',
			'fields'   => 'ids',

		);
		$categories    = get_terms( $args );
		print_r($categories);
		
		return $value;

	}
}
