<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/StoreCartProcessor.php';

/**
 * Handles XML-RPC requests to update the cart
 *
 * @package   Store
 * @copyright 2010 silverorange
 */
class StoreCartServer extends SiteXMLRPCServer
{
	// {{{ protected properties

	protected $cart_ui;
	protected $cart_ui_xml = 'Store/pages/product-cart.xml';
	protected $entries_added = array();
	protected $entries_saved = array();

	// }}}

	// init phase
	// {{{ public function init()

	/**
	 * Load the cart.
	 *
	 * @xmlrpc.hidden
	 */
	public function init()
	{
		parent::init();
		$this->app->cart->load();
	}

	// }}}

	// xml-rpc methods
	// {{{ public function addEntries()

	/**
	 * Adds entries to the cart
	 *
	 * @param array $entries The cart entries to add to the cart. Entries are
	 *                       objects with values for 'item_id' and 'quantity'.
	 * @param integer $source_category The category id from which the product
	 *                       came from.
	 * @param boolean $mini_cart Whether or not to return the XHTML for
	 *                       displaying a mini-cart on the page.
	 *
	 * @return array An array containing: 'mini_cart', 'product_items',
	 *               'total_items', 'total_products'
	 */
	public function addEntries($entries, $source_category = null,
		$mini_cart = false)
	{
		$product_id = null;
		$class_name = StoreCartProcessor::$class_name;
		$processor = new $class_name($this->app);

		foreach ($entries as $e) {
			$entry = $processor->createCartEntry($e['item_id'], $e['quantity']);
			$entry->source_category = $source_category;
			$entry->source = StoreCartEntry::SOURCE_PRODUCT_PAGE;
			$this->setupCartEntry($entry, $e);

			$status = $processor->addEntryToCart($entry);

			if ($product_id === null) {
				$product_id = $entry->item->product->id;
			}

			if ($status == StoreCartProcessor::ENTRY_ADDED)
				$this->entries_added[] = $entry;
			elseif ($status == StoreCartProcessor::ENTRY_SAVED)
				$this->entries_saved[] = $entry;
		}

		return $this->getCartInfo($product_id, $mini_cart);
	}

	// }}}
	// {{{ public function removeEntry()

	/**
	 * Remove an entry from the cart
	 *
	 * @param integer $entry_id The id of the entry to be removed
	 *
	 * @return array
	 */
	public function removeEntry($entry_id)
	{
		$removed = false;

		$entry = $this->app->cart->checkout->removeEntryById($entry_id);

		if ($entry !== null) {
			$this->app->cart->checkout->save();
			$removed = true;
			$product_id = $entry->item->product->id;
		} else {
			$product_id = null;
		}

		return $this->getCartInfo($product_id, false);
	}

	// }}}
	// {{{ public function getMiniCart()

	/**
	 * Get a mini cart for a specific product page
	 *
	 * @param integer $product_id Product id for the mini-cart entries.
	 *
	 * @return string The mini cart.
	 */
	public function getMiniCart($product_id)
	{
		$this->cart_ui = new SwatUI();
		$this->cart_ui->loadFromXML($this->cart_ui_xml);
		$this->cart_ui->init();

		$cart_view = $this->cart_ui->getWidget('cart_view');
		$cart_view->model = $this->getCartTableStore($product_id);
		$count = count($cart_view->model);

		ob_start();

		if ($count == 0) {
			$h2_tag = new SwatHtmlTag('h2');
			$h2_tag->setContent(Store::_('Your Cart is Empty'));
			$h2_tag->display();
		} else {
			$this->checkCartDescription($cart_view);

			if (count($this->entries_added) > 0) {
				$locale = SwatI18NLocale::get($this->app->getLocale());

				$h3_tag = new SwatHtmlTag('h3');
				$h3_tag->class = 'cart-added';
				$h3_tag->setContent(sprintf(Store::ngettext(
					'One item has been added to your cart.',
					'%s items have been added to your cart.',
					count($this->entries_added)),
					$locale->formatNumber(count($this->entries_added))));

				$h3_tag->display();
			}

			$h2_tag = new SwatHtmlTag('h2');
			$h2_tag->setContent(Store::ngettext(
				'The following item on this page is in your cart:',
				'The following items on this page are in your cart:',
				$count));

			$h2_tag->display();
			$this->cart_ui->display();

			echo '<div class="cart-message-links">';
			$this->displayCartLinks();
			echo '</div>';
		}

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function setupCartEntry()

	protected function setupCartEntry(StoreCartEntry $entry, array $e)
	{
		// Do custom entry manipulation here
	}

	// }}}
	// {{{ protected function getCartInfo()

	protected function getCartInfo($product_id = null, $mini_cart = false)
	{
		$product_items = 0;
		$total_items = 0;
		$total_products = 0;
		$currrent_product = null;

		foreach ($this->app->cart->checkout->getAvailableEntries() as $e) {
			$total_items++;
			if ($e->item->getInternalValue('product') !== $currrent_product) {
				$currrent_product = $e->item->getInternalValue('product');
				$total_products++;
			}

			if ($e->item->getInternalValue('product') === $product_id) {
				$product_items++;
			}
		}

		$return = array();
		$return['product_items'] = $product_items;
		$return['total_items'] = $total_items;
		$return['total_products'] = $total_products;

		if ($mini_cart) {
			$return['mini_cart'] = $this->getMiniCart($product_id);
		} else {
			$return['mini_cart'] = '';
		}

		return $return;
	}

	// }}}

	// mini cart
	// {{{ protected function checkCartDescription()

	protected function checkCartDescription($cart_view)
	{
		/* if the view has a description column, check all columns to make sure
		 * they have a description. If none have a description, hide the column.
		 * if some are empty, but others have description, use the product title
		 * for the description instead of an empty column.
		 */
		if ($cart_view->hasColumn('description')) {
			$description_column = $cart_view->getColumn('description');
			$has_description = false;
			foreach($cart_view->model as $ds) {
				if ($ds->description == '') {
					$ds->description = $ds->item->product->title;
				} else {
					$has_description = true;
				}
			}

			$description_column->visible = $has_description;
		}
	}

	// }}}
	// {{{ protected function displayCartLinks()

	protected function displayCartLinks()
	{
		printf(Store::_(
			'%sContinue shopping%s, %sview your shopping cart%s, '.
				'or %sproceed to the checkout%s.'),
			'<a class="store-close-cart" href="store">', '</a>',
			'<a href="cart">', '</a>',
			'<a href="checkout">', '</a>');
	}

	// }}}
	// {{{ protected function getCartTableStore()

	/**
	 * Gets the cart data-store for the product on this page
	 */
	protected function getCartTableStore($product_id)
	{
		$store = new SwatTableStore();
		$show_group = false;

		foreach ($this->app->cart->saved->getEntries() as $entry) {
			if ($this->isOnThisPage($product_id, $entry->item)) {
				$ds = $this->getCartDetailsStore($entry);
				$ds->status_title = Store::_('Saved For Later');
				$store->add($ds);
			}
		}

		foreach ($this->app->cart->checkout->getEntries() as $entry) {
			if ($this->isOnThisPage($product_id, $entry->item)) {
				$ds = $this->getCartDetailsStore($entry);
				$ds->status_title = Store::_('Available For Purchase');
				$store->add($ds);
				$show_group = true;
			}
		}

		$this->cart_ui->getWidget('cart_view')->getGroup(
			'status_group')->visible = false;

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

		return $ds;
	}

	// }}}
	// {{{ protected function getEntryDescription()

	protected function getEntryDescription(StoreCartEntry $entry)
	{
		$description = array();
		foreach ($entry->item->getDescriptionArray() as $element)
			$description[] = SwatString::minimizeEntities($element);

		return implode(' - ', $description);
	}

	// }}}
	// {{{ protected function isOnThisPage()

	protected function isOnThisPage($product_id, StoreItem $item)
	{
		return ($product_id === $item->getInternalValue('product'));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	/**
	 * Save the cart
	 *
	 * @xmlrpc.hidden
	 */
	public function finalize()
	{
		$this->app->cart->save();
	}

	// }}}
}

?>
