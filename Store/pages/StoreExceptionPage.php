<?php

require_once 'Site/pages/SiteExceptionPage.php';

/**
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class StoreExceptionPage extends SiteExceptionPage
{
	// build phase
	// {{{ protected function display()

	protected function display($status)
	{
		printf('<p>%s</p>', $this->getSummary($status));

		$output = '<ul class="spaced">'.
			'<li>If you followed a link from our site or elsewhere, '.
			'please <a href="about/contact">contact us</a> and let us '.
			'know where you came from so we can do our best to fix '.
			'it.</li><li>If you typed in the address, please double '.
			'check the spelling.</li><li>If you are looking for a '.
			'product or product information, try browsing the product '.
			'listing to the left or using the search box on the top '.
			'right.</li></ul>';

		echo $output;

		if ($this->exception !== null)
			$this->exception->process(false);
	}

	// }}}
}
