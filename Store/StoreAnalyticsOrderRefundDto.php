<?php

/**
 * Generates tracking code for a cart for Google Tag Manager Event Tracker.
 *
 * @copyright 2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 */
class StoreAnalyticsOrderRefundDto
{
    /**
     * @param list<StoreAnalyticsItemDto> $items
     */
    public function __construct(
        public string $transaction_id,
        public float $value,
        public float $shipping,
        public string $currency,
        public array $items,
    ) {
    }
}
