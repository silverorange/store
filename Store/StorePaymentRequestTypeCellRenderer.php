<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/Store.php';
require_once 'Store/StorePaymentRequest.php';

/**
 * Renders textual descriptions for payment request types
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentRequestTypeCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * Request type to renderer
	 *
	 * Should be one of the StorePaymentRequest::TYPE_* constants.
	 *
	 * @var integer
	 */
	public $type;

	// }}}
	// {{{ public function render()

	public function render()
	{
		switch ($this->type) {
		case StorePaymentRequest::TYPE_PAY:
			$title = Store::_('payment');
			break;
		case StorePaymentRequest::TYPE_VERIFY:
			$title = Store::_('verify');
			break;
		case StorePaymentRequest::TYPE_VERIFIEDPAY:
			$title = Store::_('verified payment');
			break;
		case StorePaymentRequest::TYPE_HOLD:
			$title = Store::_('hold');
			break;
		case StorePaymentRequest::TYPE_RELEASE:
			$title = Store::_('release');
			break;
		case StorePaymentRequest::TYPE_ABORT:
			$title = Store::_('abort');
			break;
		case StorePaymentRequest::TYPE_VOID:
			$title = Store::_('void');
			break;
		case StorePaymentRequest::TYPE_REFUND:
			$title = Store::_('refund');
			break;
		case StorePaymentRequest::TYPE_3DS_AUTH:
			$title = Store::_('authentication');
			break;
		default:
			$title = Store::_('unknown');
			break;
		}

		echo SwatString::minimizeEntities($title);
	}

	// }}}
}

?>
