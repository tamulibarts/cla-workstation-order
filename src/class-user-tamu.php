<?php
/**
 * The file that applies TAMU conventions to WordPress user information management.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/src/class-user-tamu.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.1.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/src
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

namespace CLA_Workstation_Order;

/**
 * TAMU User information handling.
 *
 * @package cla-workstation-order
 * @since 1.1.0
 */
class User_Tamu {

	/**
	 * Construct the class object instance.
	 *
	 * @return User_Tamu
	 */
	public function __construct() {

		add_action( 'admin_head-user-edit.php', array( $this, 'change_profile_labels' ) );
		add_action( 'admin_head-user-new.php', array( $this, 'change_profile_labels' ) );

	}

	/**
	 * Replace Username with NetID
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function change_profile_labels() {

		add_filter( 'gettext', array( $this, 'change_labels' ) );

	}

	/**
	 * Filter the Username label.
	 *
	 * @since 1.1.0
	 *
	 * @param string $input The label input.
	 *
	 * @return string
	 */
	public function change_labels( $input ) {

		switch ( $input ) {

			case 'Username':
				$input = 'NetID';
				break;
			case 'First Name':
			case 'Last Name':
				$input .= ' (recommended)';
				break;
			default:
				break;
		}

		return $input;

	}
}
