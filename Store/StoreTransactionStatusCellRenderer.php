<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StorePaymentTransaction.php';

/**
 * Cell renderer for payment transaction statuses
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreTransactionStatusCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * The status to render
	 *
	 * Should be one of the {@link StorePaymentTransaction}::STATUS_*
	 * constants. Is {@link StorePaymentTransaction::STATUS_MISSING} by default.
	 *
	 * @var integer
	 */
	public $status = StorePaymentTransaction::STATUS_MISSING;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheet(
			'packages/store/styles/store-transaction-status-cell-renderer.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		switch ($this->status) {
		case (StorePaymentTransaction::STATUS_PASSED):
			$css_class = 'store-transaction-status-cell-renderer-passed';
			$title = Store::_('passed');
			break;
		case (StorePaymentTransaction::STATUS_FAILED):
			$css_class = 'store-transaction-status-cell-renderer-failed';
			$title = Store::_('failed');
			break;
		case (StorePaymentTransaction::STATUS_NOTCHECKED):
			$css_class = 'store-transaction-status-cell-renderer-not-checked';
			$title = Store::_('not checked');
			break;
		case (StorePaymentTransaction::STATUS_MISSING):
		default:
			$css_class = 'store-transaction-status-cell-renderer-missing';
			$title = Store::_('not provided');
			break;
		}

		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = $css_class;
		$span_tag->setContent($title);

		$span_tag->display();
	}

	// }}}
}

?>
