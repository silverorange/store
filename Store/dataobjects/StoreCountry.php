<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A country data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCountry extends StoreDataObject
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
		$this->table = 'Country';
		$this->id_field = 'text:id';
	}

	// }}}
}

?>
