<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Delete confirmation page for Account Payment Methods
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodDelete extends AdminDBDelete
{
	// {{{ private properties

	private $account_id;
	private $account;

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		// we can't do this in init, as its a replace page
		$this->buildAccount();

		$item_list = $this->getItemList('integer');
		$sql = sprintf(
			'delete from AccountPaymentMethod where id in (%s)',
			$item_list
		);

		$locale = SwatI18NLocale::get();

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(
			sprintf(
				Store::ngettext(
					'One payment method for %2$s has been deleted.',
					'%1$s payment method for %2$s have been deleted.',
					$num
				),
				$locale->formatNumber($num),
				$this->account->getFullName()
			)
		);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		// we don't want the fancy relocate to index thats in AdminDBDelete.
		AdminConfirmation::relocate();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		// we can't do this in init, as its a replace page, and it has to happen
		// before buildInternal due to the navbar
		$this->buildAccount();
		parent::build();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('account', $this->account_id);

		$fullname = $this->account->getFullName();

		$this->navbar->popEntry();
		$this->navbar->addEntry(
			new SwatNavBarEntry(
				$fullname,
				sprintf(
					'Account/Details?id=%s',
					$this->account_id
				)
			)
		);

		$this->navbar->createEntry(Store::_('Payment Method Delete'));

		$this->title = $fullname;

		$item_list = $this->getItemList('integer');
		$num = $this->getItemCount();

		$dep = new AdminListDependency();

		$singular = sprintf(Store::_('payment method for %s'), $fullname);
		$plural = sprintf(Store::_('payment methods for %s'), $fullname);
		$dep->setTitle($singular, $plural);

		$sql = sprintf('select * from AccountPaymentMethod where id in (%s)',
			$item_list);

		$methods = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreAccountPaymentMethodWrapper'));

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
	// {{{ protected function buildAccount()

	protected function buildAccount()
	{
		$item_list = $this->getItemList('integer');
		$this->account_id = SwatDB::queryOne(
			$this->app->db,
			sprintf(
				'select account from AccountPaymentMethod where id in (%s)',
				$item_list
			)
		);

		$class_name = SwatDBClassMap::get('StoreAccount');
		$this->account = new $class_name();
		$this->account->setDatabase($this->app->db);

		if ($this->account_id !== null) {
			if (!$this->account->load($this->account_id))
				throw new AdminNotFoundException(
					sprintf(Store::_('Account with id “%s” not found.'),
						$this->account_id));
		}
	}

	// }}}
}

?>
