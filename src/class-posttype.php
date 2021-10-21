<?php
/**
 * The file that initializes custom post types
 *
 * A class definition that registers custom post types with their attributes
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-posttype.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

namespace CLA_Workstation_Order;

/**
 * The post type registration class
 *
 * @since 1.0.0
 * @return void
 */
class PostType {

	/**
	 * Post type slug
	 *
	 * @var search_file
	 */
	private $post_type;

	/**
	 * Builds and registers the custom taxonomy.
	 *
	 * @param  array  $name       The post type name.
	 * @param  string $slug       The post type slug.
	 * @param  array  $taxonomies The taxonomies this post type supports. Accepts arguments found in
	 *                            WordPress core register_post_type function.
	 * @param  string $icon       The icon used in the admin navigation sidebar.
	 * @param  array  $supports   The attributes this post type supports. Accepts arguments found in
	 *                            WordPress core register_post_type function.
	 * @param  array  $user_args  Additional user arguments which override all others for the function register_post_type.
	 * @return void
	 */
	public function __construct(
		$name = array(
			'singular' => '',
			'plural'   => '',
		),
		$slug,
		$taxonomies = array(
			'category',
			'post_tag',
		),
		$icon = 'dashicons-portfolio',
		$supports = array( 'title' ),
		$user_args = array()
	) {

		$this->post_type = $slug;
		$singular        = $name['singular'];
		$plural          = $name['plural'];

		// Backend labels.
		$labels = array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => __( 'Add New', 'cla-wso-textdomain' ),
			'add_new_item'       => __( 'Add New', 'cla-wso-textdomain' ) . " $singular",
			'edit_item'          => __( 'Edit', 'cla-wso-textdomain' ) . " $singular",
			'new_item'           => __( 'New', 'cla-wso-textdomain' ) . " $singular",
			'view_item'          => __( 'View', 'cla-wso-textdomain' ) . " $singular",
			'search_items'       => __( 'Search', 'cla-wso-textdomain' ) . " $plural",
			/* translators: placeholder is the plural taxonomy name */
			'not_found'          => sprintf( esc_html__( 'No %d Found', 'cla-wso-textdomain' ), $plural ),
			/* translators: placeholder is the plural taxonomy name */
			'not_found_in_trash' => sprintf( esc_html__( 'No %d found in trash', 'cla-wso-textdomain' ), $plural ),
			'parent_item_colon'  => '',
			'menu_name'          => $plural,
		);

		// Post type arguments.
		$this->args = array_merge(
			array(
				'can_export'         => true,
				'has_archive'        => true,
				'labels'             => $labels,
				'menu_icon'          => $icon,
				'menu_position'      => 20,
				'public'             => true,
				'publicly_queryable' => true,
				'show_in_rest'       => true,
				'show_in_menu'       => true,
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'show_ui'            => true,
				'supports'           => $supports,
				'taxonomies'         => $taxonomies,
				'rewrite'            => array(
					'with_front' => false,
					'slug'       => $slug,
				),
			),
			$user_args
		);

		// Register the post type.
		register_post_type( $this->post_type, $this->args );

	}

}
