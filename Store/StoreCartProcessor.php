<?php

require_once 'Swat/SwatObject.php';
require_once 'Store/dataobjects/StoreCartEntry.php';
require_once 'Store/dataobjects/StoreItem.php';

/**
 * General processor class for adding items to the cart.
 *
 * $processor = new StoreCartProcessor($app);
 * $entry = $processor->createCartEntry(123, 1);
 * $entry->source_category = $category;
 * $entry->custom_price = 123.45; // and any other custom entry modifications
 * $processor->addToCart($entry);
 *
 * @package   Store
 * @copyright 2010 silverorange
 */
class StoreCartProcessor extends SwatObject
{
	// {{{ class constants

	const ENTRY_ADDED = 1;
	const ENTRY_SAVED = 2;

	// }}}
	// {{{ protected properties

	protected $app;
	protected $entries_added = array();

	// }}}
	// {{{ public static properties

	public static $class_name = 'StoreCartProcessor';

	// }}}
	// {{{ public static function get()

	public static function get(SiteApplication $app)
	{
		$class_name = self::$class_name;
		return new $class_name($app);
	}

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
		//$this->app->cart->load();
	}

	// }}}
	// {{{ public function createCartEntry()

	public function createCartEntry($item_id, $quantity = 1)
	{
		$class_name = SwatDBClassMap::get('StoreCartEntry');
		$entry = new $class_name();
		$entry->setDatabase($this->app->db);

		$class_name = SwatDBClassMap::get('StoreItem');
		$item = new $class_name();
		$item->setDatabase($this->app->db);
		$item->setRegion($this->app->getRegion());
		if ($item->load($item_id) === false) {
			throw new StoreException('Item id "'.$item_id.'" not found.');
		}

		$entry->item = $item;
		$entry->setQuantity($quantity);

		return $entry;
	}

	// }}}
	// {{{ public function addEntryToCart()

	/**
	 * Add an entry to the cart
	 */
	public function addEntryToCart(StoreCartEntry $entry)
	{
		$this->app->session->activate();

		if ($this->app->session->isLoggedIn()) {
			$entry->account = $this->app->session->getAccountId();
		} else {
			$entry->sessionid = $this->app->session->getSessionId();
		}

		$status = null;

		if ($entry->item->hasAvailableStatus()) {
			$entry->item = $entry->item->id;
			if ($this->app->cart->checkout->addEntry($entry) !== null)
				$status = self::ENTRY_ADDED;
		} else {
			if ($this->app->cart->saved->addEntry($entry) !== null)
				$status = self::ENTRY_SAVED;
		}

		$this->entries_added[] = array('entry' => $entry, 'status' => $status);

		return $status;
	}

	// }}}
	// {{{ public function getEntriesAdded()

	public function getEntriesAdded()
	{
		return $this->entries_added;
	}

	// }}}
	// {{{ public function getUpdatedCartMessage()

	public function getUpdatedCartMessage()
	{
		if (count($this->entries_added) == 0)
			return null;

		$cart_message = new SwatMessage(
			Store::_('Your cart has been updated.'), 'cart');

		$locale = SwatI18NLocale::get($this->app->getLocale());
		$count = count($this->entries_added);
		$cart_message->secondary_content = sprintf(Store::ngettext(
			'One item added', '%s items added', $count),
			$locale->formatNumber($count));

		return $cart_message;
	}

	// }}}
	// {{{ public function getProductCartMessage()

	public function getProductCartMessage(StoreProduct $product)
	{
		$added = 0;
		foreach ($this->app->cart->checkout->getAvailableEntries() as $entry) {
			if ($entry->item->product->id == $product->id) {
				$added++;
			}
		}

		$saved = 0;
		foreach ($this->app->cart->saved->getEntries() as $entry) {
			if ($entry->item->product->id == $product->id) {
				$saved++;
			}
		}

		if ($added == 0 && $saved == 0) {
			$message = null;
		} else {
			ob_start();
			$locale = SwatI18NLocale::get($this->app->getLocale());

			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'product-page-cart-message';
			$div_tag->open();

			if ($added > 0 && $saved > 0) {
				echo SwatString::minimizeEntities(sprintf(Store::_(
					'You have %s from this page in your cart and '.
					'%s saved for later.'),
					sprintf(Store::ngettext('one item', '%s items',
						$added),
						$locale->formatNumber($added)),
					sprintf(Store::ngettext('one item', '%s items',
						$saved),
						$locale->formatNumber($saved))));

			} elseif ($saved > 0) {
				echo SwatString::minimizeEntities(sprintf(Store::ngettext(
					'You have one item from this page saved for later.',
					'You have %s items from this page saved for later.',
					$saved),
					$locale->formatNumber($saved)));
			} else {
				echo SwatString::minimizeEntities(sprintf(Store::ngettext(
					'You have one item from this page in your cart.',
					'You have %s items from this page in your cart.',
					$added),
					$locale->formatNumber($added)));
			}

			echo ' ';

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = 'cart';
			$a_tag->class = 'product-page-cart-link';
			$a_tag->setContent(Store::_('View Details'));
			$a_tag->display();

			echo '.';

			$div_tag->close();
			$message = ob_get_clean();
		}

		return $message;
	}

	// }}}
}

?>
