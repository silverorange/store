<?php

require_once 'Swat/Swat.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/Site.php';

/**
 * Container for package wide static methods
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
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
	// {{{ public static function getConfigDefinitions()

	/**
	 * Gets configuration definitions used by the Store package
	 *
	 * Applications should add these definitions to their config module before
	 * loading the application configuration.
	 *
	 * @return array the configuration definitions used by the Store package.
	 *
	 * @see SiteConfigModule::addDefinitions()
	 */
	public static function getConfigDefinitions()
	{
		return array(
			// Expiry dates for the privateer data deleter
			'expiry.accounts'       => '3 years',
			'expiry.orders'         => '1 year',

			// Froogle
			'froogle.filename'      => null,
			'froogle.server'        => null,
			'froogle.username'      => null,
			'froogle.password'      => null,

			// smtp server name (ex: smtp.mail.silverorange.com)
			'email.smtp_server'     => null,

			// to address for contact-us emails
			'email.contact_address' => null,

			// from address for automated emails sent by orders or accounts
			'email.service_address' => null,

			// from address for contact-us emails (from "the website" to client)
			'email.website_address' => null,

			// Optional Wordpress API key for Akismet spam filtering.
			'store.akismet_key'     => null,

			// Optional StrikeIron API keys for address verification.
			'strikeiron.verify_address_usa_key' => null,
			'strikeiron.verify_address_canada_key' => null,
		);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Prevent instantiation of this static class
	 */
	private function __construct()
	{
	}

	// }}}
}

Store::setupGettext();
SwatUI::mapClassPrefixToPath('Store', 'Store');

SwatDBClassMap::addPath('Store/dataobjects');
SwatDBClassMap::add('SiteAccount',        'StoreAccount');
SwatDBClassMap::add('SiteArticle',        'StoreArticle');
SwatDBClassMap::add('SiteArticleWrapper', 'StoreArticleWrapper');

if (class_exists('Blorg')) {
	require_once 'Blorg/BlorgViewFactory.php';
	BlorgViewFactory::addPath('Store/views');
	BlorgViewFactory::registerView('post-search', 'StoreBlorgPostSearchView');
}

?>
