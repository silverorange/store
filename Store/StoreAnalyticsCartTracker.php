<?php

/**
 * Generates tracking code for a cart for Google Tag Manager Event Tracker.
 *
 * @copyright 2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 *
 * @phpstan-type CartItem array{
 *     item_id: int,
 *     item_name: string,
 *     item_category: string,
 *     item_category2: string,
 *     affiliation: string,
 *     price: float,
 *     quantity: int,
 * }
 */
class StoreAnalyticsCartTracker
{
    public function __construct(
        protected StoreAnalyticsCartDto $add_to_cart,
    ) {
    }

    public function getGoogleTagManagerCommands(): array
    {
        return [
            $this->getGoogleTagManagerAddToCartCommand(),
        ];
    }

    /**
     * @return list<CartItem>
     */
    protected function getGoogleTagManagerItemsParameter(): array
    {
        return array_map(
            fn(StoreAnalyticsCartItemDto $item): array => [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_category' => $item->category,
                'item_category2' => $item->category2,
                'affiliation' => $this->add_to_cart->affiliation,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ],
            $this->add_to_cart->items,
        );
    }

    /**
     * @return array{
     *     event: string,
     *     ecommerce: array{
     *         value: float,
     *         currency: string,
     *         items: list<CartItem>,
     *     },
     * }
     */
    protected function getGoogleTagManagerAddToCartCommand(): array
    {
        return [
            'event' => 'add_to_cart',
            'ecommerce' => [
                'value'          => $this->add_to_cart->value,
                'currency'       => $this->add_to_cart->currency,
                'items'          => $this->getGoogleTagManagerItemsParameter(),
            ],
        ];
    }
}