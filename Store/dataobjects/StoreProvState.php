<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreCountry.php';

/**
 * A province/state data object
 *
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProvState extends SwatDBDataObject
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
	public $abbrev;

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
		$this->table = 'provstates';
		$this->id_field = 'integer:id';

		$this->registerInternalField('country', 'StoreCountry');
	}

	// }}}
}

?>
