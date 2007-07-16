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
			SwatDBClassMap::get('StoreRegion'));

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
	// {{{ public function getTitle()
	
	/**
	 * Get a title that can be displayed for this locale
	 *
	 * This method should be over-ridden on a per-site basis. By default,
	 * it simply displays the locale id.
	 *
	 * @return string the title of the locale.
	 */
	public function getTitle()
	{
		return $this->id;
	}

	// }}}
}

?>
