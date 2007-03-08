<?php

require_once 'Swat/SwatCellRenderer.php';
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
	/**
	 * @var integer
	 */
	public $type;

	public function render()
	{
		switch ($this->type) {
		case StorePaymentRequest::TYPE_PAY:
			$title = 'payment';
			break;
		case StorePaymentRequest::TYPE_VERIFY:
			$title = 'verify';
			break;
		case StorePaymentRequest::TYPE_VERIFIEDPAY:
			$title = 'verified payment';
			break;
		case StorePaymentRequest::TYPE_HOLD:
			$title = 'hold';
			break;
		case StorePaymentRequest::TYPE_RELEASE:
			$title = 'release';
			break;
		case StorePaymentRequest::TYPE_ABORT:
			$title = 'abort';
			break;
		case StorePaymentRequest::TYPE_VOID:
			$title = 'void';
			break;
		case StorePaymentRequest::TYPE_REFUND:
			$title = 'refund';
			break;
		default:
			$title = 'unknown';
			break;
		}

		echo SwatString::minimizeEntities($title);
	}
}

?>
