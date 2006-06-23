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
	 * Unique identifier of this province or state 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title of this province or state 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Whether or not provincial sales tax applies in this province or state
	 *
	 * @var boolean
	 */
	public $pst;

	/**
	 * Whether or not harmonized sales tax applies in this province or state
	 *
	 * @var boolean
	 */
	public $hst;

	/**
	 * Whether or not general sales tax applies in this province or state
	 *
	 * @var boolean
	 */
	public $gst;

	/**
	 * A two letter abbreviation used to identify this province of state 
	 *
	 * This is also used for displaying addresses.
	 *
	 * @var string
	 */
	public $abbreviation;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'ProvState';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('country',
			$this->class_map->resolveClass('StoreCountry'));
	}

	// }}}
}

?>
