<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatDate.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';

//TODO: make the card_lastdigits more flexible, add newer fields to it,
//      and possibly make work as a creator

/**
 * Edit page for Account Payment Methods
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodEdit extends AdminDBEdit
{
	// {{{ private properties

	private $fields;
	private $account_id;
	private $account_fullname;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/paymentmethodedit.xml');

		$this->initAccount();

		$this->fields = array('integer:payment_type','card_fullname',
			'card_last4','date:card_expiry');
	}

	// }}}
	// {{{ protected function initAccount()

	protected function initAccount()
	{
		if ($this->id === null)
			$this->account_id = $this->app->initVar('account');
		else
			$this->account_id = SwatDB::queryOne($this->app->db, sprintf(
				'select account from AccountPaymentMethod where id = %s',
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
		$values = $this->ui->getValues(array('payment_type', 'card_fullname',
			'card_expiry'));

		$values['card_expiry'] = $values['card_expiry']->getDate();

		// do not overwrite card_lastdigits field, as we display it, but don't
		// actually edit it
		foreach ($this->fields as $key => $field)
			if ($field == 'card_lastdigits')
				unset($this->fields[$key]);

		SwatDB::updateRow($this->app->db, 'AccountPaymentMethod',
			$this->fields, $values, 'id', $this->id);

		$message = new SwatMessage(sprintf(
			Store::_('Payment method for “%s” has been saved.'),
			$this->account_fullname));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('edit_frame');
		$frame->subtitle = $this->account_fullname;

		$provstate_flydown = $this->ui->getWidget('payment_type');
		$provstate_flydown->show_blank = true;
		$provstate_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'PaymentType', 'title', 'id', 'title'));

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('account', $this->account_id);
	}

	// }}}
	// {{{ protected buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$last_entry = $this->navbar->popEntry();
		$last_entry->title = sprintf(Store::_('%s Payment Method'),
			$last_entry->title);

		$this->navbar->addEntry(new SwatNavBarEntry($this->account_fullname,
			sprintf('Account/Details?id=%s', $this->account_id)));

		$this->navbar->addEntry($last_entry);
		
		$this->title = $this->account_fullname;
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$row = SwatDB::queryRowFromTable($this->app->db,
			'AccountPaymentMethod', $this->fields, 'id', $this->id);

		if ($row === null)
			throw new AdminNotFoundException(sprintf(
				Store::_('Account payment method with id ‘%s’ not found.'),
				$this->id));

		$card_lastdigits = $this->ui->getWidget('card_lastdigits');
		$card_lastdigits->content = StorePaymentMethod::formatCardNumber(
			$row->card_lastdigits, '**** **** **** ####');
		//todo: pass right mask in

		$row->card_expiry = new SwatDate($row->card_expiry);

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}
?>
