<?php

require_once 'Store/dataobjects/StoreAddress.php';

/**
 *
 * @package   Store
 * @copyright 2005-2006 silverorane
 */
class StoreOrderAddress extends StoreAddress
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderAddress';
	}

	// }}}
}

?>
