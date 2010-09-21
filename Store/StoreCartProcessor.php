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

	// }}}
	// {{{ public static properties

	public static $class_name = 'StoreCartProcessor';

	// }}}
	// {{{ public function get()

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

		return $status;
	}

	// }}}
}

?>
