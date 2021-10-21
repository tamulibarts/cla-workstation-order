/**
 * Order search page template functionality.
 *  
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/js/order-search.js
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/js
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */
 (function($){
	var admin_ajax = WSOSearchOrderAJAX;
	var $form      = $('#cla_search_order_form');
	var $view      = $('#ajax-results');
	var $response  = $form.find('.ajax-response');
	var $programs  = $form.find('#search-program');
	var $statuses  = $form.find('#search-status');
	var $reset     = $form.find('#reset-button');

	function ajaxSearch(e) {
    e.preventDefault();
    var form_data   = new FormData();
    var new_program = $programs.val();
    var new_status  = $statuses.val();
  	form_data.append('program_id', new_program);
  	form_data.append('order_status', new_status);
    form_data.append('action', 'search_order');
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
      			// Update page title.
            if ( 0 === parseInt(new_program) ) {
              $('h1.entry-title').html('All Orders');
            } else {
        			var prefix = response.program_prefix;
        			$('h1.entry-title').html('Orders for <span id="heading_program_prefix">' + prefix + '</span>');
            }
      			// Update entries.
      			var output = response.output;
      			$view.html(output);
            // Update the window URL with the new parameters.
            var url    = window.location.origin + window.location.pathname;
            var search = window.location.search.replace(/^\?/,'');
            url += '?';
            url += 'program_id=' + new_program;
            url += '&status=' + new_status;
            history.pushState({id: 'search'}, 'Orders', url);
      		} else {
        		$response.html('<span class="notice-red">' + response.status + '</span>');
	      	}
      	} else {
      		$response.html('<span class="notice-red">There was an error: ' + data + '</span>');
      	}
      },
      error: function( jqXHR, textStatus, errorThrown ) {
      	$response.html('<span class="notice-red">The application encountered an error while submitting your request (' + errorThrown + ').<br>' + jqXHR.responseText + '</span>');
      }
    });
    return false;
  }

  function resetSearch(e) {
  	e.preventDefault();
    var default_program_index = parseInt( $programs.attr('data-default-value') );
  	$programs.prop('selectedIndex',default_program_index);
  	$statuses.prop('selectedIndex',0);
  	ajaxSearch(e);
  	return false;
  };

  $programs.on('change', ajaxSearch);
  $statuses.on('change', ajaxSearch);
  $reset.on('click', resetSearch);

})(jQuery);
