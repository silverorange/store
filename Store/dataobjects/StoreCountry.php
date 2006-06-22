<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A country data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCountry extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier of this country 
	 *
	 * @var string 
	 */
	public $id;

	/**
	 * User visible title of this country 
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
