(function($){

	var admin_ajax        = WSOAjax;
	var $approval_form    = jQuery('#cla_order_approval_form');
	var $acquisition_form = jQuery('#cla_acquisition_form');

	var ajaxConfirm = function(e){
    e.preventDefault();
    var form_data = new FormData();
    form_data.append('approval_comments', $approval_form.find('#approval_comments').val());
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
      		$approval_form.parent().html('You have confirmed the order.');
      	} else {
      		$approval_form.find('.ajax-response').html('There was an error confirming the order: ' + data);
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$approval_form.find('.ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
      }
    });
  };

	var ajaxReturn = function(e){
    e.preventDefault();
 		var validation = validateForm();
 		if ( validation.status === true ) {
      var form_data = new FormData();
      form_data.append('approval_comments', $approval_form.find('#approval_comments').val());
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
	      		$approval_form.parent().html('You have returned the order to the end user.');
	      	} else {
	      		$approval_form.find('.ajax-response').html('There was an error returning the order: ' + data);
	      	}
	      },
	      error: function( jqXHR, textStatus, errorThrown ) {
	      	$approval_form.find('.ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
	      }
	    });
    }
  };

  var ajaxLogistics = function(e){
  	e.preventDefault();
			$acquisition_form.find('.ajax-response').html('');
    var form_data = new FormData();
    form_data.append('cla_item_count', $acquisition_form.find('.cla-order-item').length);
    form_data.append('cla_quote_count', $acquisition_form.find('.cla-quote-item').length);
    $acquisition_form.find('input').each(function(){
    	form_data.append(this.name, this.value);
    });
    form_data.append('action', 'update_order_acquisitions');
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
      		$acquisition_form.find('.ajax-response').html('<div class="fade-out">You have updated the order.</div>');
      		window.setTimeout(function(){$acquisition_form.find('.ajax-response .fade-out').fadeOut();}, 3000);
      	} else {
      		$acquisition_form.find('.ajax-response').html('There was an error updating the order: ' + data);
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$acquisition_form.find('.ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
      }
    });
  };

	$approval_form.find('#cla_confirm').on('click', ajaxConfirm);
	$approval_form.find('#cla_return').on('click', ajaxReturn);
	$acquisition_form.submit(ajaxLogistics);

})(jQuery);
