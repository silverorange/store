<?php

/**
 * Generates tracking code for an order (set of order items) refund for Google Tag Manager Event Tracker.
 *
 * @copyright 2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 */
class StoreAnalyticsOrderRefundTracker
{
    public function __construct(
        protected StoreAnalyticsOrderRefundDto $refund,
    ) {
    }

    public function getGoogleTagManagerCommands(): array
    {
        return [
            $this->getGoogleTagManagerRefundCommand(),
        ];
    }

    /**
     * @return list<array{
     *     item_id: string,
     *     item_name: string,
     *     item_category: string,
     *     item_category2: string,
     *     price: float,
     *     quantity: int,
     * }>
     */
    private function getGoogleTagManagerItemsParameter(): array
    {
        return array_map(
            fn(StoreAnalyticsCartItemDto $item): array => [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_category' => $item->category,
                'item_category2' => $item->category2,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ],
            $this->refund->items,
        );
    }

    /**
     * @return array{
     *     event: string,
     *     ecommerce: array{
     *         transaction_id: string,
     *         value: float,
     *         currency: string,
     *         items: list<array{
     *             item_id: string,
     *             item_name: string,
     *             item_category: string,
     *             item_category2: string,
     *             price: float,
     *             quantity: int,
     *         }>,
     *     },
     * }
     */
    protected function getGoogleTagManagerRefundCommand(): array
    {
        return [
            'event' => 'refund',
            'ecommerce' => [
                'transaction_id' => $this->refund->transaction_id,
                'value'          => $this->refund->value,
                'currency'       => $this->refund->currency,
                'items'          => $this->getGoogleTagManagerItemsParameter(),
            ],
        ];
    }
}
