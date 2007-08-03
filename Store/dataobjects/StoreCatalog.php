<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * @package   Store
 * @copyright 2005-2006 silverorange
 */
abstract class StoreCatalog extends SwatDBDataObject
{
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

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('clone_of', 'Catalog');

		$this->table = 'Catalog';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ abstract static public function getStatusTitle()

	abstract static public function getStatusTitle($status);

	// }}}

	// {{{ abstract static public function getStatuses()

	abstract static public function getStatuses();

	// }}}

	// {{{ abstract static public function getStatusConstant()

	abstract static public function getStatusConstant($status);

	// }}}
}

?>
