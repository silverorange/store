<?php

require_once 'Site/SiteApplicationModule.php';

require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/StoreRecentStack.php';

/**
 * Tracks recently viewed things in a web-store application
 *
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRecentModule extends SiteApplicationModule
{
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * @return array an array of {@link SiteModuleDependency} objects defining
	 *                        the features this module depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency(
			'SiteAccountSessionModule');

		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');

		if ($this->app->hasModule('SiteMultipleInstanceModule'))
			$depends[] = new SiteApplicationModuleDependency(
				'SiteMultipleInstanceModule');

		return $depends;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		if ($this->app->session->isActive())
			if (!isset($this->app->session->recent) ||
				!($this->app->session->recent instanceof ArrayObject))
					$this->app->session->recent = new ArrayObject();
	}

	// }}}
	// {{{ public function add()

	public function add($stack_name, $id)
	{
		$this->app->session->activate();
		$this->init();

		if (!$this->app->session->recent->offsetExists($stack_name))
			$this->app->session->recent->offsetSet($stack_name, new StoreRecentStack());

		$stack = $this->app->session->recent->offsetGet($stack_name);
		$stack->add($id);
	}

	// }}}
	// {{{ public function get()

	public function get($stack_name, $count = null)
	{
		if (!$this->app->session->isActive())
			return null;

		$this->init();

		$exclude_id = null;

		$page = $this->app->getPage();
		if ($stack_name == 'products' && $page instanceof StoreProductPage)
			$exclude_id = $page->product_id;

		if (!$this->app->session->recent->offsetExists($stack_name))
			$this->app->session->recent->offsetSet($stack_name, new StoreRecentStack());

		$stack = $this->app->session->recent->offsetGet($stack_name);

		return $stack->get($count, $exclude_id);
	}

	// }}}
}

?>
