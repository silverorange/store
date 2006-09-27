/**
 * Fills name in billing address and credit card fullname when unfocusing
 * basic info fullname
 *
 * @param String fullname_id the name of the fullname entry input.
 * @param String billing_address_fullname_id the name of the billing fullname
 *                                            entry input.
 * @param String credit_card_fullname_id the name of the credit card entry
 *                                        input.
 */
function StoreCheckoutFirstPage(fullname_id, billing_address_fullname_id,
	credit_card_fullname_id)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.fullname = document.getElementById(fullname_id);
	this.billing_address_fullname =
		document.getElementById(billing_address_fullname_id);

	this.credit_card_fullname =
		document.getElementById(credit_card_fullname_id);

	function handleBlur(event)
	{
		if (self.billing_address_fullname.value == '')
			self.billing_address_fullname.value =
				self.fullname.value;

		if (self.credit_card_fullname.value == '')
			self.credit_card_fullname.value =
				self.fullname.value;
	}

	if (is_ie)
		this.fullname.attachEvent('onblur', handleBlur);
	else
		this.fullname.addEventListener('blur', handleBlur, false);
}
