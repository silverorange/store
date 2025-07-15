<?php

/**
 * Index page for Orders.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderIndex extends AdminSearch
{
    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->ui->loadFromXML($this->getSearchXml());
        $this->addAdditionalSearchFields(
            $this->getAdditionalSearchFieldsUiXmlFiles()
        );

        $this->ui->loadFromXML($this->getUiXml());

        if ($this->ui->hasWidget('search_region')) {
            $search_region = $this->ui->getWidget('search_region');
            $search_region->show_blank = true;
            $options = SwatDB::getOptionArray(
                $this->app->db,
                'Region',
                'title',
                'id',
                'title'
            );

            if (count($options) > 1) {
                $search_region->addOptionsByArray($options);
                $search_region->parent->visible = true;
            }
        }

        if ($this->app->getInstance() === null
            && $this->ui->hasWidget('search_instance')) {
            $search_instance = $this->ui->getWidget('search_instance');
            $search_instance->show_blank = true;
            $options = SwatDB::getOptionArray(
                $this->app->db,
                'Instance',
                'title',
                'id',
                'title'
            );

            if (count($options) > 1) {
                $search_instance->addOptionsByArray($options);
                $search_instance->parent->visible = true;
            }
        }

        // Set a default order on the table view. Default to id and not
        // createdate in case two createdates are the same.
        $index_view = $this->ui->getWidget('index_view');
        if ($index_view->hasColumn('id')) {
            $index_view->setDefaultOrderbyColumn(
                $index_view->getColumn('id'),
                SwatTableViewOrderableColumn::ORDER_BY_DIR_DESCENDING
            );
        }
    }

    protected function getSearchXml()
    {
        return __DIR__ . '/search.xml';
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    protected function addAdditionalSearchFields(array $ui_xml_files)
    {
        if ($this->ui->hasWidget('additional_search_fields')) {
            foreach ($ui_xml_files as $ui_xml) {
                $this->ui->loadFromXML(
                    $ui_xml,
                    $this->ui->getWidget('additional_search_fields')
                );
            }
        }
    }

    protected function getAdditionalSearchFieldsUiXmlFiles()
    {
        return [];
    }

    // process phase

    protected function processInternal()
    {
        parent::processInternal();

        if ($this->hasGetState()) {
            $this->loadGetState();
            $frame = $this->ui->getWidget('results_frame');
            $frame->visible = true;
        }

        $this->ui->getWidget('pager')->process();
    }

    protected function hasGetState()
    {
        return isset($_GET['has_comments']);
    }

    protected function loadGetState()
    {
        // make it possible to link to page with only orders having comments
        if (isset($_GET['has_comments'])) {
            $this->ui->getWidget('search_comments')->value =
                (mb_strtolower($_GET['has_comments']) == 'yes')
                    ? true
                    : false;
        }
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $view = $this->ui->getWidget('index_view');

        // set default time zone
        $date_column = $view->getColumn('createdate');
        $date_renderer = $date_column->getRendererByPosition();
        $date_renderer->display_time_zone = $this->app->default_time_zone;
        $date_renderer->time_zone_format = SwatDate::TZ_CURRENT_SHORT;

        if ($view->hasColumn('instance')
            && $this->ui->hasWidget('search_instance')) {
            $view->getColumn('instance')->visible =
                ($this->ui->getWidget('search_instance')->value === null)
                && $this->ui->getWidget('search_instance')->parent->visible;
        }

        if ($view->hasColumn('region')
            && $this->ui->hasWidget('search_region')) {
            $view->getColumn('region')->visible =
                ($this->ui->getWidget('search_region')->value === null)
                && $this->ui->getWidget('search_region')->parent->visible;
        }
    }

    protected function getWhereClause()
    {
        $where = '1=1';

        // Instance
        $instance_id = $this->app->getInstanceId();
        if ($instance_id === null && $this->ui->hasWidget('search_instance')) {
            $instance_id = $this->ui->getWidget('search_instance')->value;
        }

        if ($instance_id !== null) {
            $clause = new AdminSearchClause('integer:instance');
            $clause->table = 'Orders';
            $clause->value = $instance_id;
            $where .= $clause->getClause($this->app->db);
        }

        // Order #
        $clause = new AdminSearchClause('integer:id');
        $clause->table = 'Orders';
        $clause->value = $this->ui->getWidget('search_id')->value;
        $where .= $clause->getClause($this->app->db);

        // Order # Range gt
        $clause = new AdminSearchClause('integer:id');
        $clause->table = 'Orders';
        $clause->value = $this->ui->getWidget('search_id_gt')->value;
        $clause->operator = AdminSearchClause::OP_GT;
        $where .= $clause->getClause($this->app->db);

        // Order # Range lt
        $clause = new AdminSearchClause('integer:id');
        $clause->table = 'Orders';
        $clause->value = $this->ui->getWidget('search_id_lt')->value;
        $clause->operator = AdminSearchClause::OP_LT;
        $where .= $clause->getClause($this->app->db);

        // fullname, check accounts, and both order addresses
        $where .= $this->getFullnameWhereClause();

        // email, check accounts and order
        $where .= $this->getEmailWhereClause();

        // postal code, check billing and shipping addresses
        $postal_code = trim($this->ui->getWidget('search_postal_code')->value);
        if ($postal_code != '') {
            $where .= ' and (';

            $clause = new AdminSearchClause('postal_code');
            $clause->table = 'BillingAddress';
            $clause->value = $postal_code;
            $clause->operator = AdminSearchClause::OP_STARTS_WITH;
            $where .= $clause->getClause($this->app->db, '');

            $clause = new AdminSearchClause('postal_code');
            $clause->table = 'ShippingAddress';
            $clause->value = $postal_code;
            $clause->operator = AdminSearchClause::OP_STARTS_WITH;
            $where .= $clause->getClause($this->app->db, 'or');

            $where .= ')';
        }

        // date range gt
        if ($this->ui->getWidget('search_createdate_gt')->value !== null) {
            // clone so the date displayed will stay the same
            $date_gt =
                clone $this->ui->getWidget('search_createdate_gt')->value;

            $date_gt->setTZ($this->app->default_time_zone);
            $date_gt->toUTC();

            $clause = new AdminSearchClause('date:createdate');
            $clause->table = 'Orders';
            $clause->value = $date_gt->getDate();
            $clause->operator = AdminSearchClause::OP_GTE;
            $where .= $clause->getClause($this->app->db);
        }

        // date range lt
        if ($this->ui->getWidget('search_createdate_lt')->value !== null) {
            // clone so the date displayed will stay the same
            $date_lt =
                clone $this->ui->getWidget('search_createdate_lt')->value;

            $date_lt->setTZ($this->app->default_time_zone);
            $date_lt->toUTC();

            $clause = new AdminSearchClause('date:createdate');
            $clause->table = 'Orders';
            $clause->value = $date_lt->getDate();
            $clause->operator = AdminSearchClause::OP_LT;
            $where .= $clause->getClause($this->app->db);
        }

        if ($this->ui->getWidget('search_comments')->value) {
            $where .= ' and orders.comments is not null';
        }

        // Region
        $clause = new AdminSearchClause('integer:id');
        $clause->table = 'Region';
        $clause->value = $this->ui->getWidget('search_region')->value;
        $where .= $clause->getClause($this->app->db);

        return $where;
    }

    protected function getEmailWhereClause()
    {
        $where = '';

        // email, check accounts and order
        $email = trim($this->ui->getWidget('search_email')->value);
        if ($email != '') {
            $where .= ' and (';
            $clause = new AdminSearchClause('email');
            $clause->table = 'Account';
            $clause->value = $email;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->app->db, '');

            $clause = new AdminSearchClause('email');
            $clause->table = 'Orders';
            $clause->value = $email;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->app->db, 'or');
            $where .= ')';
        }

        return $where;
    }

    protected function getFullnameWhereClause()
    {
        $where = '';

        // fullname, check accounts, and both order addresses
        $fullname = trim($this->ui->getWidget('search_fullname')->value);
        if ($fullname != '') {
            $where .= ' and (';
            $clause = new AdminSearchClause('fullname');
            $clause->table = 'Account';
            $clause->value = $fullname;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->app->db, '');

            $clause = new AdminSearchClause('fullname');
            $clause->table = 'BillingAddress';
            $clause->value = $fullname;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->app->db, 'or');

            $clause = new AdminSearchClause('fullname');
            $clause->table = 'ShippingAddress';
            $clause->value = $fullname;
            $clause->operator = AdminSearchClause::OP_CONTAINS;
            $where .= $clause->getClause($this->app->db, 'or');
            $where .= ')';
        }

        return $where;
    }

    protected function getSelectClause()
    {
        $clause = 'Orders.id, Orders.total, Orders.createdate,
			Orders.locale, Orders.instance, Orders.notes,
			Orders.comments, Orders.billing_address, Orders.email,
			Orders.phone, Orders.account,
			(Orders.comments is not null and Orders.comments != %1$s)
				as has_comments,
			(Orders.notes is not null and Orders.notes != %1$s) as has_notes,
			Orders.cancel_date is not null as is_cancelled';

        return sprintf(
            $clause,
            $this->app->db->quote('', 'text')
        );
    }

    protected function getJoinClauses()
    {
        return 'left outer join Account on Orders.account = Account.id
			left outer join OrderAddress as BillingAddress
				on Orders.billing_address = BillingAddress.id
			left outer join OrderAddress as ShippingAddress
				on Orders.shipping_address = ShippingAddress.id
			inner join Locale on Orders.locale = Locale.id
			inner join Region on Locale.region = Region.id';
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        $sql = 'select count(Orders.id) from Orders %s where %s';

        $sql = sprintf(
            $sql,
            $this->getJoinClauses(),
            $this->getWhereClause()
        );

        $pager = $this->ui->getWidget('pager');
        $pager->total_records = SwatDB::queryOne($this->app->db, $sql);

        $orders = $this->getOrders(
            $view,
            $pager->page_size,
            $pager->current_record
        );

        if (count($orders) > 0) {
            $this->ui->getWidget('results_message')->content =
                $pager->getResultsMessage('result', 'results');
        }

        $class_name = SwatDBClassMap::get(StoreOrder::class);
        $store = new SwatTableStore();
        foreach ($orders as $row) {
            if ($row instanceof StoreOrder) {
                $order = $row;
            } else {
                $order = new $class_name($row);
                $order->setDatabase($this->app->db);
            }
            $store->add($this->getOrderDetailsStore($order, $row));
        }

        return $store;
    }

    protected function getOrderDetailsStore(StoreOrder $order, $row)
    {
        $ds = new SwatDetailsStore($order);

        $ds->fullname = $this->getOrderFullname($order);
        $ds->title = $this->getOrderTitle($order);
        $ds->has_comments = $row->has_comments;
        $ds->has_notes = $row->has_notes;
        $ds->is_cancelled = $row->is_cancelled;

        $ds->notes = sprintf(
            '<span class="order-notes">%s</span>',
            SwatString::minimizeEntities($order->notes)
        );

        $ds->comments = sprintf(
            '<span class="order-comments">%s</span>',
            SwatString::minimizeEntities($order->comments)
        );

        return $ds;
    }

    protected function getOrderFullname(StoreOrder $order)
    {
        $fullname = null;

        if ($order->account instanceof SiteAccount) {
            $fullname = $order->account->getFullname();
        } elseif ($order->billing_address instanceof StoreOrderAddress) {
            $fullname = $order->billing_address->getFullname();
        } elseif ($order->email != '') {
            $fullname = $order->email;
        } elseif ($order->phone != '') {
            $fullname = $order->phone;
        }

        return $fullname;
    }

    protected function getOrders($view, $limit, $offset)
    {
        $sql = 'select %s
				from Orders
				%s
				where %s
				order by %s';

        $sql = sprintf(
            $sql,
            $this->getSelectClause(),
            $this->getJoinClauses(),
            $this->getWhereClause(),
            $this->getOrderByClause($view, 'Orders.id desc')
        );

        $this->app->db->setLimit($limit, $offset);

        return SwatDB::query($this->app->db, $sql);
    }

    protected function getOrderTitle($order)
    {
        return sprintf(Store::_('Order %s'), $order->id);
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/styles/store-order-index.css'
        );
    }
}
