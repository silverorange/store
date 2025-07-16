<?php

/**
 * Front page of checkout.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutFrontPage extends StoreCheckoutPage
{
    protected function getUiXml()
    {
        return __DIR__ . '/checkout-front.xml';
    }

    protected function getNextSource()
    {
        return $this->getCheckoutSource() . '/first';
    }

    // init phase

    public function init()
    {
        // skip the checkout front page if logged in
        if ($this->app->session->isLoggedIn()) {
            $this->initDataObjects();
            $this->resetProgress();
            $this->updateProgress();
            $this->relocate();
        }

        parent::init();
    }

    /**
     * Subclassed to avoid loading xml from a form that doesn't exist.
     */
    protected function loadUI()
    {
        $this->ui = new SwatUI();
        $this->ui->loadFromXML($this->getUiXml());
    }

    protected function initInternal()
    {
        foreach ($this->ui->getRoot()->getDescendants('SwatForm') as $form) {
            $form->action = $this->source;
        }
    }

    // process phase

    protected function processInternal()
    {
        parent::processInternal();

        $new_form = $this->ui->getWidget('new_form');
        $login_form = $this->ui->getWidget('login_form');

        if ($new_form->isProcessed()) {
            $this->processNewForm($new_form);
        }

        if ($login_form->isProcessed()) {
            $this->processLoginForm($login_form);
        }
    }

    protected function processNewForm($form)
    {
        $this->initDataObjects();
        $this->resetProgress();
        $this->updateProgress();

        $email = $this->ui->getWidget('new_email_address');
        $this->app->session->checkout_email = $email->value;

        $this->relocate();
    }

    protected function processLoginForm($form)
    {
        if (!$form->hasMessage()) {
            $email = $this->ui->getWidget('login_email_address')->value;
            $password = $this->ui->getWidget('login_password')->value;

            if ($this->app->session->login($email, $password)) {
                $this->initDataObjects();
                $this->resetProgress();
                $this->updateProgress();
                $this->relocate();
            } else {
                $message = new SwatMessage(
                    Store::_(
                        'The email or password you entered is not correct'
                    ),
                    'warning'
                );

                $tips = [
                    Store::_('Please check the spelling on your email ' .
                        'address or password'),
                    sprintf(Store::_('Password is case-sensitive. Make sure ' .
                        'your %sCaps Lock%s key is off'), '<kbd>', '</kbd>'),
                ];
                $message->secondary_content =
                    vsprintf('<ul><li>%s</li><li>%s</li></ul>', $tips);

                $message->content_type = 'text/xml';

                $this->ui->getWidget('message_display')->add($message);
            }
        }
    }

    protected function relocate()
    {
        $this->app->relocate($this->getNextSource());
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $this->buildMessages();
        $this->buildForgotPasswordLink();
    }

    protected function buildMessages()
    {
        $message_display = $this->ui->getWidget('message_display');
        foreach ($this->app->messages->getAll() as $message) {
            $message_display->add($message);
        }
    }

    protected function buildForgotPasswordLink()
    {
        $block = $this->ui->getWidget('forgot_password');

        $block->content = $this->getForgotPasswordLink();
        $block->content_type = 'text/xml';
    }

    protected function getForgotPasswordLink()
    {
        $email = $this->ui->getWidget('login_email_address');

        $link = sprintf(
            Store::_(' %sForgot your password?%s'),
            '<a href="account/forgotpassword%s" class="forgot-password-link">',
            '</a>'
        );

        if ((!$email->hasMessage()) && ($email->value != '')) {
            $link_value = sprintf('?email=%s', urlencode($email->value));
        } else {
            $link_value = null;
        }

        return sprintf($link, $link_value);
    }
}
