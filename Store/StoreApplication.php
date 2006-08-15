<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCookieModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteMessagesModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreSessionModule.php';
require_once 'Store/StoreCartModule.php';
require_once 'Store/dataobjects/StoreAdWrapper.php';

/**
 * Web application class for a store
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreApplication extends SiteApplication
{
	// {{{ public properties

	public $db;

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
			'messages' => 'SiteMessagesModule',
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
