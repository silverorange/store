<?php

/**
 * @copyright 2009-2016 silverorange
 */
class StoreShippingTypeDetails extends AdminIndex
{
    /**
     * @var StoreShippingType
     */
    protected $shipping_type;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getUiXml());
        $this->initShippingType();
    }

    private function initShippingType()
    {
        $id = SiteApplication::initVar('id');
        $class_name = SwatDBClassMap::get(StoreShippingType::class);
        $this->shipping_type = new $class_name();
        $this->shipping_type->setDatabase($this->app->db);

        if (!$this->shipping_type->load($id)) {
            throw new AdminNotFoundException(
                sprintf(
                    Store::_('Shipping Type with id ‘%s’ not found.'),
                    $id
                )
            );
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('ShippingType/RateDelete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $ds = new SwatDetailsStore($this->shipping_type);
        $this->ui->getWidget('details_view')->data = $ds;
        $this->ui->getWidget('rate_toolbar')->setToolLinkValues(
            $this->shipping_type->id
        );

        $this->ui->getWidget('details_toolbar')->setToolLinkValues(
            $this->shipping_type->id
        );
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->navbar->addEntry(new SwatNavBarEntry(Store::_('Details')));
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = sprintf(
            'select * from ShippingRate where shipping_type = %s
			order by region, threshold',
            $this->app->db->quote($this->shipping_type->id, 'integer')
        );

        $rows = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(StoreShippingRateWrapper::class)
        );

        $store = new SwatTableStore();
        foreach ($rows as $row) {
            $ds = new SwatDetailsStore($row);
            $store->add($ds);
        }

        return $store;
    }
}
