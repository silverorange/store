<?php

/**
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrder extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Snapshot of the customer's email address.
     *
     * @var string
     */
    public $email;

    /**
     * Extra email address to which the order confirmation email is CC'd.
     *
     * @var string
     */
    public $cc_email;

    /**
     * Snapshot of the customer's company name.
     *
     * @var string
     */
    public $company;

    /**
     * Snapshot of the customer's phone number.
     *
     * @var string
     */
    public $phone;

    /**
     * Comments.
     *
     * @var string
     */
    public $comments;

    /**
     * Admin Comments, visible to customer.
     *
     * @var string
     */
    public $admin_comments;

    /**
     * Admin Notes, invisible to customer.
     *
     * @var string
     */
    public $notes;

    /**
     * Creation date.
     *
     * @var SwatDate
     */
    public $createdate;

    /**
     * Cancellation date.
     *
     * @var SwatDate
     */
    public $cancel_date;

    /**
     * Total amount.
     *
     * @var float
     */
    public $total;

    /**
     * Item total.
     *
     * @var float
     */
    public $item_total;

    /**
     * Surcharge total.
     *
     * @var float
     */
    public $surcharge_total;

    /**
     * Shipping total.
     *
     * @var float
     */
    public $shipping_total;

    /**
     * Tax total.
     *
     * @var float
     */
    public $tax_total;

    /**
     * Gift certificate, merchandise credit or coupon total.
     *
     * @var float
     */
    public $voucher_total;

    /**
     * Whether or not this order is cancelled.
     *
     * @var bool
     *
     * @deprecated use {@link StoreOrder::$cancel_date} instead
     */
    public $cancelled = false;

    /**
     * Whether or not this order is a failed order attempt stored only
     * for debugging and recordkeeping.
     *
     * @var bool
     */
    public $failed_attempt = false;

    /**
     * Whether or not the comments on this order have been sent to any
     * notification system in place.
     *
     * @var bool
     */
    public $comments_sent = false;

    /**
     * The id of the {@link StoreOrderStatus} of this order.
     *
     * @var int
     *
     * @see StoreOrder::getStatus()
     */
    protected $status;

    /**
     * Gets the subtotal for this order.
     *
     * By default this is defined as item_total. Site-specific sub-classes may
     * include other values in addition to item_total.
     *
     * @return int this order's subtotal
     */
    public function getSubtotal()
    {
        return $this->item_total;
    }

    public function getOrderDetailsTableStore()
    {
        $store = new SwatTableStore();

        foreach ($this->items as $item) {
            $ds = $this->getOrderItemDetailsStore($item);
            $store->add($ds);
        }

        return $store;
    }

    public function getTitle()
    {
        return sprintf(Store::_('Order %s'), $this->id);
    }

    public function sendConfirmationEmail(SiteApplication $app)
    {
        // This is demo code. StoreOrderConfirmationMailMessage is
        // abstract and the site-specific version must be used.

        if ($this->getConfirmationEmailAddress() === null) {
            return;
        }

        try {
            $email = new StoreOrderConfirmationMailMessage($app, $this);
            $email->send();
        } catch (SiteMailException $e) {
            $e->process(false);
        }
    }

    public function sendPaymentFailedEmail(SiteApplication $app)
    {
        // This is demo code. StoreOrderConfirmationMailMessage is
        // abstract and the site-specific version must be used.

        if ($this->email === null) {
            return;
        }

        try {
            $email = new StoreOrderConfirmationMailMessage($app, $this);
            $email->send();
        } catch (SiteMailException $e) {
            $e->process(false);
        }
    }

    /**
     * Gets the header text for order receipts.
     *
     * Subclasses should return a string from this method if they wish to
     * display a header on all order receipts. By default, an empty string is
     * returned so no header is displayed on order receipts.
     *
     * @return string the header text for order receipts
     */
    public function getReceiptHeaderXml()
    {
        return '';
    }

    /**
     * Gets the header text for order receipts.
     *
     * Subclasses should return a string from this method if they wish to
     * display a header on all order receipts. By default, an empty string is
     * returned so no header is displayed on order receipts.
     *
     * @return string the header text for order receipts
     */
    public function getReceiptHeaderText()
    {
        return '';
    }

    /**
     * Gets the footer text for order receipts.
     *
     * This text will be displayed as footer on all order receipts. By default,
     * a note indicating in which currency prices are displayed is returned.
     *
     * @return string the footer text for order receipts
     */
    public function getReceiptFooter()
    {
        $footer = [];

        if ($this->shipping_address instanceof StoreOrderAddress
            && $this->shipping_address->provstate instanceof StoreProvState
            && $this->shipping_address->provstate->tax_message !== null) {
            $footer[] = $this->shipping_address->provstate->tax_message;
        }

        $footer[] = sprintf(
            Store::_('All prices are in %s.'),
            SwatString::getInternationalCurrencySymbol(
                $this->getInternalValue('locale')
            )
        );

        return implode("\n\n", $footer);
    }

    /**
     * Gets a short, textual description of this order.
     *
     * For example: "Example Company Order #12345".
     *
     * This description is used for various purposes including financial
     * transaction records.
     *
     * @return string a short, textual description of this order
     */
    public function getDescription()
    {
        return sprintf('Order #%s', $this->id);
    }

    public function duplicate(): static
    {
        $new_order = parent::duplicate();

        if ($this->shipping_address === $this->billing_address) {
            $new_order->shipping_address = $new_order->billing_address;
        }

        return $new_order;
    }

    /**
     * Gets the address to which to send order confirmation messages.
     *
     * If the order is placed on account, send to the account email address
     * by default. Orders placed on account do not have an email address
     * entered explicitly during the checkout.
     *
     * @return string the email address to which to send order confirmation
     *                messages
     */
    public function getConfirmationEmailAddress()
    {
        $address = $this->email;

        if ($this->account instanceof SiteAccount
            && $this->account->email != '') {
            $address = $this->account->email;
        }

        return $address;
    }

    protected function init()
    {
        // TODO: remove this
        $this->registerDeprecatedProperty('previous_attempt');
        $this->registerDeprecatedProperty('invoice');

        $this->registerInternalProperty('status');
        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(StoreAccount::class)
        );

        $this->registerInternalProperty(
            'billing_address',
            SwatDBClassMap::get(StoreOrderAddress::class),
            true
        );

        $this->registerInternalProperty(
            'shipping_address',
            SwatDBClassMap::get(StoreOrderAddress::class),
            true
        );

        $this->registerInternalProperty(
            'shipping_type',
            SwatDBClassMap::get(StoreShippingType::class)
        );

        $this->registerInternalProperty(
            'locale',
            SwatDBClassMap::get(StoreLocale::class),
            true
        );

        $this->registerInternalProperty(
            'ad',
            SwatDBClassMap::get(SiteAd::class),
            true
        );

        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );

        $this->registerDateProperty('createdate');
        $this->registerDateProperty('cancel_date');

        $this->table = 'Orders';
        $this->id_field = 'integer:id';
    }

    protected function getSerializableSubDataObjects()
    {
        return [
            'shipping_address',
            'billing_address',
            'payment_methods',
            'items',
        ];
    }

    protected function getOrderItemDetailsStore($order_item)
    {
        $ds = new SwatDetailsStore($order_item);
        $ds->item = $order_item;
        $ds->description = $order_item->getDescription();
        $ds->item_count = $this->getProductItemCount($order_item);

        if ($order_item->alias_sku !== null
            && $order_item->alias_sku != '') {
            $ds->sku .= sprintf(' (%s)', $order_item->alias_sku);
        }

        $item = $order_item->getAvailableItem($this->locale->region);
        if ($item !== null && $item->product->primary_image !== null) {
            $image = $item->product->primary_image;
            $ds->image = $image->getUri($this->getImageDimension());
            $ds->image_width = $image->getWidth($this->getImageDimension());
            $ds->image_height = $image->getHeight($this->getImageDimension());
        } else {
            $ds->image = null;
            $ds->image_width = null;
            $ds->image_height = null;
        }

        return $ds;
    }

    /**
     * @return string Image dimension shortname
     */
    protected function getImageDimension()
    {
        return 'pinky';
    }

    protected function getProductItemCount(StoreOrderItem $item)
    {
        static $item_counts;

        if ($item_counts === null) {
            $item_counts = [];

            $items = clone $this->items;
            foreach ($items as $current_item) {
                $id = $this->getItemIndex($current_item);
                if (array_key_exists($id, $item_counts)) {
                    $item_counts[$id]++;
                } else {
                    $item_counts[$id] = 1;
                }
            }
        }

        $id = $this->getItemIndex($item);
        if (array_key_exists($id, $item_counts)) {
            $count = $item_counts[$id];
        } else {
            $count = 1;
        }

        return $count;
    }

    protected function getItemIndex(StoreOrderItem $item)
    {
        return $item->product;
    }

    // order status methods

    /**
     * Gets the status of this order.
     *
     * @return StoreOrderStatus the status of this order or null if this
     *                          orders's status is undefined
     */
    public function getStatus()
    {
        if ($this->status === null && $this->hasInternalValue('status')) {
            $list = StoreOrderStatusList::statuses();
            $this->status = $list->getById($this->getInternalValue('status'));
        }

        return $this->status;
    }

    public function setStatus(StoreOrderStatus $status)
    {
        $this->status = $status;
        $this->setInternalValue('status', $status->id);
    }

    /**
     * Gets whether or not this order is ready to bill.
     *
     * This order is ready to bill if payment is authorized and this order is
     * not cancelled.
     *
     * @return bool true if this order is ready to be billed and false if it
     *              is not
     */
    public function isBillable()
    {
        return !$this->failed_attempt && !$this->cancelled
            && $this->getStatus() === StoreOrderStatusList::status('authorized');
    }

    /**
     * Gets whether or not this order is ready to ship.
     *
     * This order is ready to ship if payment is completed and this order is
     * not cancelled.
     *
     * @return bool true if this order is ready to be shipped and false if
     *              it is not
     */
    public function isShippable()
    {
        return !$this->failed_attempt && !$this->cancelled
            && $this->getStatus() === StoreOrderStatusList::status('billed');
    }

    /**
     * Gets whether or not this order is finished being processed.
     *
     * This order is finished being processed if the order has been shipped and
     * is not cancelled.
     *
     * @return bool true if this order is finished and false if it is not
     */
    public function isFinished()
    {
        return !$this->failed_attempt && !$this->cancelled
            && $this->getStatus() === StoreOrderStatusList::status('shipped');
    }

    // loader methods

    protected function loadItems()
    {
        $sql = sprintf(
            'select * from OrderItem
			where ordernum = %s
			order by sku asc',
            $this->db->quote($this->id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreOrderItemWrapper::class)
        );
    }

    protected function loadPaymentMethods()
    {
        $sql = sprintf(
            'select OrderPaymentMethod.*
			from OrderPaymentMethod
			inner join PaymentType on
				OrderPaymentMethod.payment_type = PaymentType.id
			where ordernum = %s
			order by OrderPaymentMethod.displayorder,
				PaymentType.displayorder, PaymentType.title',
            $this->db->quote($this->id, 'integer')
        );

        $payment_methods = SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(StoreOrderPaymentMethodWrapper::class)
        );

        // efficiently load transactions for all payment methods
        $payment_methods->loadAllSubRecordsets(
            'transactions',
            SwatDBClassMap::get(StorePaymentMethodTransactionWrapper::class),
            'PaymentMethodTransaction',
            'payment_method',
            '',
            'createdate, id'
        );

        return $payment_methods;
    }

    // saver methods

    /**
     * Automatically saves StoreOrderItem sub-data-objects when this
     * StoreOrder object is saved.
     */
    protected function saveItems()
    {
        foreach ($this->items as $item) {
            $item->ordernum = $this;
        }

        $this->items->setDatabase($this->db);
        $this->items->save();
    }

    /**
     * Automatically saves StoreOrderPaymentMethod sub-data-objects when this
     * StoreOrder object is saved.
     */
    protected function savePaymentMethods()
    {
        foreach ($this->payment_methods as $payment_method) {
            $payment_method->ordernum = $this;
        }

        $this->payment_methods->setDatabase($this->db);
        $this->payment_methods->save();
    }
}
