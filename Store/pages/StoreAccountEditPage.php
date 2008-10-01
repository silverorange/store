<?php

require_once 'Site/pages/SiteAccountEditPage.php';

/**
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEditPage extends SiteAccountEditPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/account-edit.xml';
	}

	// }}}

	// process phase
	// {{{ protected function updateAccount()

	protected function updateAccount(SwatForm $form)
	{
		parent::updateAccount($form);
		$this->assignUiValuesToObject($this->account, array(
			'phone',
			'company',
		));
	}

	// }}}

	// build phase
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		parent::load($form);
		$this->assignObjectValuesToUi($this->account, array(
			'phone',
			'company',
		));
	}

	// }}}
}

?>
