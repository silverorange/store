<?php

/**
 * Generates tracking code for a cart for Google Tag Manager Event Tracker.
 *
 * @copyright 2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 */
class StoreAnalyticsCartDto
{
    /**
     * @param list<StoreAnalyticsCartItemDto> $items
     */
    public function __construct(
        public float $value,
        public string $currency,
        public string $affiliation,
        public array $items,
    ) {
    }
}
