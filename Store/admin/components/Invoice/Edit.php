<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreLocaleWrapper.php';
require_once 'Swat/SwatDate.php';

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

	protected $ui_xml = 'Store/admin/components/Invoice/edit.xml';
	protected $invoice;
	protected $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
	
		$this->initAccount();
		$this->initInvoice();

		$locale_flydown = $this->ui->getWidget('locale');
		$locale_flydown->show_blank = false;

		$class_map = SwatDBClassMap::instance();
		$locale_wrapper = $class_map->resolveClass('StoreLocaleWrapper');

		$locales = SwatDB::query($this->app->db, 'select * from Locale',
			$locale_wrapper);

		foreach ($locales as $locale)
			$locale_flydown->addOption($locale->id, $locale->getTitle());
	}

	// }}}
	// {{{ protected function initInvoice()

	protected function initInvoice()
	{
		$class_map = SwatDBClassMap::instance();
		$class = $class_map->resolveClass('StoreInvoice');
		$invoice = new $class();
		$invoice->setDatabase($this->app->db);

		if ($this->id === null) {
			$invoice->account = $this->account;
			$invoice->createdate = new SwatDate();
			$invoice->createdate->toUTC();
		} else {
			if (!$invoice->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Store::_('Invoice with id ‘%s’ not found.'),
					$this->id));
			}
		}

		$this->invoice = $invoice;
	}

	// }}}
	// {{{ protected function initAccount()

	protected function initAccount() 
	{
		if ($this->id === null) 
			$account_id = $this->app->initVar('account');
		else
			$account_id = SwatDB::queryOne($this->app->db, sprintf(
				'select account from Invoice where id = %s',
				$this->app->db->quote($this->id, 'integer')));

		$class_map = SwatDBClassMap::instance();
		$class = $class_map->resolveClass('StoreAccount');
		$account = new $class();
		$account->setDatabase($this->app->db);
		if (!$account->load($account_id))
			throw new AdminNotFoundException(sprintf(
				Store::_('Account with id ‘%s’ not found.'),
				$this->id));

		$this->account = $account;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array('locale', 'comments',
			'shipping_total', 'tax_total'));

		$this->invoice->comments = $values['comments'];
		$this->invoice->locale = $values['locale'];
		$this->invoice->shipping_total = $values['shipping_total'];
		$this->invoice->tax_total = $values['tax_total'];
		$this->invoice->save();

		$message = new SwatMessage(sprintf(
			Store::_('Invoice %s has been saved.'),
			$this->invoice->id));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(
			sprintf('Invoice/Details?id=%s', $this->invoice->id));
	}

	// }}}

	// build phase
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('account', $this->account->id);
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

		$this->navbar->addEntry(new SwatNavBarEntry($this->account->fullname,
			sprintf('Account/Details?id=%s', $this->account->id)));

		$this->navbar->addEntry($last_entry);
		
		$this->title = $this->account->fullname;
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->invoice));
	}

	// }}}
}

?>
