<?php

require_once 'Site/pages/SiteAccountPage.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';
require_once 'Store/dataobjects/StoreCardTypeWrapper.php';
require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatYUI.php';

/**
 * Page to allow customers to add or edit payment methods on their account
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountPaymentMethodEditPage extends SiteAccountPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/pages/account-payment-method-edit.xml';

	protected $ui;
	protected $id;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);

		$this->id = intval($this->getArgument('id'));

		if ($this->id == 0)
			$this->id = null;
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
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$this->initInternal();
		$this->ui->init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;
	}

	// }}}
	// {{{ private function findPaymentMethod()

	/**
	 * @return StoreAccountPaymentMethod
	 */
	private function findPaymentMethod()
	{
		$account = $this->app->session->account;

		if ($this->id === null) {
			// create a new payment method
			$class = SwatDBClassMap::get('StoreAccountPaymentMethod');
			$payment_method = new $class();
			$payment_method->payment_type = $this->getPaymentType();

			// this page currently only supports editing of payment methods
			// with payment type is "card"
			if ($payment_method->payment_type === null)
				throw new StoreException('Payment type with shortname of \'card\' not found.');
		} else {
			// edit existing payment method
			$payment_method = $account->payment_methods->getByIndex($this->id);

			// go back to account page if payment type is disabled
			$payment_type = $payment_method->payment_type;
			if (!$payment_type->isAvailableInRegion($this->app->getRegion()))
				$this->app->relocate('account');

			// go back to account page if card type is disabled
			$card_type = $payment_method->card_type;
			if (!$card_type->isAvailableInRegion($this->app->getRegion()))
				$this->app->relocate('account');
		}

		if ($payment_method === null)
			throw new SiteNotFoundException(
				sprintf('A payment method with an id of ‘%d’ does not exist.',
				$this->id));

		return $payment_method;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$type = $this->getCardType();

		if ($type !== null) {
			$this->ui->getWidget('card_inception')->required =
				$type->hasInceptionDate();

			$this->ui->getWidget('card_issue_number')->required =
				$type->hasIssueNumber();
		}

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if ($this->id === null) {
				$payment_type = $this->getPaymentType();
				if ($payment_type !== null && $payment_type->isCard()) {
					$card_type_list = $this->ui->getWidget('card_type');
					// determine card type automatically if type flydown is hidden
					if (!$card_type_list->visible)
						$this->processCardType();
				}
			}

			if (!$form->hasMessage()) {
				$payment_method = $this->findPaymentMethod();
				$this->updatePaymentMethod($payment_method);

				if ($payment_method->payment_type->isCard() &&
					$payment_method->getInternalValue('card_type') === null)
						throw new StoreException('Payment method must '.
							'a card_type when isCard() is true.');

				if ($this->id === null) {
					$this->app->session->account->payment_methods->add(
						$payment_method);

					$this->addMessage('add', $payment_method);
				} elseif ($payment_method->isModified()) {
					$this->addMessage('update', $payment_method);
				}

				$this->app->session->account->save();
				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ protected function processCardType()

	protected function processCardType()
	{
		$card_number = $this->ui->getWidget('card_number');
		if ($card_number->show_blank_value)
			return;

		$message = null;

		$info = StoreCardType::getInfoFromCardNumber($card_number->value);

		if ($info !== null) {
			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$found = $type->loadFromShortname($info->shortname);

			if ($found)
				$this->ui->getWidget('card_type')->value = $type->id;
			else
				$message = sprintf('Sorry, we don’t accept %s payments.',
					SwatString::minimizeEntities($info->description));
		} else {
			$message = 'Sorry, we don’t accept your card type.';
		}

		if ($message !== null) {
			$message = new SwatMessage(sprintf('%s %s', $message,
				$this->getAcceptedCardTypesMessage()), SwatMessage::ERROR);

			$card_number->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function updatePaymentMethod()

	/**
	 * Updates an account payment method's properties from form values
	 *
	 * @param StoreAccountPaymentMethod $payment_method
	 */
	protected function updatePaymentMethod(
		StoreAccountPaymentMethod $payment_method)
	{
		if ($this->id === null) {
			$payment_method->card_type =
				$this->ui->getWidget('card_type')->value;

			$payment_method->setCardNumber(
				$this->ui->getWidget('card_number')->value);
		}

		$payment_method->card_issue_number =
			$this->ui->getWidget('card_issue_number')->value;

		$payment_method->card_expiry =
			$this->ui->getWidget('card_expiry')->value;

		$payment_method->card_inception =
			$this->ui->getWidget('card_inception')->value;

		$payment_method->card_fullname =
			$this->ui->getWidget('card_fullname')->value;
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
	// {{{ protected function getPaymentType()

	protected function getPaymentType()
	{
		// this page currently only supports editing of payment methods
		// with payment type is "card"
		$class_name = SwatDBClassMap::get('StorePaymentType');
		$type = new $class_name();
		$type->setDatabase($this->app->db);
		$type->loadFromShortname('card');

		return $type;
	}

	// }}}
	// {{{ protected function getCardType()

	protected function getCardType()
	{
		static $type = null;

		if ($type === null) {
			$type_list = $this->ui->getWidget('card_type');
			$type_list->process();
			$class_name = SwatDBClassMap::get('StoreCardType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$type->load($type_list->value);
		}

		return $type;
	}

	// }}}
	// {{{ protected function getAcceptedCardTypesMessage()

	protected function getAcceptedCardTypesMessage()
	{
		$types = SwatDB::getOptionArray($this->app->db,
			'CardType', 'title', 'shortname', 'title');

		if (count($types) > 2) {
			array_push($types, sprintf('and %s',
				array_pop($types)));

			$type_list = implode(', ', $types);
		} else {
			$type_list = implode(' and ', $types);
		}

		return sprintf('We accept %s.', $type_list);
	}

	// }}}
	// {{{ private function addMessage()

	private function addMessage($text, StorePaymentMethod $payment_method)
	{
		$text = $this->getMessageText($text);

		ob_start();
		$payment_method->display();
		$payment_display = ob_get_clean();

		$message = new SwatMessage($text, SwatMessage::NOTIFICATION);
		$message->secondary_content = $payment_display;
		$message->content_type = 'text/xml';
		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		if (!$form->isProcessed()) {
			if ($this->id === null) {
				$this->ui->getWidget('card_fullname')->value =
					$this->app->session->account->fullname;
			} else {
				$payment_method = $this->findPaymentMethod();
				$this->setWidgetValues($payment_method);
			}
		}

		$this->buildLabels();

		if ($this->id === null) {
			$this->ui->getWidget('card_number')->visible = true;
			$this->ui->getWidget('card_number_preview')->visible = false;
		} else {
			$this->ui->getWidget('card_number')->visible = false;
			$this->ui->getWidget('card_number_preview')->visible = true;
			$this->ui->getWidget('card_type')->show_blank = false;
		}

		$this->buildCardTypes();

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
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
					$type->title, $type->note);
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

		$wrapper = SwatDBClassMap::get('StoreCardTypeWrapper');
		$types = SwatDB::query($this->app->db, $sql, $wrapper);

		return $types;
	}

	// }}}
	// {{{ protected function buildLabels()

	protected function buildLabels()
	{
		if ($this->id === null) {
			$this->layout->navbar->createEntry(
				Store::_('Add a New Payment Method'));

			$this->layout->data->title = Store::_('Add a New Payment Method');
		} else {
			$this->layout->navbar->createEntry(
				Store::_('Edit a Payment Method'));

			$this->ui->getWidget('submit_button')->title =
				Store::_('Update Payment Method');

			$this->layout->data->title = Store::_('Edit a Payment Method');
		}
	}

	// }}}
	// {{{ protected function setWidgetValues()

	protected function setWidgetValues(
		StoreAccountPaymentMethod $payment_method)
	{
		$this->ui->getWidget('card_type')->value =
			$payment_method->card_type->id;

		$this->ui->getWidget('card_number_preview')->content =
			StoreCardType::formatCardNumber(
				$payment_method->card_number_preview,
				$payment_method->card_type->getMaskedFormat());

		$this->ui->getWidget('card_issue_number')->value =
			$payment_method->card_issue_number;

		$this->ui->getWidget('card_expiry')->value =
			$payment_method->card_expiry;

		if (!$this->ui->getWidget('card_expiry')->isValid()) {
			$expiry = $this->ui->getWidget('card_expiry');

			$content = sprintf(Store::_('The expiry date that was entered '.
				'(%s) is in the past. Please enter an updated date.'),
				$expiry->value->format(SwatDate::DF_CC_MY));

			$message = new SwatMessage($content, SwatMessage::WARNING);
			$expiry->addMessage($message);

			$expiry->value = null;
		}

		$this->ui->getWidget('card_inception')->value =
			$payment_method->card_inception;

		$this->ui->getWidget('card_fullname')->value =
			$payment_method->card_fullname;
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
