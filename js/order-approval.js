(function($){

	var admin_ajax = WSOAjax;
	var $form      = jQuery('#cla_order_approval_form');

	var validateForm = function(){
		var passed  = true;
		var message = '';
		return {status: passed, message: message};
	};
	console.log($form.find('#cla_confirm'));

	var ajaxConfirm = function(e){
		console.log('ajaxConfirm');
    e.preventDefault();
 		var validation = validateForm();
 		if ( validation.status === true ) {
      var form_data = new FormData();
      form_data.append('cla_comments', $form.find('#approval_comments').val());
      form_data.append('action', 'confirm_order');
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
	      		$form.parent().html('You have confirmed the order.');
	      	} else {
	      		$form.find('#ajax-response').html('There was an error confirming the order: ' + data);
	      	}
	      },
	      error: function( jqXHR, textStatus, errorThrown ) {
	      	$form.find('#ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
	      }
	    });
    }
  };

	var ajaxReturn = function(e){
    e.preventDefault();
 		var validation = validateForm();
 		if ( validation.status === true ) {
      var form_data = new FormData();
      form_data.append('comments', $form.find('#approval_comments').val());
      form_data.append('action', 'return_order');
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
	      		$form.parent().html('You have returned the order to the end user.');
	      	} else {
	      		$form.find('#ajax-response').html('There was an error returning the order: ' + data);
	      	}
	      },
	      error: function( jqXHR, textStatus, errorThrown ) {
	      	$form.find('#ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
	      }
	    });
    }
  };

	$form.find('#cla_confirm').on('click', ajaxConfirm);
	$form.find('#cla_return').on('click', ajaxReturn);

})(jQuery);
