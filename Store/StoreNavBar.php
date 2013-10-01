<?php

require_once 'Swat/SwatNavBar.php';

/**
 * Visible navigation tool (breadcrumb trail)
 *
 * @package   Store
 * @copyright 2010-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SwatNavBar
 */
class StoreNavBar extends SwatNavBar
{
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this navigational bar
	 *
	 * @return array the array of CSS classes that are applied to this
	 *                navigational bar.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('swat-nav-bar');
		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getContainerTag()

	/**
	 * Gets the container tag for this navigational bar
	 *
	 * The container tag wraps around all entries in this navigational bar.
	 *
	 * @return SwatHtmlTag the container tag for this navigational bar.
	 */
	protected function getContainerTag()
	{
		if ($this->container_tag === null)
			$tag = new SwatHtmlTag('div');
		else
			$tag = $this->container_tag;

		$tag->id = $this->id;
		$tag->class = $this->getCSSClassString();
		return $tag;
	}

	// }}}
}

?>
