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
	// {{{ private function getAdditionalFields()

	private function getAdditionalFields()
	{
		$fields = array();

		if ($this->ui->hasWidget('phone'))
			$fields[] = 'phone';

		if ($this->ui->hasWidget('company'))
			$fields[] = 'company';

		return $fields;
	}

	// }}}

	// process phase
	// {{{ protected function updateAccount()

	protected function updateAccount(SwatForm $form)
	{
		parent::updateAccount($form);

		$fields = $this->getAdditionalFields();

		if (count($fields) > 0)
			$this->assignUiValuesToObject($this->account, $fields);
	}

	// }}}

	// build phase
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		parent::load($form);

		$fields = $this->getAdditionalFields();

		if (count($fields) > 0)
			$this->assignObjectValuesToUi($this->account, $fields);
	}

	// }}}
}

?>
