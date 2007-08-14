<?php

require_once 'Site/dataobjects/SiteArticleWrapper.php';
require_once 'Store/dataobjects/StoreArticle.php';

/**
 *
 * @package   Store
 * @copyright 2007 silverorange
 */
class StoreArticleWrapper extends SiteArticleWrapper
{
	// {{{ protected properties

	/**
	 * The region to use when loading region-specific sub-articles
	 *
	 * @var StoreRegion
	 * @see StoreProduct::setRegion()
	 */ 
	protected $region = null;

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region to use when loading region-specific sub-articles
	 *
	 * @param StoreRegion $region the region to use.
	 */
	public function setRegion(StoreRegion $region)
	{
		$this->region = $region;

		foreach ($this->getArray() as $article)
			$article->setRegion($region);
	}

	// }}}
}

?>
