<?php

/**
 * Delete confirmation page for item minimum quantity groups.
 *
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemMinimumQuantityGroupDelete extends AdminDBDelete
{
    // process phase
    // {{{ protected function processDBData()

    protected function processDBData(): void
    {
        parent::processDBData();

        $item_list = $this->getItemList('integer');

        $sql = sprintf(
            'delete from ItemMinimumQuantityGroup where id in (%s)',
            $item_list
        );

        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(
            sprintf(
                Store::ngettext(
                    'One item minimum quantity sale group has been deleted.',
                    '%s item minimum quantity sale groups have been deleted.',
                    $num
                ),
                SwatString::numberFormat($num)
            ),
            'notice'
        );

        $this->app->messages->add($message);
    }

    // }}}

    // build phase
    // {{{ protected function buildInternal()

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(
            Store::_('minimum quantity sale group'),
            Store::_('minimum quantity sale groups')
        );

        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'ItemMinimumQuantityGroup',
            'integer:id',
            null,
            'text:title',
            'id',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }

    // }}}
}
