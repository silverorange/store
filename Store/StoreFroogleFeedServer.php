<?php

require_once 'Site/SiteAtomFeedServer.php';
require_once 'Store/StoreFroogleFeed.php';

/**
 * Abstract base class for serving a Froogle Atom feed
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreFroogleFeedServer extends SiteAtomFeedServer
{
	// {{{ protected function getFeed()

	/**
	 * Instasiates a Froogle Feed object
	 *
	 * @return StoreFroogleFeed 
	 */
	protected function getFeed()
	{
		$feed = new StoreFroogleFeed();

		return $feed;
	}

	// }}}
}

?>
