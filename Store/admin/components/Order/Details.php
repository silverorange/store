<?php

/**
 * Details page for Orders.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreOrderDetails extends AdminPage
{
    protected $id;
    protected $order;

    /**
     * If we came from an account page, this is the id of the account.
     * Otherwise it is null.
     *
     * @var int
     */
    protected $account;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->mapClassPrefixToPath('Store', 'Store');
        $this->ui->loadFromXML($this->getUiXml());

        $this->id = SiteApplication::initVar('id');
        $this->account = SiteApplication::initVar('account');

        $this->getOrder();
    }

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    protected function getOrder()
    {
        if ($this->order === null) {
            $order_class = SwatDBClassMap::get(StoreOrder::class);
            $this->order = new $order_class();

            $this->order->setDatabase($this->app->db);

            if (!$this->order->load($this->id)) {
                throw new AdminNotFoundException(sprintf(
                    Store::_('An order with an id of ‘%d’ does not exist.'),
                    $this->id
                ));
            }

            $instance_id = $this->app->getInstanceId();
            if ($instance_id !== null) {
                $order_instance_id = ($this->order->instance === null) ?
                    null : $this->order->instance->id;

                if ($order_instance_id !== $instance_id) {
                    throw new AdminNotFoundException(sprintf(Store::_(
                        'Incorrect instance for order ‘%d’.'
                    ), $this->id));
                }
            }
        }

        return $this->order;
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $this->buildCancelledMessage();

        $details_frame = $this->ui->getWidget('details_frame');
        $details_frame->title = Store::_('Order');
        $details_frame->subtitle = $this->getOrderTitle();

        // set default time zone on each date field
        $view = $this->ui->getWidget('order_details');
        foreach ($view->getDescendants('SwatDateCellRenderer') as $renderer) {
            $renderer->display_time_zone = $this->app->default_time_zone;
        }

        $this->buildOrderDetails();
        $this->buildMessages();
        $this->buildToolBar();
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        if ($this->account !== null) {
            // use account navbar
            $this->navbar->popEntry();
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Customer Accounts'),
                'Account'
            ));

            $this->navbar->addEntry(new SwatNavBarEntry(
                $this->order->account->getFullname(),
                'Account/Details?id=' . $this->order->account->id
            ));

            $this->title = $this->order->account->getFullname();
        }

        $this->navbar->addEntry(new SwatNavBarEntry($this->getOrderTitle()));
    }

    protected function buildToolBar()
    {
        $toolbar = $this->ui->getWidget('details_toolbar');
        if ($this->account === null) {
            $toolbar->setToolLinkValues($this->id);
        } else {
            foreach ($toolbar->getToolLinks() as $tool_link) {
                $tool_link->link .= '&account=%s';
                $tool_link->value = [$this->id,
                    $this->order->account->id];
            }
        }

        if ($this->order->cancel_date instanceof SwatDate) {
            if ($this->ui->hasWidget('cancel_order_link')) {
                $this->ui->getWidget('cancel_order_link')->visible = false;
            }

            if ($this->ui->hasWidget('resend_confirmation_link')) {
                $link = $this->ui->getWidget('resend_confirmation_link');
                $link->sensitive = false;
                $link->tooltip = Store::_(
                    'Can not resend email confirmation for cancelled orders.'
                );
            }
        }
    }

    protected function buildCancelledMessage()
    {
        if ($this->order->cancel_date instanceof SwatDate) {
            $cancel_date = clone $this->order->cancel_date;
            $cancel_date->convertTZ($this->app->default_time_zone);
            $message = new SwatMessage(
                sprintf(
                    Store::_('This order was cancelled on %s.'),
                    $cancel_date->formatLikeIntl(Store::_('d MMMM, yyyy'))
                ),
                'warning'
            );

            $this->ui->getWidget('message_display')->add(
                $message,
                SwatMessageDisplay::DISMISS_OFF
            );
        }
    }

    protected function getOrderTitle()
    {
        return $this->order->getTitle();
    }

    abstract protected function buildOrderDetails();

    // finalize phase

    public function finalize()
    {
        parent::finalize();
        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/styles/store-order-details.css'
        );
    }
}
