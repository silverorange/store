<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatDate.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';

//TODO: make the credit_card_last4 more flexible,
//      and possibly make work as a creator

/**
 * Edit page for Account Payment Methods
 *
 * @package   veseys2
 * @copyright 2006 silverorange
 */
class AccountPaymentMethodEdit extends AdminDBEdit
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

		$this->ui->loadFromXML(dirname(__FILE__).'/paymentmethodedit.xml');

		$this->initAccount();

		$this->fields = array(
			'integer:payment_type',
			'credit_card_fullname',
			'credit_card_last4',
			'date:credit_card_expiry',
		);
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
		$values = $this->ui->getValues(array('payment_type', 
			'credit_card_fullname', 'credit_card_expiry')); 

		$values['credit_card_expiry'] =
			$values['credit_card_expiry']->getDate();

		// do not overwrite last4 field
		foreach ($this->fields as $key => $field)
			if ($field == 'credit_card_last4')
				unset($this->fields[$key]);

		SwatDB::updateRow($this->app->db, 'AccountPaymentMethod', 
			$this->fields, $values, 'id', $this->id);

		$msg = new SwatMessage(sprintf(
			'Payment method for &#8220;%s&#8221; has been saved.', 
			$this->account_fullname));

		$this->app->messages->add($msg);
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
		$last_entry->title = $last_entry->title.' Payment Method';

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
			throw new AdminNotFoundException(
				sprintf("Account payment method with id '%s' not found.", 
					$this->id));

		$credit_card_last4 = $this->ui->getWidget('credit_card_last4');
		$credit_card_last4->content =
			StorePaymentMethod::formatCreditCardNumber(
				$row->credit_card_last4, '**** **** **** ####');

		$row->credit_card_expiry = new SwatDate($row->credit_card_expiry);

		$this->ui->setValues(get_object_vars($row));
	}

	// }}}
}
?>
