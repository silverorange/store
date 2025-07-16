<?php

/**
 * Page for viewing account details.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       StoreAccount
 */
class StoreAccountDetailsPage extends SiteUiPage
{
    protected function getUiXml()
    {
        return __DIR__ . '/account-details.xml';
    }

    // init phase

    public function init()
    {
        // redirect to login page if not logged in
        if (!$this->app->session->isLoggedIn()) {
            $uri = sprintf(
                '%s?relocate=%s',
                $this->app->config->uri->account_login,
                $this->source
            );

            $this->app->relocate($uri);
        }

        parent::init();
    }

    protected function initInternal()
    {
        $account = $this->app->session->account;

        if ($this->ui->hasWidget('account_payment_method_container')) {
            $this->initPaymentMethodViews($account);
        }

        if ($this->ui->hasWidget('account_address_container')) {
            $this->initAddressViews($account);
        }
    }

    protected function initAddressViews(StoreAccount $account)
    {
        $container = $this->ui->getWidget('account_address_views');

        foreach ($account->addresses as $address) {
            $view_id = 'address' . $address->id;
            $view = new StoreAddressView($view_id);
            $view->classes[] = 'compact-button';
            $view->address = $address;
            $container->addChild($view);
        }
    }

    protected function initPaymentMethodViews(StoreAccount $account)
    {
        $container = $this->ui->getWidget('account_payment_method_views');

        foreach ($account->payment_methods as $payment_method) {
            $payment_type = $payment_method->payment_type;
            if ($payment_type->isAvailableInRegion($this->app->getRegion())) {
                $view_id = 'payment_method' . $payment_method->id;
                $view = new StorePaymentMethodView($view_id);
                $view->paymentMethodConfirmText =
                    $this->getPaymentMethodText('confirm');

                $view->classes[] = 'compact-button';
                $view->payment_method = $payment_method;
                $container->addChild($view);
            }
        }
    }

    protected function getPaymentMethodText($text)
    {
        switch ($text) {
            case 'confirm':
                return Store::_('Are you sure you want to remove the following ' .
                'payment method?');

            case 'removed':
                return Store::_('One payment method has been removed.');
        }
    }

    // process phase

    protected function processInternal()
    {
        parent::processInternal();

        $account = $this->app->session->account;

        if ($this->ui->hasWidget('account_address_container')) {
            $this->processAddressViews($account);
        }

        if ($this->ui->hasWidget('account_payment_method_container')) {
            $this->processPaymentMethodViews($account);
        }

        if ($account->isModified()) {
            $account->save();
            $this->relocate();
        }
    }

    protected function relocate()
    {
        $this->app->relocate('account');
    }

    protected function processAddressViews(StoreAccount $account)
    {
        $container = $this->ui->getWidget('account_address_container');
        $views = $container->getDescendants('StoreAddressView');

        foreach ($views as $view) {
            if ($view->hasBeenClicked()) {
                ob_start();
                $view->address->displayCondensed();
                $address_condensed = ob_get_clean();

                $account->addresses->remove($view->address);
                $view->visible = false;

                $message = new SwatMessage(
                    Store::_('One address has been removed.')
                );

                $message->secondary_content = $address_condensed;
                $message->content_type = 'text/xml';
                $this->app->messages->add($message);
            }
        }
    }

    protected function processPaymentMethodViews(StoreAccount $account)
    {
        $container = $this->ui->getWidget('account_payment_method_container');
        $views = $container->getDescendants('StorePaymentMethodView');

        foreach ($views as $view) {
            if ($view->hasBeenClicked()) {
                ob_start();
                $view->payment_method->display();
                $payment_condensed = ob_get_clean();

                $account->payment_methods->remove($view->payment_method);
                $view->visible = false;

                $message = new SwatMessage(
                    $this->getPaymentMethodText('removed')
                );

                $message->secondary_content = $payment_condensed;
                $message->content_type = 'text/xml';
                $this->app->messages->add($message);
            }
        }
    }

    // build phase

    protected function buildInternal()
    {
        if ($this->ui->hasWidget('details_frame')) {
            $this->buildAccountDetails();
        }

        $this->buildSavedCartMessage();

        if ($this->ui->hasWidget('account_order_container')) {
            $this->buildOrders();
        }

        foreach ($this->app->messages->getAll() as $message) {
            $this->ui->getWidget('message_display')->add($message);
        }

        if ($this->ui->hasWidget('account_form')) {
            $this->ui->getWidget('account_form')->action = $this->source;
        }
    }

    protected function buildAccountDetails()
    {
        $account = $this->app->session->account;

        $ds = $this->getAccountDetailsStore($account);

        $details_view = $this->ui->getWidget('account_details_view');
        $details_view->data = $ds;

        if ($account->phone === null && $details_view->hasField('phone')) {
            $details_view->getField('phone')->visible = false;
        }

        if ($account->company === null && $details_view->hasField('company')) {
            $details_view->getField('company')->visible = false;
        }
    }

    protected function buildSavedCartMessage()
    {
        if (!isset($this->app->cart->saved)) {
            return;
        }

        $count = $this->app->cart->saved->getEntryCount();

        if ($count > 0) {
            $message = new SwatMessage('', 'cart');

            $message->primary_content = Store::ngettext(
                'You have an item saved for later.',
                'You have items saved for later.',
                $count
            );

            $message->secondary_content = sprintf(Store::ngettext(
                'View your %sShopping Cart%s to add this item to your order.',
                'View your %sShopping Cart%s to add these items to your order.',
                $count
            ), '<a href="cart">', '</a>');

            $message->content_type = 'text/xml';

            $message_display = $this->ui->getWidget('message_display');
            $message_display->add($message, SwatMessageDisplay::DISMISS_OFF);
        }
    }

    protected function buildOrders()
    {
        $block = $this->ui->getWidget('account_order');
        $block->content_type = 'text/xml';

        ob_start();
        $this->displayOrders();
        $block->content = ob_get_clean();
    }

    protected function displayOrders()
    {
        $orders = $this->getOrders();

        if (count($orders) > 0) {
            $ul = new SwatHtmlTag('ul');
            $li = new SwatHtmlTag('li');

            $ul->open();

            foreach ($orders as $order) {
                $li->open();
                $this->displayOrder($order);
                $li->close();
            }

            $ul->close();
        } else {
            $div_tag = new SwatHtmlTag('div');
            $div_tag->class = 'swat-none';
            $div_tag->setContent(Store::_('<none>'));
            $div_tag->display();
        }
    }

    protected function displayOrder(StoreOrder $order)
    {
        $createdate = clone $order->createdate;
        $createdate->convertTZ($this->app->default_time_zone);

        $a = new SwatHtmlTag('a');
        $a->href = $this->getOrderDetailsURI($order);
        $a->setContent($order->getTitle());
        $a->display();

        echo ' - ' . SwatString::minimizeEntities(
            $createdate->format(SwatDate::DF_DATE)
        );
    }

    protected function getOrderDetailsURI(StoreOrder $order)
    {
        return sprintf('account/order%s', $order->id);
    }

    /**
     * Gets the details store for the account to display on this details page.
     *
     * @return SwatDetailsStore the details store for the account
     */
    protected function getAccountDetailsStore(StoreAccount $account)
    {
        $ds = new SwatDetailsStore($account);
        $ds->fullname = $account->getFullName();

        return $ds;
    }

    /**
     * Gets the orders of the account to display on this account details page.
     *
     * @return StoreOrderWrapper the orders to display on this account details
     *                           page
     */
    protected function getOrders()
    {
        return $this->app->session->account->orders;
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry(
            'packages/store/styles/store-account-details-page.css'
        );
    }
}
