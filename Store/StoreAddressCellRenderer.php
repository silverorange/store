<?php

/**
 * A cell renderer for rendering address objects.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddressCellRenderer extends SwatCellRenderer
{
    // {{{ public properties

    /**
     * The store address object to render.
     *
     * @var StoreAddress
     */
    public $address;

    /**
     * Whether or not to display the address in condensed format or not. If
     * false, call the normal display.
     *
     * @var bool
     */
    public $condensed = true;

    // }}}
    // {{{ public function render()

    /**
     * Renders an address.
     */
    public function render()
    {
        if (!$this->visible) {
            return;
        }

        parent::render();

        if ($this->address instanceof StoreAddress) {
            if ($this->condensed) {
                $this->address->displayCondensed();
            } else {
                $this->address->display();
            }
        }
    }

    // }}}
}
