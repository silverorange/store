<?php

/**
 * Page displayed when an order is processed successfully on the checkout.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutThankYouPage extends StoreCheckoutFinalPage
{
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/checkout-thank-you.xml';
    }

    // }}}

    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        parent::initInternal();

        if ($this->ui->hasWidget('checkout_progress')) {
            $checkout_progress = $this->ui->getWidget('checkout_progress');
            $checkout_progress->current_step = 3;
        }

        if (property_exists($this->layout, 'analytics_tracked_order')) {
            $this->layout->analytics_tracked_order = $this->getOrder();
        }

        // This will only queue the order if the correct MailChimp cookies
        // exist otherwise nothing will happen.
        if ($this->app->config->mail_chimp->track_orders) {
            $this->app->mailchimp->queueOrder($this->getOrder());
        }
    }

    // }}}

    // build phase
    // {{{ protected function displayFinalNote()

    protected function displayFinalNote(StoreOrder $order)
    {
        echo '<div id="checkout_thank_you">';

        $header_tag = new SwatHtmlTag('h3');
        $header_tag->setContent(Store::_('Your order has been placed.'));
        $header_tag->display();

        ob_start();

        $this->displayEmailNote($order);
        $this->displayPrintNote($order);

        $paragraph_tag = new SwatHtmlTag('p');
        $paragraph_tag->setContent(ob_get_clean(), 'text/xml');
        $paragraph_tag->display();

        echo '</div>';
    }

    // }}}
    // {{{ protected function displayEmailNote()

    protected function displayEmailNote(StoreOrder $order)
    {
        if ($order->email != '') {
            printf(
                Store::_(
                    'An email has been sent to %s containing ' .
                    'the following detailed order receipt.'
                ),
                SwatString::minimizeEntities($order->email)
            );
            echo ' ';
        }
    }

    // }}}
    // {{{ protected function displayPrintNote()

    protected function displayPrintNote(StoreOrder $order)
    {
        echo Store::_(
            'If you wish, you can print a copy of this page for reference.'
        );
    }

    // }}}
    // {{{ protected function buildConversionTracking()

    protected function buildConversionTracking(StoreOrder $order)
    {
        if ($this->app->config->adwords->conversion_id !== null) {
            $footer = $this->ui->getWidget('footer');
            if ($footer instanceof SwatContentBlock) {
                $tracker = new StoreAdWordsTracker(
                    $order,
                    $this->app->config->adwords->conversion_id,
                    $this->app->config->adwords->conversion_label
                );

                $footer->content .= $tracker->getInlineXHtml();
            } else {
                // log an exception (but don't exit), so that we know ad
                // conversion tracking isn't working correctly.
                $e = new SiteException('Ad conversion tracking not working ' .
                    'as footer content block not found.');

                $e->process(false);
            }
        }
    }

    // }}}
}
