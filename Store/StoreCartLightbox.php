<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'XML/RPCAjax.php';

/**
 * Control to display a lightbox driven cart on the page
 *
 * @package   Store
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartLightbox extends SwatControl
{
	// {{{ constants

	const GOOGLE_ANALYTICS = 1;

	// }}}
	// {{{ public properties

	/**
	 * Optional content to display when the cart is empty
	 *
	 * This is a string of XHTML.
	 *
	 * If not specified, the default content of an &lt;h3&gt; element with
	 * the text 'Your Shopping Cart is Empty' is used.
	 *
	 * @var string
	 */
	public $empty_content = null;

	/**
	 * Optional content that ovberrides the default content of this lightbox
	 *
	 * This is a string of XHTML.
	 *
	 * This is useful for pages where you just want to display a message
	 * instead of displaying the cart contents.
	 *
	 * @var string
	 */
	public $override_content = null;

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * @var boolean
	 */
	protected $is_cached = null;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null,
		SiteApplication $app,
		StoreCartProcessor $processor = null)
	{
		parent::__construct($id);

		$this->app = $app;
		$this->processor = $processor;

		$yui = new SwatYUI(array('dom', 'event', 'animation', 'selector'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());
		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$this->addJavascript('packages/store/javascript/store-cart-lightbox.js',
			Store::PACKAGE_ID);

		$this->addStyleSheet('packages/store/styles/store-cart-lightbox.css',
			Store::PACKAGE_ID);

		$this->addJavaScript('packages/swat/javascript/swat-z-index-manager.js',
			Swat::PACKAGE_ID);

		$h3 = new SwatHtmlTag('h3');
		$h3->setContent(Store::_('Your Shopping Cart is Empty'));
		$this->empty_content = strval($h3);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		if (!$this->isInitialized()) {
			$this->confirmCompositeWidgets();
			$this->initCartUI();
			parent::init();
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible) {
			return;
		}

		parent::display();

		echo '<div id="store_cart_lightbox" class="swat-hidden">';
		echo '<div id="store_cart_lightbox_top"></div>';

		echo '<div id="store_cart_lightbox_content">';

		$this->displayContent();

		echo '</div>';

		echo '<div id="store_cart_lightbox_bottom"></div>';
		echo '</div>';

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function clearCache()

	public function clearCache()
	{
		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs($this->app->session->getSessionId());
		}

		$this->initialized = false;
		$this->composite_widgets_created = false;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $translated = false;

		$javascript = '';

		if (!$translated) {
			$javascript.= sprintf("StoreCartLightbox.empty_content = %s;\n",
				SwatString::quoteJavaScriptString($this->empty_content));

			$javascript.= sprintf("StoreCartLightbox.loading_message = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('Loading…')));

			$javascript.= sprintf(
				"StoreCartLightbox.recalculating_message = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('Updating…')));

			$javascript.= sprintf("StoreCartLightbox.submit_message = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('Updating Cart…')));

			$javascript.= sprintf(
				"StoreCartLightbox.item_count_message_singular = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('(1 item)')));

			$javascript.= sprintf(
				"StoreCartLightbox.item_count_message_plural = %s;\n",
				SwatString::quoteJavaScriptString(Store::_('(%s items)')));

			$translated = true;
		}

		$available_entries = count(
			$this->app->cart->checkout->getAvailableEntries());

		$saved_entries = (isset($this->app->cart->saved)) ?
			count($this->app->cart->saved->getEntries()) : 0;

		$javascript.= sprintf(
			"var cart_lightbox = %s.getInstance(%s, %s);\n",
			$this->getJavaScriptClassName(),
			intval($available_entries),
			intval($available_entries + $saved_entries));

		return $javascript;
	}

	// }}}
	// {{{ protected function getJavaScriptClassName()

	protected function getJavaScriptClassName()
	{
		return 'StoreCartLightbox';
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/cart-lightbox.xml';
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$cart = $this->app->getCacheValue('mini-cart',
			$this->app->session->getSessionId());

		// if not cached, load the UI XML
		if ($cart === false) {
			$this->ui = new SwatUI();
			$this->ui->loadFromXml($this->getUiXml());
			$this->addCompositeWidget($this->ui->getRoot(), 'ui');
		}
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
		$this->confirmCompositeWidgets();

		if (!$this->isInitialized()) {
			$this->init();
		}

		$cart = $this->app->cart;

		if ($this->override_content !== null) {
			echo $this->override_content;
		} elseif (count($cart->checkout->getAvailableEntries()) === 0 &&
			(!isset($cart->saved) || $cart->saved->isEmpty())) {

			echo '<div class="empty-content">'.
				$this->empty_content.'</div>';
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

			$this->displayCartEntries();
		}
	}

	// }}}
	// {{{ public function getAvailableHtmlHeadEntrySet()

	public function getAvailableHtmlHeadEntrySet()
	{
		$set = $this->app->getCacheValue(
			'mini-cart-resources',
			$this->app->session->getSessionId()
		);

		if ($set !== false) {
			$set = unserialize($set);
		}

		if ($set === false ) {
			// get all available HTML head entries, not just the
			// ones we need right now. We get all available because a new
			// cart view can be loaded via AJAX that requires more resources
			// than the initial view.
			$set = parent::getAvailableHtmlHeadEntrySet();
			$this->app->addCacheValue(
				serialize($set),
				'mini-cart-resources',
				$this->app->session->getSessionId()
			);
		}

		return $set;
	}

	// }}}
	// {{{ protected function displayCartEntries()

	/**
	 * @return string The mini cart entries.
	 */
	protected function displayCartEntries()
	{
		$cart = $this->app->getCacheValue(
			'mini-cart',
			$this->app->session->getSessionId()
		);

		if ($cart === false) {
			$this->buildCartUI();

			ob_start();
			$this->ui->display();
			$cart = ob_get_clean();

			$this->app->addCacheValue(
				$cart,
				'mini-cart',
				$this->app->session->getSessionId()
			);
		}

		echo $cart;
	}

	// }}}
	// {{{ protected function initCartUI()

	protected function initCartUI()
	{
	}

	// }}}
	// {{{ protected function buildCartUI()

	protected function buildCartUI()
	{
		$message_display = $this->ui->getWidget('lightbox_message_display');
		foreach ($this->getCartMessages() as $message) {
			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);
		}

		$cart_view = $this->ui->getWidget('lightbox_cart_view');
		$cart_view->model = $this->getCartTableStore();

		if ($this->ui->hasWidget('lightbox_cart_totals_view')) {
			$view = $this->ui->getWidget('lightbox_cart_totals_view');
			// these two properties are needed to make the table view display
			// without any real rows
			$view->model = new SwatTableStore();
			$view->no_records_message = null;
			if ($view->hasRow('lightbox_subtotal')) {
				$view->getRow('lightbox_subtotal')->value =
					$this->app->cart->checkout->getSubtotal();
			}

			if ($view->hasRow('lightbox_shipping')) {
				$class_name = SwatDBClassMap::get('StoreOrderAddress');
				$view->getRow('lightbox_shipping')->value =
					$this->app->cart->checkout->getShippingTotal(
						new $class_name(), new $class_name);
			}
		}

		$this->ui->getWidget('lightbox_cart_title')->content =
			$this->getCartTitle();
	}

	// }}}
	// {{{ protected function getCartMessages()

	protected function getCartMessages()
	{
		return array();
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
		return $this->app->cart->checkout->getAvailableEntries();
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

		if (isset($this->app->cart->saved)) {
			$status_title = Store::_('Saved For Later');
			foreach ($this->app->cart->saved->getEntries() as $entry) {
				$ds = $this->getCartDetailsStore($entry);
				$ds->status_title = $status_title;
				$ds->status_class = 'saved';
				$store->add($ds);
				$saved_count++;
			}

			$count = (count($this->app->cart->saved->getEntries())
				- $saved_count);
		}

		if ($saved_count > 0 && $count > 0) {
			$ds = $this->getMoreRow($count);
			$ds->status_title = $status_title;
			$ds->status_class = 'saved';
			$store->add($ds);
		}

		$this->ui->getWidget('lightbox_cart_view')->getGroup(
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
		$ds->product_link = $this->getProductLink($entry);

		$dimension = $this->getImageDimension();
		$image = $entry->item->product->primary_image;

		if ($image !== null && $image->hasDimension($dimension)) {
			$ds->image        = $image->getUri($dimension);
			$ds->image_width  = $image->getWidth($dimension);
			$ds->image_height = $image->getHeight($dimension);
		} else {
			$ds->image        = null;
			$ds->image_width  = null;
			$ds->image_height = null;
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
		$ds->description = sprintf(
			'<a class="more-link" href="cart?link=cart-lightbox-more">%s</a>',
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
			$a_tag->href = $this->getProductLink($entry);
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
	// {{{ protected function getProductLink()

	protected function getProductLink(StoreCartEntry $entry)
	{
		return sprintf('%s%s?link=cart-lightbox',
			$this->app->config->store->path,
			$entry->item->product->path);
	}

	// }}}
}

?>
