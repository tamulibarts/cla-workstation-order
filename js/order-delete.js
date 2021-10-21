/**
 * Order deletion functionality.
 *  
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/js/order-delete.js
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/js
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */
 (function($){

	var admin_ajax      = WSODeleteOrderAJAX;
  var ajaxDeleteOrder = function(e){
  	e.preventDefault();
    var $button = $(this);
    var clear_container = $button.attr('data-clear-container');
    var post_id = $button.attr('data-post-id');
    var container_selector = '.entry.wsorder';
    if ( post_id !== undefined ) {
      post_id = parseInt( post_id );
      if ( false === Number.isNaN( post_id ) ) {
        container_selector += '.post-' + post_id;
      }
    }
    var $container = $(container_selector);
    $container.find('.ajax-delete-message').remove();
    var confirmed = confirm('Are you sure you want to delete the order? This cannot be undone.');
    if ( true === confirmed ) {
      var form_data = new FormData();
      var message_html = '<span class="ajax-delete-message notice-red"></span>';
      if ( 'true' !== clear_container ) {
        $button.before(message_html);
      } else {
        $button.after(message_html);
      }
      var $message = $container.find('.ajax-delete-message');
      if ( post_id !== undefined && false === Number.isNaN( post_id ) ) {
        form_data.append('order_post_id', post_id);
      }
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
              if ( 'true' !== clear_container ) {
                $button.remove();
              } else {
                $container.fadeOut();
              }
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

	$('.cla-delete-order').on('click', ajaxDeleteOrder);

})(jQuery);
