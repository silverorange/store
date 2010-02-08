<?php

require_once 'Swat/SwatNavBar.php';

/**
 * Visible navigation tool (breadcrumb trail)
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SwatNavBar
 */
class StoreNavBar extends SwatNavBar
{
	// {{{ public properties

	/**
	 * Whether or not to add a url parameter for analytics tracking.
	 *
	 * If set to true, all links have an added parameter of link=navbar.
	 * Defaults to false.
	 *
	 * @var boolean
	 */
	public $add_analytics_url_parameter = false;

	// {{{ protected function displayEntry()

	protected function getLink(SwatNavBarEntry $entry)
	{
		$link = parent::getLink($entry);
		if ($this->add_analytics_url_parameter) {
			$concatenater = (strpos($link, '?')) ? '&' : '?';
			$link.= $concatenater.'link=navbar';
		}

		return $link;
	}

	// }}}
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
