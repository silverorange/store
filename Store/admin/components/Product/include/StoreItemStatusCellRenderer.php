<?php

require_once 'Swat/SwatNullTextCellRenderer.php';
require_once 'Swat/SwatString.php';

/**
 * Cell renderer that displays a summary of statuses of items in a product
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreItemStatusCellRenderer extends SwatNullTextCellRenderer
{
	public $count_available = 0;
	public $count_outofstock = 0;
	public $count_disabled = 0;

	public function render()
	{
		if ($this->isSensitive() === false) {
			parent::render();
			return;
		}

		echo implode(', ', $this->getDescriptions());
	}

	public function isSensitive()
	{
		return ($this->count_available + $this->count_outofstock +
			$this->count_disabled == 0) ? false : true;
	}

	public function getDescriptions()
	{
		$descriptions = array();

		if ($this->count_available > 0)
			$descriptions[] = sprintf(Store::_('%s available'),
				SwatString::numberFormat($this->count_available));

		if ($this->count_outofstock > 0)
			$descriptions[] = sprintf(Store::_('%s out of stock'),
				SwatString::numberFormat($this->count_outofstock));

		if ($this->count_disabled > 0)
			$descriptions[] = sprintf(Store::_('%s disabled'),
				SwatString::numberFormat($this->count_disabled));

		return $descriptions;
	}
}

?>
