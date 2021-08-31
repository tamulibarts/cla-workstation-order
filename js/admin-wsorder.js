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
