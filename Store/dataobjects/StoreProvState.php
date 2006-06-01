<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';

/**
 * A province/state data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProvState extends StoreDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $pst;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $hst;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $gst;

	/**
	 * 
	 *
	 * @var string
	 */
	public $abbreviation;

	/**
	 * 
	 *
	 * @var string
	 */
	public $location;

	/**
	 * 
	 *
	 * @var string
	 */
	public $pcode;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ProvState';
		$this->id_field = 'integer:id';

		$this->registerInternalField('country',
			$this->class_map->resolveClass('StoreCountry'));
	}

	// }}}
}

?>
