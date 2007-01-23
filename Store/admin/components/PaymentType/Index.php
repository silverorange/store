<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for payment types
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeIndex extends AdminIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->checked_items);
		$message = null;

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('PaymentType/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;

		case 'enable':
			SwatDB::updateColumn($this->app->db, 'PaymentType', 
				'boolean:enabled', true, 'id', $view->checked_items);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One payment type has been enabled.',
				'%d payment types have been enabled.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'disable':
			SwatDB::updateColumn($this->app->db, 'PaymentType', 
				'boolean:enabled', false, 'id', $view->checked_items);

			$message = new SwatMessage(sprintf(Store::ngettext(
				'One payment type has been disabled.',
				'%d payment types have been disabled.', $num),
				SwatString::numberFormat($num)));
		}
		
		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$sql = sprintf('select id, title, enabled, shortname
				from PaymentType order by %s',
			$this->getOrderByClause($view, 'displayorder, title'));

		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		return $store;
	}

	// }}}
}	

?>
