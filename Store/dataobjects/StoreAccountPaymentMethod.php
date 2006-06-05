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
		$this->table = 'AccountPaymentMethod';

		$this->registerInternalField('account',
			$this->class_map->resolveClass('StoreAccount'));
	}

	// }}}
}

?>
