<?php

/**
 * A dependency for items.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductItemDependency extends AdminSummaryDependency
{
    protected function getDependencyText($count)
    {
        $message = Store::ngettext(
            'contains one item',
            'contains %s items',
            $count
        );

        return sprintf($message, SwatString::numberFormat($count));
    }
}
