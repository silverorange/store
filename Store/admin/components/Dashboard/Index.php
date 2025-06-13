<?php

/**
 * Front-page dashboard.
 *
 * @copyright 2012-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreDashboardIndex extends AdminIndex
{
    /**
     * @var array
     */
    protected $new_content = [];

    /**
     * @var array
     */
    protected $new_content_notes = [];

    // init phase

    protected function initInternal()
    {
        $this->ui->loadFromXML($this->getUiXml());
        $this->navbar->popEntry();

        if ($this->app->session->user->hasAccessByShortname('Account')) {
            $this->initSuspiciousAccounts();
        }

        parent::initInternal();
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    protected function initSuspiciousAccounts()
    {
        $account_count = $this->getSuspiciousAccountCount();
        if ($account_count > 0) {
            $locale = SwatI18NLocale::get();
            $message = new SwatMessage(sprintf(
                Store::ngettext(
                    'One Suspicious Account This Week',
                    '%s Suspicious Accounts This Week',
                    $account_count
                ),
                $locale->formatNumber($account_count)
            ), SwatMessage::WARNING);

            $message->content_type = 'text/xml';
            $message->secondary_content =
                $this->getSuspiciousAccountLink($account_count);

            $this->ui->getWidget('message_display')->add(
                $message,
                SwatMessageDisplay::DISMISS_OFF
            );
        }
    }

    protected function getSuspiciousAccountCount()
    {
        $sql = 'select count(Account.id) from Account
				inner join SuspiciousAccountView on
					SuspiciousAccountView.account = Account.id';

        return SwatDB::queryOne($this->app->db, $sql);
    }

    protected function getSuspiciousAccountLink($count)
    {
        $a_tag = new SwatHtmlTag('a');
        $a_tag->href = 'Account/Suspicious';
        $a_tag->setContent(Store::_('See Details') . ' ›');

        return $a_tag;
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        if ($this->app->session->user->hasAccessByShortname('Order')) {
            $this->buildOrders();
        } else {
            $this->ui->getWidget('order_stats_frame')->visible = false;
        }

        $this->ui->getWidget('new_content_frame')->visible =
            $this->isNewContentFrameVisible();
    }

    protected function isNewContentFrameVisible()
    {
        return $this->app->session->user->hasAccessByShortname('Order');
    }

    protected function buildOrders()
    {
        $view_all_orders = $this->ui->getWidget('view_all_orders');
        $view_all_orders->link = 'Order?has_comments=yes';

        $this->ui->getWidget('order_chart')->setApplication($this->app);
        if ($this->app->getInstance() !== null) {
            $this->ui->getWidget('order_chart')->setInstance(
                $this->app->getInstance()
            );
        }
    }

    protected function getTableModel(SwatView $view): ?SwatTableStore
    {
        switch ($view->id) {
            case 'new_content_view':
                return $this->getNewContentTableModel($view);
        }

        return null;
    }

    // new content table

    protected function getNewContentTableModel(SwatView $view): SwatTableStore
    {
        $this->buildNewContentData();
        $this->buildNewContentNote();
        uasort($this->new_content, [$this, 'sortNewContent']);

        $store = new SwatTableStore();
        foreach ($this->new_content as $content) {
            $date = $content['date'];
            $date->convertTZ($this->app->default_time_zone);

            $ds = new SwatDetailsStore();
            $ds->date = $date;
            $ds->date_formatted = $date->format(SwatDate::DF_DATE);
            $ds->content = $content['content'];
            $ds->rating = $content['rating'];

            if ($content['icon'] !== null) {
                $ds->content = '<span class="' . $content['icon'] . '"></span>' .
                    $ds->content;
            }

            $store->add($ds);
        }

        return $store;
    }

    protected function buildNewContentData()
    {
        if ($this->app->session->user->hasAccessByShortname('Order')) {
            $this->buildOrdersNewContentData();
            $this->ui->getWidget('view_all_orders')->visible = true;
            $this->new_content_notes[] = Store::_('orders with comments');
        }
    }

    protected function buildNewContentNote()
    {
        $note = $this->ui->getWidget('new_content_note');
        $count = count($this->new_content_notes);
        if ($count === 0) {
            $note->visible = false;
        } elseif ($count < 4) {
            $note->content = sprintf(
                Store::_('Showing %s.'),
                SwatString::toList($this->new_content_notes)
            );
        } else {
            ob_start();
            echo SwatString::minimizeEntities(Store::_('Showing:'));
            echo '<ul>';
            foreach ($this->new_content_notes as $note_part) {
                $li_tag = new SwatHtmlTag('li');
                $li_tag->setContent($note_part);
                $li_tag->display();
            }
            echo '</ul>';
            $note->content = ob_get_clean();
            $note->content_type = 'text/xml';
        }
    }

    protected function buildOrdersNewContentData()
    {
        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $date = new SwatDate($order->createdate);

            $content = sprintf(
                '<div><a href="Order/Details?id=%s">Order #%s</a>
				 by <a href="mailto:%s">%s</a><p>%s</p></div>',
                $order->id,
                $order->id,
                SwatString::minimizeEntities($order->email),
                SwatString::minimizeEntities($order->email),
                SwatString::minimizeEntities($order->comments)
            );

            $this->addNewContent($date, $content, null, 'product');
        }
    }

    protected function addNewContent(
        SwatDate $date,
        $content,
        $rating = null,
        $icon = null
    ) {
        $this->new_content[] = [
            'date'    => $date,
            'content' => $content,
            'rating'  => $rating,
            'icon'    => $icon,
        ];
    }

    protected function sortNewContent($a, $b)
    {
        return SwatDate::compare($b['date'], $a['date']);
    }

    protected function getNewContentCutoffDate()
    {
        $date = new SwatDate();
        $date->addDays(-7);

        return $date;
    }

    protected function getOrders()
    {
        $date = $this->getNewContentCutoffDate();
        $date->toUTC();

        $sql = sprintf(
            'select Orders.*
			from Orders
			where Orders.createdate >= %s
				and %s
				and %s
			order by Orders.createdate desc',
            $this->app->db->quote($date->getDate(), 'date'),
            $this->getOrdersWhereClause(),
            $this->getInstanceWhereClause()
        );

        $orders = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get('StoreOrderWrapper')
        );

        $account_sql = 'select * from Account where id in (%s)';
        $accounts = $orders->loadAllSubDataObjects(
            'account',
            $this->app->db,
            $account_sql,
            SwatDBClassMap::get('SiteAccountWrapper'),
            'integer'
        );

        return $orders;
    }

    protected function getOrdersWhereClause()
    {
        return 'Orders.comments is not null';
    }

    protected function getInstanceWhereClause()
    {
        if ($this->app->isMultipleInstanceAdmin()) {
            return '1 = 1';
        }

        $instance_id = $this->app->getInstanceId();

        return sprintf(
            'Orders.instance %s %s',
            SwatDB::equalityOperator($instance_id),
            $this->app->db->quote($instance_id, 'integer')
        );
    }

    // finalize phase

    public function finalize()
    {
        parent::finalize();
        $this->layout->addHtmlHeadEntry(
            'packages/store/admin/styles/store-dashboard.css'
        );
    }
}
