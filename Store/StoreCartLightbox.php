<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';

/**
 * Control to display a lightbox driven cart on the page
 *
 * @package   Store
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartLightbox extends SwatControl
{
	// {{{ constants

	const GOOGLE_ANALYTICS = 1;

	// }}}
	// {{{ public properties

	/*
	 * @var string
	 */
	public $class_name = 'StoreCartLightBox';

	/*
	 * @var integer
	 */
	public $analytics;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new cart lightbox
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$yui = new SwatYUI(array('dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/swat/javascript/swat-view.js',
			Swat::PACKAGE_ID);

		$this->addJavaScript('packages/swat/javascript/swat-table-view.js',
			Swat::PACKAGE_ID);

		$this->addJavascript('packages/store/javascript/store-cart-lightbox.js',
			Store::PACKAGE_ID);

		$this->addStyleSheet('packages/store/styles/store-cart-lightbox.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $translated = false;

		$javascript = '';

		if (!$translated) {
			$javascript.= sprintf("StoreCartLightBox.empty_message = %s;\n",
				SwatString::quoteJavaScriptString(
					Store::_('Your Shopping Cart is Empty')));

			$javascript.= sprintf("StoreCartLightBox.loading_message = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('Loading…')));

			$javascript.= sprintf("StoreCartLightBox.submit_message = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('Updating Cart…')));

			$javascript.= sprintf(
				"StoreCartLightBox.item_count_message_singular = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('(1 item)')));

			$javascript.= sprintf(
				"StoreCartLightBox.item_count_message_plural = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('(%s items)')));

			$translated = true;
		}

		$javascript.= 'var cart_lightbox = '.$this->class_name.
			".getInstance();\n";

		if ($this->analytics === self::GOOGLE_ANALYTICS) {
			$javascript.= "cart_lightbox.analytics = 'google_analytics';\n";
		}

		return $javascript;
	}

	// }}}
}

?>
