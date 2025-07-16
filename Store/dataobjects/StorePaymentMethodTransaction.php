<?php

/**
 * A transaction for particular payment on an e-commerce Web application.
 *
 * The set of {@link StorePaymentProvider} classes return
 * StorePaymentMethodTransaction objects for most transaction methods.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StorePaymentProvider
 * @see       StoreOrderPaymentMethod
 *
 * @property int                     $id
 * @property ?string                 $transaction_id
 * @property ?SwatDate               $createdate
 * @property ?int                    $transaction_type
 * @property StoreOrderPaymentMethod $payment_method
 */
class StorePaymentMethodTransaction extends SwatDBDataObject
{
    /**
     * Payment transaction identifier.
     *
     * @var int
     */
    public $id;

    /**
     * The payment-provider specific transaction identifier.
     *
     * @var string
     */
    public $transaction_id;

    /**
     * The date this transaction was created on.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * The type of this transaction.
     *
     * This should be one of the {@link StorePaymentRequest}::TYPE_* constants.
     *
     * @var int
     */
    public $transaction_type;

    protected function init()
    {
        $this->id_field = 'integer:id';
        $this->table = 'PaymentMethodTransaction';
        $this->registerDateProperty('createdate');
        $this->registerInternalProperty(
            'payment_method',
            SwatDBClassMap::get(StoreOrderPaymentMethod::class)
        );
    }
}
