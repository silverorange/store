<?php

/**
 * Index page for payment types.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeIndex extends AdminIndex
{
    private $regions;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML(__DIR__ . '/index.xml');
        $this->regions = SwatDB::getOptionArray(
            $this->app->db,
            'Region',
            'title',
            'id'
        );
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $num = count($view->getSelection());
        $message = null;

        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('PaymentType/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;

            case 'enable':
                $region = $this->ui->getWidget('enable_region')->value;
                $region_list = ($region > 0) ?
                    [$region] : array_flip($this->regions);

                $insert_sql = sprintf(
                    'insert into PaymentTypeRegionBinding
				(payment_type, region)
				select %%1$s, id from region where id in (%s) and
					id not in (select region from PaymentTypeRegionBinding
						where payment_type = %%1$s)',
                    $this->app->db->datatype->implodeArray(
                        $region_list,
                        'integer'
                    )
                );

                foreach ($view->getSelection() as $payment_type_id) {
                    $sql = sprintf(
                        $insert_sql,
                        $this->app->db->quote($payment_type_id, 'integer')
                    );

                    SwatDB::exec($this->app->db, $sql);
                }

                $num = count($view->getSelection());
                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'One payment type has been enabled.',
                        '%s payment types have been enabled.',
                        $num
                    ),
                    SwatString::numberFormat($num)
                ));

                break;

            case 'disable':
                $region = $this->ui->getWidget('disable_region')->value;

                $region_where_clause = ($region > 0) ?
                    sprintf(
                        'region = %s and',
                        $this->app->db->quote($region, 'integer')
                    ) : '';

                $delete_sql = sprintf(
                    'delete from PaymentTypeRegionBinding
				where %s payment_type = %%s',
                    $region_where_clause
                );

                foreach ($view->getSelection() as $payment_type_id) {
                    $sql = sprintf(
                        $delete_sql,
                        $this->app->db->quote($payment_type_id, 'integer')
                    );

                    SwatDB::exec($this->app->db, $sql);
                }

                $num = count($view->getSelection());
                $message = new SwatMessage(sprintf(
                    Store::ngettext(
                        'One payment type has been disabled.',
                        '%s payment types have been disabled.',
                        $num
                    ),
                    SwatString::numberFormat($num)
                ));

                break;
        }

        if ($message !== null) {
            $this->app->messages->add($message);
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        // setup the flydowns for enabled/disabled actions
        $regions = $this->regions;
        $regions[0] = Store::_('All Regions');

        $this->ui->getWidget('enable_region')->addOptionsByArray($regions);
        $this->ui->getWidget('disable_region')->addOptionsByArray($regions);
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            'select id, title, shortname
				from PaymentType order by %s',
            $this->getOrderByClause($view, 'displayorder, title')
        );

        $rs = SwatDB::query($this->app->db, $sql);

        $view = $this->ui->getWidget('index_view');
        $view->getColumn('status')->getRendererByPosition()->db =
            $this->app->db;

        return $rs;
    }
}
