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

		var radio_list = document.getElementsByName(this.custom_item.name);

		for (var i = 0; i < radio_list.length; i++) {
			YAHOO.util.Event.addListener(radio_list[i], 'change',
				this.handlePriceChange, this, true);

			YAHOO.util.Event.addListener(radio_list[i], 'keyup',
				this.handlePriceChange, this, true);
		}

		YAHOO.util.Event.addListener(this.custom_price, 'click',
			this.updateOption, this, true);

		this.handlePriceChange();
	}
}

StoreGiftCertificateRadioList.prototype.updateOption = function()
{
	this.custom_item.checked = true;
	this.handlePriceChange();
}

StoreGiftCertificateRadioList.prototype.handlePriceChange = function()
{
	if (this.old_custom_price_value != this.custom_price.value) {
		this.custom_item.checked = true;
		this.old_custom_price_value = this.custom_price.value;
	}

	if (this.custom_item.checked) {
		YAHOO.util.Dom.removeClass(this.custom_price, 'swat-insensitive');
	} else {
		YAHOO.util.Dom.addClass(this.custom_price, 'swat-insensitive');
	}
};
