jQuery(window).on('load', function(){
	if ( jQuery('#post-status-select #post_status').val() === 'draft'  ) {
		jQuery('#post-status-select #post_status').val('action_required');
		jQuery('#post-status-display').html('Action Required');
	}
	// Show the save button when the work order status is "completed".
	jQuery('#post-status-select #post_status').on('change', function(){
		jQuery('body').removeClass('action_required completed returned awaiting_another').addClass(this.value);
	});

	// Disable the order program field.
	if ( false === jQuery('body').is('.wso_admin,.wso_logistics') ) {
		acf.addAction('select2_init', function( $select, options, data ){
			if ( 'field_5ffcc2590682b' === data.field.data.key ) {
				$select.select2({disabled:true});
			}
		});
	}
});
