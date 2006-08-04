<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCookieModule.php';
require_once 'Site/SiteConfigModule.php';

require_once 'Store/Store.php';
require_once 'Store/StoreSessionModule.php';
require_once 'Store/StoreCartModule.php';
require_once 'Store/dataobjects/StoreAdWrapper.php';

/**
 * Web application class for a store
 *
 * @package   Store
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreApplication extends SiteApplication
{
	// {{{ public properties

	public $db;

	// }}}
	// {{{ public function relocate()

	/**
	 * Relocates to another URI
	 *
	 * Appends the session identifier (SID) to the URL if necessary.
	 * Then call parent method Site::relocate to do the actual relocating.
	 * This function does not return and in fact calls the PHP exit()
	 * function just to be sure execution does not continue.
	 *
	 * @param string $uri the URI to relocate to.
	 * @param boolean $append_sid whether to append the SID to the URI
	 */
	public function relocate($uri, $secure = null, $append_sid = true)
	{
		if ($append_sid && $this->session->isActive()) {
			if (strpos($uri, '?') === FALSE)
				$uri.= '?';
			else
				$uri.= '&';

			$uri.= sprintf('%s=%s', session_name(), session_id());
		}

		parent::relocate($uri, $secure);
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
			'session'  => 'StoreSessionModule',
			'cookie'   => 'SiteCookieModule',
			'cart'     => 'StoreCartModule',
		);
	}

	// }}}
	// {{{ protected function initModules()

	protected function initModules()
	{
		$this->session->registerDataObject('account', 'StoreAccount');
		$this->session->registerDataObject('order', 'StoreOrder');
		$this->session->registerDataObject('ad', 'StoreAd');

		parent::initModules();

		// set up convenience references
		$this->db = $this->database->getConnection();
	}

	// }}}
	// {{{ protected function getSecureSourceList()

	/**
	 * @see SiteApplication::getSecureSourceList()
	 */
	protected function getSecureSourceList()
	{
		$list = parent::getSecureSourceList();
		$list[] = '^checkout.*';
		$list[] = '^account.*';

		return $list;
	}

	// }}}
	// {{{ protected function parseAd()

	protected function parseAd($ad_shortname)
	{
		$sql = sprintf('select id, title, shortname from Ad
			where shortname = %s',
			$this->db->quote($ad_shortname, 'text'));

		$ad = SwatDB::query($this->db, $sql, 'StoreAdWrapper')->getFirst();

		if ($ad !== null) {
			if (!$this->session->isActive())
				$this->session->activate();

			$this->session->ad = $ad;
		}
	}

	// }}}
}

?>
