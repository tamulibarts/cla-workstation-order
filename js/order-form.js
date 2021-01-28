(function(){
	// Get elements.
	var contributionField = document.querySelector('#cla_contribution_amount');
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

		// Enable the Add to Cart button.
		var cartButton = document.querySelector('#cart-btn-' + productID);
		cartButton.removeAttribute('disabled');
		cartButton.innerHTML = 'Add';

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

		// Disable button.
		this.setAttribute('disabled','disabled');
		this.innerHTML = 'In cart';

		// Update total purchase price.
		updateTotals();
	};

	// Get total cost of products.
	var getTotal = function(){

		// Get array of post IDs for product post type.
		var ids = productIDsField.value;
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
			var selector  = '.price-' + ids[i];
			var price = document.querySelector(selector).innerHTML;
					price = price.replace( /\$|,/g, '' );
					price = parseFloat( price );
			total += price;
		}

		return total;

	};

	var getContributionMade = function(){

		var contributionField = document.querySelector('#cla_contribution_amount');
		var contribution = contributionField.value;

		return contribution;

	};

	var isOverageTriggered = function(){

		var allocationDataEl = document.querySelector('#allocation-data');

		// Get numbers involved.
		var total = getTotal();
		var contributionMade = getContributionMade();
		var allocation = parseFloat( allocationDataEl.getAttribute('data-allocation') );
		var threshold = parseFloat( allocationDataEl.getAttribute('data-allocation-threshold') );
		var returnVal = {};

		if ( threshold < total ) {
			// They have to pay everything beyond the allocation.
			var contributionAmountNeeded = total - contributionMade - allocation;
			returnVal.allowed = false;
			returnVal.overage = contributionAmountNeeded;
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
		productsTotalEl.innerHTML = totalString;
		productsTotalField.value = total;

		// Check for contribution needed.
		var allocationDataEl = document.querySelector('#allocation-data');
		var contributionNeededEl = document.querySelector('#contribution_needed');
		var isOverThreshold = isOverageTriggered();

		// Show or hide the contribution amount needed and disable the form.
		if ( isOverThreshold.allowed === false ) {

			// Contribution needed.
			var contributionAmountNeeded = isOverThreshold.overage;
			contributionNeededEl.innerHTML = '$' + contributionAmountNeeded.toFixed(2).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");

			if ( allocationDataEl.className.indexOf('hidden') > 0 ) {
				// Show contribution needed.
				allocationDataEl.className = allocationDataEl.className.replace(' hidden', '');
			}

			// Disable submit button.
			if ( ! document.querySelector('#cla_submit').hasAttribute('disabled') ) {
				document.querySelector('#cla_submit').setAttribute('disabled','disabled');
			}

		} else {

			// Contribution not needed. Hide element if visible.
			if ( allocationDataEl.className.indexOf('hidden') < 0 ) {
				allocationDataEl.className += ' hidden';
				contributionNeededEl.innerHTML = '$0.00';
			}

			// Enable submit button.
			if ( document.querySelector('#cla_submit').hasAttribute('disabled') ) {
				document.querySelector('#cla_submit').removeAttribute('disabled');
			}

		}

	};

	// Add event handlers.
	for ( var i=0; i < productAdds.length; i++ ) {
		productAdds[i].onclick = addProductToCart;
	}

	contributionField.onkeyup = updateTotals;

})();

// Provide toggle feature for product categories.
(function(){
	var toggles = document.querySelectorAll('#products .toggle .btn');
	var toggleActive = function(e) {

		// Prevent default.
		e.preventDefault();

		// Toggle "active" class name on parent.
		var element = this.parentNode.parentNode;

		if (element.classList) {

		  element.classList.toggle("active");

		} else {

		  // For IE9
		  var classes = element.className.split(" ");
		  var i = classes.indexOf("active");

		  if (i >= 0)
		    classes.splice(i, 1);
		  else
		    classes.push("active");

	    element.className = classes.join(" ");

		}

	}

	// Attach event handlers.
	for ( var i=0; i < toggles.length; i++ ) {
		toggles[i].onclick = toggleActive;
	}
})();

// Provide toggle feature for product details.
(function(){
	var toggles = document.querySelectorAll('#products .more-details');
	var toggleActive = function(e) {

		// Prevent default.
		e.preventDefault();

		// Toggle "active" class name on parent.
		var element = this.parentNode;

		if (element.classList) {

		  element.classList.toggle("active");

		} else {

		  // For IE9
		  var classes = element.className.split(" ");
		  var i = classes.indexOf("active");

		  if (i >= 0)
		    classes.splice(i, 1);
		  else
		    classes.push("active");

	    element.className = classes.join(" ");

		}

	}

	// Attach event handlers.
	for ( var i=0; i < toggles.length; i++ ) {
		toggles[i].onclick = toggleActive;
	}
})();
