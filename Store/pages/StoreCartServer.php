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

	protected $processor;
	protected $cart_ui;
	protected $cart_ui_xml = 'Store/pages/product-cart.xml';

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
		$this->processor = StoreCartProcessor::get($this->app);

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

		foreach ($entries as $e) {
			$entry = $this->processor->createCartEntry(
				$e['item_id'], $e['quantity']);

			$entry->source_category = $source_category;
			$entry->source = StoreCartEntry::SOURCE_PRODUCT_PAGE;
			$this->setupCartEntry($entry, $e);

			$status = $this->processor->addEntryToCart($entry);

			if ($product_id === null) {
				$product_id = $entry->item->product->id;
			}
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

		if ($entry === null) {
			$entry = $this->app->cart->saved->removeEntryById($entry_id);
			if ($entry === null) {
				$product_id = null;
			} else {
				$this->app->cart->saved->save();
				$removed = true;
				$product_id = $entry->item->product->id;
			}
		} else {
			$this->app->cart->checkout->save();
			$removed = true;
			$product_id = $entry->item->product->id;
		}

		return $this->getCartInfo($product_id, false);
	}

	// }}}
	// {{{ public function getCartInfo()

	/**
	 * Get information about what's in the user's cart
	 *
	 * @param integer $product_id Optional product id to filter by 
	 * @param boolean $mini_cart Whether or not to return the mini-cart 
	 *
	 * @return array
	 */
	public function getCartInfo($product_id = null, $mini_cart = false)
	{
		$product_entries = 0;	// total number of cart-enties for the product
		$product_quantity = 0;	// sum of all quantities for the product
		$total_entries = 0;		// total number of cart-entries
		$total_quantity = 0;	// sum of all cart-entry quantites

		$currrent_product = null;

		foreach ($this->app->cart->checkout->getAvailableEntries() as $e) {
			$total_entries++;
			$total_quantity += $e->getQuantity();

			if ($e->item->getInternalValue('product') === $product_id) {
				$product_entries++;
				$product_quantity += $e->getQuantity();
			}
		}

		// only count saved entries for products - not for the main cart
		foreach ($this->app->cart->saved->getEntries() as $e) {
			if ($e->item->getInternalValue('product') === $product_id) {
				$product_entries++;
				$product_quantity += $e->getQuantity();
			}
		}

		$return = array();
		$return['product_entries']  = $product_entries;
		$return['product_quantity'] = $product_quantity;
		$return['total_entries']    = $total_entries;
		$return['total_quantity']   = $total_quantity;

		if ($product_id !== null) {
			$class_name =  SwatDBClassMap::get('StoreProduct');
			$product = new $class_name();
			$product->setDatabase($this->app->db);
			$product->load($product_id);

			$return['cart_message'] = (string)
				$this->processor->getProductCartMessage($product);
		}

		$return['cart_link'] = $this->getCartLink($return);

		if ($mini_cart) {
			$return['mini_cart'] = $this->getMiniCart($product_id);
		} else {
			$return['mini_cart'] = '';
		}

		return $return;
	}

	// }}}
	// {{{ protected function setupCartEntry()

	protected function setupCartEntry(StoreCartEntry $entry, array $e)
	{
		// Do custom entry manipulation here
	}

	// }}}
	// {{{ protected function getMiniCart()

	/**
	 * Get a mini cart for a specific product page
	 *
	 * @param integer $product_id Product id for the mini-cart entries.
	 *
	 * @return string The mini cart.
	 */
	protected function getMiniCart($product_id)
	{
		$this->cart_ui = new SwatUI();
		$this->cart_ui->loadFromXML($this->cart_ui_xml);
		$this->cart_ui->init();

		$cart_view = $this->cart_ui->getWidget('cart_view');
		$cart_view->model = $this->getCartTableStore($product_id);
		$count = count($cart_view->model);

		if ($count == 0) {
			$h2_tag = new SwatHtmlTag('h2');
			$h2_tag->setContent(Store::_('Your Cart is Empty'));
			$mini_cart = $h2_tag->__toString();
		} else {
			$this->cart_ui->getWidget('cart_title')->content =
				$this->getCartTitle();

			$cart_link = new SwatHtmlTag('a');
			$cart_link->href = 'cart';
			$cart_link->setContent(Store::_('View Cart'));
			$this->cart_ui->getWidget('cart_link')->content =
				$cart_link->__toString().' '.Store::_('or');

			ob_start();
			$this->cart_ui->display();
			$mini_cart = ob_get_clean();
		}

		return $mini_cart;
	}

	// }}}

	// mini cart
	// {{{ protected function getCartTitle()

	protected function getCartTitle()
	{
		$locale = SwatI18NLocale::get($this->app->getLocale());

		$title = '';
		$added = count($this->processor->getEntriesAdded());
		if ($added > 0) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'added-message';
			$div_tag->setContent(sprintf(Store::ngettext(
				'One item added', '%s items added', $added),
				$locale->formatNumber($added)));

			$title.= $div_tag->__toString();
		}

		$h3_tag = new SwatHtmlTag('h3');
		$h3_tag->setContent(Store::_('Shopping Cart'));
		$title.= $h3_tag->__toString();
		return $title;
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

		$saved_count = 0;
		$entry_count = 0;

		$status_title = Store::_('Available For Purchase');
		foreach ($this->app->cart->checkout->getEntries() as $entry) {
			if ($this->isOnThisPage($product_id, $entry->item)) {
				$ds = $this->getCartDetailsStore($entry);
				$ds->status_title = $status_title;
				$store->add($ds);
				$entry_count++;
			}
		}

		$count = (count($this->app->cart->checkout->getEntries())
			- $entry_count);

		if ($entry_count > 0 && $count > 0) {
			$ds = $this->getMoreRow($count);
			$ds->status_title = $status_title;
			$store->add($ds);
		}

		$status_title = Store::_('Saved For Later');
		foreach ($this->app->cart->saved->getEntries() as $entry) {
			if ($this->isOnThisPage($product_id, $entry->item)) {
				$ds = $this->getCartDetailsStore($entry);
				$ds->status_title = $status_title;
				$store->add($ds);
				$saved_count++;
			}
		}

		$count = (count($this->app->cart->saved->getEntries()) - $saved_count);
		if ($saved_count > 0 && $count > 0) {
			$ds = $this->getMoreRow($count);
			$ds->status_title = $status_title;
			$store->add($ds);
		}

		$this->cart_ui->getWidget('cart_view')->getGroup(
			'status_group')->visible = ($saved_count > 0 && $entry_count > 0);

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
		$description = sprintf('<h4>%s - %s</h4>%s',
			SwatString::minimizeEntities($entry->item->sku),
			SwatString::minimizeEntities($entry->item->getDescription(false)),
			implode(', ', $this->getItemDescriptionArray($entry)));

		return $description;
	}

	// }}}
	// {{{ protected function getItemDescriptionArray()

	protected function getItemDescriptionArray(StoreCartEntry $entry)
	{
		$description = array();

		foreach ($entry->item->getDescriptionArray() as $key => $element) {
			if ($key !== 'description') {
				$description[] = SwatString::minimizeEntities($element);
			}
		}

		return $description;
	}

	// }}}
	// {{{ protected function isOnThisPage()

	protected function isOnThisPage($product_id, StoreItem $item)
	{
		return ($product_id === $item->getInternalValue('product'));
	}

	// }}}
	// {{{ protected function getCartLink()

	protected function getCartLink(array $cart_info)
	{
		$locale = SwatI18NLocale::get($this->app->getLocale());

		if ($cart_info['total_entries'] == 0) {
			$link = sprintf('<span>%s</span>',
				Store::_('Shopping Cart'));
		} else {
			$link = sprintf('<span>%s</span> (%s)',
				Store::_('Shopping Cart'),
				sprintf(Store::ngettext('%s item', '%s items',
					$cart_info['total_entries']),
					$locale->formatNumber($cart_info['total_entries'])));
		}

		return $link;
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
