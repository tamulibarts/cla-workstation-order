/**
 * Helper function for custom post status options.
 *  
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/js/admin-wsorder.js
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/js
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */
jQuery(window).on('load', function(){
	if ( jQuery('#post-status-select #post_status').val() === 'draft'  ) {
		jQuery('#post-status-select #post_status').val('action_required');
		jQuery('#post-status-display').html('Action Required');
	}
	// Show the save button when the work order status is "completed".
	jQuery('#post-status-select #post_status').on('change', function(){
		jQuery('body').removeClass('action_required completed returned awaiting_another').addClass(this.value);
	});
});
