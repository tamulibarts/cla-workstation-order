(function(){
	// Get elements.
	var productsTotalEl = document.querySelector('#products_total');
	var productsTotalField = document.querySelector('#cla_total_purchase');
	var productsListEl = document.querySelector('#list_purchases');
	var productIDsField = document.querySelector('#cla_product_ids');
	var productAdds = document.querySelectorAll('.add-product');
	// Remove product from shopping cart.
	var removeProduct = function(e){

		// Prevent default behavior on click.
		e.preventDefault();

		// Get variables used to update the shopping cart.
		var productID = this.getAttribute('data-product-id');
		var newProductIDs = productIDsField.value.replace( productID, '' ).replace( /,+/g, ',' ).replace( /^,/, '' );
		var shoppingCartEl = document.querySelector('.shopping-cart-' + productID);

		// Update the field of product IDs.
		productIDsField.value = newProductIDs;

		// Remove the shopping cart element.
		shoppingCartEl.remove();

		// Update total purchase price.
		updateTotals();

	};
	// Add product to shopping cart.
	var addProductToCart = function(e){

		// Prevent default behavior on click.
		e.preventDefault();

		// Get elements.
		var productID = this.getAttribute('data-product-id');
		var productName = this.getAttribute('data-product-name');
		var productPrice = this.getAttribute('data-product-price');

		// Add product ID to form field.
		productIDsField.value += productID + ',';

		// Generate HTML element for shopping cart.
		var productListingEl = document.createElement('div');
				productListingEl.className = 'cart-item shopping-cart-' + productID + ' grid-x';
		var productImgElSrc = document.querySelector('#product-' + productID + '.card .wp-post-image').src;
		var productImgEl = document.createElement('div');
				productImgEl.className = 'cell shrink';
				productImgEl.innerHTML = '<img width="50" src="' + productImgElSrc + '">';
		var productNameEl = document.createElement('div');
				productNameEl.className = 'cell auto';
				productNameEl.innerHTML = productName;
		var productPriceEl = document.createElement('div');
				productPriceEl.className = 'cell shrink align-right bold';

		// Generate Trash HTML element for shopping cart.
		var trash = document.createElement('button');
				trash.type = 'button';
				trash.className = 'trash';
				trash.innerHTML = 'X';
				trash.setAttribute('data-product-id', productID);
				trash.setAttribute('data-product-price', productPrice);
				trash.onclick = removeProduct;

		// Add elements to page.
		productPriceEl.appendChild(trash);
		productPriceEl.appendChild(document.createTextNode(productPrice));
		productListingEl.appendChild(productImgEl);
		productListingEl.appendChild(productNameEl);
		productListingEl.appendChild(productPriceEl);
		productsListEl.appendChild(productListingEl);

		// Update total purchase price.
		updateTotals();
	};
	// Update total purchase cost.
	var updateTotals = function(){

		// Get array of post IDs for product post type.
		var ids = productIDsField.value;
		ids = ids.replace(/^,|,$/g,'');
		ids = ids.split(',');

		// Add price associated with each product to total.
		var total = 0;
		for ( var i=0; i < ids.length; i++ ) {
			var selector  = '.price-' + ids[i];
			var price = document.querySelector(selector).innerHTML;
			price = price.replace( /\$|,/g, '' );
			price = parseFloat( price );
			total += price;
		}

		// Convert total to string and push to DOM elements.
		var totalString = '$' + total.toFixed(2).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
		productsTotalEl.innerHTML = totalString;
		productsTotalField.value = total;

		// Check for contribution needed.
		var allocationDataEl = document.querySelector('#allocation-data');
		var contributionEl = document.querySelector('#contribution_needed');
		var allocation = parseFloat( allocationDataEl.getAttribute('data-allocation') );
		var threshold = parseFloat( allocationDataEl.getAttribute('data-allocation-threshold') );
		if ( total > threshold ) {
			// Contribution needed.
			var contributionAmount = total - allocation;
			contributionEl.innerHTML = '$' + contributionAmount.toFixed(2).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
			if ( allocationDataEl.className.indexOf('hidden') > 0 ) {
				allocationDataEl.className = allocationDataEl.className.replace(' hidden', '');
			}
		} else {
			// Contribution not needed. Hide element if visible.
			if ( allocationDataEl.className.indexOf('hidden') < 0 ) {
				allocationDataEl.className += ' hidden';
			}
		}

	};
	// Add event handlers.
	for ( var i=0; i < productAdds.length; i++ ) {
		productAdds[i].onclick = addProductToCart;
	}

})();
