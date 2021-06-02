(function($){

	var admin_ajax = WSOAjax;
	var $form      = jQuery('#cla_update_account_form');
  var $response  = $form.find('.ajax-response');
  var values     = {
    first_name: $form.find('#cla_first_name').val(),
    last_name: $form.find('#cla_last_name').val(),
    email: $form.find('#cla_email').val(),
    department: $form.find('#cla_department').val(),
  };

	var ajaxUpdateAccount = function(e){
    e.preventDefault();
    var form_data = new FormData();
    var first_name = $form.find('#cla_first_name').val();
    var last_name  = $form.find('#cla_last_name').val();
    var email      = $form.find('#cla_email').val();
    var department = $form.find('#cla_department').val();
    // Determine if we need to make an AJAX call to update the user account details.
    if (
      first_name === values.first_name
      && last_name === values.last_name
      && email === values.email
      && department === values.department
      ) {
      $response.html('<span class="notice-red">No changes were made.</span>');
      return;
    } else {
      values.first_name = first_name;
      values.last_name  = last_name;
      values.email      = email;
      values.department = department;
      $response.html('');
    }
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
        var response_output = '';
      	if ( data.indexOf('{') === 0 ) {
					// Only JSON returned.
      		var response = JSON.parse(data);
          var errors = response.errors;
          if ( 'success' === response.status ) {
            response_output += '<span class="notice-green">You have updated your account.</span>';
            if ( errors.length > 0 ) {
              // There was a success but also an error.
              response_output += '<span class="notice-red">' + errors.join(' ') + '</span>';
            }
          } else {
            response_output += '<span class="notice-red">' + errors.join(' ') + '</span>';
          }
      	} else {
      		response_output += '<span class="notice-red">There was an error updating your account: ' + data + '</span>';
      	}
        $response.html(response_output);
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$response.html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
      }
    });
  };

	$form.find('#cla_update_account').on('click', ajaxUpdateAccount);

})(jQuery);
