(function($){

	var admin_ajax = WSOAjax;
	var $form      = jQuery('#cla_update_account_form');
  var $response  = $form.find('.ajax-response');

	var ajaxUpdateAccount = function(e){
    e.preventDefault();
    $response.html('');
    var form_data = new FormData();
    var first_name = $form.find('#cla_first_name').val();
    var last_name  = $form.find('#cla_last_name').val();
    var email      = $form.find('#cla_email').val();
    var department = $form.find('#cla_department').val();
    form_data.append('first_name', first_name);
    form_data.append('last_name', last_name);
    form_data.append('email', email);
    form_data.append('department', department);
    form_data.append('action', 'update_acount');
    form_data.append('_ajax_nonce', admin_ajax.nonce);
    jQuery.ajax({
      type: "POST",
      url: admin_ajax.ajaxurl,
			contentType: false,
			processData: false,
      data: form_data,
      success: function(data) {
      	if ( data.indexOf('{') === 0 ) {
					// Only JSON returned.
      		var response = JSON.parse(data);
      		$response.html('<span class="notice-green">You have updated your account.</span>');
      	} else {
      		$response.html('<span class="notice-red">There was an error updating your account: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$response.html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
      }
    });
  };

	$form.find('#cla_update_account').on('click', ajaxUpdateAccount);

})(jQuery);
