<?php

/**
 * Displays sales split by region for a year.
 *
 * @copyright 2015-2023 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSalesByRegionReportDetails extends AdminIndex
{
    /**
     * The starting date for this report.
     *
     * @var SwatDate
     */
    protected $start_date;

    /**
     * @var bool
     */
    protected $show_shipping = false;

    /**
     * @var bool
     */
    protected $show_tax = false;

    /**
     * @var StoreCountryWrapper
     */
    protected $detail_countries;

    /**
     * @var StoreSalesByRegionTaxationStartDate
     */
    protected $taxation_start_date;

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $id = SiteApplication::initVar('id');

        if (preg_match('/^[12][0-9]{3}$/', $id) !== 1) {
            throw new AdminNotFoundException(
                sprintf(
                    'Unable to load report with id of “%s”',
                    $id
                )
            );
        }

        $this->start_date = new SwatDate('now', $this->app->default_time_zone);
        $this->start_date->setTime(0, 0, 0);
        $this->start_date->toUTC();
        if ($this->start_date->setDate($id, 1, 1) === false) {
            throw new AdminNotFoundException(
                sprintf(
                    'Unable to load report with id of “%s”',
                    $id
                )
            );
        }

        $this->initTaxationStartDate();
        if ($this->start_date->getYear() <
            $this->taxation_start_date->getDate()->getYear()) {
            throw new AdminNotFoundException(
                sprintf(
                    'Unable to load report for a year prior to “%s”',
                    $this->taxation_start_date->getDate()->getYear()
                )
            );
        }

        $this->ui->loadFromXML($this->getUiXml());

        $this->ui->getWidget('tax_note_message_display')->add(
            $this->taxation_start_date->getWarningMessage(),
            SwatMessageDisplay::DISMISS_OFF
        );
    }

    protected function initTaxationStartDate()
    {
        $this->taxation_start_date = new StoreSalesByRegionTaxationStartDate(
            $this->app
        );
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $title_pattern = $this->taxation_start_date->getTitlePatternFromDate(
            $this->start_date
        );

        $report_title = sprintf(
            $title_pattern,
            $this->start_date->formatLikeIntl(Store::_('YYYY'))
        );

        // set frame title
        $index_frame = $this->ui->getWidget('index_frame');
        $index_frame->subtitle = $report_title;

        // and navbar entry
        $this->layout->navbar->createEntry($report_title);

        $view = $this->ui->getWidget('index_view');
        $view->getColumn('shipping')->visible = $this->show_shipping;
        $view->getColumn('tax')->visible = $this->show_tax;

        $view->getGroup('country')->getRenderer('shipping_total')->visible =
            $this->show_shipping;

        $view->getGroup('country')->getRenderer('tax_total')->visible =
            $this->show_shipping;
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $end_date = clone $this->start_date;
        $end_date->setDate(
            $this->start_date->getYear() + 1,
            $this->start_date->getMonth(),
            $this->start_date->getDay()
        );
        $end_date->toUTC();

        $sql = sprintf(
            'select sum(Orders.total) as gross_total,
				sum(Orders.shipping_total) as shipping_total,
				sum(Orders.tax_total) as tax_total,
				Country.title as country_title,
				Country.id as country_id
			from Orders
				left outer join OrderAddress
					on Orders.billing_address = OrderAddress.id
				left outer join Country on OrderAddress.Country = Country.id
			where Orders.createdate >= %1$s
				and Orders.createdate < %2$s
				and Orders.createdate >= %3$s
				and Orders.cancel_date is null
				and Orders.total > 0
				%4$s
			group by Country.id, Country.title
			order by Country.title nulls first',
            $this->app->db->quote($this->start_date->getDate(), 'date'),
            $this->app->db->quote($end_date->getDate(), 'date'),
            $this->app->db->quote(
                $this->taxation_start_date->getDate(),
                'date'
            ),
            $this->getInstanceWhereClause()
        );

        $store = new SwatTableStore();

        $rs = SwatDB::query(
            $this->app->db,
            $sql
        );

        $detail_countries = $this->getDetailCountries();

        foreach ($rs as $row) {
            if (isset($detail_countries[$row->country_id])) {
                $provstate_model = $this->getProvStateModel($row->country_id);
                foreach ($provstate_model as $provstate_row) {
                    $ds = new SwatDetailsStore($provstate_row);
                    $ds->country_gross_total = $row->gross_total;
                    $ds->country_shipping_total = $row->shipping_total;
                    $ds->country_tax_total = $row->tax_total;
                    $ds->country_group = $row->country_title;

                    if ($provstate_row->provstate_title === null) {
                        $ds->region_title = Store::_('Unknown');
                    } else {
                        $ds->region_title = $provstate_row->provstate_title;
                    }

                    $store->add($ds);
                }
            }
        }

        $other_gross_total = 0;
        $other_shipping_total = 0;
        $other_tax_total = 0;
        foreach ($rs as $row) {
            if (!isset($detail_countries[$row->country_id])) {
                $other_gross_total += $row->gross_total;
                $other_shipping_total += $row->shipping_total;
                $other_tax_total += $row->tax_total;
            }
        }

        foreach ($rs as $row) {
            if (!isset($detail_countries[$row->country_id])) {
                $ds = new SwatDetailsStore($row);

                $ds->country_group = Store::_('Other');
                $ds->country_gross_total = $other_gross_total;
                $ds->country_shipping_total = $other_shipping_total;
                $ds->country_tax_total = $other_tax_total;

                if ($row->country_title === null) {
                    $ds->region_title = Store::_('Unknown');
                } else {
                    $ds->region_title = $row->country_title;
                }

                $store->add($ds);
            }
        }

        return $store;
    }

    protected function getProvStateModel($country_id)
    {
        $end_date = clone $this->start_date;
        $end_date->setDate(
            $this->start_date->getYear() + 1,
            $this->start_date->getMonth(),
            $this->start_date->getDay()
        );
        $end_date->toUTC();

        $sql = sprintf(
            'select sum(Orders.total) as gross_total,
				sum(Orders.shipping_total) as shipping_total,
				sum(Orders.tax_total) as tax_total,
				Country.title as country_title,
				Country.id as country_id,
				ProvState.title as provstate_title
			from Orders
			inner join OrderAddress
				on Orders.billing_address = OrderAddress.id
			inner join Country on OrderAddress.country = Country.id
			left outer join ProvState
				on OrderAddress.provstate = ProvState.id
			where Country.id = %4$s
				and Orders.createdate >= %1$s
				and Orders.createdate < %2$s
				and Orders.cancel_date is null
				and Orders.total > 0
				%3$s
			group by Country.id, Country.title, ProvState.title
			order by Country.title, ProvState.title nulls first',
            $this->app->db->quote($this->start_date->getDate(), 'date'),
            $this->app->db->quote($end_date->getDate(), 'date'),
            $this->getInstanceWhereClause(),
            $this->app->db->quote($country_id, 'text')
        );

        return SwatDB::query(
            $this->app->db,
            $sql
        );
    }

    protected function getInstanceWhereClause()
    {
        if ($this->app->isMultipleInstanceAdmin()) {
            return '';
        }

        $instance_id = $this->app->getInstanceId();

        return sprintf(
            'and Orders.instance %s %s',
            SwatDB::equalityOperator($instance_id),
            $this->app->db->quote($instance_id, 'integer')
        );
    }

    protected function getDetailCountries()
    {
        if (!$this->detail_countries instanceof StoreCountryWrapper) {
            $this->detail_countries = SwatDB::query(
                $this->app->db,
                sprintf(
                    'select * from Country
					where %s
					order by Country.title',
                    $this->getDetailCountriesWhereClause()
                ),
                SwatDBClassMap::get('StoreCountryWrapper')
            );
        }

        return $this->detail_countries;
    }

    protected function getDetailCountriesWhereClause()
    {
        return 'id in (select country from ProvState)';
    }
}
