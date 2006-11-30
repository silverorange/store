<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Delete confirmation page for Account Addresses
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountAddressDelete extends AdminDBDelete
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
		
		$sql = sprintf('delete from AccountAddress where id in (%s)',
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One address for %s has been deleted.',
			'%d addresses for %s have been deleted.', $num),
			SwatString::numberFormat($num), $this->account_fullname),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
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

		$this->buildNavBar();

		$item_list = $this->getItemList('integer');
		$num = $this->getItemCount();

		$dep = new AdminListDependency();

		$fullname = $this->account_fullname;
		$singular = sprintf(Store::_('address for %s'), $fullname);
		$plural = sprintf(Store::_('addresses for %s'), $fullname);
		$dep->setTitle($singular, $plural);

		$rs = SwatDB::query($this->app->db,
			'select AccountAddress.id, fullname, line1, line2, city,
				ProvState.abbreviation as provstate, Country.title as country,
				postal_code
			from AccountAddress
				inner join ProvState 
					on ProvState.id = AccountAddress.provstate
				inner join Country 
					on Country.id = AccountAddress.country
			where AccountAddress.id in ('.$item_list.')
			order by createdate desc');

		$entries = array();
		$title_one_line =
			'<address>%s<br />%s<br />%s, %s<br/>%s<br />%s</address>';

		$title_two_lines =
			'<address>%s<br />%s<br />%s<br />%s, %s<br/>%s<br />%s</address>';

		foreach ($rs as $row) {
			$entry = new AdminDependencyEntry();
			$entry->id = $row->id;
			$entry->status_level = AdminDependency::DELETE;
			$entry->content_type = 'text/xml';
			$entry->title = ($row->line2 === null) ?
				sprintf($title_one_line,
					SwatString::minimizeEntities($row->fullname),
					SwatString::minimizeEntities($row->line1),
					SwatString::minimizeEntities($row->city),
					SwatString::minimizeEntities($row->provstate),
					SwatString::minimizeEntities($row->country),
					SwatString::minimizeEntities($row->postal_code)) :
				sprintf($title_two_lines,
					SwatString::minimizeEntities($row->fullname),
					SwatString::minimizeEntities($row->line1),
					SwatString::minimizeEntities($row->line2),
					SwatString::minimizeEntities($row->city),
					SwatString::minimizeEntities($row->provstate),
					SwatString::minimizeEntities($row->country),
					SwatString::minimizeEntities($row->postal_code));

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
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->account_fullname,
			sprintf('Account/Details?id=%s', $this->account_id)));

		$this->navbar->createEntry(Store::_('Address Delete'));

		$this->title = $this->account_fullname;
	}

	// }}}
	// {{{ private function buildAccount()

	private function buildAccount()
	{
		$item_list = $this->getItemList('integer');

		$row = SwatDB::queryRow($this->app->db, 
			sprintf('select Account.id, Account.fullname from Account
				inner join AccountAddress
					on Account.id = AccountAddress.account
				where AccountAddress.id in (%s)',
				$item_list));
		
		$this->account_id = $row->id;
		$this->account_fullname = $row->fullname;
	}

	// }}}
}

?>
