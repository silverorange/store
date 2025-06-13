<?php

/**
 * Cell renderer for rendering a payment method wrapper.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderPaymentMethodsCellRenderer extends SwatCellRenderer
{
    public function __construct()
    {
        parent::__construct();

        $this->addStyleSheet(
            'packages/store/styles/' .
            'store-order-payment-methods-cell-renderer.css'
        );
    }

    /**
     * The StoreOrderPaymentMethodWrapper dataobject to display.
     *
     * @var StoreOrderPaymentMethodWrapper
     */
    public $payment_methods;

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

        if ($this->payment_methods instanceof StoreOrderPaymentMethodWrapper
            && count($this->payment_methods) > 0) {
            if (count($this->payment_methods) == 1) {
                $payment_method = $this->payment_methods->getFirst();
                $payment_method->showCardNumber($this->show_card_number);
                $payment_method->showCardExpiry($this->show_card_expiry);
                $payment_method->showCardFullname($this->show_card_fullname);
                $payment_method->display($this->display_details);
            } else {
                echo '<table class="store-order-payment-methods-cell-renderer">';
                echo '<tbody>';

                $payment_total = 0;
                foreach ($this->payment_methods as $payment_method) {
                    $payment_method->showCardNumber($this->show_card_number);
                    $payment_method->showCardExpiry($this->show_card_expiry);
                    $payment_method->showCardFullname(
                        $this->show_card_fullname
                    );

                    $payment_total += $payment_method->amount;

                    echo '<tr><th class="payment">';
                    $payment_method->display($this->display_details);
                    echo '</th><td class="payment-amount">';
                    $payment_method->displayAmount();
                    echo '</td></tr>';
                }

                echo '</tbody><tfoot>';
                $locale = SwatI18NLocale::get();
                echo '<tr><th>Payment Total:</th><td class="payment-amount">';
                echo $locale->formatCurrency($payment_total);
                echo '</td></tr>';
                echo '</tfoot></table>';
            }
        } else {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->class = 'swat-none';
            $span_tag->setContent(Store::_('<none>'));
            $span_tag->display();
        }
    }
}
