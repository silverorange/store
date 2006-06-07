<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountPaymentMethod extends StorePaymentMethod
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'AccountPaymentMethod';
		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));
	}

	// }}}
}

?>
