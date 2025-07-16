<?php

/**
 * Edit page for notes on orders.
 *
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderNoteEdit extends AdminDBEdit
{
    /**
     * @var StoreOrder
     */
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
        $this->account = SiteApplication::initVar('account');
        $this->ui->loadFromXML(__DIR__ . '/note-edit.xml');
        $this->order = $this->getOrder();
    }

    protected function getOrder()
    {
        if ($this->order === null) {
            $order_class = SwatDBClassMap::get(StoreOrder::class);
            $this->order = new $order_class();

            $this->order->setDatabase($this->app->db);

            if (!$this->order->load($this->id)) {
                throw new AdminNotFoundException(sprintf(
                    Store::_('An order with an id of “%s” does not exist.'),
                    $this->id
                ));
            }

            $instance_id = $this->app->getInstanceId();
            if ($instance_id !== null) {
                $order_instance_id = ($this->order->instance === null) ?
                    null : $this->order->instance->id;

                if ($order_instance_id !== $instance_id) {
                    throw new AdminNotFoundException(sprintf(Store::_(
                        'Incorrect instance for order “%s”.'
                    ), $this->id));
                }
            }
        }

        return $this->order;
    }

    // process phase

    protected function saveDBData(): void
    {
        $values = $this->ui->getValues(['notes']);

        $this->order->notes = $values['notes'];

        $this->order->save();

        $this->app->messages->add($this->getSaveMessage());
    }

    protected function getSaveMessage()
    {
        return new SwatMessage(Store::_('Note has been saved.'));
    }

    // build phase

    protected function loadDBData()
    {
        $this->ui->setValues($this->order->getAttributes());
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        if ($this->account !== null) {
            // use account navbar
            $this->navbar->popEntries(2);
            $this->navbar->createEntry(
                Store::_('Customer Accounts'),
                'Account'
            );

            $this->navbar->createEntry(
                $this->order->account->getFullname(),
                'Account/Details?id=' . $this->order->account->id
            );

            $this->title = $this->order->account->getFullname();
        } else {
            $this->navbar->popEntry();
        }

        $this->navbar->createEntry(
            $this->getOrderTitle(),
            'Order/Details?id=' . $this->order->id
        );

        $this->navbar->createEntry(Store::_('Edit Administrative Note'));
    }

    protected function getOrderTitle()
    {
        return sprintf(Store::_('Order %s'), $this->order->id);
    }
}
