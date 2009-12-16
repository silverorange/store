<?php

require_once 'Site/SiteApplicationModule.php';
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
	// {{{ private properties

	private $stacks;

	// }}}
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
			'SiteCookieModule');

		return $depends;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		if ($this->stacks === null) {
			if (isset($this->app->cookie->recent) &&
				($this->app->cookie->recent instanceof ArrayObject))
					$this->stacks = $this->app->cookie->recent;
			else
					$this->stacks = new ArrayObject();
		}
	}

	// }}}
	// {{{ public function add()

	public function add($stack_name, $id)
	{
		$this->init();

		if (!$this->stacks->offsetExists($stack_name))
			$this->stacks->offsetSet($stack_name, new StoreRecentStack());

		$stack = $this->stacks->offsetGet($stack_name);
		$stack->add($id);
	}

	// }}}
	// {{{ public function get()

	public function get($stack_name, $count = null)
	{
		$this->init();

		$exclude_id = null;

		$page = $this->app->getPage();
		if ($stack_name == 'products' && $page instanceof StoreProductPage)
			$exclude_id = $page->product_id;

		if ($this->stacks->offsetExists($stack_name)) {
			$stack = $this->stacks->offsetGet($stack_name);
			$values =  $stack->get($count, $exclude_id);
		} else {
			$this->stacks->offsetSet($stack_name, new StoreRecentStack());
			$values = null;
		}

		return $values;
	}

	// }}}
	// {{{ public function save()

	public function save()
	{
		$this->app->cookie->setCookie('recent', $this->stacks);
	}

	// }}}
}

?>
