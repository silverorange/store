<?php

/**
 * Page to resend the confirmation email for an order.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderEmailConfirmation extends AdminConfirmation
{
    // {{{ protected properties

    protected $id;
    protected $order;

    /**
     * If we came from an account page, this is the id of the account.
     * Otherwise it is null.
     *
     * @var int
     */
    protected $account;

    // }}}

    // init phase
    // {{{ protected function initInternal()

    protected function initInternal()
    {
        $this->ui->loadFromXML($this->getUiXml());

        $this->id = SiteApplication::initVar('id');
        $this->account = SiteApplication::initVar('account');

        $this->getOrder();
    }

    // }}}
    // {{{ protected function getOrder()

    protected function getOrder()
    {
        if ($this->order === null) {
            $order_class = SwatDBClassMap::get('StoreOrder');
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

    // }}}

    // process phase
    // {{{ protected function processResponse()

    protected function processResponse(): void
    {
        $form = $this->ui->getWidget('confirmation_form');

        if ($form->button->id == 'yes_button') {
            $this->sendOrderConfirmation();

            $cc = ($this->order->cc_email !== null) ?
                ' and cc’d to ' . $this->order->cc_email : '';

            $message = new SwatMessage(
                sprintf(
                    Store::_(
                        'A confirmation of %s has been emailed to %s%s.'
                    ),
                    $this->getOrderTitle(),
                    $this->order->getConfirmationEmailAddress(),
                    $cc
                )
            );

            $this->app->messages->add($message);
        }
    }

    // }}}
    // {{{ protected function sendOrderConfirmation()

    protected function sendOrderConfirmation()
    {
        $this->order->sendConfirmationEmail($this->app);
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        $form = $this->ui->getWidget('confirmation_form');
        $form->addHiddenField('id', $this->id);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $this->getConfirmationMessage();
        $message->content_type = 'text/xml';

        $this->ui->getWidget('yes_button')->title =
            Store::_('Resend Confirmation');
    }

    // }}}
    // {{{ protected function getConfirmationMessage()

    protected function getConfirmationMessage()
    {
        ob_start();
        $confirmation_title = new SwatHtmlTag('h3');

        $confirmation_title->setContent(
            sprintf(
                Store::_('Are you sure you want to resend the ' .
                'order confirmation email for %s?'),
                $this->getOrderTitle()
            )
        );

        $confirmation_title->display();

        $email_address = $this->order->getConfirmationEmailAddress();

        $email_anchor = new SwatHtmlTag('a');
        $email_anchor->href = sprintf('mailto:%s', $email_address);
        $email_anchor->setContent($email_address);

        printf(
            Store::_('A confirmation of %s will be sent to '),
            $this->getOrderTitle()
        );

        $email_anchor->display();

        if ($this->order->cc_email !== null) {
            $email_anchor = new SwatHtmlTag('a');
            $email_anchor->href = sprintf('mailto:%s', $this->order->cc_email);
            $email_anchor->setContent($this->order->cc_email);

            echo Store::_(' and cc’d to ');
            $email_anchor->display();
        }

        echo '.';

        return ob_get_clean();
    }

    // }}}
    // {{{ protected function buildNavBar()

    protected function buildNavBar()
    {
        parent::buildNavBar();

        if ($this->account === null) {
            $this->navbar->createEntry(
                $this->getOrderTitle(),
                sprintf('Order/Details?id=%s', $this->id)
            );
        } else {
            // use account navbar
            $this->navbar->popEntry();
            $this->navbar->addEntry(new SwatNavBarEntry(
                Store::_('Customer Accounts'),
                'Account'
            ));

            $this->navbar->addEntry(new SwatNavBarEntry(
                $this->order->account->getFullname(),
                'Account/Details?id=' . $this->order->account
            ));

            $this->title = $this->order->account->fullname;

            $this->navbar->createEntry(
                $this->getOrderTitle(),
                sprintf(
                    'Order/Details?id=%s&account=%s',
                    $this->id,
                    $this->account
                )
            );
        }

        $this->navbar->createEntry(Store::_('Resend Confirmation Email'));
    }

    // }}}
    // {{{ protected function getOrderTitle()

    protected function getOrderTitle()
    {
        return sprintf(Store::_('Order %s'), $this->order->id);
    }

    // }}}
}
