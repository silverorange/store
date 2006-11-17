function StoreCheckoutPage_sensitizeFields(elements)
{
	var element;
	for (var i = 0; i < elements.length; i++) {
		if (typeof elements[i] == 'string')
			element = document.getElementById(elements[i]);
		else
			element = elements[i];

		if (element) {
			element.disabled = false;
			YAHOO.util.Dom.removeClass(element, 'swat-insensitive');
		}
	}
}

function StoreCheckoutPage_desensitizeFields(elements)
{
	var element;
	for (var i = 0; i < elements.length; i++) {
		if (typeof elements[i] == 'string')
			element = document.getElementById(elements[i]);
		else
			element = elements[i];

		if (element) {
			element.disabled = true;
			YAHOO.util.Dom.addClass(element, 'swat-insensitive');
		}
	}
}
