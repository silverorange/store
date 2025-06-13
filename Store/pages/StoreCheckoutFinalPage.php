<?php

/**
 * Abstract base class for final page of the checkout.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutFinalPage extends StoreCheckoutPage
{
    // init phase

    // subclassed to avoid setting form that doesn't exist
    protected function initInternal() {}

    // subclassed to avoid loading form xml that doesn't exist
    protected function loadUI()
    {
        $this->ui = new SwatUI();
        $this->ui->loadFromXML($this->getUiXml());
    }

    protected function checkCart()
    {
        // always return true - cart should be empty now
        return true;
    }

    protected function getProgressDependencies()
    {
        return [$this->getConfirmationSource()];
    }

    protected function initDataObjects()
    {
        // do nothing
    }

    // build phase

    public function build()
    {
        parent::build();
        $this->resetProgress();
        $this->logoutSession();
    }

    protected function buildInternal()
    {
        parent::buildInternal();

        $order = $this->getOrder();

        $this->buildOrderHeader($order);
        $this->buildOrderDetails($order);
        $this->buildOrderFooter($order);

        $this->buildFinalNote($order);
        $this->buildAccountNote($order);
        $this->buildConversionTracking($order);
    }

    protected function buildOrderHeader(StoreOrder $order)
    {
        $header = $this->ui->getWidget('header');
        if ($header instanceof SwatContentBlock) {
            $header->content_type = 'text/xml';
            $header->content =
                SwatString::toXHTML($order->getReceiptHeaderXml());
        }
    }

    protected function buildOrderFooter(StoreOrder $order)
    {
        $footer = $this->ui->getWidget('footer');
        if ($footer instanceof SwatContentBlock) {
            $footer->content_type = 'text/xml';
            $footer->content = SwatString::toXHTML($order->getReceiptFooter());
        }
    }

    protected function buildOrderDetails(StoreOrder $order)
    {
        $details_view = $this->ui->getWidget('order_details');
        $details_view->data = $this->getOrderDetailsStore($order);

        $createdate_column = $details_view->getField('createdate');
        $createdate_renderer = $createdate_column->getFirstRenderer();
        $createdate_renderer->display_time_zone = $this->app->default_time_zone;

        if ($order->email === null && $details_view->hasField('email')) {
            $details_view->getField('email')->visible = false;
        }

        if ($order->comments === null && $details_view->hasField('comments')) {
            $details_view->getField('comments')->visible = false;
        }

        if ($order->phone === null && $details_view->hasField('phone')) {
            $details_view->getField('phone')->visible = false;
        }

        if ($this->ui->hasWidget('items_view')) {
            $items_view = $this->ui->getWidget('items_view');
            $items_view->model = $order->getOrderDetailsTableStore();

            $items_view->getRow('shipping')->value = $order->shipping_total;
            $items_view->getRow('subtotal')->value = $order->getSubtotal();

            if ($order->surcharge_total > 0) {
                $items_view->getRow('surcharge')->value =
                    $order->surcharge_total;
            }

            $items_view->getRow('total')->value = $order->total;
        }
    }

    protected function getOrderDetailsStore(StoreOrder $order)
    {
        $ds = new SwatDetailsStore($order);

        if (!$this->app->config->store->multiple_payment_support) {
            $ds->payment_method = $order->payment_methods->getFirst();
        }

        return $ds;
    }

    protected function buildFinalNote(StoreOrder $order)
    {
        if ($this->ui->hasWidget('final_note')) {
            $note = $this->ui->getWidget('final_note');
            if ($note instanceof SwatContentBlock) {
                $note->content_type = 'text/xml';
                ob_start();
                $this->displayFinalNote($order);
                $note->content = ob_get_clean();
            }
        }
    }

    protected function buildAccountNote(StoreOrder $order)
    {
        // TODO: Possibly refactor this. Some sites display an account note
        //       but do not use this mechanism to do it, using a
        //       SwatMessageDisplay instead.
        //
        if (!$this->ui->hasWidget('account_note')) {
            return;
        }

        $note = $this->ui->getWidget('account_note');
        if ($note instanceof SwatContentBlock && $order->account !== null) {
            $note->content_type = 'text/xml';
            ob_start();
            $this->displayAccountNote();
            $note->content = ob_get_clean();
        }
    }

    /**
     * Displays the final note at the top of the page to the user.
     *
     * This note indicated whether or not the checkout was successful and may
     * contain additional instructions depending on the particular store.
     *
     * @param StoreOrder $order the order for which to display the final note
     */
    abstract protected function displayFinalNote(StoreOrder $order);

    protected function displayAccountNote()
    {
        echo '<div id="checkout_thank_you_account">';

        $header_tag = new SwatHtmlTag('h3');
        $header_tag->setContent(Store::_('Your Account'));
        $paragraph_tag = new SwatHtmlTag('p');
        $paragraph_tag->setContent(
            sprintf(
                Store::_(
                    'By logging in with your account (%s) the next time you ' .
                    'visit, you can edit your information, view previously ' .
                    'placed orders, re-order items from your previous ' .
                    'orders, and checkout faster without having to re-enter ' .
                    'all of your information.'
                ),
                $this->app->session->account->email
            )
        );
        $header_tag->display();
        $paragraph_tag->display();

        echo '</div>';
    }

    protected function buildConversionTracking(StoreOrder $order)
    {
        // by default do nothing.
    }

    /**
     * @return StoreOrder
     */
    protected function getOrder()
    {
        return $this->app->session->order;
    }

    protected function logoutSession()
    {
        $this->app->session->logout();
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry('packages/store/styles/store-cart.css');
    }
}
