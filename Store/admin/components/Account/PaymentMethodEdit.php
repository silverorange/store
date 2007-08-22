<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatDate.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';
require_once 'Store/dataobjects/StoreAccount.php';

/**
 * Edit page for Account Payment Methods
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Make this tool have the ability to enter new card numbers.
 * @todo      Add card_inception and card_issue_number fields to this tool.
 */
class StoreAccountPaymentMethodEdit extends AdminDBEdit
{
	// {{{ private properties

	private $payment_method;
	private $account;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/admin-account-paymentmethodedit.xml');
	}

	// }}}
	// {{{ protected function getAccount()

	protected function getAccount()
	{
		if ($this->account === null) {
			if ($this->id === null)
				$account_id = $this->app->initVar('account');
			else
				$account_id = SwatDB::queryOne($this->app->db, sprintf(
					'select account from AccountPaymentMethod where id = %s',
					$this->app->db->quote($this->id, 'integer')));

			$class_name = SwatDBClassMap::get('StoreAccount');
			$this->account = new $class_name();
			$this->account->setDatabase($this->app->db);
			$this->account->load($account_id);
		}

		return $this->account;
	}

	// }}}
	// {{{ protected function getPaymentMethod()

	protected function getPaymentMethod()
	{
		if ($this->payment_method === null) {
			$class_name = SwatDBClassMap::get('StoreAccountPaymentMethod');
			$this->payment_method = new $class_name();
			$this->payment_method->setDatabase($this->app->db);

			if ($this->id === null) {
				$this->payment_method->account = $this->getAccount();
			} else {
				if (!$this->payment_method->load($this->id)) {
					throw new AdminNotFoundException(sprintf(Store::_(
						'Account payment method with id ‘%s’ not found.'),
						$this->id));
				}
			}
		}

		return $this->payment_method;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$payment_method = $this->getPaymentMethod();
		$payment_method->payment_type =
			$this->ui->getWidget('payment_type')->value;

		$payment_method->card_fullname =
			$this->ui->getWidget('card_fullname')->value;

		$payment_method->card_expiry =
			$this->ui->getWidget('card_expiry')->value;

		$payment_method->save();

		$message = new SwatMessage(sprintf(
			Store::_('Payment method for “%s” has been saved.'),
			$this->getAccount()->fullname));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('edit_frame');
		$frame->subtitle = $this->getAccount()->fullname;

		$payment_type_flydown = $this->ui->getWidget('payment_type');
		$payment_type_flydown->show_blank = true;
		$payment_type_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'PaymentType', 'title', 'id',
			'displayorder, title'));

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('account', $this->getAccount()->id);
	}

	// }}}
	// {{{ protected buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$last_entry = $this->navbar->popEntry();
		$last_entry->title = sprintf(Store::_('%s Payment Method'),
			$last_entry->title);

		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->getAccount()->fullname,
			sprintf('Account/Details?id=%s', $this->getAccount()->id)));

		$this->navbar->addEntry($last_entry);
		
		$this->title = $this->getAccount()->fullname;
	}
	
	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$payment_method = $this->getPaymentMethod();

		$card_number_preview = $this->ui->getWidget('card_number_preview');
		$card_number_preview->content = StorePaymentType::formatCardNumber(
			$payment_method->card_number_preview,
			$payment_method->payment_type->getCardMaskedFormat());

		$this->ui->getWidget('payment_type')->value =
			$payment_method->payment_type->id;

		$this->ui->getWidget('card_fullname')->value =
			$payment_method->card_fullname;

		$this->ui->getWidget('card_expiry')->value =
			$payment_method->card_expiry;
	}

	// }}}
}

?>
