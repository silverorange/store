<?php

require_once 'Swat/SwatYesNoFlydown.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Site/dataobjects/SiteAccountWrapper.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';
require_once 'Store/dataobjects/StoreProductReviewWrapper.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/admin/StoreOrderChart.php';

/**
 * Front-page dashboard
 *
 * @package   Store
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreDashboardIndex extends AdminIndex
{
	// {{{ protected properties

	protected $new_content = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML($this->getUiXml());
		$this->navbar->popEntry();

		$this->initSuspiciousAccounts();

		parent::initInternal();
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/Dashboard/index.xml';
	}

	// }}}
	// {{{ protected function initSuspiciousAccounts()

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

			$this->ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);
		}
	}

	// }}}
	// {{{ protected function getSuspiciousAccountCount()

	protected function getSuspiciousAccountCount()
	{
		$sql = 'select count(Account.id) from Account
				inner join SuspiciousAccountView on
					SuspiciousAccountView.account = Account.id';

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getSuspiciousAccountLink()

	protected function getSuspiciousAccountLink($count)
	{
		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = 'Account/Suspicious';
		$a_tag->setContent(Store::_('See Details').' ›');
		return $a_tag;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->app->session->user->hasAccessByShortname('Order')) {
			$this->buildOrders();
		} else {
			$this->ui->getWidget('order_stats_frame')->visible = false;
		}
	}

	// }}}
	// {{{ protected function buildOrders()

	protected function buildOrders()
	{
		$view_all_orders = $this->ui->getWidget('view_all_orders');
		$view_all_orders->link = 'Order?has_comments=yes';

		$this->ui->getWidget('order_chart')->setApplication($this->app);
		if ($this->app->getInstance() !== null) {
			$this->ui->getWidget('order_chart')->setInstance(
				$this->app->getInstance());
		}
	}

	// }}}
	// {{{ protected function getOrdersChart()

	protected function getOrdersChart()
	{
		$now = new SwatDate();
		$now->convertTZ($this->app->default_time_zone);

		$orders_this_year = $this->getOrdersByDay($now->getYear());
		$data_this_year = implode(', ',
			$this->getChartData($orders_this_year, true));

		$now->addYears(-1);
		$orders_last_year = $this->getOrdersByDay($now->getYear());
		$data_last_year = implode(', ',
			$this->getChartData($orders_last_year));

		?>
		var chart_data = [];

		chart_data.push({
			data: [<?= $data_this_year ?>],
			lines: { lineWidth: 2 },
			color: 'rgb(52, 101, 164)'
		});

		chart_data.push({
			data: [<?= $data_last_year ?>],
			lines: { lineWidth: 1 },
			color: 'rgb(152, 201, 255)',
			shadowSize: 0
		});

		var chart = new StoreDashboardOrders('order_stats', chart_data);
		<?

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function getOrdersByDay()

	protected function getOrdersByDay($year)
	{
		$sql = sprintf('select sum(item_total) as total,
			date_part(\'doy\', convertTZ(createdate, %1$s)) as doy
			from orders
			where date_part(\'year\', convertTZ(createdate, %1$s)) = %2$s
			group by date_part(\'doy\', convertTZ(createdate, %1$s))',
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($year, 'integer'));

		$orders = SwatDB::query($this->app->db, $sql);
		$return = array();
		foreach ($orders as $order) {
			$return[$order->doy] = $order->total;
		}

		return $return;
	}

	// }}}
	// {{{ protected function getChartData()

	protected function getChartData($orders, $remove_current_week = false)
	{
		$now = new SwatDate();
		$now->convertTZ($this->app->default_time_zone);
		$doy = $now->getDayOfYear();

		$data = array();

		$sum = 0;
		for ($i = 0; $i < 365; $i++) {
			if ($i > 0 && $i % 7 == 0 && $sum > 0 &&
				(!$remove_current_week || $i < $doy)) {
				$data[] = sprintf('[%d, %f]',
					$i - 7, $sum);

				$sum = 0;
			}

			if (isset($orders[$i])) {
				$sum += $orders[$i];
			}
		}

		return $data;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'new_content_view':
			return $this->getNewContentTableModel($view);
		}
	}

	// }}}

	// new content table
	// {{{ protected function getNewContentTableModel()

	protected function getNewContentTableModel(SwatView $view)
	{
		$this->buildNewContentData();
		uasort($this->new_content, array($this, 'sortNewContent'));

		$store = new SwatTableStore();
		foreach ($this->new_content as $content) {
			$date = $content['date'];
			$date->convertTZ($this->app->default_time_zone);

			$ds = new SwatDetailsStore();
			$ds->date           = $date;
			$ds->date_formatted = $date->format(SwatDate::DF_DATE);
			$ds->content        = $content['content'];
			$ds->rating         = $content['rating'];

			if ($content['icon'] !== null) {
				$ds->content = '<span class="'.$content['icon'].'"></span>'.
					$ds->content;
			}

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function buildNewContentData()

	protected function buildNewContentData()
	{
		if ($this->app->session->user->hasAccessByShortname('Order')) {
			$this->buildOrdersNewContentData();
		}

		if ($this->app->session->user->hasAccessByShortname('ProductReview')) {
			$this->buildProductReviewsNewContentData();
		}
	}

	// }}}
	// {{{ protected function buildOrdersNewContentData()

	protected function buildOrdersNewContentData()
	{
		$orders = $this->getOrders();

		foreach ($orders as $order) {
			$date = new SwatDate($order->createdate);

			$content = sprintf('<div><a href="Order/Details?id=%s">Order #%s</a>
				 by <a href="mailto:%s">%s</a><p>%s</p></div>',
				$order->id,
				$order->id,
				SwatString::minimizeEntities($order->email),
			SwatString::minimizeEntities($order->email),
			SwatString::minimizeEntities($order->comments));

			$this->addNewContent($date, $content, null, 'product');
		}
	}

	// }}}
	// {{{ protected function buildProductReviewsNewContentData()

	protected function buildProductReviewsNewContentData()
	{
		$reviews = $this->getProductReviews();

		foreach ($reviews as $review) {
			$date = new SwatDate($review->createdate);

			if ($review->status == StoreProductReview::STATUS_PENDING) {
				$link = 'ProductReview/Approval?id='.$review->id;
			} else {
				$link = 'ProductReview/Edit?id='.$review->id;
			}

			$content = sprintf('<div><a href="%s">Product review by %s</a>
				 of <a href="Product/Details?id=%s">%s</a><p>%s</p></div>',
				$link,
				SwatString::minimizeEntities($review->fullname),
				$review->product->id,
				SwatString::minimizeEntities($review->product->title),
				SwatString::minimizeEntities($review->bodytext));

			$this->addNewContent($date, $content, $review->rating, 'edit');
		}
	}

	// }}}
	// {{{ protected function addNewContent()

	protected function addNewContent(SwatDate $date, $content, $rating = null,
		$icon = null)
	{
		$this->new_content[] = array(
			'date'    => $date,
			'content' => $content,
			'rating'  => $rating,
			'icon'    => $icon,
		);
	}

	// }}}
	// {{{ protected function sortNewContent()

	protected function sortNewContent($a, $b)
	{
		return SwatDate::compare($b['date'], $a['date']);
	}

	// }}}
	// {{{ protected function getNewContentCutoffDate()

	protected function getNewContentCutoffDate()
	{
		$date = new SwatDate();
		$date->addDays(-7);
		return $date;
	}

	// }}}
	// {{{ protected function getOrders()

	protected function getOrders()
	{
		$date = $this->getNewContentCutoffDate();
		$date->toUTC();

		$sql = sprintf('select Orders.*
			from Orders
			where Orders.createdate >= %s
				and %s 
			order by Orders.createdate desc',
			$this->app->db->quote($date->getDate(), 'date'),
			$this->getOrdersWhereClause());

		$orders = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreOrderWrapper'));

		$account_sql = 'select * from Account where id in (%s)';
		$accounts = $orders->loadAllSubDataObjects('account',
			$this->app->db, $account_sql,
			SwatDBClassMap::get('SiteAccountWrapper'), 'integer');

		return $orders;
	}

	// }}}
	// {{{ protected function getOrdersWhereClause()

	protected function getOrdersWhereClause()
	{
		return 'Orders.comments is not null';
	}

	// }}}
	// {{{ protected function getProductReviews()

	protected function getProductReviews()
	{
		$date = $this->getNewContentCutoffDate();
		$date->toUTC();

		$sql = sprintf('select ProductReview.*
			from ProductReview
			where ProductReview.createdate >= %s
				and ProductReview.author_review = %s
			order by ProductReview.createdate desc',
			$this->app->db->quote($date->getDate(), 'date'),
			$this->app->db->quote(false, 'boolean'));

		$reviews = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreProductReviewWrapper'));

		$product_sql = 'select * from Product where id in (%s)';
		$reviews->loadAllSubDataObjects('product',
			$this->app->db, $product_sql,
			SwatDBClassMap::get('StoreProductWrapper'), 'integer');

		return $reviews;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/admin/styles/store-dashboard.css'),
			Store::PACKAGE_ID);
	}

	// }}}
}

?>
