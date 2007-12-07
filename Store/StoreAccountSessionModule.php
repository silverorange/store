<?php

require_once 'Site/SiteAccountSessionModule.php';

/**
 * Web application module for store sessions with accounts.
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountSessionModule extends SiteAccountSessionModule
{
	// {{{ public function logout()

	/**
	 * Logs the current user out and clears any order they have started
	 */
	public function logout()
	{
		parent::logout();

		unset($this->order);
	}

	// }}}
}

?>
