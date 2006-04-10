<?php

require_once 'Swat/SwatControl.php';
require_once 'Store/StoreCartModule.php';

/**
 * A viewer for shopping carts for an e-commerce web application
 *
 * StoreCart*View classes provide multiple views for StoreCart objects.
 * Each cart viewer class implements this abstract class.
 *
 * Some suggested classes to implement are:
 *
 * - StoreCartCheckoutView
 * - StoreCartShortView
 * - StoreCartReceiptView
 * - StoreCartTextView
 * - StoreCartNormalView
 *
 * When implementing views, use protected methods to make it easier to subclass
 * views. Also, make good use of Swat widgets where possible.
 *
 * TODO: Figure out how a StoreCart*View can be used for the receipt step of
 *       the checkout. Usually the cart is cleared from the session by the time
 *       the receipt is displayed.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCartView extends SwatControl
{
	/**
	 * The StoreCart object to view
	 *
	 * @var StoreCart
	 */
	protected $cart;

	/**
	 * Gets the StoreCart object that is being viewed
	 *
	 * @return StoreCart the object that this viewer is viewing.
	 */
	public function getCart()
	{
		return $this->cart;
	}

	/**
	 * Sets the Cart object to be viewed
	 *
	 * @param StoreCart $cart a reference to the cart to view.
	 */
	public function setCart(StoreCartModule $cart)
	{
		$this->cart = $cart;
	}
}

?>
