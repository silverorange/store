<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Swat/SwatYUI.php';

/**
 * Base address verification page of checkout
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreCheckoutAddressVerificationPage extends
	StoreCheckoutEditPage
{
	// {{{ protected properties

	/**
	 * @var StoreOrderAddress
	 *
	 * @see StoreCheckoutAddressPage::getAddress()
	 */
	protected $address;

	protected $verified_address;

	// }}}
	// {{{ protected function getWidget()

	protected function getWidget($name)
	{
		$name = $this->getWidgetPrefix().$name;
		$widget = $this->ui->getWidget($name);

		return $widget;
	}

	// }}}
	// {{{ abstract protected function getWidgetPrefix()

	abstract protected function getWidgetPrefix();

	// }}}

	// process phase
	// {{{ public function processCommon()

	public function processCommon()
	{
		parent::processCommon();

		if ($this->address === null)
			return;

		$form = $this->ui->getWidget('form');
		$list = $this->getWidget('verification_list');

		if ($list->value === 'verified') {
			$verified_address = $form->getHiddenField('verified_address');

			if ($verified_address === null) {
				// If verified address object is missing from POST data
				// then re-verify the address.
				$this->address->verify($this->app);
			} else {
				$this->address->copyFrom($verified_address);
			}

			if ($this->app->session->isLoggedIn())
				$this->app->session->account->save();
		}
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		if ($this->address === null) {
			$container = $this->getWidget('address_verification_container');
			$container->visible = false;
		} else {
			$this->buildList();
		}
	}

	// }}}
	// {{{ protected function buildList()

	protected function buildList()
	{
		$list = $this->getWidget('verification_list');
		$block = $this->getWidget('verification_message');
		$block->content_type = 'text/xml';
		$block_bottom = $this->getWidget('verification_message_bottom');
		$block_bottom->content_type = 'text/xml';

		$verified_address = clone $this->address;
		$valid = $verified_address->verify($this->app);
		$equal = $verified_address->mostlyEqual($this->address);

		if ($valid && $equal) {
			$container = $this->getWidget('address_verification_container');
			$container->visible = false;

			$this->address->copyFrom($verified_address);

			if ($this->app->session->isLoggedIn())
				$this->app->session->account->save();

		} elseif ($valid) {
			ob_start();
			echo '<p>';
			echo SwatString::minimizeEntities(
				Store::_('Is this your address?')
			);
			echo '</p>';
			$block->content = ob_get_clean();

			ob_start();
			echo '<span class="address-option">';
			echo SwatString::minimizeEntities(
				Store::_('Yes, this is my address:')
			);
			echo '</span>';
			$verified_address->display();
			$list->addOption('verified', ob_get_clean(), 'text/xml');
			$list->value = 'verified';

			ob_start();
			echo '<span class="address-option">';
			echo SwatString::minimizeEntities(
				Store::_('No, use the address I entered:')
			);
			echo '</span>';
			$this->address->display();
			$list->addOption('entered', ob_get_clean(), 'text/xml');

			ob_start();
			echo '<div>';
			printf(
				SwatString::minimizeEntities(
					Store::_(
						'Or, %sreturn to the previous step%s to '.
						'change your address.'
					)
				),
				sprintf(
					'<a href="%s">',
					$this->getCheckoutSource().'/first'
				),
				'</a>'
			);
			$block_bottom->content = ob_get_clean();

			$form = $this->ui->getWidget('form');
			$form->addHiddenField('verified_address', $verified_address);
		} else {
			$this->getWidget('verification_list')->visible = false;

			ob_start();
			echo '<p><strong>';
			echo SwatString::minimizeEntities(
				Store::_('This address was not found.')
			);
			echo '</strong><br />';
			printf(
				SwatString::minizeEntities(
					Store::_(
						'If there is a mistake, %splease return to the '.
						'previous step%s to change the address, otherwise '.
						'continue to the next step.'
					)
				),
				sprintf(
					'<a href="%s">',
					$this->getCheckoutSource().'/first'
				),
				'</a>'
			);
			echo '</p>';
			$this->address->display();
			$block_bottom->content = ob_get_clean();
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/store/styles/checkout-address-verification-page.css'
		);

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
