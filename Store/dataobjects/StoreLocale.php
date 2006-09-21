<?php

require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreRegion.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreLocale extends StoreDataObject
{
	// {{{ public properties

	/**
	 * not null,
	 *
	 * @var string
	 */
	public $id;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('region',
			$this->class_map->resolveClass('StoreRegion'));

		$this->table = 'Locale';
		$this->id_field = 'text:id';
	}

	// }}}
	// {{{ public function getURLLocale()
	
	/**
	 * Get locale formatted for the URL
	 *
	 * @return string the locale.
	 */
	public function getURLLocale()
	{
		$language = substr($this->id, 0, 2);
		$country = strtolower(substr($this->id, 3, 2));
		return $country.'/'.$language.'/';
	}

	// }}}
}

?>
