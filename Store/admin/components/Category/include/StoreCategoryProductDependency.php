<?php

/**
 * A dependency for products.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryProductDependency extends AdminSummaryDependency
{
    protected function getDependencyText($count)
    {
        $message = Store::ngettext(
            'contains %s product',
            'contains %s products',
            $count
        );

        return sprintf($message, SwatString::numberFormat($count));
    }
}
