<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatYUI.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SiteDBEditPage.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';
require_once 'Store/dataobjects/StoreCardTypeWrapper.php';

/**
 * Page to allow customers to add or edit payment methods on their account
 *
 * @package   Store
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodEditPage extends SiteDBEditPage
{
	// {{{ protected properties

	/**
	 * @var StoreAccountPaymentMethod
	 */
	protected $payment_method;

	// }}}
	// {{{ private properties

	/**
	 * @var integer
	 */
	private $id;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Store/pages/account-payment-method-edit.xml';
	}

	// }}}
	// {{{ protected function isNew()

	protected function isNew(SwatForm $form)
	{
		return (!$this->id);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'id' => array(0, 0),
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		// redirect to login page if not logged in
		if (!$this->app->session->isLoggedIn())
			$this->app->relocate('account/login');

		parent::initInternal();

		$this->id = intval($this->getArgument('id'));
		$this->initPaymentMethod();
	}

	// }}}
	// {{{ protected function initPaymentMethod()

	protected function initPaymentMethod()
	{
		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			$class = SwatDBClassMap::get('StoreAccountPaymentMethod');
			$payment_method = new $class();
			$payment_method->setDatabase($this->app->db);
			$payment_method->payment_type = $this->initPaymentType();
		} else {
			// edit existing payment method
			$payment_method =
				$this->app->session->account->payment_methods->getByIndex(
					$this->id);

			if ($payment_method === null) {
				throw new SiteNotFoundException(sprintf(
					'A payment method with an id of ‘%d’ does not exist.',
					$this->id));
			}

			// go back to account page if payment type is disabled
			$payment_type = $payment_method->payment_type;
			if (!$payment_type->isAvailableInRegion($this->app->getRegion()))
				$this->app->relocate('account');

			// go back to account page if card type is disabled
			$card_type = $payment_method->card_type;
			if (!$card_type->isAvailableInRegion($this->app->getRegion()))
				$this->app->relocate('account');
		}

		$this->payment_method = $payment_method;
	}

	// }}}
	// {{{ protected function initPaymentType()

	protected function initPaymentType()
	{
		// this page currently only supports editing of payment methods
		// with payment type is "card"
		$class_name = SwatDBClassMap::get('StorePaymentType');
		$type = new $class_name();
		$type->setDatabase($this->app->db);
		$type->loadFromShortname('card');

		// this page currently only supports editing of payment methods
		// with payment type is "card"
		if ($type === null) {
			throw new StoreException(
				'Payment type with shortname of ‘card’ not found.');
		}

		return $type;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$this->ui->getWidget('card_number')->setDatabase($this->app->db);
		parent::process();

		$type = $this->getCardType();

		if ($type !== null) {
			$this->ui->getWidget('card_inception')->required =
				$type->hasInceptionDate();

			$this->ui->getWidget('card_issue_number')->required =
				$type->hasIssueNumber();
		}
	}

	// }}}
	// {{{ protected function updatePaymentMethod()

	protected function updatePaymentMethod(SwatForm $form)
	{
		$this->assignUiValuesToObject($this->payment_method, array(
			'card_issue_number',
			'card_inception',
			'card_fullname',
		));

		// this can't be in assignUiValuesToObject because we don't convert
		// expiry date to UTC and assignUiValuesToObject automagically converts.
		$this->payment_method->card_expiry =
			$this->ui->getWidget('card_expiry')->value;

		if ($this->isNew($form)) {
			$this->payment_method->card_type = $this->getCardType();
			$this->payment_method->setCardNumber(
				$this->ui->getWidget('card_number')->value);
		}
	}

	// }}}
	// {{{ protected function getCardType()

	protected function getCardType()
	{
		static $type = null;

		if ($type === null) {
			$type_list = $this->ui->getWidget('card_type');

			if (isset($_POST['card_type'])) {
				$type_list->process();
				$card_type_id = $type_list->value;
			} else {
				$card_number = $this->ui->getWidget('card_number');
				$card_number->process();
				$card_type_id = $card_number->getCardType();
			}

			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$type->load($card_type_id);
		}

		return $type;
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData(SwatForm $form)
	{
		$this->updatePaymentMethod($form);

		if ($this->payment_method->payment_type->isCard() &&
			$this->payment_method->card_type === null) {
				throw new StoreException('Payment method must have '.
					'a card_type when isCard() is true.');
		}

		if ($this->isNew($form)) {
			$this->payment_method->account = $this->app->session->account;
			$this->payment_method->save();

			$this->addMessage($this->getMessageText('add'));
		} elseif ($this->payment_method->isModified()) {
			$this->payment_method->save();

			$this->addMessage($this->getMessageText('update'));
		}
	}

	// }}}
	// {{{ protected function getMessageText()

	protected function getMessageText($text)
	{
		switch ($text) {
		case 'add':
			return Store::_('One payment method has been added.');
		case 'update':
			return Store::_('One payment method has been updated.');
		default:
			return $text;
		}
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate(SwatForm $form)
	{
		$this->app->relocate('account');
	}

	// }}}
	// {{{ private function addMessage()

	private function addMessage($text)
	{
		ob_start();
		$this->payment_method->display();
		$payment_method_condensed = ob_get_clean();

		$message = new SwatMessage($text, SwatMessage::NOTIFICATION);
		$message->secondary_content = $payment_method_condensed;
		$message->content_type = 'text/xml';
		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');
		if ($this->isNew($form)) {
			if (!$form->isProcessed())
				$this->setDefaultValues($this->app->session->account);

			$this->ui->getWidget('card_number')->visible = true;
			$this->ui->getWidget('card_number_preview')->visible = false;
		} else {
			$this->ui->getWidget('card_number')->visible = false;
			$this->ui->getWidget('card_number_preview')->visible = true;
			$this->ui->getWidget('card_type')->show_blank = false;
		}

		$this->buildLabels($form);
		$this->buildCardTypes();
	}

	// }}}
	// {{{ protected function buildLabels()

	protected function buildLabels(SwatForm $form)
	{
		if ($this->isNew($form)) {
			$this->layout->data->title = Store::_('Add a New Payment Method');
			$this->layout->navbar->createEntry(
				Store::_('Add a New Payment Method'));
		} else {
			$this->layout->data->title = Store::_('Edit a Payment Method');
			$this->layout->navbar->createEntry(
				Store::_('Edit a Payment Method'));

			$this->ui->getWidget('submit_button')->title =
				Store::_('Update Payment Method');
		}
	}

	// }}}
	// {{{ protected function buildCardTypes()

	protected function buildCardTypes()
	{
		$types = $this->getCardTypes();
		$type_list = $this->ui->getWidget('card_type');

		foreach ($types as $type) {
			if (strlen($type->note) > 0)
				$title = sprintf('%s<br /><span class="swat-note">%s</span>',
					$type->title,
					$type->note);
			else
				$title = $type->title;

			$type_list->addOption(
				new SwatOption($type->id, $title, 'text/xml'));
		}

		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript($this->getInlineJavaScript($types));
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getCardTypes()

	/**
	 * Gets available payment types for new payment methods
	 *
	 * @return StorePaymentTypeWrapper
	 */
	protected function getCardTypes()
	{
		$sql = 'select CardType.* from CardType
			inner join CardTypeRegionBinding on
				card_type = id and region = %s
			order by displayorder, title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$types = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreCardTypeWrapper'));

		return $types;
	}

	// }}}
	// {{{ protected function load()

	protected function load(SwatForm $form)
	{
		$this->assignObjectValuesToUi($this->payment_method, array(
			'card_type',
			'card_issue_number',
			'card_inception',
			'card_fullname',
		));

		// this can't be in assignObjectValuesToUi because we don't convert
		// expiry date to UTC, and assignObjectValuesToUi automagically
		// converts.
		$this->ui->getWidget('card_expiry')->value =
			$this->payment_method->card_expiry;

		$this->ui->getWidget('card_number_preview')->content =
			StoreCardType::formatCardNumber(
				$this->payment_method->card_number_preview,
				$this->payment_method->card_type->getMaskedFormat());

		$expiry = $this->ui->getWidget('card_expiry');
		if (!$expiry->isValid()) {
			$content = sprintf(Store::_('The expiry date that was entered '.
				'(%s) is in the past. Please enter an updated date.'),
				$expiry->value->format(SwatDate::DF_CC_MY));

			$message = new SwatMessage($content, SwatMessage::WARNING);
			$expiry->addMessage($message);

			$expiry->value = null;
		}
	}

	// }}}
	// {{{ protected function setDefaultValues()

	/**
	 * Sets default values of this payment method based on values from the
	 * account
	 *
	 * @param StoreAccount $account the account to set default values from.
	 */
	protected function setDefaultValues(StoreAccount $account)
	{
		$this->ui->getWidget('card_fullname')->value = $account->fullname;
	}

	// }}}

	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript(StoreCardTypeWrapper $types)
	{
		$id = 'account_payment_method';
		$inception_date_ids = array();
		$issue_number_ids = array();
		foreach ($types as $type) {
			if ($type->hasInceptionDate())
				$inception_date_ids[] = $type->id;

			if ($type->hasIssueNumber())
				$issue_number_ids[] = $type->id;
		}

		return sprintf("var %s_obj = ".
			"new StoreAccountPaymentMethodPage('%s', [%s], [%s]);",
			$id,
			$id,
			implode(', ', $inception_date_ids),
			implode(', ', $issue_number_ids));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/store/javascript/store-account-payment-method-page.js',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
