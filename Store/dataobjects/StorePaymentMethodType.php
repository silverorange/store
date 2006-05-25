<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A payment method data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentMethodType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var string 
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PaymentMethod';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
