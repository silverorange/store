<?php

require_once 'Store/pages/StoreAccountPage.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethod.php';
require_once 'Store/StoreUI.php';
require_once 'Store/StoreClassMap.php';
require_once 'Swat/SwatDate.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreAccountPaymentMethodEditPage extends StoreAccountPage
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

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$id = null)
	{
		parent::__construct($app, $layout);
		$this->id = intval($id);

		if ($this->id === 0)
			$this->id = null;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new StoreUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$this->ui->init();
	}

	// }}}
	// {{{ private function findPaymentMethod()

	private function findPaymentMethod()
	{
		if ($this->id === null) {
			$class_map = StoreClassMap::instance();
			$class = $class_map->resolveClass('StoreAccountPaymentMethod');
			return new $class();
		}

		$payment_method =
			$this->app->session->account->payment_methods->getByIndex($this->id);

		// go back to account page if payment type is disabled
		$payment_type = $payment_method->payment_type;
		if (!$payment_type->isAvailableInRegion($this->app->getRegion()))
			$this->app->relocate('account');

		if ($payment_method === null)
			throw new SiteNotFoundException(
				sprintf('A payment method with an id of %d does not exist.',
				$this->id));

		return $payment_method;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('edit_form');
		$form->process();

		if ($form->isProcessed()) {
			if (!$form->hasMessage()) {
				$payment_method = $this->findPaymentMethod();
				$this->updatePaymentMethod($payment_method);

				if ($this->id === null) {
					$this->app->session->account->payment_methods->add(
						$payment_method);

					$this->addMessage(
						Store::_('One credit card has been added.'),
						$payment_method);

				} elseif ($payment_method->isModified()) {
					$this->addMessage(
						Store::_('One credit card has been updated.'),
						$payment_method);
				}

				$this->app->session->account->save();
				$this->app->relocate('account');
			}
		}
	}

	// }}}
	// {{{ private function addMessage()
	private function addMessage($text, $payment_method)
	{
		ob_start();
		$payment_method->display();
		$payment_display = ob_get_clean();

		$message = new SwatMessage($text, SwatMessage::NOTIFICATION);
		$message->secondary_content = $payment_display;
		$message->content_type = 'text/xml';
		$this->app->messages->add($message);
	}

	// }}}
	// {{{ private function updatePaymentMethod()

	private function updatePaymentMethod($payment_method)
	{
		$payment_method->payment_type =
			$this->ui->getWidget('payment_type')->value;

		$payment_method->credit_card_expiry =
			$this->ui->getWidget('credit_card_expiry')->value;

		$payment_method->credit_card_fullname =
			$this->ui->getWidget('credit_card_fullname')->value;

		if ($this->id === null)
			$payment_method->setCreditCardNumber(
				$this->ui->getWidget('credit_card_number')->value);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$form = $this->ui->getWidget('edit_form');
		$form->action = $this->source;

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$type_where_clause = 'enabled = true';
		$type_join_clause = sprintf('inner join PaymentTypeRegionBinding on '.
			'payment_type = id and region = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		if (!$form->isProcessed()) {
			if ($this->id === null) {
				$this->ui->getWidget('credit_card_fullname')->value =
					$this->app->session->account->fullname;
			} else {
				$payment_method = $this->findPaymentMethod();
				$this->setWidgetValues($payment_method);

				if ($payment_method !== null) {
					// allow disabled types
					$type_where_clause = sprintf('id = %s',
						$this->app->db->quote(
							$payment_method->payment_type->id, 'integer'));

					$join_clause = '';
				}
			}
		}

		$this->buildLabels();

		if ($this->id !== null) {
			$this->ui->getWidget('credit_card_number_field')->visible = false;
			$this->ui->getWidget('credit_card_number_last4_field')->visible = true;
			$this->ui->getWidget('payment_type')->show_blank = false;
		}

		$type_flydown = $this->ui->getWidget('payment_type');
		$types_sql = sprintf('select id, title from PaymentType
			%s where %s order by title',
			$type_join_clause, $type_where_clause);

		$types = SwatDB::query($this->app->db, $types_sql);
		foreach ($types as $type)
			$type_flydown->addOption(
				new SwatOption($type->id, $type->title));

		$this->layout->startCapture('content');
		$this->ui->display();
		$this->layout->endCapture();
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
	// {{{ private function setWidgetValues()

	private function setWidgetValues($payment_method)
	{
		$this->ui->getWidget('credit_card_expiry')->value =
			$payment_method->credit_card_expiry;

		$this->ui->getWidget('payment_type')->value =
			$payment_method->payment_type->id;

		$this->ui->getWidget('credit_card_fullname')->value =
			$payment_method->credit_card_fullname;

		if (!$this->ui->getWidget('credit_card_expiry')->isValid()) {
			$expiry = $this->ui->getWidget('credit_card_expiry');

			$content = sprintf(Store::_('The expiry date that was entered '.
				'(%s) is in the past. Please enter an updated date.'),
				$expiry->value->format(SwatDate::DF_CC_MY));

			$message = new SwatMessage($content, SwatMessage::WARNING);
			$expiry->addMessage($message);

			$expiry->value = null;
		}

		$this->ui->getWidget('credit_card_number_last4')->content =
			StorePaymentMethod::formatCreditCardNumber(
				$payment_method->credit_card_last4,
				'**** **** **** ####');
	}

	// }}}
}

?>
