<?php

/**
 * Generates tracking code for a cart for Google Tag Manager Event Tracker.
 *
 * @copyright 2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see      https://developers.google.com/tag-platform/tag-manager
 */
class StoreAnalyticsCartItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $category,
        public string $category2,
        public float $price,
        public int $quantity = 1,
    ) {
    }
}
