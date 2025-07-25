<?php

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
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property StoreCountryWrapper     $billing_countries
 * @property StoreProvStateWrapper   $billing_prov_states
 * @property StoreCardTypeWrapper    $card_types
 * @property StoreLocaleWrapper      $locales
 * @property StorePaymentTypeWrapper $payment_types
 * @property StoreCountryWrapper     $shipping_countries
 * @property StoreProvStateWrapper   $shipping_prov_states
 */
class StoreRegion extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * The title of thie region.
     *
     * This is something like "Canada", "U.S.A." or "Europe".
     *
     * @var ?string
     */
    public $title;

    /**
     * Gets the first locale of this region.
     *
     * @return Locale the first locale
     */
    public function getFirstLocale()
    {
        return $this->locales->getFirst();
    }

    protected function init()
    {
        $this->table = 'Region';
        $this->id_field = 'integer:id';
    }

    protected function loadLocales()
    {
        $sql = 'select * from Locale where region = %s';
        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreLocaleWrapper::class)
        );
    }

    /**
     * Gets payment types that orders may use in this region.
     *
     * @return StorePaymentTypeWrapper a recordset of StorePaymentType objects
     *                                 that orders may use in this region
     */
    protected function loadPaymentTypes()
    {
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
            SwatDBClassMap::get(StorePaymentTypeWrapper::class)
        );
    }

    /**
     * Gets payment card types that orders may use in this region.
     *
     * @return StoreCardTypeWrapper a recordset of StoreCardType objects
     *                              that orders may use in this region
     */
    protected function loadCardTypes()
    {
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
            SwatDBClassMap::get(StoreCardTypeWrapper::class)
        );
    }

    /**
     * Gets countries that orders may be billed to in this region.
     *
     * @return StoreCountryWrapper a recordset of StoreCountry objects that
     *                             orders may be billed to
     */
    protected function loadBillingCountries()
    {
        $sql = 'select id, title from Country
			inner join RegionBillingCountryBinding on
				Country.id = RegionBillingCountryBinding.country and
					RegionBillingCountryBinding.region = %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreCountryWrapper::class)
        );
    }

    /**
     * Gets countries that orders may be shipped to in this region.
     *
     * @return StoreCountryWrapper a recordset of StoreCountry objects that
     *                             orders may be shipped to
     */
    protected function loadShippingCountries()
    {
        $sql = 'select id, title from Country
			inner join RegionShippingCountryBinding on
				Country.id = RegionShippingCountryBinding.country and
					RegionShippingCountryBinding.region = %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreCountryWrapper::class)
        );
    }

    /**
     * Gets provinces and states that orders may be billed to in this region.
     *
     * @return StoreProvStateWrapper a recordset of StoreProvState objects that
     *                               orders may be billed to
     */
    protected function loadBillingProvStates()
    {
        $sql = 'select id, title from ProvState
			inner join RegionBillingProvStateBinding on
				ProvState.id = RegionBillingProvStateBinding.provstate and
					RegionBillingProvStateBinding.region = %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreProvStateWrapper::class)
        );
    }

    /**
     * Gets provinces and states that orders may be shipped to in this region.
     *
     * @return StoreProvStateWrapper a recordset of StoreProvState objects that
     *                               orders may be shipped to
     */
    protected function loadShippingProvStates()
    {
        $sql = 'select id, title from ProvState
			inner join RegionShippingProvStateBinding on
				ProvState.id = RegionShippingProvStateBinding.provstate and
					RegionShippingProvStateBinding.region = %s';

        $sql = sprintf(
            $sql,
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreProvStateWrapper::class)
        );
    }
}
