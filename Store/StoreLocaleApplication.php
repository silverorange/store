<?php

require_once 'Store/StoreApplication.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';

/**
 *
 *
 * @package   Store
 * @copyright 2004-2015 silverorange
 */
abstract class StoreLocaleApplication extends StoreApplication
{
	// {{{ public function getBaseHref()

	public function getBaseHref($secure = null)
	{
		$locale = $this->locale;

		if ($locale === null)
			return parent::getBaseHref($secure);

		$language = substr($locale, 0, 2);
		$country = strtolower(substr($locale, 3, 2));
		return parent::getBaseHref($secure).$country.'/'.$language.'/';
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
	// {{{ public function getBaseHrefRelativeUri()

	public function getBaseHrefRelativeUri($secure = null)
	{
		$uri = parent::getBaseHrefRelativeUri($secure);

		// trim locale from beginning of relative uri
		$uri = preg_replace('|^[a-z][a-z]/[a-z][a-z]/|', '', $uri);

		return $uri;
	}

	// }}}
	// {{{ public function getSwitchLocaleLink()

	/**
	 * Gets the link to switch locales
	 *
	 * @param string $locale the locale to link to.
	 * @param string $source optional additional source path to append to the
	 *                       base link.
	 *
	 * @return string the link to switch locales on the site
	 */
	public function getSwitchLocaleLink($locale, $source = null)
	{
		$link = $this->getRootBaseHref();

		if (isset($this->mobile)) {
			if ($this->mobile->isMobileUrl() &&
				$this->mobile->getPrefix() !== null) {

				$link.= $this->mobile->getPrefix().'/';
			}
		}

		$language = substr($locale, 0, 2);
		$country = strtolower(substr($locale, 3, 2));
		$link.= $country.'/'.$language.'/';

		if ($source !== null) {
			$link.= $source;
		}

		return $link;
	}

	// }}}
	// {{{ public function getSwitchMobileLink()

	/**
	 * Gets the link to switch to the mobile, or non-mobile url
	 *
	 * @param boolean $mobile If true, the link is for the mobile version
	 *                        of the site, if false, for the non-mobile version.
	 *
	 * @return string the link to switch the mobile url of the site
	 */
	public function getSwitchMobileLink($mobile = true, $source = null)
	{
		$link = $this->getRootBaseHref();

		if (!isset($this->mobile)) {
			throw new SwatException(
				'This site does not have a SiteMobileModule');
		}

		if ($mobile) {
			$link.= $this->mobile->getPrefix().'/';
		}

		if ($this->locale instanceof StoreLocale) {
			$language  = substr($this->locale->id, 0, 2);
			$country   = strtolower(substr($this->locale->id, 3, 2));
			$link.= $language.'/'.$country.'/';
		}

		if ($source !== null) {
			$link.= $source;
		}

		$link.= sprintf('?%s=%s',
			$this->mobile->getSwitchGetVar(),
			$mobile ? '1' : '0');

		return $link;
	}

	// }}}
	// {{{ protected function loadPage()

	protected function loadPage()
	{
		$this->parseLocale(self::initVar('locale'));

		parent::loadPage();
	}

	// }}}
	// {{{ protected function parseLocale()

	protected function parseLocale($locale)
	{
		$this->locale = null;
		$this->region = null;

		$matches = array();
		if (preg_match('|([a-z][a-z])/([a-z][a-z])|', $locale, $matches) != 1)
			return;

		$this->locale = $matches[2].'_'.strtoupper($matches[1]);

		$sql = 'select id, title from Region where id in
			(select region from Locale where id = %s)';

		$sql = sprintf($sql, $this->db->quote($this->locale, 'text'));
		$regions = SwatDB::query($this->db, $sql, 'StoreRegionWrapper');
		$this->region = $regions->getFirst();

		if ($this->region === null)
			$this->locale = null;
	}

	// }}}
}

?>
