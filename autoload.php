<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/store');

$package->addRule(new Rule('pages', 'Store', array('Page', 'Server')));
$package->addRule(new Rule('exceptions', 'Store', 'Exception'));

// Need to list views because many views live in Store/ and can't be moved
// to the Store/views/ directory.
$package->addRule(
	new Rule(
		'views',
		'Store',
		array(
			'ProductReviewView',
			'BlorgPostSearchView'
		)
	)
);

$package->addRule(
	new Rule(
		'dataobjects',
		'Store',
		array(
			'Binding',
			'Wrapper',
			'AccountAddress',
			'AccountPaymentMethod',
			'Account',
			'Address',
			'Article',
			'Attribute',
			'AttributeType',
			'CardType',
			'CartEntry',
			'Catalog',
			'CategoryImage',
			'Category',
			'ContactMessage',
			'Country',
			'Feature',
			'Feedback',
			'Image',
			'ItemAlias',
			'ItemGroup',
			'ItemMinimumQuantityGroup',
			'Item',
			'Locale',
			'MailChimpOrder',
			'OrderAddress',
			'OrderItem',
			'OrderPaymentMethod',
			'Order',
			'PaymentMethod',
			'PaymentMethodTransaction',
			'PaymentType',
			'PriceRange',
			'ProductImage',
			'Product',
			'ProductReview',
			'ProvState',
			'QuantityDiscount',
			'Region',
			'SaleDiscount',
			'ShippingRate',
			'ShippingType',
			'Voucher',
		)
	)
);

$package->addRule(new Rule('', 'Store'));

Autoloader::addPackage($package);

?>
