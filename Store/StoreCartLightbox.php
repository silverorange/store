<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';

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
	 * Empty message
	 *
	 * Optional message to display when the cart is empty.
	 *
	 * @var string
	 */
	public $empty_message = null;

	/*
	 * Override Message
	 *
	 * Optional message to override content of the lightbox. Useful for pages
	 * where you just want to display a message instead of displaying the cart
	 * contents.
	 *
	 * @var string
	 */
	public $override_message = null;

	/*
	 * @var integer
	 */
	public $analytics;

	// }}}
	// {{{ protected properties

	protected $app;
	protected $cart_ui;
	protected $cart_ui_xml = 'Store/pages/mini-cart.xml';

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null,
		SiteApplication $app,
		StoreCartProcessor $processor = null)
	{
		parent::__construct($id);

		$this->app = $app;
		$this->processor = $processor;

		$yui = new SwatYUI(array('dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/swat/javascript/swat-view.js',
			Swat::PACKAGE_ID);

		$this->addJavaScript('packages/swat/javascript/swat-table-view.js',
			Swat::PACKAGE_ID);

		$this->addStyleSheet('packages/swat/styles/swat-table-view.css',
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

		echo '<div id="store_product_cart" class="swat-hidden">';
		echo '<div id="store_product_cart_top"></div>';

		echo '<div id="store_product_cart_body">';
		echo '<div id="store_product_cart_content">';

		$this->displayContent();

		echo '</div>';
		echo '</div>';

		echo '<div id="store_product_cart_bottom"></div>';
		echo '</div>';

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $translated = false;

		$javascript = '';

		if (!$translated) {
			if ($this->empty_message === null) {
				$h2_tag = new SwatHtmlTag('h2');
				$h2_tag->setContent(Store::_('Your Shopping Cart is Empty'));
				$empty_message = $h2_tag->__toString();
			} else {
				$empty_message = $this->empty_message;
			}

			$javascript.= sprintf("StoreCartLightBox.empty_message = %s;\n",
				SwatString::quoteJavaScriptString($empty_message));

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

		$javascript.= sprintf('var cart_lightbox = '.$this->class_name.
			".getInstance(%d, %d);\n",
			count($this->app->cart->checkout->getAvailableEntries()),
			count($this->app->cart->checkout->getAvailableEntries() +
				$this->app->cart->saved->getEntries()));

		if ($this->analytics === self::GOOGLE_ANALYTICS) {
			$javascript.= "cart_lightbox.analytics = 'google_analytics';\n";
		}

		if ($this->override_message !== null) {
			$javascript.= sprintf("cart_lightbox.override_message = %s;\n",
				SwatString::quoteJavaScriptString($this->override_message));
		}

		return $javascript;
	}

	// }}}

	// cart content display methods
	// {{{ public function displayContent()

	/**
	 * Get a mini cart for a specific product page
	 *
	 * @return string The mini cart.
	 */
	public function displayContent()
	{
		if ($this->app->cart->checkout->isEmpty()) {
			// TODO: also handle override messages like this
			echo $this->empty_message;
		} else {
			if ($this->processor !== null) {
				$added = count($this->processor->getEntriesAdded());
				if ($added > 0) {
					$locale = SwatI18NLocale::get($this->app->getLocale());
					$div_tag = new SwatHtmlTag('div');
					$div_tag->class = 'added-message';
					$div_tag->setContent(sprintf(Store::ngettext(
						'One item added', '%s items added', $added),
						$locale->formatNumber($added)));

					$div_tag->display();
				}
			}

			$this->cart_ui = new SwatUI();
			$this->cart_ui->loadFromXML($this->cart_ui_xml);
			$this->cart_ui->init();

			$cart_view = $this->cart_ui->getWidget('cart_view');
			$cart_view->model = $this->getCartTableStore();

			$this->cart_ui->getWidget('cart_title')->content =
				$this->getCartTitle();

			$cart_link = new SwatHtmlTag('a');
			$cart_link->href = 'cart';
			$cart_link->setContent(Store::_('View Cart'));
			$this->cart_ui->getWidget('cart_link')->content =
				$cart_link->__toString().' '.Store::_('or');

			$this->cart_ui->display();
		}
	}

	// }}}
	// {{{ protected function displayCartEntries()

	/**
	 * @return string The mini cart entries.
	 */
	protected function displayCartEntries()
	{
		$cart = $this->app->getCacheValue('mini-cart',
			$this->app->session->getSessionId());

		if ($cart === false) {
			$this->cart_ui = new SwatUI();
			$this->cart_ui->loadFromXML($this->cart_ui_xml);
			$this->cart_ui->init();

			$cart_view = $this->cart_ui->getWidget('cart_view');
			$cart_view->model = $this->getCartTableStore();

			$this->cart_ui->getWidget('cart_title')->content =
				$this->getCartTitle();

			$cart_link = new SwatHtmlTag('a');
			$cart_link->href = 'cart';
			$cart_link->setContent(Store::_('View Cart'));
			$this->cart_ui->getWidget('cart_link')->content =
				$cart_link->__toString().' '.Store::_('or');

			ob_start();
			$this->cart_ui->display();
			$cart = ob_get_clean();
			$this->app->addCacheValue($cart, 'mini-cart',
				$this->app->session->getSessionId());
		}

		echo $cart;
	}

	// }}}
	// {{{ protected function getCartTitle()

	protected function getCartTitle()
	{
		$locale = SwatI18NLocale::get($this->app->getLocale());

		$title = '';

		$item_count = count($this->app->cart->checkout->getAvailableEntries());
		if ($item_count > 0) {
			$items = sprintf('<span class="item-count"> (%s)</span>',
				sprintf(Store::ngettext('%s item', '%s items', $item_count),
				$locale->formatNumber($item_count)));
		} else {
			$items = '';
		}

		$h3_tag = new SwatHtmlTag('h3');
		$h3_tag->setContent(
			SwatString::minimizeEntities(Store::_('Shopping Cart')).$items,
			'text/xml');

		$title.= $h3_tag->__toString();
		return $title;
	}

	// }}}
	// {{{ protected function getAvailableEntries()

	/**
	 * Gets the cart entries
	 */
	protected function getAvailableEntries()
	{
		return $this->app->cart->checkout->getEntries();
	}

	// }}}
	// {{{ protected function getCartTableStore()

	/**
	 * Gets the cart data-store
	 */
	protected function getCartTableStore()
	{
		$store = new SwatTableStore();
		$show_group = false;

		$saved_count = 0;
		$entry_count = 0;

		$status_title = Store::_('Available For Purchase');
		foreach ($this->getAvailableEntries() as $entry) {
			$ds = $this->getCartDetailsStore($entry);
			$ds->status_title = $status_title;
			$ds->status_class = 'available';
			$store->add($ds);
			$entry_count++;
		}

		$count = (count($this->getAvailableEntries()) - $entry_count);

		if ($entry_count > 0 && $count > 0) {
			$ds = $this->getMoreRow($count);
			$ds->status_title = $status_title;
			$ds->status_class = 'available';
			$store->add($ds);
		}

		$status_title = Store::_('Saved For Later');
		foreach ($this->app->cart->saved->getEntries() as $entry) {
			$ds = $this->getCartDetailsStore($entry);
			$ds->status_title = $status_title;
			$ds->status_class = 'saved';
			$store->add($ds);
			$saved_count++;
		}

		$count = (count($this->app->cart->saved->getEntries()) - $saved_count);
		if ($saved_count > 0 && $count > 0) {
			$ds = $this->getMoreRow($count);
			$ds->status_title = $status_title;
			$ds->status_class = 'saved';
			$store->add($ds);
		}

		$this->cart_ui->getWidget('cart_view')->getGroup(
			'status_group')->visible = ($saved_count > 0);

		return $store;
	}

	// }}}
	// {{{ protected function getCartDetailsStore()

	protected function getCartDetailsStore(StoreCartEntry $entry)
	{
		$ds = new SwatDetailsStore($entry);

		$ds->quantity = $entry->getQuantity();
		$ds->description = $this->getEntryDescription($entry);
		$ds->price = $entry->getCalculatedItemPrice();
		$ds->extension = $entry->getExtension();
		$ds->discount = $entry->getDiscount();
		$ds->discount_extension = $entry->getDiscountExtension();
		$ds->show_remove_button = true;
		$ds->product_link = 'store/'.$entry->item->product->path;

		$image = $entry->item->product->primary_image;
		if ($image === null) {
			$ds->image        = null;
			$ds->image_width  = null;
			$ds->image_height = null;
		} else {
			$ds->image        = $image->getUri($this->getImageDimension());
			$ds->image_width  = $image->getWidth($this->getImageDimension());
			$ds->image_height = $image->getHeight($this->getImageDimension());
		}

		return $ds;
	}

	// }}}
	// {{{ protected function getMoreRow()

	/**
	 * Gets the cart data-store for the product on this page
	 */
	protected function getMoreRow($num_items)
	{
		$locale = SwatI18NLocale::get($this->app->getLocale());

		$ds = new SwatDetailsStore();
		$ds->id = 0;
		$ds->status_title = null;
		$ds->quantity = null;
		$ds->description = sprintf('<a class="more-link" href="cart">%s</a>',
			sprintf(Store::ngettext('and one other item',
					'and %s other items…', $num_items),
				$locale->formatNumber($num_items)));

		$ds->price = null;
		$ds->extension = null;
		$ds->discount = null;
		$ds->discount_extension = null;
		$ds->image        = null;
		$ds->image_width  = null;
		$ds->image_height = null;
		$ds->show_remove_button = false;
		return $ds;
	}

	// }}}
	// {{{ protected function getImageDimension()

	/**
	 * @return string Image dimension shortname
	 */
	protected function getImageDimension()
	{
		return 'pinky';
	}

	// }}}
	// {{{ protected function getEntryDescription()

	protected function getEntryDescription(StoreCartEntry $entry)
	{
		$description = '';
		$title = array();

		if ($entry->item->sku !== null) {
			$title[] = $entry->item->sku;
		}

		if ($entry->item->product->title !== null) {
			$title[] = $entry->item->product->title;
		}

		if (count($title) > 0) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = 'store/'.$entry->item->product->path;
			$a_tag->setContent(implode(' - ', $title));
			$description.= '<h4>'.$a_tag->__toString().'</h4>';
		}

		$description.= implode(', ', $this->getItemDescriptionArray($entry));

		return $description;
	}

	// }}}
	// {{{ protected function getItemDescriptionArray()

	protected function getItemDescriptionArray(StoreCartEntry $entry)
	{
		$description = array();

		foreach ($entry->item->getDescriptionArray() as $key => $element) {
			$description[$key] = SwatString::minimizeEntities($element);
		}

		$discount = $entry->getDiscountExtension();
		if ($discount > 0) {
			$locale = SwatI18NLocale::get($this->app->getLocale());

			$span = new SwatHtmlTag('span');
			$span->class = 'store-cart-discount';
			$span->setContent(sprintf(Store::_('You save %s'),
				$locale->formatCurrency($discount)));

			$description['discount'] = $span->__toString();
		}

		return $description;
	}

	// }}}
}

?>
