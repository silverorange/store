<?php

/**
 * StoreCart*View classes provide multiple views for StoreCart objects.
 * Each class implements the abstract class below.
 *
 * Some suggested classes to implement:
 *
 *  - StoreCartCheckoutView
 *  - StoreCartShortView
 *  - StoreCartReceiptView
 *  - StoreCartTextView
 *  - StoreCartNormalView
 *
 * NOTE: use protected methods when implementing views to make it easier to
 *       subclass.
 *
 * NOTE: use Swat widgets when possible
 *
 * TODO: Figure out how a StoreCart*View can be used for the receipt step of
 *       the checkout. Usually the cart is cleared from the session by the time
 *       the receipt is displayed.
 */
abstract class StoreCartView {

	/**
	 * The StoreCart object to view
	 *
	 * @var StoreCart
	 * @access private
	 */
	private var $cart;

	public function __construct($cart);

	/**
	 * Display this view
	 *
	 * @access public
	 */
	public abstract function display();

	/**
	 * Gets the StoreCart object that is being viewed
	 *
	 * @return StoreCart the object that this viewer is viewing.
	 *
	 * @access private
	 */
	public function getCart();

	/**
	 * Sets the Cart object to be viewed
	 *
	 * @param StoreCart $cart a reference to the cart to view.
	 *
	 * @access public
	 */
	public function setCart($cart);

}

?>
