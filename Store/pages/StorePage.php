<?php

require_once 'Site/pages/SitePage.php';
require_once 'Store/StorePath.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
abstract class StorePage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var StoreCategoryPath
	 */
	protected $path;

	// }}}
	// {{{ public function getPath()

	/**
	 * Gets the path of this page
	 *
	 * @return StorePath the path of this page.
	 */
	public function getPath()
	{
		return $this->path;
	}

	// }}}
	// {{{ public function setPath()

	/**
	 * Sets the path of this page
	 *
	 * @param StorePath $path
	 */
	public function setPath(StorePath $path)
	{
		$this->path = $path;
	}

	// }}}
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
