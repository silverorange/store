/**
 * Fills name in billing address and credit card fullname when unfocusing
 * basic info fullname
 */
function StoreCheckoutFirstPage()
{
	this.fullname = document.getElementById('fullname');
	this.credit_card_fullname = document.getElementById('credit_card_fullname');
	this.billing_address_fullname =
		document.getElementById('billing_address_fullname');

	if (this.fullname) {
		YAHOO.util.Event.addListener(this.fullname, 'blur',
			StoreCheckoutFirstPage.handleFullnameBlur, this);
	}
}

StoreCheckoutFirstPage.handleFullnameBlur = function(event, page)
{
	page.updateFields();
}

StoreCheckoutFirstPage.prototype.updateFields = function()
{
	if (this.fullname) {
		if (this.billing_address_fullname &&
			this.billing_address_fullname.value == '') {
			this.billing_address_fullname.value = this.fullname.value;
		}

		if (this.credit_card_fullname &&
			this.credit_card_fullname.value == '') {
			this.credit_card_fullname.value = this.fullname.value;
		}
	}
}
