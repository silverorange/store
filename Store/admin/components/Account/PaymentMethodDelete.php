<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * Delete confirmation page for Account Payment Methods
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodDelete extends AdminDBDelete
{
	// {{{ private properties

	private $account_id;
	private $account_fullname;

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();
		$this->buildAccount();

		$item_list = $this->getItemList('integer');

		$sql = sprintf('delete from AccountPaymentMethod where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One payment method for %s has been deleted.',
			'%d payment methods for %s have been deleted.', $num),
			SwatString::numberFormat($num), $this->account_fullname),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildAccount();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('account', $this->account_id);

		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->account_fullname,
			sprintf('Account/Details?id=%s', $this->account_id)));

		$this->navbar->createEntry(Store::_('Payment Method Delete'));

		$this->title = $this->account_fullname;

		$item_list = $this->getItemList('integer');
		$num = $this->getItemCount();

		$dep = new AdminListDependency();

		$fullname = $this->account_fullname;
		$singular = sprintf(Store::_('payment method for %s'), $fullname);
		$plural = sprintf(Store::_('payment methods for %s'), $fullname);
		$dep->setTitle($singular, $plural);

		$sql = sprintf('select * from AccountPaymentMethod where id in (%s)',
			$item_list);

		$methods = SwatDB::query($this->app->db, $sql,
			'StoreAccountPaymentMethodWrapper');

		$entries = array();
		$title = '%s<br />%s<br />%s<br />%s';

		foreach ($methods as $method) {
			$entry = new AdminDependencyEntry();
			$entry->id = $method->id;
			$entry->status_level = AdminDependency::DELETE;
			$entry->content_type = 'text/xml';
			ob_start();
			$method->display();
			$entry->title = ob_get_clean();
			$entries[] = $entry;
		}

		$dep->entries = &$entries;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ private function buildAccount()

	private function buildAccount()
	{
		$item_list = $this->getItemList('integer');

		$row = SwatDB::queryRow($this->app->db, 
			sprintf('select Account.id, fullname from Account
				inner join AccountPaymentMethod
					on Account.id = AccountPaymentMethod.account
				where AccountPaymentMethod.id in (%s)',
				$item_list));

		$this->account_id = $row->id;
		$this->account_fullname = $row->fullname;
	}

	// }}}
}

?>
