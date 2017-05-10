<?php


/**
 * Edit page for Accounts
 *
 * @package   Store
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEdit extends SiteAccountEdit
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Store', 'Store');

		parent::initInternal();
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/admin/components/Account/edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function updateAccount()

	protected function updateAccount()
	{
		parent::updateAccount();

		$this->account->phone = $this->ui->getWidget('phone')->value;
		$this->account->company = $this->ui->getWidget('company')->value;
	}

	// }}}
}

?>
