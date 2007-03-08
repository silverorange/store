<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/StoreOrderStatus.php';
require_once 'Store/StoreOrderStatusList.php';
require_once 'Store/dataobjects/StoreOrder.php';

/**
 * Cell renderer for order statuses
 *
 * @package   Store
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreOrderStatusCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	/**
	 * The status to render
	 *
	 * @var StoreOrderStatus
	 */
	public $status;

	/**
	 * Whether or not the order is cancelled
	 *
	 * @var boolean
	 */
	public $cancelled = false;

	/**
	 * Whether or not to render a textual summary of the current order status
	 *
	 * @var boolean
	 */
	public $show_summary = true;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheet(
			'packages/store/styles/store-order-status-cell-renderer.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		$image_path = 'packages/store/images/';

		$complete_img_tag = new SwatHtmlTag('img');
		$complete_img_tag->src =
			$image_path.'store-order-status-cell-renderer-complete.png';

		$complete_img_tag->alt = Store::_('complete');
		$complete_img_tag->width = 20;
		$complete_img_tag->height = 10;

		$incomplete_img_tag = new SwatHtmlTag('img');
		$incomplete_img_tag->src =
			$image_path.'store-order-status-cell-renderer-incomplete.png';

		$incomplete_img_tag->alt = Store::_('incomplete');
		$incomplete_img_tag->width = 20;
		$incomplete_img_tag->height = 10;

		$completed = true;
		$first = true;

		foreach (StoreOrderStatusList::statuses() as $status) {
			if ($first) {
				// ignore first status (initialized)
				$first = false;
			} else {
				if ($completed) {
					$complete_img_tag->title = $status->title;
					$complete_img_tag->display();
				} else {
					$incomplete_img_tag->title = $status->title;
					$incomplete_img_tag->display();
				}
			}

			// Order statuses are progressive. Once we reach the current
			// status, subsequent statuses are incomplete.
			if ($status === $this->status)
				$completed = false;
		}

		if ($this->show_summary) {
			echo '&nbsp;';
			echo SwatString::minimizeEntities($this->status->title);
			if ($this->cancelled)
				printf('&nbsp;<strong>(%s)</strong>', Store::_('cancelled'));
		}
	}

	// }}}
}

?>
