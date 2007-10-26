<?php

require_once 'Site/SiteWebApplication.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteCookieModule.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteMessagesModule.php';
require_once 'Site/SiteAccountSessionModule.php';
require_once 'Store/Store.php';
require_once 'Store/StoreMessage.php';
require_once 'Store/StoreCartModule.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StoreAdWrapper.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * Web application class for a store
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreApplication extends SiteWebApplication
{
	// {{{ public properties

	/**
	 * A convenience reference to the database connection of this store
	 * application
	 *
	 * This reference is available after StoreWebApplication::initModules() is
	 * called. This means this convenience reference is usually available just
	 * after the construction of this application is completed.
	 *
	 * @var MDB2_Driver_Common
	 */
	public $db;

	/**
	 * Default locale
	 *
	 * This locale is used for translations, collation and locale-specific
	 * formatting. The locale is a five character identifier composed of a
	 * language code (ISO 639) an underscore and a country code (ISO 3166). For
	 * example, use 'en_CA' for Canadian English.
	 *
	 * @var string
	 */
	public $default_locale = null;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @var StoreRegion
	 */
	protected $region;

	// }}}
	// {{{ public function getCountry()

	/**
	 * @return string
	 */
	public function getCountry($locale = null)
	{
		if ($locale === null)
			$locale = $this->locale;

		$country = substr($locale, 3, 2);

		return $country;
	}

	// }}}
	// {{{ public function getLocale()

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	// }}}
	// {{{ public function getRegion()

	/**
	 * @return StoreRegion
	 */
	public function getRegion()
	{
		return $this->region;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
			'session'  => 'SiteAccountSessionModule',
			'cookie'   => 'SiteCookieModule',
			'cart'     => 'StoreCartModule',
			'messages' => 'SiteMessagesModule',
		);
	}

	// }}}
	// {{{ protected function initModules()

	protected function initModules()
	{
		$this->session->registerDataObject('account',
			SwatDBClassMap::get('StoreAccount'));

		$this->session->registerDataObject('order',
			SwatDBClassMap::get('StoreOrder'));

		$this->session->registerDataObject('transaction',
			SwatDBClassMap::get('StorePaymentTransaction'));

		$this->session->registerDataObject('ad',
			SwatDBClassMap::get('StoreAd'));

		parent::initModules();

		// set up convenience references
		$this->db = $this->database->getConnection();
	}

	// }}}
	// {{{ protected function getSecureSourceList()

	/**
	 * Gets an array of pages sources that are secure
	 *
	 * For store web applications, this list containes all checkout and account
	 * pages by default.
	 *
	 * @return array an array or regular expressions using PREG syntax that
	 *                match source strings that are secure.
	 *
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

	/**
	 * Parses an ad shortname into an ad object and stores a row in the
	 * ad referral table
	 *
	 * After the referral is logged, the ad is removed from the URL through
	 * a relocate.
	 *
	 * @param string $ad_shortname the shortname of the ad.
	 */
	protected function parseAd($ad_shortname)
	{
		$sql = sprintf('select id, title, shortname from Ad
			where shortname = %s',
			$this->db->quote($ad_shortname, 'text'));

		$wrapper = SwatDBClassMap::get('StoreAdWrapper');
		$ad = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

		if ($ad !== null) {
			if (!$this->session->isActive())
				$this->session->activate();

			$this->session->ad = $ad;

			/*
			 * Do to mass mailings, large numbers of people follow links with
			 * ads which can lead to database deadlock when inserting the ad
			 * referrer. Here we make five attempts before giving up and 
			 * throwing the exception.
			 */
			$attempt = 0;
			while (true) {
				try {
					$attempt++;
					$this->insertAdReferrer($ad);
					break;
				} catch (SwatDBException $e) {
					if ($attempt > 5)
						throw $e;
				}
			}

			$regexp = sprintf('/&?\??ad=%s/u',
				preg_quote($ad_shortname, '/'));

			$uri = preg_replace($regexp, '', $_SERVER['REQUEST_URI']);
			$this->relocate($uri);
		}
	}

	// }}}
	// {{{ private function insertAdReferrer()

	private function insertAdReferrer($ad)
	{
		$now = new SwatDate();
		$now->toUTC();

		SwatDB::insertRow($this->db, 'AdReferrer',
			array('date:createdate', 'integer:ad'),
			array('createdate' => $now->getDate(), 'ad' => $ad->id));
	}

	// }}}
	// {{{ protected function loadPage()

	protected function loadPage()
	{
		if ($this->locale === null)
			$this->locale = $this->default_locale;

		if ($this->locale !== null)
			setlocale(LC_ALL, $this->locale.'.UTF-8');

		$ad_shortname = self::initVar('ad');
		if ($ad_shortname !== null)
			$this->parseAd($ad_shortname);

		parent::loadPage();
	}

	// }}}
}

?>
