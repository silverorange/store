<?php

require_once 'Site/admin/components/Account/Edit.php';

/**
 * Edit page for Accounts
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEdit extends SiteAccountEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Account/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Store', 'Store');

		parent::initInternal();

		$this->fields[] = 'phone';
		$this->fields[] = 'company';
	}

	// }}}

	// process phase
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		$values = parent::getUIValues();
		$values = array_merge($values,
			$this->ui->getValues(array('phone', 'company')));

		return $values;
	}

	// }}}
}

?>
