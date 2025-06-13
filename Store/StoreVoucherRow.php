<?php

/**
 * Displays voucher totals and remove buttons.
 *
 * @copyright 2006-2016 silverorange
 */
class StoreVoucherRow extends StoreTotalRow
{
    // {{{ public function __construct()

    protected SwatButton $remove_button;

    public function __construct()
    {
        parent::__construct();

        $this->show_free = false;

        $this->remove_button = new SwatButton();
        $this->remove_button->parent = $this;
        $this->remove_button->title = Store::_('Remove');
        $this->remove_button->classes[] = 'compact-button';
        $this->remove_button->classes[] = 'store-remove';
    }

    // }}}
    // {{{ public function init()

    public function init()
    {
        parent::init();

        $this->remove_button->init();
    }

    // }}}
    // {{{ public function process()

    public function process()
    {
        parent::process();

        $this->remove_button->process();
    }

    // }}}
    // {{{ public function hasBeenClicked()

    public function hasBeenClicked()
    {
        return $this->remove_button->hasBeenClicked();
    }

    // }}}
    // {{{ protected function displayTitle()

    protected function displayTitle()
    {
        $this->remove_button->display();
        parent::displayTitle();
    }

    // }}}
    // {{{ protected function displayValue()

    protected function displayValue()
    {
        if ($this->locale !== null) {
            $this->money_cell_renderer->locale = $this->locale;
        }

        // display
        $this->money_cell_renderer->value = -$this->value;
        $this->money_cell_renderer->render();
    }

    // }}}
}
