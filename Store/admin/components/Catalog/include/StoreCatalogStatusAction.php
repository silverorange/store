<?php

require_once 'Swat/SwatFlydown.php';
require_once 'Swat/SwatControl.php';

require_once '../../include/dataobjects/Item.php';
require_once '../../include/dataobjects/Catalog.php';

/**
 * Actions flydown to control the status of catalogues
 *
 * @package   veseys2
 * @copyright 2005-2006 silverorange
 */
class CatalogStatusAction extends SwatControl
{
	public $db;

	private $status;
	private $region;
	private $regions;
	private $items;

	public function init()
	{
		$this->region = new SwatFlydown('region');
		$this->region->parent = $this;
		$this->status = new SwatFlydown('status');
		$this->status->parent = $this;

		$this->regions = SwatDB::getOptionArray(
			$this->db, 'Region', 'title', 'id', 'title');
	}

	public function display()
	{
		$this->init();

		$this->region->show_blank = false;
		$options = array(0 => 'All');

		$label_tag = new SwatHtmlTag('label');
		$label_tag->for = 'region';
		$label_tag->setContent('For Region: ');
		$label_tag->display();

		$options = $options + $this->regions;
		$this->region->addOptionsByArray($options);
		$this->region->display();

		echo '&nbsp;';

		$label_tag = new SwatHtmlTag('label');
		$label_tag->for = 'status';
		$label_tag->setContent('Status:');
		$label_tag->display();

		$this->status->show_blank = false;
		$this->status->addOptionsByArray(Catalog::getStatuses());
		$this->status->display();
	}

	public function process()
	{
		$this->status->process();
		$this->region->process();
	}

	public function processAction()
	{
		$status = $this->status->value;
		$region = $this->region->value;

		$items = array();
		foreach ($this->items as $id)
			$items[] = $this->db->quote($id, 'integer');

		$id_list = implode(',', $items);

		$where = ($region == 0) ?
			'1 = 1' : 'region = '.$this->db->quote($region, 'integer');

		SwatDB::query($this->db, sprintf(
			'delete from CatalogRegionBinding where %s and catalog in (%s)',
			$where, $id_list));

		if ($status != Catalog::STATUS_DISABLED) {
			$regions = ($region == 0) ?
				array_keys($this->regions) : array($region);

			foreach ($this->items as $item)
				foreach ($regions as $region) {
					$available = ($status == Catalog::STATUS_ENABLED_IN_SEASON);
					$fields = array('integer:catalog', 'integer:region',
						'boolean:available');

					$values = array('catalog' => $item, 'region' => $region,
						'available' => $available);

					SwatDB::insertRow($this->db, 'CatalogRegionBinding',
						$fields, $values);
				}
		}
	}

	public function setItems($items = array())
	{
		$this->items = $items;
	}
}

?>
