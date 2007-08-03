<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * A payment method for an account for an e-commerce web application 
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethod
 */
class StoreAccountPaymentMethod extends StorePaymentMethod
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'AccountPaymentMethod';
		$this->registerInternalProperty('account',
			SwatDBClassMap::get('StoreAccount'));
	}

	// }}}
}

?>
