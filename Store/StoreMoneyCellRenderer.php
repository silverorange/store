<?php

/**
 * Money cell renderer that displays n/a when no value is available.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreMoneyCellRenderer extends SwatMoneyCellRenderer
{
    public function __construct()
    {
        parent::__construct();

        $this->null_display_value = Store::_('n/a');

        $this->addStyleSheet('packages/swat/styles/swat.css');
    }
}
