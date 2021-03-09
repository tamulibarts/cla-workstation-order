jQuery(window).on('load', function(){
	if ( jQuery('#post-status-select #post_status').val() === 'draft'  ) {
		jQuery('#post-status-select #post_status').val('action_required');
		jQuery('#post-status-display').html('Action Required');
	}
	// jQuery('#post-body-content #title').attr('disabled','disabled');
	// Show the save button when the work order status is "completed".
	jQuery('#post-status-select #post_status').on('change', function(){
		jQuery('body').removeClass('action_required completed returned awaiting_another').addClass(this.value);
	});

	// Disable publish button for users who shouldn't be publishing.
	jQuery('body.wp-admin.post-type-wsorder:not(.wso_admin) input#publish').attr('disabled','disabled');
});
