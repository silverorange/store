<?php

/**
 * Generates order transaction tracking code for an order for Google Tag Manager and Meta/Facebook pixel Event Tracker.
 *
 * @copyright 2008-2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.facebook.com/docs/meta-pixel/

 */
class StoreAnalyticsOrderTracker
{
    public function __construct(
        protected StoreOrder $order,
        protected ?string $promotion_code = null,
        protected ?string $affiliation = null,
    ) {
    }

    // Google

    public function getGoogleTagManagerCommands(): array
    {
        return [
            $this->getGoogleTagManagerPurchaseCommand(),
            $this->getGoogleTagManagerShippingCommand(),
        ];
    }

    protected function getGoogleTagManagerItemsParameter(): array
    {
        $items = [];
        foreach ($this->order->items as $item) {
            $items[] = [
                'item_id'       => $this->getSku($item),
                'item_name'     => $this->getProductTitle($item),
                'item_category' => $this->getCategoryTitle($item),
                'affiliation'   => $this->affiliation,
                'price'         => $item->price,
            ];
        }

        return $items;
    }

    protected function getGoogleTagManagerPurchaseCommand(): array
    {

        $data = [
            'event'        => 'purchase',
            'ecommerce' => [
                'transaction_id' => strval($this->order->id),
                'value'          => $this->getOrderTotal(),
                'currency'       => 'USD',
                'items'          => $this->getGoogleTagManagerItemsParameter(),
            ],
        ];

        if($this->promotion_code !== null) {
            $data['ecommerce']['coupon'] = $this->promotion_code;
        }

        return $data;
    }

    protected function getGoogleTagManagerShippingCommand(): array
    {
        return [
            'event'        => 'add_shipping_info',
            'ecommerce' => [
                'currency' => 'USD',
                'value'    => $this->getShippingTotal(),
            ],
        ];
    }

    // Facebook

    public function getFacebookPixelCommands()
    {
        $command = [
            'track',
            'Purchase',
            [
                'value'    => $this->getOrderTotal(),
                'currency' => 'USD',
            ],
        ];

        return [$command];
    }

    protected function getAddress()
    {
        return $this->order->billing_address;
    }

    protected function getCity(?StoreOrderAddress $address = null) : string
    {
        return $address instanceof StoreOrderAddress
            ? $address->city
            : '';
    }

    protected function getProvStateTitle(?StoreOrderAddress $address = null) : string
    {
        $title = '';

        if ($address instanceof StoreOrderAddress) {
            $title = $address->provstate === null
                ? $address->provstate_other ?? ''
                : $address->provstate->title ?? ''; 
        }

        return $title;
    }

    protected function getCountryTitle(?StoreOrderAddress $address = null) : string
    {
        return $address instanceof StoreOrderAddress
            ? $address->country->title
            : '';
    }

    protected function getOrderTotal(): float
    {
        return $this->order->total;
    }

    protected function getOrderQuantity()
    {
        $quantity = 0;
        foreach ($this->order->items as $item) {
            $quantity += $item->quantity;
        }

        return $quantity;
    }

    protected function getTaxTotal() : float|string
    {
        return ($this->order->tax_total == 0) ? '' : $this->order->tax_total;
    }

    protected function getShippingTotal() : float|string
    {
        return ($this->order->shipping_total == 0)
            ? ''
            : $this->order->shipping_total;
    }

    protected function getSku(StoreOrderItem $item) : ?string
    {
        return $item->sku;
    }

    protected function getProductTitle(StoreOrderItem $item) : ?string
    {
        return $item->product_title;
    }

    protected function getCategoryTitle(StoreOrderItem $item)
    {
        return $item->getSourceCategoryTitle();
    }
}
