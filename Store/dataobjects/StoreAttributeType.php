<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An attribute type data object
 *
 * @package   Store
 * @copyright 2008 silverorange
 */
class StoreAttributeType extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Shortname
	 *
	 * @var string
	 */
	public $shortname;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AttributeType';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
