function StoreCheckoutPage_sensitizeFields(elements)
{
	var element;
	for (var i = 0; i < elements.length; i++) {
		if (typeof elements[i] == 'string')
			element = document.getElementById(elements[i]);
		else
			element = elements[i];

		element.disabled = false;
		element.className = element.className.replace(/ *swat-insensitive/, '');
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

		element.disabled = true;
		element.className += ' swat-insensitive';
	}
}
