/**
 * Order approval page template functionality.
 *  
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/js/order-approval.js
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/js
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */
(function($){

	var admin_ajax        = WSOAjax;
	var $approval_form    = jQuery('#cla_order_approval_form');
	var $acquisition_form = jQuery('#cla_acquisition_form');
	var $reassign_form    = jQuery('#cla_order_reassign_form');

	var validateLogisticsFields = function(){
		var valid = true;
		$acquisition_form.find('.flagged').removeClass('flagged');
		$acquisition_form.find('input[name*="req_number"],input[name*="req_date"]').each(function(){
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
    var $response = $approval_form.find('.ajax-response');
    console.log($response);
    $response.html('');
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
      		if ( 'success' === response.status ) {
	      		output = 'You have confirmed the order.';
	      		if ( response.hasOwnProperty('refresh') && true === response.refresh ) {
	      			output += ' The page will refresh in 3 seconds.';
	      			window.setTimeout(function(){location.reload();}, 3000);
	      		}
	      		$approval_form.parent().html( '<span class="notice-green">' + output + '</span>' );
      		} else {
      			$response.html('<span class="notice-red">' + response.status + '</span>');
      		}
      	} else {
      		$response.html('<span class="notice-red">There was an error confirming the order: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$response.html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
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
      		$approval_form.parent().html('<span class="notice-red">You have returned the order to the end user.</span>');
      	} else {
      		$approval_form.find('.ajax-response').html('<span class="notice-red">There was an error returning the order: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$approval_form.find('.ajax-response').html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
      }
    });
  };

  var ajaxLogistics = function(e, success_callback){
  	e.preventDefault();
		$acquisition_form.find('.ajax-response').html('');
    var form_data = new FormData();
    var field_selector = [];
    if ( admin_ajax.item_count > 0 ) {
    	for ( var i=0; i < admin_ajax.item_count; i++ ) {
    		field_selector.push('input[name="cla_item_' + i + '_req_number"]');
    		field_selector.push('input[name="cla_item_' + i + '_req_date"]');
    		field_selector.push('input[name="cla_item_' + i + '_asset_number"]');
    	}
    }
    if ( admin_ajax.quote_count > 0 ) {
    	for ( var i=0; i < admin_ajax.item_count; i++ ) {
    		field_selector.push('input[name="cla_quote_' + i + '_req_number"]');
    		field_selector.push('input[name="cla_quote_' + i + '_req_date"]');
    		field_selector.push('input[name="cla_quote_' + i + '_asset_number"]');
    	}
    }
    field_selector = field_selector.join(',');
    $acquisition_form.find(field_selector).each(function(){
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
          if ( 'success' === response.status ) {
            $acquisition_form.find('.ajax-response').html('<div class="fade-out notice-green">You have updated the order.</div>');
            window.setTimeout(function(){$acquisition_form.find('.ajax-response .fade-out').fadeOut();}, 3000);
            if ( typeof success_callback === 'function' ) {
            	success_callback(e);
            }
          } else {
            $acquisition_form.find('.ajax-response').html('<div class="notice-red">' + response.status + '</div>');
          }
      	} else {
      		$acquisition_form.find('.ajax-response').html('<span class="notice-red">There was an error updating the order: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$acquisition_form.find('.ajax-response').html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
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
	      			$acquisition_form.find('.ajax-response').html('<span class="notice-red">' + output + '</span>');
	      		} else {
		      		$acquisition_form.find('.ajax-response').html('<span class="notice-green">You have published the order. The page will refresh in 3 seconds.</span>');
		      		window.setTimeout(function(){location.reload();}, 3000);
	      		}
	      	} else {
	      		$acquisition_form.find('.ajax-response').html('<span class="notice-red">There was an error publishing the order: ' + data + '</span>');
	      	}
	      },
	      error: function( jqXHR, textStatus, errorThrown ) {
	      	$acquisition_form.find('.ajax-response').html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
	      }
	    });
  	} else {
  		$acquisition_form.find('.ajax-response').html('<div class="flagged">Some of your fields are empty. You must provide information for them before you can publish the order.</div>');
  	}
  };

  var ajaxLogisticsPublish = function(e){
  	ajaxLogistics(e, ajaxPublish);
  };

  var ajaxReassign = function(e){
  	e.preventDefault();
  	var $response = $reassign_form.find('.ajax-response');
    var form_data = new FormData();
    var $button   = $(this);
    form_data.append('action', 'reassign_order');
    form_data.append('_ajax_nonce', admin_ajax.nonce);
  	$response.html('');
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
      		if ( 'success' === response.status ) {
	      		$response.html('<span class="notice-green">You have reassigned the order to yourself. The page will refresh in 3 seconds.</span>');
	      		$button.remove();
	      		window.setTimeout(function(){location.reload();}, 3000);
      		} else {
      			$response.html('<span class="notice-red">' + response.status + '</span>');
      		}
      	} else {
      		$response.html('<span class="notice-red">There was an error reassigning the order: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$response.html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
      }
    });
  };

	$approval_form.find('#cla_confirm').on('click', ajaxConfirm);
	$approval_form.find('#cla_return').on('click', ajaxReturn);
	$acquisition_form.submit(ajaxLogistics);
	$acquisition_form.find('#cla_publish').on('click', ajaxLogisticsPublish);
	$reassign_form.find('#cla_reassign').on('click', ajaxReassign);

})(jQuery);
