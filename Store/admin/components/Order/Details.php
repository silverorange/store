<?php

require_once 'Admin/AdminTableStore.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';

require_once 'Store/StoreTotalRow.php';
require_once 'Store/StoreShippingAddressCellRenderer.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Details page for Orders
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreOrderDetails extends AdminPage
{
	// {{{ protected properties

	protected $id;
	protected $order;

	/**
	 * If we came from an account page, this is the id of the account.
	 * Otherwise it is null.
	 *
	 * @var integer
	 */
	protected $account;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Order/details.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->ui_xml);
		$this->ui->getRoot()->addStyleSheet('styles/order-details.css', 1);

		$this->id = SiteApplication::initVar('id');
		$this->account = SiteApplication::initVar('account');

		$this->getOrder();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	public function buildInternal() 
	{
		parent::buildInternal();

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Store::_('Order');
		$details_frame->subtitle = $this->getOrderTitle();

		// set default time zone
		$date_field =
			$this->ui->getWidget('order_details')->getField('createdate');

		$date_renderer = $date_field->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		$this->buildOrderDetails();
		$this->buildMessages();
		$this->buildToolBar();
		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function getOrder()

	protected function getOrder() 
	{
		if ($this->order === null) {
			$class_map = StoreClassMap::instance();
			$order = $class_map->resolveClass('StoreOrder');
			$this->order = new $order();

			$this->order->setDatabase($this->app->db);

			if (!$this->order->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Store::_('An order with an id of %d '.
						'does not exist.'),
						$this->id));
		}
		return $this->order;
	}

	// }}}
	// {{{ protected function getOrderTitle()

	protected function getOrderTitle() 
	{
		return sprintf(Store::_('Order %s'), $this->order->id);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar() 
	{
		if ($this->account !== null) {
			// use account navbar
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Customer Accounts'),
				'Account'));

			$this->navbar->addEntry(new SwatNavBarEntry(
				$this->order->account->fullname, 
				'Account/Details?id='.$this->order->account->id));

			$this->title = $this->order->account->fullname;
		}

		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->getOrderTitle()));
	}

	// }}}
	// {{{ protected function buildToolBar()

	protected function buildToolBar()
	{
		$toolbar = $this->ui->getWidget('details_toolbar');
		if ($this->account === null) {
			$toolbar->setToolLinkValues($this->id);
		} else {
			foreach ($toolbar->getToolLinks() as $tool_link) {
				$tool_link->link.= '&account=%s';
				$tool_link->value = array($this->id,
					$this->order->account->id);
			}
		}

	}

	// }}}
	// {{{ protected abstract function buildOrderDetails()

	protected abstract function buildOrderDetails();

	// }}}
}

?>
