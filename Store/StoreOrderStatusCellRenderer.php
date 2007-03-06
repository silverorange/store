<?php

require_once 'Swat/SwatCellRenderer.php';
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
	 * Should be one of the {@link StoreOrder}::STATUS_* constants. Is
	 * Is {@link StoreOrder::STATUS_INITIALIZED} by default.
	 *
	 * @var integer
	 */
	public $status = StoreOrder::STATUS_INITIALIZED;

	/**
	 * The largest possible order status (complete)
	 *
	 * @var integer
	 */
	public $max_status = StoreOrder::STATUS_COMPLETE;

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
		for ($i = 0; $i < $this->status; $i++) {
			$complete_img_tag->title = ''; //TODO
			$complete_img_tag->display();
		}

		$incomplete_img_tag = new SwatHtmlTag('img');
		$incomplete_img_tag->src =
			$image_path.'store-order-status-cell-renderer-incomplete.png';

		$incomplete_img_tag->alt = Store::_('incomplete');
		$incomplete_img_tag->width = 20;
		$incomplete_img_tag->height = 10;
		for ($i = $this->status; $i < $this->max_status; $i++) {
			$incomplete_img_tag->title = ''; //TODO
			$incomplete_img_tag->display();
		}

		echo '<br />';

		$current_status = 'test'; //TODO
		echo SwatString::minimizeEntities($current_status);
	}

	// }}}
}

?>
