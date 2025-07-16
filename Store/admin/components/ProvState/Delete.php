<?php

/**
 * Delete confirmation page for ProvStates.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProvStateDelete extends AdminDBDelete
{
    // process phase

    protected function processDBData(): void
    {
        parent::processDBData();

        $sql = $this->getProcessSQL();
        $item_list = $this->getItemList('integer');
        $sql = sprintf($sql, $item_list);
        $num = SwatDB::exec($this->app->db, $sql);

        $message = new SwatMessage(
            sprintf(
                Store::ngettext(
                    'One province or state has been deleted.',
                    '%s provinces and/or states have been deleted.',
                    $num
                ),
                SwatString::numberFormat($num)
            ),
            'notice'
        );

        $this->app->messages->add($message);
    }

    protected function getProcessSQL()
    {
        return 'delete from ProvState where id in (%s)
			and id not in
			(select provstate from OrderAddress where provstate is not null)';
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $item_list = $this->getItemList('integer');

        $dep = new AdminListDependency();
        $dep->setTitle(
            Store::_('province or state'),
            Store::_('provinces or states')
        );

        $dep->entries = AdminListDependency::queryEntries(
            $this->app->db,
            'ProvState',
            'id',
            null,
            'text:title',
            'title',
            'id in (' . $item_list . ')',
            AdminDependency::DELETE
        );

        $this->getDependencies($dep, $item_list);

        $message = $this->ui->getWidget('confirmation_message');
        $message->content = $dep->getMessage();
        $message->content_type = 'text/xml';

        if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0) {
            $this->switchToCancelButton();
        }
    }

    protected function getDependencies($dep, $item_list)
    {
        // dependent orders
        $dep_orders = new AdminSummaryDependency();
        $dep_orders->setTitle(
            Store::_('order address'),
            Store::_('order addresses')
        );

        $dep_orders->summaries = AdminSummaryDependency::querySummaries(
            $this->app->db,
            'OrderAddress',
            'integer:id',
            'integer:provstate',
            'provstate in (' . $item_list . ')',
            AdminDependency::NODELETE
        );

        $dep->addDependency($dep_orders);
    }
}
