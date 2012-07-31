<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreCountryWrapper.php';
require_once 'Store/dataobjects/StoreProvStateWrapper.php';
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
 * @copyright 2006-2012 silverorange
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
	// {{{ protected function loadPaymentTypes()

	/**
	 * Gets payment types that orders may use in this region
	 *
	 * @return StorePaymentTypeWrapper a recordset of StorePaymentType objects
	 *                                  that orders may use in this region.
	 */
	protected function loadPaymentTypes()
	{
		require_once 'Store/dataobjects/StorePaymentTypeWrapper.php';

		$sql = sprintf(
			'select PaymentType.* from PaymentType
				inner join PaymentTypeRegionBinding on
					PaymentType.id = PaymentTypeRegionBinding.payment_type and
					PaymentTypeRegionBinding.region = %s
			order by displayorder, title',
			$this->db->quote($this->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('StorePaymentTypeWrapper')
		);
	}

	// }}}
	// {{{ protected function loadCardTypes()

	/**
	 * Gets payment card types that orders may use in this region
	 *
	 * @return StoreCardTypeWrapper a recordset of StoreCardType objects
	 *                               that orders may use in this region.
	 */
	protected function loadCardTypes()
	{
		require_once 'Store/dataobjects/StoreCardTypeWrapper.php';

		$sql = sprintf(
			'select CardType.* from CardType
				inner join CardTypeRegionBinding on
					CardType.id = CardTypeRegionBinding.card_type and
					CardTypeRegionBinding.region = %s
			order by displayorder, title',
			$this->db->quote($this->id, 'integer')
		);

		return SwatDB::query(
			$this->db,
			$sql,
			SwatDBClassMap::get('StoreCardTypeWrapper')
		);
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
	// {{{ protected function loadBillingProvStates()

	/**
	 * Gets provinces and states that orders may be billed to in this region
	 *
	 * @return StoreProvStateWrapper a recordset of StoreProvState objects that
	 *                              orders may be billed to.
	 */
	protected function loadBillingProvStates()
	{
		$this->checkDB();

		$sql = 'select id, title from ProvState
			inner join RegionBillingProvStateBinding on
				ProvState.id = RegionBillingProvStateBinding.provstate and
					RegionBillingProvStateBinding.region = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'StoreProvStateWrapper');
	}

	// }}}
	// {{{ protected function loadShippingProvStates()

	/**
	 * Gets provinces and states that orders may be shipped to in this region
	 *
	 * @return StoreProvStateWrapper a recordset of StoreProvState objects that
	 *                              orders may be shipped to.
	 */
	protected function loadShippingProvStates()
	{
		$this->checkDB();

		$sql = 'select id, title from ProvState
			inner join RegionShippingProvStateBinding on
				ProvState.id = RegionShippingProvStateBinding.provstate and
					RegionShippingProvStateBinding.region = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'StoreProvStateWrapper');
	}

	// }}}
}

?>
