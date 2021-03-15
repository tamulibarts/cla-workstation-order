jQuery(window).on('load', function(){
	if ( jQuery('#post-status-select #post_status').val() === 'draft'  ) {
		jQuery('#post-status-select #post_status').val('action_required');
		jQuery('#post-status-display').html('Action Required');
	}
	// jQuery('#post-body-content #title').attr('disabled','disabled');
	// Show the save button when the work order status is "completed".
	jQuery('#post-status-select #post_status').on('change', function(){
		jQuery('body').removeClass('action_required completed returned awaiting_another').addClass(this.value);
	});

	// Disable publish button for users who shouldn't be publishing.
	jQuery('body.wp-admin.post-type-wsorder:not(.wso_admin) input#publish').attr('disabled','disabled');

	jQuery('#major-publishing-actions #print').on('click', function(e){
		var data = wsorder_data;
		var html = '<html style="margin:0;"><head><style>@media screen{body{padding:100px}} @media print{body{padding:10px;}}</style></head><body style="font-size:16px;font-family:\'Arial Narrow\',Arial,sans-serif;margin:0;">';
		html += '<div style="height:100%;display:block;position:relative;">';
		// Header
		html += '<div style="float:right;font-size:1.7vw;text-align:right;"><div style="font-size:3.5vw;">Order # ' + data.post_title + '</div>';
		html += data.publish_date_formatted + '<br>';
		html += data.program_name + '<br>';
		html += data.program_fiscal_year + '</div>';
		html += '<div><img style="width:32vw;" src="'+data.logo+'"></div>';
		html += '<h1 style="font-size:3.9vw;clear:both;">' + data.program_name + ' Order</h1>';
		html += '<div style="vertical-align:top;width:100%;margin:0 auto;">';
		// User details
		html += '<div class="user-details" style="font:2.2vw \'Arial Narrow\',Arial,sans-serif;display:inline-block;width:42%;text-align:center;">';
		html += '<strong>User Details</strong>';
		html += '<table style="width:100%;font-size:2.2vw;border-top:1px solid #000;margin:4px auto 0;padding-top:4px;text-align:left;"><tbody style="vertical-align:top;">';
		html += '<tr><td style="width:45%;">First Name</td><td>' + data.first_name + '</td></tr>';
		html += '<tr><td>Last Name</td><td>' + data.last_name + '</td></tr>';
		html += '<tr><td>Email</td><td>' + data.author_email + '</td></tr>';
		html += '<tr><td>Department</td><td>' + data.author_department + '</td></tr>';
		html += '<tr><td>Office Location</td><td>' + data.building + ' ' + data.office_location + '</td></tr>';
		html += '<tr><td>Current Asset</td><td>';
		if ( '1' === data.i_dont_have_a_computer_yet ) {
			html += 'none';
		} else {
			html += data.current_asset
		}
		html += '</td></tr>';
		html += '</tbody></table></div>';
		// Processing Steps
		html += '<div class="processing-steps" style="font:2.2vw \'Arial Narrow\',Arial,sans-serif;display:inline-block;width:42%;margin-left:16%;text-align:center;">';
		html += '<strong>Processing Steps</strong>';
		html += '<table style="width:100%;font-size:2.2vw;border-top:1px solid #000;margin:4px auto 0;padding-top:4px;text-align:left;"><tbody style="vertical-align:top;">';
		html += '<tr><td style="width:45%;">IT Staff ('+data.it_rep_status_it_rep+')</td><td>' + data.it_rep_status_date + '</td></tr>';
		html += '<tr><td>Business ('+data.business_staff_status_business_staff+')</td><td>' + data.business_staff_status_date + '</td></tr>';
		html += '<tr><td>Logistics</td><td>' + data.it_logistics_status_date + '</td></tr>';
		html += '</tbody></table></div>';
		html += '</div>';
		// Items
		html += '<div style="padding:6vw 0;"><table style="font:2vw \'Arial Narrow\',Arial,sans-serif;text-align:left;border-collapse:collapse;width:100%;">';
		html += '<thead style="border-bottom:1px solid #000;"><tr style="vertical-align:top;"><th style="padding:8px;width:54%;">Item</th><th style="padding:8px;width:18%;">SKU</th><th style="padding:8px;width:7%;">Req #</th><th style="padding:8px;width:13%;">Req Date</th><th style="padding:8px;width:8%;text-align:right;">Price</th></tr></thead>';
		html += '<tbody>';
		var item_count = data.order_items;
		for ( var i=0; i < item_count; i++ ) {
			var item = {
				sku: data['order_items_' + i + '_sku'],
				name: data['order_items_' + i + '_item'],
				price: data['order_items_' + i + '_price'],
				req_num: data['order_items_' + i + '_requisition_number'],
				req_date: data['order_items_' + i + '_requisition_date'],
			};
			html += '<tr style="vertical-align:top;margin:4px 0;padding:4px 0;border-bottom:1px solid #000;">';
			html += '<td style="padding:8px;">'+item.name+'</td><td style="padding:8px;">'+item.sku+'</td><td style="padding:8px;">'+item.req_num+'</td><td style="padding:8px;">'+item.req_date+'</td><td style="padding:8px;text-align:right;">'+item.price+'</td>';
			html += '</tr>';
		}
		html += '<tr><td colspan="4" style="padding:8px;text-align:right;">Products Subtotal</td>';
		html += '<td style="padding:8px;text-align:right;">'+data.products_subtotal+'</td></tr>';
		html += '</tbody></table>';
		// Footer
		html += '<div style="position:fixed;bottom:0;left:25px;right:25px;font-size:1.5vw;text-align:center;border-top:1px solid #000;background-color:#FFF;padding-top:6px;white-space:pre;">' + data.program_name + '   |   ' + data.post_title + '   |   ' + data.first_name + ' ' + data.last_name + ' - ' + data.author_department + '   |   Generated at: ' + data.now + '</div>';
		html += '</div>';
		// End tag
		html += '</body></html>';
		var printwindow = window.open('', 'windowName');
		printwindow.document.write(html);

	});
});
