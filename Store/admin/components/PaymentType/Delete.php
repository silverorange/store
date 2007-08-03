<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for payment types
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('text');
		
		$sql = sprintf('delete from PaymentType where id in (%s)
			and id not in (select payment_type from OrderPaymentMethod)
			and id not in (select payment_type from AccountPaymentMethod)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One payment method has been deleted.',
			'%d payment methods have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Store::_('payment type'), Store::_('payment types'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'PaymentType', 'integer:id', null, 'text:title', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		// dependent order payment methods
		$dep_orders = new AdminSummaryDependency();
		$dep_orders->setTitle(
			Store::_('order payment method'),
			Store::_('order payment methods'));

		$dep_orders->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'OrderPaymentMethod', 'integer:id',
			'integer:payment_type', 'payment_type in ('.$item_list.')',
			AdminDependency::NODELETE);

		$dep->addDependency($dep_orders);
		
		// dependent account payment methods
		$dep_account = new AdminSummaryDependency();
		$dep_account->setTitle(
			Store::_('account payment method'),
			Store::_('account payment methods'));

		$dep_account->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'AccountPaymentMethod', 'integer:id',
			'integer:payment_type', 'payment_type in ('.$item_list.')',
			AdminDependency::NODELETE);

		$dep->addDependency($dep_account);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
