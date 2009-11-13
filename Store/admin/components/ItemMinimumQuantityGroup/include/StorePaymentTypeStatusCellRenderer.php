<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * Cell renderer that displays a summary of the status of a PaymentType
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StorePaymentTypeStatusCellRenderer extends SwatCellRenderer
{
	public $payment_type;
	public $db;

	public function render()
	{
		$sql = 'select Region.title
			from Region
				inner join PaymentTypeRegionBinding on
					Region.id = region and payment_type = %s
			order by Region.title';

		$sql = sprintf($sql,
			$this->db->quote($this->payment_type, 'integer'));

		$available_regions = SwatDB::query($this->db, $sql);

		if (count($available_regions)) {
			foreach ($available_regions as $row)
				echo SwatString::minimizeEntities($row->title), '<br />';
		} else {
			echo sprintf('&lt;%s&gt;', Store::_('disabled in all regions'));
		}
	}
}
?>
