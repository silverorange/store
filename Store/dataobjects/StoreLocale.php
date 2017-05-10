<?php


/**
 * @package   Store
 * @copyright 2006-2016 silverorange
 */
class StoreLocale extends SwatDBDataObject
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
		$language = mb_substr($this->id, 0, 2);
		$country = mb_strtolower(mb_substr($this->id, 3, 2));
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
