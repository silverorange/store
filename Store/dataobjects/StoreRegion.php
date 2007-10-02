<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreCountryWrapper.php';
require_once 'Store/dataobjects/StoreLocaleWrapper.php';

/**
 * Regions are areas in which products may be sold. Each region may have
 * region-specific pricing and shipping rules. Sometimes regionscorrespond
 * directly with countries and other times, regions are more general. Examples
 * of regions are:
 *
 * - Canada
 * - Quebec
 * - U.S.A.
 * - Europe
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreRegion extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The title of thie region
	 *
	 * This is something like "Canada", "U.S.A." or "Europe".
	 *
	 * @var string
	 */
	public $title;

	// }}}
	// {{{ public function getFirstLocale()

	/**
	 * Gets the first locale of this region
	 *
	 * @return Locale the first locale.
	 */
	public function getFirstLocale()
	{
		return $this->locales->getFirst();
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Region';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadLocales()

	protected function loadLocales()
	{
		$sql = 'select * from Locale where region = %s';
		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'StoreLocaleWrapper');
	}

	// }}}
	// {{{ protected function loadBillingCountries()

	/**
	 * Gets countries that orders may be billed to in this region
	 *
	 * @return StoreCountryWrapper a recordset of StoreCountry objects that
	 *                              orders may be billed to.
	 */
	protected function loadBillingCountries()
	{
		$this->checkDB();

		$sql = 'select id, title from Country
			inner join RegionBillingCountryBinding on
				Country.id = RegionBillingCountryBinding.country and
					RegionBillingCountryBinding.region = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'StoreCountryWrapper');
	}

	// }}}
	// {{{ protected function loadShippingCountries()

	/**
	 * Gets countries that orders may be shipped to in this region
	 *
	 * @return StoreCountryWrapper a recordset of StoreCountry objects that
	 *                              orders may be shipped to.
	 */
	protected function loadShippingCountries()
	{
		$this->checkDB();

		$sql = 'select id, title from Country
			inner join RegionShippingCountryBinding on
				Country.id = RegionShippingCountryBinding.country and
					RegionShippingCountryBinding.region = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'StoreCountryWrapper');
	}

	// }}}
}

?>
