<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A payment type data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StorePaymentType extends StoreDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var string 
	 */
	public $id;

	/**
	 * Non-visible string indentifier
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Whether this payment type is available
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * User visible name for this type
	 *
	 * @var string
	 */
	public $title;

	/**
	 * User visible note for this type
	 *
	 * @var string
	 */
	public $note;

	/**
	 * Relative order of display
	 *
	 * @var integer
	 */
	public $displayorder;

	/**
	 * Whether this type is a credit card
	 *
	 * @var boolean
	 */
	public $credit_card;

	/**
	 * Additional charge applied when using this type
	 *
	 * @var double
	 */
	public $surcharge;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PaymentType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
