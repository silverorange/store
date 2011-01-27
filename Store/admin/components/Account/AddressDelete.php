<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Delete confirmation page for Account Addresses
 *
 * @package   Store
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountAddressDelete extends AdminDBDelete
{
	// {{{ private properties

	private $account;
	private $account_id;

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		// we can't do this in init, as its a replace page
		$this->buildAccount();

		$item_list = $this->getItemList('integer');
		$sql = sprintf('delete from AccountAddress where id in (%s)',
			$item_list);

		$locale = SwatI18NLocale::get();

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(
			sprintf(
				Store::ngettext(
					'One address for %2$s has been deleted.',
					'%1$s addresses for %2$s have been deleted.',
					$num
				),
				SwatString::numberFormat($num),
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
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$num = $this->getItemCount();

		$dep = new AdminListDependency();

		$fullname = $this->account->getFullName();
		$singular = sprintf(Store::_('address for %s'), $fullname);
		$plural = sprintf(Store::_('addresses for %s'), $fullname);
		$dep->setTitle($singular, $plural);

		$sql = 'select * from AccountAddress where id in (%s)
			order by createdate desc';

		$sql = sprintf($sql, $item_list);
		$addresses = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreAccountAddressWrapper'));

		$entries = array();
		foreach ($addresses as $address) {
			$entry = new AdminDependencyEntry();
			$entry->id = $address->id;
			$entry->status_level = AdminDependency::DELETE;
			$entry->content_type = 'text/xml';
			ob_start();
			$address->display();
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
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->account->getFullName(),
			sprintf('Account/Details?id=%s', $this->account->id)));

		$this->navbar->createEntry(Store::_('Address Delete'));

		$this->title = $this->account->getFullname();
	}

	// }}}
	// {{{ protected function buildAccount()

	protected function buildAccount()
	{
		$item_list = $this->getItemList('integer');
		$this->account_id = SwatDB::queryOne($this->app->db,
			sprintf('select account from AccountAddress where id in (%s)',
				$item_list));

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
