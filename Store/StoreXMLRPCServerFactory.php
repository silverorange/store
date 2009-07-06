<?php

require_once 'Site/SiteXMLRPCServerFactory.php';

/**
 * @package   Store
 * @copyright 2007-2008 silverorange
 */
class StoreXMLRPCServerFactory extends SiteXMLRPCServerFactory
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);

		// set location to load Store page classes from
		$this->page_class_map['Store'] = 'Store/pages';
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'quickorder'      => 'StoreQuickOrderServer',
			'product-reviews' => 'StoreProductReviewServer',
			'search-panel'    => 'StoreSearchPanelServer',
			'feedback-panel'  => 'StoreFeedbackPanelServer',
		);
	}

	// }}}
}

?>
