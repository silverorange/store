<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreItem.php';
require_once 'Store/StoreCartProcessor.php';
require_once 'Store/StoreCartLightbox.php';

/**
 * Handles XML-RPC requests to update the cart
 *
 * @package   Store
 * @copyright 2010-2012 silverorange
 */
class StoreCartServer extends SiteXMLRPCServer
{
	// {{{ protected properties

	protected $processor;

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
	 * @param integer $request_id A unique id for this request
	 * @param array $entries The cart entries to add to the cart. Entries are
	 *                       objects with values for 'item_id' and 'quantity'.
	 * @param integer $source The source of the added product. See
	 *                        StoreCartEntry::SOURCE_* constants.
	 * @param integer $source_category The category id from which the product
	 *                       came from.
	 * @param boolean $mini_cart Whether or not to return the XHTML for
	 *                       displaying a mini-cart on the page.
	 *
	 * @return array An array containing: 'mini_cart', 'product_items',
	 *               'total_items', 'total_products'
	 */
	public function addEntries($request_id, $entries, $source = null,
		$source_category = null, $mini_cart = false)
	{
		$product_id = null;

		$added_entries = array();
		$saved_entries = array();
		$other_entries = array();

		foreach ($entries as $e) {
			$entry = $this->processor->createCartEntry(
				$e['item_id'], $e['quantity']);

			if ($source != 0) {
				$entry->source = $source;
			}

			if ($source_category != 0) {
				$entry->source_category = $source_category;
			}

			$this->setupCartEntry($entry, $e);

			$this->processor->addEntryToCart($entry);

			$added = $this->processor->getEntriesAdded();
			foreach ($added as $result) {
				if ($result['status'] === StoreCartProcessor::ENTRY_ADDED) {
					$added_entries[] = array(
						'item_id' => $result['entry']->getItemId(),
						'sku' => $result['entry']->getItemSku(),
					);
				} elseif ($result['status'] === StoreCartProcessor::ENTRY_SAVED) {
					$saved_entries[] = array(
						'item_id' => $result['entry']->getItemId(),
						'sku' => $result['entry']->getItemSku(),
					);
				} else {
					$other_entries[] = array(
						'item_id' => $result['entry']->getItemId(),
						'sku' => $result['entry']->getItemSku(),
					);
				}
			}

			if ($product_id === null) {
				$product_id = $entry->item->product->id;
			}
		}

		$this->app->cart->save();

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs($this->app->session->getSessionId());
		}

		$response = $this->getCartInfo($request_id, $product_id, $mini_cart);

		$response['added_entries'] = $added_entries;
		$response['saved_entries'] = $saved_entries;
		$response['other_entries'] = $other_entries;

		return $response;
	}

	// }}}
	// {{{ public function removeEntry()

	/**
	 * Remove an entry from the cart
	 *
	 * @param integer $request_id A unique id for this request
	 * @param integer $entry_id The id of the entry to be removed
	 * @param integer $product_id The id of the product if on a
	 *                            product page. Note that this product id is
	 *                            not necesarily the same as the product for
	 *                            the entry being removed
	 *
	 * @return array
	 */
	public function removeEntry($request_id, $entry_id, $product_id = null)
	{
		$entry = $this->app->cart->checkout->removeEntryById($entry_id);

		if ($entry === null && isset($this->app->cart->saved)) {
			$entry = $this->app->cart->saved->removeEntryById($entry_id);
		}

		$this->app->cart->save();
		$response = $this->getCartInfo($request_id, $product_id, true);

		if ($entry instanceof StoreCartEntry) {
			$response['removed_sku'] = $entry->getItemSku();
			$response['removed_item'] = $entry->getItemId();
		}

		return $response;
	}

	// }}}
	// {{{ public function getCartInfo()

	/**
	 * Get information about what's in the user's cart
	 *
	 * @param integer $request_id A unique id for this request
	 * @param integer $product_id Optional product id to filter by
	 * @param boolean $get_mini_cart Whether or not to return the mini-cart
	 *
	 * @return array
	 */
	public function getCartInfo($request_id, $product_id = null,
		$get_mini_cart = false)
	{
		$product_entries  = 0; // total number of cart-enties for the product
		$product_quantity = 0; // sum of all quantities for the product
		$total_entries    = 0; // total number of cart-entries
		$total_quantity   = 0; // sum of all cart-entry quantites
		$total_saved      = 0;

		foreach ($this->app->cart->checkout->getAvailableEntries() as $e) {
			$total_entries++;
			$total_quantity += $e->getQuantity();

			if ($e->item->getInternalValue('product') === $product_id) {
				$product_entries++;
				$product_quantity += $e->getQuantity();
			}
		}

		if (isset($this->app->cart->saved)) {
			// only count saved entries for products - not for the main cart
			foreach ($this->app->cart->saved->getEntries() as $e) {
				if ($e->item->getInternalValue('product') === $product_id) {
					$product_entries++;
					$product_quantity += $e->getQuantity();
				}

				$total_saved++;
			}
		}

		$return = array();
		$return['request_id']       = $request_id;
		$return['product_entries']  = $product_entries;
		$return['product_quantity'] = $product_quantity;
		$return['total_entries']    = $total_entries;
		$return['total_quantity']   = $total_quantity;
		$return['total_saved']      = $total_saved;
		$return['head_entries']     = $total_saved;

		if ($product_id !== null) {
			$class_name =  SwatDBClassMap::get('StoreProduct');
			$product = new $class_name();
			$product->setDatabase($this->app->db);
			$product->load($product_id);

			$return['cart_message'] = (string)
				$this->processor->getProductCartMessage($product);
		}

		$return['cart_link'] = $this->getCartLink($return);

		if ($get_mini_cart) {
			$mini_cart = $this->getMiniCart();
			ob_start();
			$mini_cart->displayContent();

			// Since the mini cart cache is rebuilt, the UI will not be re-
			// loaded on the next page load. We need to cache the HTML head
			// entries here because without the UI we will get an empty set.
			// Just getting the set here should cause it to be cached properly.
			$mini_cart->getAvailableHtmlHeadEntrySet();

			$return['mini_cart'] = ob_get_clean();
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
	 * Get a mini cart to display
	 *
	 * @return StoreCartLightbox The mini cart.
	 */
	protected function getMiniCart()
	{
		return new StoreCartLightbox(null, $this->app, $this->processor);
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
}

?>
