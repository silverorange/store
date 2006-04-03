<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 *
 *
 * @package   veseys2
 * @copyright silverorange 2006
 */
class Country extends SwatDBDataObject
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

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'countries';
		$this->id_field = 'text:id';
	}

	// }}}
}

?>
