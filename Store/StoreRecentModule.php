<?php


/**
 * Tracks recently viewed things in a web-store application
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreRecentStack
 */
class StoreRecentModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * @var ArrayObject
	 */
	protected $stacks;

	/**
	 * @var array
	 */
	protected $exclusion_ids = array();

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
			'SiteCookieModule'
		);
		$depends[] = new SiteApplicationModuleDependency(
			'SiteSessionModule'
		);
		return $depends;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		if ($this->stacks === null) {
			if (isset($this->app->cookie->recent) &&
				($this->app->cookie->recent instanceof ArrayObject)) {
				$this->stacks = $this->app->cookie->recent;
			} else {
				$this->stacks = new ArrayObject();
			}
		}
	}

	// }}}
	// {{{ public function add()

	public function add($stack_name, $id)
	{
		$this->init();

		if (!isset($this->stacks[$stack_name])) {
			$this->stacks[$stack_name] = new StoreRecentStack();
		}

		$stack = $this->stacks[$stack_name];
		$stack->add($id);
	}

	// }}}
	// {{{ public function get()

	public function get($stack_name, $count = null)
	{
		$this->init();

		if (isset($this->exclusion_ids[$stack_name])) {
			$exclude_id = $this->exclusion_ids[$stack_name];
		} else {
			$exclude_id = null;
		}

		if (isset($this->stacks[$stack_name])) {
			$stack = $this->stacks[$stack_name];
			$values =  $stack->get($count, $exclude_id);
		} else {
			$this->stacks[$stack_name] = new StoreRecentStack();
			$values = null;
		}

		return $values;
	}

	// }}}
	// {{{ public function save()

	public function save()
	{
		// Only save recently viewed item when session is active. This
		// is intended to assist full page caching. If we save recent items
		// before the session is started, we can never cache pages effectively.
		if ($this->app->session->isActive()) {
			$this->app->cookie->setCookie('recent', $this->stacks);
		}
	}

	// }}}
	// {{{ public function setExclusionId()

	public function setExclusionId($stack_name, $id)
	{
		$this->exclusion_ids[$stack_name] = $id;
	}

	// }}}
}

?>
