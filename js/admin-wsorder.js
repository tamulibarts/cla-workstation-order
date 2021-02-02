if ( jQuery('#post-status-select #post_status').val() === 'draft' ) {
	jQuery('#post-status-select #post_status').val('action_required');
	jQuery('#post-status-display').html('Action Required');
}
jQuery('#post-status-select #post_status option[value="pending"]').remove();
jQuery('#post-status-select #post_status option[value="draft"]').remove();
// jQuery('#post-body-content #title').attr('disabled','disabled');
