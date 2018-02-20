<?php

/**
 * Container for package wide static methods
 *
 * @package   Store
 * @copyright 2006-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Store
{
	// {{{ constants

	const GETTEXT_DOMAIN = 'store';

	// }}}
	// {{{ private properties

	/**
	 * Whether or not this package is initialized
	 *
	 * @var boolean
	 */
	private static $is_initialized = false;

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return self::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(self::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext(
		$singular_message,
		$plural_message,
		$number
	) {
		return dngettext(self::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(self::GETTEXT_DOMAIN, '@DATA-DIR@/Store/locale');
		bind_textdomain_codeset(self::GETTEXT_DOMAIN, 'UTF-8');
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
			'store.multiple_payment_support' => false,
			'store.multiple_payment_ui' => false,
			'store.save_account_address' => true,
			'store.path' => 'store/',

			// Expiry dates for the privateer data deleter
			'expiry.accounts'       => '3 years',
			'expiry.orders'         => '1 year',

			// Froogle
			'froogle.filename'      => null,
			'froogle.server'        => null,
			'froogle.username'      => null,
			'froogle.password'      => null,

			// Bing Shopping
			'bing.filename' => 'bingshopping.txt',
			'bing.server'   => null,
			'bing.username' => null,
			'bing.password' => null,

			// Optional Wordpress API key for Akismet spam filtering.
			'store.akismet_key'     => null,

			// Optional StrikeIron API keys for address verification.
			'strikeiron.verify_address_usa_key'    => null,
			'strikeiron.verify_address_canada_key' => null,

			// Optional Email address to send feedback to
			'email.feedback_address' => null,

			// Optional list of email addresses to send order comments to
			'email.order_comments_digest_list' => null,

			// mailchimp
			// Optional Plugin ID for reporting sales for mailchimp stats
			'mail_chimp.plugin_id'    => null,
			'mail_chimp.track_orders' => false,

			// AdWords
			'adwords.conversion_id'    => null,
			'adwords.conversion_label' => null,
			// API server used for automating ad creation
			'adwords.server'           => 'https://adwords-sandbox.google.com',
			// client ID used for automating ad creation
			'adwords.client_id'        => null,
			// developer token used for automating ad creation
			'adwords.developer_token'  => null,

			// Authorize.net
			'authorizenet.mode'                  => 'sandbox',
			'authorizenet.login_id'              => null,
			'authorizenet.transaction_key'       => null,
			'authorizenet.invoice_number_prefix' => null,

			// Braintree Payments
			'braintree.environment' => 'sandbox',
			'braintree.merchant_id' => null,
			'braintree.public_key'  => null,
			'braintree.private_key' => null,

			// Analytics
			'analytics.friendbuy_overlay_widget_id' => null,

			// Google Address Autocomplete
			'google_address_autocomplete.enabled' => false,
			'google_address_autocomplete.api_key' => null,
		);
	}

	// }}}
	// {{{ public static function init()

	public static function init()
	{
		if (self::$is_initialized) {
			return;
		}

		Swat::init();
		Site::init();
		Admin::init();

		self::setupGettext();

		SwatUI::mapClassPrefixToPath('Store', 'Store');

		SwatDBClassMap::addPath('Store/dataobjects');
		SwatDBClassMap::add('SiteAccount', 'StoreAccount');
		SwatDBClassMap::add('SiteContactMessage', 'StoreContactMessage');
		SwatDBClassMap::add('SiteArticle', 'StoreArticle');
		SwatDBClassMap::add('SiteArticleWrapper', 'StoreArticleWrapper');

		SiteViewFactory::addPath('Store/views');
		SiteViewFactory::registerView(
			'product-review',
			'StoreProductReviewView'
		);

		if (class_exists('Blorg')) {
			SiteViewFactory::registerView(
				'post-search',
				'StoreBlorgPostSearchView'
			);
		}

		self::$is_initialized = true;
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

?>
