jQuery(window).on('load', function(){
	if ( jQuery('#post-status-select #post_status').val() === 'draft'  ) {
		jQuery('#post-status-select #post_status').val('action_required');
		jQuery('#post-status-display').html('Action Required');
	}
	// Show the save button when the work order status is "completed".
	jQuery('#post-status-select #post_status').on('change', function(){
		jQuery('body').removeClass('action_required completed returned awaiting_another').addClass(this.value);
	});
	// Disable the order status option "returned" for general users.
	jQuery('body.wp-admin.post-type-wsorder:not(.wso_admin):not(.wso_it_rep):not(.wso_business_admin):not(.wso_logistics) select#acf-field_608174efb5deb option[value="returned"]').attr('disabled','disabled');
});
