<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 */
class StoreCatalog extends SwatDBDataObject
{
	// {{{ constants

	const STATUS_IN_SEASON = 0;
	const STATUS_OUT_OF_SEASON = 1;
	const STATUS_DISABLED = 2;

	// }}}
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * In season
	 *
	 * Whether the current catalog is in season. The property can be used to
	 * either hide the product, or control how it is displayed.
	 *
	 * @var boolean
	 */
	public $in_season;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('clone_of', 'Catalog');

		$this->table = 'Catalog';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
