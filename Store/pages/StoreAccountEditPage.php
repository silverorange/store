<?php

require_once 'Site/pages/SiteAccountEditPage.php';

/**
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEditPage extends SiteAccountEditPage
{
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->ui_xml = dirname(__FILE__).'/account-edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function updateAccount()

	protected function updateAccount(StoreAccount $account)
	{
		parent::updateAccount($account);

		$account->phone = $this->ui->getWidget('phone')->value;
		$account->company = $this->ui->getWidget('company')->value;
	}

	// }}}

	// build phase
	// {{{ protected function setWidgetValues()

	protected function setWidgetValues(StoreAccount $account)
	{
		parent::setWidgetValues($account);

		$this->ui->getWidget('phone')->value = $account->phone;
		$this->ui->getWidget('company')->value = $account->company;
	}

	// }}}
}

?>
