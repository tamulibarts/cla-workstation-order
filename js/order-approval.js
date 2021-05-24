(function($){

	var admin_ajax        = WSOAjax;
	var $approval_form    = jQuery('#cla_order_approval_form');
	var $acquisition_form = jQuery('#cla_acquisition_form');

	var validateLogisticsFields = function(){
		var valid = true;
		$acquisition_form.find('.flagged').removeClass('flagged');

		$acquisition_form.find('input').each(function(){
			if ( this.value === '' ) {
				valid = false;
				jQuery(this).addClass('flagged');
			}
		});
		return valid;
	};

	var ajaxConfirm = function(e){
    e.preventDefault();
    var form_data = new FormData();
    if ( $approval_form.find('#cla_account_number').length > 0 ) {
    	form_data.append('account_number', $approval_form.find('#cla_account_number').val());
    }
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
      		output = 'You have confirmed the order.';
      		if ( response.hasOwnProperty('refresh') && true === response.refresh ) {
      			output += ' The page will refresh in 3 seconds.';
      			window.setTimeout(function(){location.reload();}, 3000);
      		}
      		$approval_form.parent().html( output );
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
  };

  var ajaxLogistics = function(e){
  	e.preventDefault();
		$acquisition_form.find('.ajax-response').html('');
    var form_data = new FormData();
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

  var ajaxPublish = function(e){
  	e.preventDefault();
		$acquisition_form.find('.ajax-response').html('');
  	var valid = validateLogisticsFields();
  	if ( true === valid ) {
	    var form_data = new FormData();
	    form_data.append('action', 'publish_order');
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
	      		if ( response.hasOwnProperty('errors') && typeof response.errors === 'array' && response.errors.length > 0 ) {
	      			var errors = response.errors;
	      			var output = '';
	      			for ( var i = 0; i < errors.length; i++ ) {
	      				if ( i > 0 ) {
	      					output += ' ';
	      				}
	      				output += errors[i];
	      			}
	      			$acquisition_form.find('.ajax-response').html(output);
	      		} else {
		      		$acquisition_form.find('.ajax-response').html('You have published the order. The page will refresh in 3 seconds.');
		      		window.setTimeout(function(){location.reload();}, 3000);
	      		}
	      	} else {
	      		$acquisition_form.find('.ajax-response').html('There was an error publishing the order: ' + data);
	      	}
	      },
	      error: function( jqXHR, textStatus, errorThrown ) {
	      	$acquisition_form.find('.ajax-response').html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
	      }
	    });
  	} else {
  		$acquisition_form.find('.ajax-response').html('<div class="flagged">Some of your fields are empty. You must provide information for them before you can publish the order.</div>');
  	}
  };

	$approval_form.find('#cla_confirm').on('click', ajaxConfirm);
	$approval_form.find('#cla_return').on('click', ajaxReturn);
	$acquisition_form.submit(ajaxLogistics);
	$acquisition_form.find('#cla_publish').on('click', ajaxPublish);

})(jQuery);
