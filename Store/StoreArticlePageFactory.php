<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/exceptions/StoreNotFoundException.php';
require_once '../include/layouts/VeseysLocaleLayout.php';

/**
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class ArticlePageFactory
{
	// {{{ private static function getPageMap()

	private static function getPageMap()
	{
		return array(
			'^(about/contact)$'                    => 'ContactPage',
			'^(gardeninfo/mantis/request)$'        => 'MantisCatalogRequestPage',
			'^(about/veseys/fundraising/request)$' => 'FundraisingRequestPage',
			'^(catalogue)$'                        => 'CatalogRequestPage',
			'^(quickorder)$'                       => 'QuickOrderPage',
			'^(search)$'                           => 'SearchPage',
			'^(cart)$'                             => 'CartPage',
			'^(cart/promotion)$'                   => 'PromotionPage',
			'^(about)$'                            => 'AboutPage',
			'^(about/website/sitemap)$'            => 'SiteMapPage',

			'^(about/website/newsletter)$'         => 'NewsletterSignupPage',
			'^(about/website/newsletter/remove)$'  => 'NewsletterRemovePage',

			'^(account)$'                          => 'AccountDetailsPage',
			'^(account/login)$'                    => 'AccountLoginPage',
			'^(account/edit)$'                     => 'AccountEditPage',
			'^(account)/order([0-9]+)$'            => 'AccountOrderPage',
			'^(account/forgotpassword)$'           => 'AccountForgotPasswordPage',

			'^(gardeninfo/gallery)$'               => 'GalleryPage',
			'^(gardeninfo/gallery)/page([0-9]+)$'  => 'GalleryPage',
			'^(gardeninfo/gallery)/photo([0-9]+)$' => 'GalleryPhotoPage',

			'^(gardeninfo/guide/.*)$'              => 'PlantingGuidePage',
			'^(gardeninfo/guide)$'                 => 'PlantingGuidePage',
			'^(gardeninfo/frost)$'                 => 'FrostDatesPage',
			'^(gardeninfo/legend)$'                => 'LegendPage',

			'^(gardeninfo/forum)$'                 => 'ForumPage',
			'^(gardeninfo/forum)/page([0-9]+)$'    => 'ForumPage',
			'^(gardeninfo/forum)/message([0-9]+)$' => 'ForumMessagePage',
			'^(gardeninfo/forum)/new$'             => 'ForumPostPage',

			'^(checkout)$'                         => 'CheckoutFrontPage',
			'^(checkout/first)$'                   => 'CheckoutFirstPage',
			'^(checkout/confirmation)$'            => 'CheckoutConfirmationPage',
			'^(checkout/basicinfo)$'               => 'CheckoutBasicInfoPage',
			'^(checkout/billingaddress)$'          => 'CheckoutBillingAddressPage',
			'^(checkout/shippingaddress)$'         => 'CheckoutShippingAddressPage',
			'^(checkout/paymentmethod)$'           => 'CheckoutPaymentMethodPage',
			'^(checkout/promotion)$'               => 'CheckoutPromotionPage',
			'^(checkout/thankyou)$'                => 'CheckoutThankYouPage',
		);
	}

	// }}}
	// {{{ public static function resolvePage()

	public static function resolvePage($app, $source)
	{
		$layout = new VeseysLocaleLayout($app, '../include/layouts/xhtml/default.php');

		foreach (self::getPageMap() as $pattern => $class) {
			$regs = array();
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				$class_file = sprintf('../include/pages/%s.php', $class);
				require_once $class_file;
				array_shift($regs); //discard full match
				$article_path = array_shift($regs);
				array_unshift($regs, $layout);
				array_unshift($regs, $app);

				$page = call_user_func_array(
					array(new ReflectionClass($class), 'newInstance'),
					$regs);

				$page->setPath($article_path);
				return $page;
			}
		}

		require_once '../include/pages/ArticlePage.php';
		return new ArticlePage($app, $layout);
	}

	// }}}
}

?>
