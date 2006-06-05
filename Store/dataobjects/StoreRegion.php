<?php

require_once 'Store/dataobjects/StoreDataObject.php';

/**
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreRegion extends StoreDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The title of thie region
	 *
	 * This is something like "Canada" or "U.S.A.".
	 *
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Region';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
