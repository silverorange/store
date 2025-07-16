<?php

/**
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int          $id
 * @property ?string      $code
 * @property ?string      $voucher_type
 * @property ?float       $amount
 * @property ?SwatDate    $used_date
 * @property SiteInstance $instance
 */
class StoreVoucher extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Redemption Code.
     *
     * @var string
     */
    public $code;

    /**
     * Type.
     *
     * One of either:
     * - 'gift-certificate',
     * - 'coupon', or
     * - 'merchandise-credit'
     *
     * @var string
     */
    public $voucher_type;

    /**
     * Amount.
     *
     * @var float
     */
    public $amount;

    /**
     * Used date.
     *
     * @var SwatDate
     */
    public $used_date;

    /**
     * Loads this voucher from its code.
     *
     * @param string       $code     the code for this voucher
     * @param SiteInstance $instance the site instance
     *
     * @return bool true if this voucher was loaded and false if it
     *              was not
     */
    public function loadFromCode($code, SiteInstance $instance)
    {
        $this->checkDB();

        $row = null;
        $loaded = false;

        if ($this->table !== null) {
            // strip prefix if there is one
            $prefix = mb_strtolower($this->getCouponPrefix($instance));
            $prefix_length = mb_strlen($prefix);
            if (mb_strtolower(mb_substr($code, 0, $prefix_length)) == $prefix) {
                $code = mb_substr($code, mb_strlen($prefix));
            }

            $sql = sprintf(
                'select * from Voucher
				where lower(Voucher.code) = lower(%s)
					and used_date %s %s and instance = %s',
                $this->db->quote($code, 'text'),
                SwatDB::equalityOperator(null),
                $this->db->quote(null, 'date'),
                $this->db->quote($instance->id, 'integer')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $loaded = true;
        }

        return $loaded;
    }

    /**
     * Gets the valid prefix for coupons.
     *
     * @param SiteInstance $instance the site instance
     *
     * @return string prefix for coupons
     */
    public function getCouponPrefix(SiteInstance $instance)
    {
        return null;
    }

    /**
     * Gets a displayable title for the particular voucher type.
     *
     * @param $show_amount boolean whether or not to show the amount of this
     *                     voucher
     *
     * @return string title for this voucher
     */
    public function getTitle($show_amount = false)
    {
        switch ($this->voucher_type) {
            case 'gift-certificate':
                $type = Store::_('Gift Certificate');
                break;

            case 'merchandise-credit':
                $type = Store::_('Merchandise Credit');
                break;

            case 'coupon':
                $type = Store::_('Coupon');
                break;

            default:
                $type = Store::_('Voucher');
                break;
        }

        $title = sprintf(
            Store::_('%s #%s'),
            $type,
            $this->code
        );

        if ($show_amount) {
            $locale = SwatI18NLocale::get('en_US');
            $title .= ' (' . $locale->formatCurrency($this->amount) . ')';
        }

        return $title;
    }

    /**
     * Gets a displayable title for the particular voucher type including the
     * amount of this voucher.
     *
     * @return string title for this voucher including the amount of this
     *                voucher
     */
    public function getTitleWithAmount()
    {
        return $this->getTitle(true);
    }

    public function isUsed()
    {
        return $this->used_date instanceof SwatDate;
    }

    public function copyFrom(StoreVoucher $voucher)
    {
        $this->code = $voucher->code;
        $this->voucher_type = $voucher->voucher_type;
        $this->used_date = $voucher->used_date;
        $this->instance = $voucher->instance;
    }

    protected function init()
    {
        $this->table = 'Voucher';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );

        $this->registerDateProperty('used_date');
    }
}
