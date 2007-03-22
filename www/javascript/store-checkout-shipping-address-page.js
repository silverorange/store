function StoreCheckoutShippingAddressPage(id, provstate_other_index)
{
	this.container = document.getElementById('shipping_address_form');
	this.list = document.getElementsByName('shipping_address_list');
	this.list_new = document.getElementById('shipping_address_list_new');

	this.provstate = document.getElementById('shipping_address_provstate');
	this.provstate_other_id = 'shipping_address_provstate_other';

	StoreCheckoutShippingAddressPage.superclass.constructor.call(this, id,
		provstate_other_index);
}

YAHOO.extend(StoreCheckoutShippingAddressPage, StoreCheckoutAddressPage, {

getFieldNames: function()
{
	return [
		'shipping_address_fullname',
		'shipping_address_line1',
		'shipping_address_line2',
		'shipping_address_city',
		'shipping_address_provstate',
		'shipping_address_postalcode',
		'shipping_address_country'];
}

});
