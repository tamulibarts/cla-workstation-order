<?php
/**
 * The file that defines the Product post type
 *
 * @link       https://github.com/zachwatkins/cla-workstation-order/blob/master/src/class-product-posttype.php
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 */

namespace CLA_Workstation_Order;

/**
 * Add assets
 *
 * @package cla-workstation-order
 * @since 1.0.0
 */
class Product_PostType {

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
		add_filter( 'manage_product_posts_columns', array( $this, 'product_filter_posts_columns' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'product_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'product_posts_orderby' ) );
		add_filter( 'query_vars', array( $this, 'add_program_url_var' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_post_program_filter' ), 10 );
		add_filter( 'parse_query', array( $this, 'parse_query_program_filter' ), 10);
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_post_category_filter' ), 10 );

		// Load the catalog page template.
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-pagetemplate.php';
		$catalog = new \CLA_Workstation_Order\PageTemplate( CLA_WORKSTATION_ORDER_TEMPLATE_PATH, 'catalog.php', 'Catalog' );
		$catalog->register();


	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_type() {

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-posttype.php';
		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'src/class-taxonomy.php';

		new \CLA_Workstation_Order\PostType(
			array(
				'singular' => 'Product',
				'plural'   => 'Products',
			),
			'product',
			array(),
			'dashicons-desktop',
			array( 'title', 'thumbnail' ),
			array(
				'capability_type'    => array( 'product', 'products' ),
				'publicly_queryable' => false,
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

		require_once CLA_WORKSTATION_ORDER_DIR_PATH . 'fields/product-fields.php';

	}

	/**
	 * Add new admin columns.
	 *
	 * @param array $columns The current admin columns.
	 *
	 * @return array
	 */
	public function product_filter_posts_columns( $columns ) {

		$thumbnail = array( 'image' => __( 'Image' ) );
		$date      = $columns['date'];
		unset( $columns['date'] );
		$columns['price'] = __( 'Price' );
		$columns['program'] = __( 'Program' );
		$columns['date']    = $date;

		$columns = $thumbnail + $columns;

		return $columns;
	}

	/**
	 * Output content for each post's new column.
	 *
	 * @param string $column  The column slug.
	 * @param int    $post_id The post ID.
	 *
	 * @return void
	 */
	public function product_column( $column, $post_id ) {
		// Image column.
		if ( 'price' === $column ) {
			$price = get_post_meta( $post_id, 'price', true );
			echo wp_kses_post( $price );
		} elseif ( 'program' === $column ) {
			$program_post_id = get_post_meta( $post_id, 'program', true );
			$program_prefix  = get_post_meta( $program_post_id, 'prefix', true );
			echo wp_kses_post( $program_prefix );
		} elseif ( 'image' === $column ) {
			echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 150, 150 ), 'style=max-height:150px' ) );
		}
	}

	/**
	 * Change post query to provide sorting by program post ID.
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return void
	 */
	public function product_posts_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'wso_program' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', 'program' );
			$query->set( 'meta_type', 'numeric' );
		}
	}

	/**
	 * Add program as a URL parameter.
	 *
	 * @param array $vars Current variables.
	 *
	 * @return array
	 */
	public function add_program_url_var( $vars ) {
		if ( ! in_array( 'program', $vars ) ) {
			$vars[] = 'program';
		}
		return $vars;
	}

	/**
	 * Render filters for Product post meta on the bulk posts page.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function add_admin_post_program_filter( $post_type ) {

		if ( 'product' !== $post_type ){
		  return; //filter your post
		}
		$selected = '';
		$request_attr = 'program';
		if ( isset( $_REQUEST[$request_attr] ) ) {
		  $selected = $_REQUEST[$request_attr];
		}
		//get unique values of the meta field to filer by.
		$meta_key = 'program';
		global $wpdb;
		$results = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '%s'
				AND p.post_status IN ('publish', 'draft')
				ORDER BY pm.meta_value",
				$meta_key
			)
		);
		//build a custom dropdown list of values to filter by
		echo '<select id="program" name="program">';
		echo '<option value="0">' . __( 'Show all Programs', 'cla-workstation-order' ) . ' </option>';
		foreach( $results as $program ) {
			if ( ! empty( $program ) ) {
				$select = ($program == $selected) ? ' selected="selected"':'';
				echo '<option value="'.$program.'"'.$select.'>' . get_the_title( $program ) . ' </option>';
			}
		}
		echo '</select>';

  }

	/**
	 * Modify the post query based on custom product filter dropdown selections.
	 *
	 * @param WP_Query $query The query object.
	 *
	 * @return WP_Query
	 */
	public function parse_query_program_filter( $query ){

		//modify the query only if it admin and main query.
		if( !(is_admin() AND $query->is_main_query()) ){
			return $query;
		}
		//we want to modify the query for the targeted custom post and filter option
		if( !('product' === $query->query['post_type'] AND isset($_REQUEST['program']) ) ){
			return $query;
		}
		//for the default value of our filter no modification is required
		if(0 == $_REQUEST['program']){
			return $query;
		}
		//modify the query_vars.
		$query->query_vars['name'] = '';
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		if ( ! empty( $_REQUEST['program'] ) ) {
			$meta_query[] = array(
				'key'	 => 'program',
				'value' => $_REQUEST['program'],
			);
		}
		$query->set( 'meta_query', $meta_query );
		return $query;

	}

	/**
	 * Render filters for Product post meta on the bulk posts page.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function add_admin_post_category_filter( $post_type ) {

		if ( 'product' !== $post_type ){
			return; //filter your post
		}
		$slug     = 'product-category';
		$taxonomy = get_taxonomy( $slug );
		$selected = '';
		// if the current page is already filtered, get the selected term slug
		$selected = isset( $_REQUEST[ $slug ] ) ? $_REQUEST[ $slug ] : '';
		// render a dropdown for this taxonomy's terms
		wp_dropdown_categories( array(
			'show_option_all' =>  $taxonomy->labels->all_items,
			'taxonomy'        =>  $slug,
			'name'            =>  $slug,
			'orderby'         =>  'name',
			'value_field'     =>  'slug',
			'selected'        =>  $selected,
			'hierarchical'    =>  true,
		) );

	}
}
