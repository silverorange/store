<?php

require_once 'Store/StoreItemPriceCellRenderer.php';

/**
 * Renders item prices and can optionally strike-through the price if it's not
 * available in the current region.
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdminItemPriceCellRenderer extends StoreItemPriceCellRenderer
{
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/store/admin/styles/'.
			'store-admin-item-price-cell-renderer.css',
			Store::PACKAGE_ID);
	}

	// }}}
	// {{{ public properties

	/**
	 * Enabled
	 *
	 * @var boolean
	 */
	public $enabled;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->enabled) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'store-item-price-disabled';
			$span_tag->open();
			parent::render();
			$span_tag->close();
		} else {
			parent::render();
		}
	}

	// }}}
	// {{{ protected function isFree()

	protected function isFree()
	{
		return false;
	}

	// }}}
}

?>
