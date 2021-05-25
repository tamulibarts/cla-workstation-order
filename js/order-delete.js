(function($){

	var admin_ajax      = WSODeleteOrderAJAX;
  var ajaxDeleteOrder = function(e){
  	e.preventDefault();
    $(this).prev('.ajax-delete-message').remove();
    var confirmed = confirm('Are you sure you want to delete the order? This cannot be undone.');
    if ( true === confirmed ) {
      var $button   = $(this);
      var form_data = new FormData();
      $button.before('<span class="ajax-delete-message notice-red"></span>');
      var $message = $button.prev('.ajax-delete-message');
      form_data.append('action', 'delete_order');
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
            if ( response.hasOwnProperty('status') && response.status !== 'deleted' ) {
              $message.html(response.status);
            } else {
              $message.html('You have deleted the order.');
              $button.remove();
            }
          } else {
            $message.html('There was an error deleting the order: ' + data);
          }
        },
        error: function( jqXHR, textStatus, errorThrown ) {
          $message.html('The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText );
        }
      });
    }
  };

	$('#cla_delete_order').on('click', ajaxDeleteOrder);

})(jQuery);
