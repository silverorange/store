<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreLocaleWrapper.php';
require_once 'Date.php';

/**
 * Edit page for Invoices
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreInvoiceEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;
	protected $ui_xml = 'Store/admin/components/Invoice/edit.xml';

	// }}}
	// {{{ private properties

	private $account_id;
	private $account_fullname;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		
		$this->fields = array('text:locale', 'comments',
			'float:shipping_total', 'float:tax_total');

		$this->initAccount();
		
		$locale_flydown = $this->ui->getWidget('locale');
		$locale_flydown->show_blank = false;

		$class_map = StoreClassMap::instance();
		$locale_wrapper = $class_map->resolveClass('StoreLocaleWrapper');

		$locales = SwatDB::query($this->app->db, 'select * from Locale', $locale_wrapper);

		foreach ($locales as $locale)
			$locale_flydown->addOption($locale->id, $locale->getTitle());
	}

	// }}}
	// {{{ protected function initAccount()

	protected function initAccount() 
	{
		if ($this->id === null)
			$this->account_id = $this->app->initVar('account');
		else
			$this->account_id = SwatDB::queryOne($this->app->db,
				sprintf('select account from Invoice where id = %s',
				$this->app->db->quote($this->id, 'integer')));

		$this->account_fullname = SwatDB::queryOne($this->app->db,
			sprintf('select fullname from Account where id = %s',
			$this->app->db->quote($this->account_id, 'integer')));
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->getUIValues();

		if ($this->id === null) {
			$this->fields[] = 'integer:account';
			$values['account'] = $this->account_id;


			$this->fields[] = 'date:createdate';
			$date = new Date();
			$date->toUTC();
			$values['createdate'] = $date->getDate();
					
			$this->id = SwatDB::insertRow($this->app->db, 'Invoice',
				$this->fields, $values, 'id');
		} else {
			SwatDB::updateRow($this->app->db, 'Invoice', $this->fields, $values,
				'id', $this->id);
		}

		$message = new SwatMessage(sprintf(Store::_('Invoice %s has been saved.'),
			$this->id));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array('locale', 'comments',
			'shipping_total', 'tax_total'));
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$button = $this->ui->getWidget('submit_continue_button');
		
		if ($button->hasBeenClicked()) {
			// manage skus
			$this->app->relocate(
				$this->app->getBaseHref().'Invoice/Details?id='.$this->id);
		} else {
			parent::relocate();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('account', $this->account_id);
	}

	// }}}
	// {{{ protected buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$last_entry = $this->navbar->popEntry();
		$last_entry->title = sprintf(Store::_('%s Invoice'),
			$last_entry->title);

		$this->navbar->replaceEntryByPosition(1,
			new SwatNavBarEntry(Store::_('Customer Accounts'), 'Account'));

		$this->navbar->addEntry(new SwatNavBarEntry($this->account_fullname,
			sprintf('Account/Details?id=%s', $this->account_id)));

		$this->navbar->addEntry($last_entry);
		
		$this->title = $this->account_fullname;
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db, 'Invoice',
			$this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('An invoice with an id of ‘%d’ does not exist.'),
				$this->id));

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}

?>
