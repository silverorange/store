<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatYUI.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDate.php';

/*
 * Order chart
 *
 * @package   Store
 * @copyright 2012-2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderChart extends SwatControl
{
	// {{{ public properties

	public $width = '100%';
	public $height = '250px';

	// }}}
	// {{{ private properties

	/**
	 * SiteApplication
	 *
	 * @var SiteApplication
	 */
	private $app;

	private $instance = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new calendar
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$this->addStyleSheet(
			'packages/store/admin/styles/store-order-chart.css'
		);

		$this->addJavaScript(
			'packages/store/admin/javascript/jquery-1.8.3.min.js'
		);

		$this->addJavaScript(
			'packages/store/admin/javascript/jquery.flot.js'
		);

		$this->addJavaScript(
			'packages/store/admin/javascript/store-order-chart.js'
		);
	}

	// }}}
	// {{{ public function setApplication()

	public function setApplication(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function setInstance()

	public function setInstance(SiteInstance $instance)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this calendar widget
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		if (!$this->app instanceof SiteApplication) {
			throw new SwatException('Application must be set');
		}

		$container_div_tag = new SwatHtmlTag('div');
		$container_div_tag->id = $this->id;
		$container_div_tag->class = $this->getCSSClassString();
		$container_div_tag->style = sprintf('width: %s; height: %s;',
			$this->width, $this->height);

		$container_div_tag->open();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());

		$container_div_tag->close();

		$this->displayLegend();
	}

	// }}}
	// {{{ protected function displayLegend()

	protected function displayLegend()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id.'_legend';
		$div_tag->class = 'order-legend clearfix';
		$div_tag->open();
		$div_tag->close();
	}

	// }}}
	// {{{ protected function getOrdersByDay()

	protected function getOrdersByDay($year)
	{
		$where_clause = '1 = 1';

		if ($this->instance !== null) {
			$where_clause.= sprintf(' and Orders.instance = %s',
				$this->app->db->quote($this->instance->id, 'integer'));
		}

		$sql = sprintf('select sum(OrderCommissionTotalView.commission_total)
				as total,
				date_part(\'doy\', convertTZ(createdate, %1$s)) as doy
			from Orders
			inner join OrderCommissionTotalView on
				OrderCommissionTotalView.ordernum = Orders.id
			where date_part(\'year\', convertTZ(createdate, %1$s)) = %2$s
				and cancel_date is null
				and %3$s
			group by date_part(\'doy\', convertTZ(createdate, %1$s))',
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($year, 'integer'),
			$where_clause);

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
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets inline calendar JavaScript
	 */
	protected function getInlineJavaScript()
	{
		$now = new SwatDate();
		$now->convertTZ($this->app->default_time_zone);

		$this_year = $now->getYear();
		$orders_this_year = $this->getOrdersByDay($this_year);
		$data_this_year = implode(', ',
			$this->getChartData($orders_this_year, true));

		$now->addYears(-1);
		$last_year = $now->getYear();
		$orders_last_year = $this->getOrdersByDay($last_year);

		ob_start();
		echo 'var chart_data = [];';

		if (count($orders_last_year)) {
			$data_last_year = implode(', ',
				$this->getChartData($orders_last_year));

			printf(
				"chart_data.push({
					data: [%s],
					lines: { lineWidth: 1 },
					color: 'rgb(152, 201, 255)',
					shadowSize: 0,
					label: '%s'
				});",
				$data_last_year,
				$last_year
			);
		}

		printf(
			"chart_data.push({
				data: [%s],
				lines: { lineWidth: 2 },
				color: 'rgb(52, 101, 164)',
				label: '%s'
			});

			var chart = new StoreOrderChart('%s', chart_data);",
			$data_this_year,
			$this_year,
			$this->id
		);

		return ob_get_clean();
	}

	// }}}
}

?>
