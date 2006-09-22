<?php

require_once 'Site/pages/SiteExceptionPage.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreExceptionPage extends SiteExceptionPage
{
	// build phase
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		$suggestions = array();

		$suggestions['contact'] = sprintf(Store::_(
			'If you followed a link from our site or elsewhere, please '.
			'%scontact us%s and let us know where you came from so we can do '.
			'our best to fix it.'),
			'<a href="about/contact">', '</a>');

		$suggestions['typo'] = Store::_(
			'If you typed in the address, please double check the spelling.');

		$suggestions['search'] = Store::_(
			'If you are looking for a product or product information, try '.
			'browsing the product listing to the left or using the search box '.
			'on the top right.');

		return $suggestions;
	}

	// }}}
}

?>
