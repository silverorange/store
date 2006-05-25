<?php

require_once 'Store/StoreCartModule.php';

/** 
 * A saved-cart object
 *
 * The saved cart is a cart object that is saved for later. Saved carts are not
 * intended for purchase. Saved carts do not have price totalling methods. This
 * This class contains saved-cart functionality common to all sites. It is
 * intended to be extended on a per-site basis.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCartModule
 */
abstract class StoreSavedCartModule extends StoreCartModule
{
}

?>
