function StoreGiftCertificateRadioList(id, custom_value)
{
	this.id = id;
	this.custom_price = document.getElementById(id + '_custom_price');
	this.custom_item = document.getElementById(this.id + '_' + custom_value);

	if (this.custom_price && this.custom_item) {
		this.old_custom_price_value = this.custom_price.value;
		YAHOO.util.Event.addListener(this.custom_price, 'change',
			this.handlePriceChange, this, true);

		YAHOO.util.Event.addListener(this.custom_price, 'keyup',
			this.handlePriceChange, this, true);
	}
}

StoreGiftCertificateRadioList.prototype.handlePriceChange = function()
{
	if (this.old_custom_price_value != this.custom_price.value) {
		this.custom_item.checked = true;
		this.old_custom_price_value = this.custom_price.value;
	}
};
