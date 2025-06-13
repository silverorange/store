<?php

/**
 * Renders item prices, including any quantity discounts.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemPriceCellRenderer extends StorePriceCellRenderer
{
    // {{{ public properties

    /**
     * Original value.
     *
     * The original price of the item prior to discounts.
     *
     * @var float
     */
    public $original_value;

    /**
     * The items quantity discount object to render.
     *
     * @var StoreQuantityDiscount
     */
    public $quantity_discounts;

    /**
     * User visible singular unit.
     *
     * @var string
     */
    public $singular_unit;

    /**
     * User visible plural unit.
     *
     * @var string
     */
    public $plural_unit;

    /**
     * Show savings.
     *
     * @var bool
     */
    public $show_savings = true;

    /**
     * Show lower limit.
     *
     * @var bool
     */
    public $show_quantity_discount_lower_limit = false;

    // }}}
    // {{{ public function __construct()

    public function __construct()
    {
        parent::__construct();

        $this->addStyleSheet(
            'packages/store/styles/store-item-price-cell-renderer.css'
        );
    }

    // }}}
    // {{{ public function render()

    public function render()
    {
        $this->renderPrice();
    }

    // }}}
    // {{{ protected function renderPrice()

    protected function renderPrice()
    {
        if ($this->original_value !== null
            && $this->original_value != $this->value) {
            $this->renderOriginalValue();
            echo ' ';
        } elseif ($this->value == 0) {
            parent::render();
        } else {
            ob_start();
            parent::render();
            $price = ob_get_clean();

            if ($this->singular_unit === null) {
                if ($this->hasQuantityDiscounts()) {
                    if ($this->show_quantity_discount_lower_limit) {
                        $this->displayQuantityDiscountLowerLimit();
                    }
                    printf(
                        Store::_('%s %seach%s'),
                        $price,
                        '<span class="unit">',
                        '</span>'
                    );
                } else {
                    echo $price;
                }
            } else {
                printf(
                    Store::_('%1$s %3$sper %2$s%4$s'),
                    $price,
                    $this->singular_unit,
                    '<span class="unit">',
                    '</span>'
                );
            }
        }

        if ($this->hasQuantityDiscounts()) {
            foreach ($this->quantity_discounts as $quantity_discount) {
                $this->renderDiscount($quantity_discount);
            }
        }
    }

    // }}}
    // {{{ protected function displayQuantityDiscountLowerLimit()

    protected function displayQuantityDiscountLowerLimit()
    {
        $first_discount = $this->quantity_discounts->getFirst();
        if ($first_discount->item->minimum_quantity > 1) {
            $lower_limit = sprintf(
                Store::_('%s or more: '),
                $first_discount->item->minimum_quantity
            );
        } else {
            $lower_limit = sprintf(
                Store::_('%s or less: '),
                $first_discount->quantity - 1
            );
        }

        echo $lower_limit;
    }

    // }}}
    // {{{ protected function renderValue()

    protected function renderValue($value, $original_value = null)
    {
        $class_value = $this->value;

        if ($value != $original_value) {
            $this->value = $original_value;
            $span = new SwatHtmlTag('span');
            $span->class = 'store-sale-discount-price';
            $span->open();
            parent::render();
            $span->close();

            echo ' ';
        }

        $this->value = $value;
        parent::render();

        $this->value = $class_value;
    }

    // }}}
    // {{{ protected function renderOriginalValue()

    protected function renderOriginalValue()
    {
        ob_start();
        $this->renderValue($this->value, $this->original_value);
        $price = ob_get_clean();

        if ($this->singular_unit === null) {
            if ($this->hasQuantityDiscounts()) {
                printf(Store::_('%s each'), $price);
            } else {
                echo $price;
            }
        } else {
            printf(Store::_('%s per %s'), $price, $this->singular_unit);
        }

        if ($this->value > 0 && $this->show_savings) {
            $savings_renderer = new StoreSavingsCellRenderer();
            $savings_renderer->value =
                round(1 - ($this->value / $this->original_value), 2);

            $span = new SwatHtmlTag('span');
            $span->open();
            echo ' ';
            $savings_renderer->render();
            $span->close();
        }
    }

    // }}}
    // {{{ protected function renderDiscount()

    protected function renderDiscount(StoreQuantityDiscount $quantity_discount)
    {
        $value = $quantity_discount->getDisplayPrice();
        $original_value = $quantity_discount->getPrice();

        ob_start();
        $this->renderValue($value, $original_value);
        $price = ob_get_clean();

        $div = new SwatHtmlTag('div');
        $div->class = 'store-quantity-discount';
        $div->open();

        if ($this->plural_unit === null) {
            printf(
                Store::_('%s or more: %s each'),
                $quantity_discount->quantity,
                $price
            );
        } else {
            printf(
                Store::_('%s or more %s: %s each'),
                $quantity_discount->quantity,
                $this->plural_unit,
                $price
            );
        }

        echo ' ';

        $savings_renderer = new StoreSavingsCellRenderer();
        $savings_renderer->value =
            round(1 - ($value / $quantity_discount->item->getPrice()), 2);
        $savings_renderer->render();

        $div->close();
    }

    // }}}
    // {{{ private function hasQuantityDiscounts()

    private function hasQuantityDiscounts()
    {
        return $this->quantity_discounts !== null
            && count($this->quantity_discounts) > 0;
    }

    // }}}
}
