<?php

require_once 'Site/pages/SiteExceptionPage.php';

/**
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class StoreExceptionPage extends SiteExceptionPage
{
	// build phase
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		$suggestions = array();

		$suggestions['contact'] =
			'If you followed a link from our site or elsewhere, please '.
			'<a href="about/contact">contact us</a> and let us know where '.
			'you came from so we can do our best to fix it.';

		$suggestions['typo'] =
			'If you typed in the address, please double check the spelling.';

		$suggestions['search'] =
			'If you are looking for a product or product information, try '.
			'browsing the product listing to the left or using the search box '.
			'on the top right.';

		return $suggestions;
	}

	// }}}
}

?>
