<?php

require_once('Site/pages/SitePage.php');

/**
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
abstract class StorePage extends SitePage
{
	// {{{ public function isVisibleInRegion()

	/**
	 * Whether or not the page is available in the given region 
	 *
	 * @param StoreRegion Region to check the visibility for.
	 *
	 * @return boolean True if the page is visible, false if not.
	 */
	public function isVisibleInRegion(StoreRegion $region)
	{
		return true;
	}

	// }}}
}

?>
