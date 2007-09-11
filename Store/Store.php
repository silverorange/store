<?php

require_once 'Swat/Swat.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/Site.php';

/**
 * Container for package wide static methods
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Store 
{
	// {{{ constants

	/**
	 * The package identifier
	 */
	const PACKAGE_ID = 'Store';

	const GETTEXT_DOMAIN = 'store';

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return Store::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(Store::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext($singular_message,
		$plural_message, $number)
	{
		return dngettext(Store::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(Store::GETTEXT_DOMAIN, '@DATA-DIR@/Store/locale');
		bind_textdomain_codeset(Store::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function getDependencies()

	/**
	 * Gets the packages this package depends on
	 *
	 * @return array an array of package IDs that this package depends on.
	 */
	public static function getDependencies()
	{
		return array(Swat::PACKAGE_ID, Site::PACKAGE_ID);
	}

	// }}}
}

Store::setupGettext();
SwatUI::mapClassPrefixToPath('Store', 'Store');

SwatDBClassMap::addPath('Store/dataobjects');
SwatDBClassMap::add('SiteAccount', 'StoreAccount');
SwatDBClassMap::add('SiteArticle', 'StoreArticle');
SwatDBClassMap::add('SiteArticleWrapper', 'StoreArticleWrapper');

?>
