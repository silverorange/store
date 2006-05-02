<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A country data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreCountry extends SwatDBDataObject
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
