(function($){

	// Remove product from shopping cart.
	var removeProduct = function(e){

		// Prevent default behavior on click.
		e.preventDefault();

		// Get variables used to update the shopping cart.
		var productID = this.getAttribute('data-product-id');

		// Remove product ID from form field.
		var $productIDsField = $('#cla_product_ids');
		var newProductIDs = $productIDsField.val().replace( productID, '' ).replace( /,+/g, ',' ).replace( /^,/, '' );
		$productIDsField.val(newProductIDs);

		// Remove the shopping cart element.
		$('.shopping-cart-'+productID).remove();

		// Enable the Add to Cart button.
		$('#cart-btn-'+productID).removeAttr('disabled').html('Add');

		// Update total purchase price.
		updateTotals();

	};

	// Add product to shopping cart.
	var addProductToCart = function(e){

		// Prevent default behavior on click.
		e.preventDefault();

		// Get elements and values.
		var $this = $(this);
		var productID = $this.attr('data-product-id');
		var productName = $('.post-title-' + productID).html();
		var productPrice = $this.attr('data-product-price');
		var thumbSrc = $('#product-'+productID+'.card .wp-post-image').attr('src');

		// Add product ID to form field.
		var $productIDsField = $('#cla_product_ids');
		var newProductIDs = $productIDsField.val() + productID + ',';
		$productIDsField.val( newProductIDs );

		// Generate HTML elements for shopping cart listing.
		var listItem = '<div class="cart-item shopping-cart-'+productID+' grid-x">';
				listItem += '<div class="cell shrink"><img width="50" src="'+thumbSrc+'"></div>';
				listItem += '<div class="cell auto">'+productName+'</div>';
				listItem += '<div class="cell shrink align-right bold"><button class="trash" type="button" data-product-id="'+productID+'" data-product-price="'+productPrice+'">Remove product from cart</button>'+productPrice+'</div>';
				listItem += '</div>';

		// Append item.
		$('#list_purchases').append(listItem);

		// Add event handlers
		$('#list_purchases').find('.shopping-cart-'+productID+' .trash').on('click', removeProduct);

		// Disable button.
		this.setAttribute('disabled','disabled');
		this.innerHTML = 'In cart';

		// Update total purchase price.
		updateTotals();
	};

	// Get total cost of products.
	var getTotal = function(){

		// Get array of post IDs for product post type.
		var $productIDsField = $('#cla_product_ids');
		var ids = $productIDsField.val();
				ids = ids.replace(/^,|,$/g,'');

		if ( ids.indexOf(',') >= 0 ) {
			ids = ids.split(',');
		} else if ( ids !== '' ) {
			ids = [ids];
		} else {
			ids = [];
		}

		// Add price associated with each product to total.
		var total = 0;
		for ( var i=0; i < ids.length; i++ ) {
			var price = $('.price-' + ids[i]).html();
					price = price.replace( /\$|,/g, '' );
					price = parseFloat( price );
			total += price;
		}

		return total;

	};

	var isOverageTriggered = function(){

		var $allocationData = $('#allocation-data');

		// Get numbers involved.
		var total = getTotal();
		var contributionMade = $('#cla_contribution_amount').val();
		var allocation = parseFloat( $allocationData.attr('data-allocation') );
		var threshold = parseFloat( $allocationData.attr('data-allocation-threshold') );
		var contributionAmountNeeded = total - contributionMade - allocation;
		var returnVal = {};

		if ( threshold < total ) {
			// They have to pay everything beyond the allocation.
			var contributionAmountNeeded = total - contributionMade - allocation;
			returnVal.overage = contributionAmountNeeded;

			if ( contributionAmountNeeded > 0 ) {
				returnVal.allowed = false;
			} else {
				returnVal.allowed = true;
			}
		} else {
			// They can make this purchase.
			returnVal.allowed = true;
			returnVal.overage = 0;
		}

		return returnVal;

	};

	// Update total purchase cost.
	var updateTotals = function(){

		// Get total cost of products.
		var total = getTotal();

		// Convert total to string and push to DOM elements.
		var totalString = '$' + total.toFixed(2).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
		$('#products_total').html(totalString);
		$('#cla_total_purchase').val(total);

		// Check for contribution needed.
		var $allocationData = $('#allocation-data');
		var $contributionNeededEl = $('#contribution_needed');
		var isOverThreshold = isOverageTriggered();

		// Show or hide the contribution amount needed and disable the form.
		if ( isOverThreshold.allowed === false ) {

			// Contribution needed.
			var contributionAmountNeeded = isOverThreshold.overage.toFixed(2).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
			$contributionNeededEl.html('$'+contributionAmountNeeded);

			if ( $allocationData.hasClass('hidden') ) {
				// Show contribution needed.
				$allocationData.removeClass('hidden');
			}

		} else {

			// Contribution not needed. Hide element if visible.
			if ( ! $allocationData.hasClass('hidden') ) {
				$allocationData.addClass('hidden');
				$contributionNeededEl.html('$0.00');
			}

		}

	};

	var saveForm = function(){

		$form = $('#cla_order_form');
		$form.find('input[type="text"],input[type="number"],input[type="hidden"]')
			.not('#cla_account_number,#cla_current_asset_number').each(function() {
		  $(this).attr('value', $(this).val());
		});
		$form.find('textarea').each(function(){
			$(this).attr('value', $(this).val());
		});
		$form.find('select').each(function(){
			var val = $(this).val();
			$(this).find('option[value="'+val+'"]').attr('selected','selected');
		});
		var cloned = $form.clone(true);
		console.log('cloned.html()',cloned.html());
		localStorage.setItem("cla-order-form", JSON.stringify(cloned.html()));

	};

	var getSavedForm = function(){

		var lastForm = JSON.parse(localStorage.getItem("cla-order-form"));
		$('#cla_order_form').html(lastForm);

	}

	var validateForm = function(e){

		var valid = true;
		var $form = $('#cla_order_form');

		// Remove flags.
		$form.find('.flagged').removeClass('flagged');

		// Account Number.
		var $accountNumber = $form.find('#cla_account_number');
		var $contributionAmount = $form.find('#cla_contribution_amount');
		var isOverThreshold = isOverageTriggered();
		if ( $contributionAmount.val() !== '' && $accountNumber.val() === '' ) {
			// Contribution and account number don't match up.
			valid = false;
			$form.find('label[for="cla_account_number"]').addClass('flagged');
		} else if ( isOverThreshold.allowed === false ) {
			// Contribution still needed.
			valid = false;
			$form.find('label[for="cla_contribution_amount"]').addClass('flagged');
			$form.find('label[for="cla_account_number"]').addClass('flagged');
		}

		// Building name.
		$buildingName = $form.find('#cla_building_name');
		if ( $buildingName.val() === '' ) {
			valid = false;
			$form.find('label[for="cla_building_name"]').addClass('flagged');
		}

		// Room number.
		$roomNumber = $form.find('#cla_room_number');
		if ( $roomNumber.val() === '' ) {
			valid = false;
			$form.find('label[for="cla_room_number"]').addClass('flagged');
		}

		// Current workstation.
		$assetNumber = $form.find('#cla_current_asset_number');
		$noComputer = $form.find('#cla_no_computer_yet');
		if ( $assetNumber.val() === '' ) {
			if ( ! $noComputer.is(':checked') ) {
				valid = false;
				$form.find('label[for="cla_current_asset_number"]').addClass('flagged');
			}
		} else if ( $noComputer.is(':checked') ) {
			valid = false;
			$form.find('label[for="cla_current_asset_number"]').addClass('flagged');
		}

		// Order comment.
		$comments = $form.find('#cla_order_comments');
		if ( $comments.val() === '' ) {
			valid = false;
			$form.find('label[for="cla_order_comments"]').addClass('flagged');
		}

		// Products purchased.
		$products = $form.find('#list_purchases .cart-item');
		if ( $products.length === 0 ) {
			valid = false;
			$form.find('#products .toggle .btn').addClass('flagged');
		}

		// Enable or disable the submit button.
		if ( ! valid ) {
			if ( typeof e === 'event' ) {
				e.preventDefault();
			} else {
				return false;
			}
		}

	};

	// getSavedForm();
	// validateForm();

	// Add event handlers.
	$form = $('#cla_order_form');
	$('.add-product').on('click', addProductToCart);
	$('#cla_contribution_amount').on('keyup', updateTotals);
	$form.find('textarea, input[type="text"], input[type="number"]').on('blur', saveForm);
	$form.find('button[type="button"]').on('click', saveForm);
	$form.on('submit', validateForm);

})(jQuery);

// Provide toggle feature for product categories.
(function($){

	var toggleActive = function(e) {

		// Prevent default.
		e.preventDefault();

		$(this).parent().parent().toggleClass('active');

	}

	// Attach event handlers.
	$('#products .toggle .btn').on('click', toggleActive);

})(jQuery);

// Provide toggle feature for product details.
(function($){

	var toggleActive = function(e) {

		// Prevent default.
		e.preventDefault();

		// Toggle "active" class name on parent.
		var element = $(this).parent().toggleClass('active');

	}

	// Attach event handlers.
	$('#products .more-details').on('click', toggleActive);

})(jQuery);
