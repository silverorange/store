<?php

require_once 'Store/dataobjects/StoreAddress.php';

/**
 *
 * @package   Store
 * @copyright 2005-2006 silverorane
 */
class StoreAccountAddress extends StoreAddress
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'AccountAddress';

		$this->registerInternalProperty('account',
			$this->class_map->resolveClass('StoreAccount'));
	}

	// }}}
}

?>
