<?php

/**
 * A payment type data object.
 *
 * Payment type shortnames are by convention:
 *
 * <pre>
 * Shortname    | Description
 * -------------+------------------------------------------------------------
 * card         | credit or debit card, more specific types in StoreCardType
 * paypal       | PayPal express checkout
 * check        | mailed cheque
 * cod          | cash on delivery
 * account      | pay on account balance (managed or unmanaged)
 * voucher      | code based gift certificate, merchandise credit or coupon
 * </pre>
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int     $id
 * @property ?string $shortname
 * @property ?string $title
 * @property ?string $note
 * @property ?int    $displayorder
 * @property ?float  $surcharge
 * @property ?int    $priority
 */
class StorePaymentType extends SwatDBDataObject
{
    /**
     * Unique identifier of this payment type.
     *
     * @var string
     */
    public $id;

    /**
     * Non-visible string indentifier.
     *
     * This is something like 'cod', 'card', 'paypal'.
     *
     * @var string
     */
    public $shortname;

    /**
     * User visible title for this payment type.
     *
     * @var string
     */
    public $title;

    /**
     * User visible note for this payment type.
     *
     * The note field should be used to inform customers of additional
     * requirements or conditions on this payment method type. For example, it
     * could contain special shipping information for COD payments.
     *
     * @var string
     */
    public $note;

    /**
     * Order of display.
     *
     * @var int
     */
    public $displayorder;

    /**
     * Additional charge applied when using this payment type.
     *
     * @var float
     */
    public $surcharge;

    /**
     * Priority of payment for when multipe payments are made on one order.
     *
     * @var int
     */
    public $priority;

    /**
     * Loads a payment type by its shortname.
     *
     * @param string $shortname the shortname of the payment type to load
     *
     * @deprecated use {@link StorePaymentType::loadByShortname()} instead
     */
    public function loadFromShortname($shortname)
    {
        return $this->loadByShortname($shortname);
    }

    /**
     * Loads a payment type by its shortname.
     *
     * @param string $shortname the shortname of the payment type to load
     */
    public function loadByShortname($shortname)
    {
        $this->checkDB();
        $row = null;

        if ($this->table !== null) {
            $sql = sprintf(
                'select * from %s where shortname = %s',
                $this->table,
                $this->db->quote($shortname, 'text')
            );

            $rs = SwatDB::query($this->db, $sql, null);
            $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
        }

        if ($row === null) {
            return false;
        }

        $this->initFromRow($row);
        $this->generatePropertyHashes();

        return true;
    }

    /**
     * Whether or not this payment type is available in the given region.
     *
     * The payment type needs to have an id before this method will work.
     *
     * @param StoreRegion $region the region to check the availability of this
     *                            payment type in
     *
     * @return bool true if this payment type is available in the
     *              given region and false if it is not
     *
     * @throws StoreException if this payment type has no id defined
     */
    public function isAvailableInRegion(StoreRegion $region)
    {
        $this->checkDB();

        if ($this->id === null) {
            throw new StoreException('Payment type must have an id set ' .
                'before region availability can be determined.');
        }

        $sql = sprintf(
            'select count(id) from PaymentType
			inner join PaymentTypeRegionBinding on payment_type = id and
				region = %s
			where id = %s',
            $this->db->quote($region->id, 'integer'),
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::queryOne($this->db, $sql) > 0;
    }

    /**
     * Gets whether or not this payment type uses a card (debit or credit).
     *
     * @return bool true if this payment type uses a card and false if this
     *              payment type does not use a card
     */
    public function isCard()
    {
        $types = [
            'card',
        ];

        return in_array($this->shortname, $types);
    }

    /**
     * Gets whether or not this payment type is PayPal.
     *
     * @return bool true if this payment type is PayPal and false if this
     *              payment type is not PayPal
     */
    public function isPayPal()
    {
        return $this->shortname === 'paypal';
    }

    public function isVoucher()
    {
        return $this->shortname === 'voucher';
    }

    public function isAccount()
    {
        return $this->shortname === 'account';
    }

    protected function init()
    {
        $this->table = 'PaymentType';
        $this->id_field = 'integer:id';
    }

    /**
     * Displays this payment type.
     */
    public function display()
    {
        echo SwatString::minimizeEntities($this->title);

        if (mb_strlen($this->note) > 0) {
            printf(
                '<br /><span class="swat-note">%s</span>',
                $this->note
            );
        }
    }
}
