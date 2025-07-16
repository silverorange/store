<?php

/**
 * Cell renderer for rendering a payment method.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentMethodCellRenderer extends SwatCellRenderer
{
    /**
     * The StorePaymentMethod dataobject to display.
     *
     * @var StorePaymentMethod
     */
    public $payment_method;

    /**
     * Whether or not to show additional details for card-type payment methods.
     *
     * @var bool
     */
    public $display_details = true;

    /**
     * Whether or not to show card_number.
     *
     * @var bool
     */
    public $show_card_number = true;

    /**
     * Whether or not to show card_expiry.
     *
     * @var bool
     */
    public $show_card_expiry = false;

    /**
     * Whether or not to show card_fullname.
     *
     * @var bool
     */
    public $show_card_fullname = true;

    public function render()
    {
        if (!$this->visible) {
            return;
        }

        parent::render();

        if ($this->payment_method instanceof StorePaymentMethod) {
            $this->payment_method->showCardNumber($this->show_card_number);
            $this->payment_method->showCardExpiry($this->show_card_expiry);
            $this->payment_method->showCardFullname($this->show_card_fullname);
            $this->payment_method->display($this->display_details);
        } else {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->class = 'swat-none';
            $span_tag->setContent(Store::_('<none>'));
            $span_tag->display();
        }
    }
}
