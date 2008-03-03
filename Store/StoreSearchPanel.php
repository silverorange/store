<?php

require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/dataobjects/StorePriceRangeWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StoreAttributeWrapper.php';

/**
 * Advanced search controls panel for Store
 *
 * @package   Store
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreSearchPanel extends SwatObject
{
	// {{{ protected properties

	/**
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * @var StoreRegion
	 */
	protected $region;

	/**
	 * @ StorePriceRange
	 */
	protected $price_range;

	/**
	 * @var StoreCategory
	 */
	protected $category;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/search-panel.xml';

	/**
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * @var array
	 */
	protected $init_search_state = array();

	/**
	 * @var array
	 */
	protected $process_search_state = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(MDB2_Driver_Common $db, StoreRegion $region,
		SwatContainer $root = null)
	{
		$this->db = $db;
		$this->region = $region;
		$this->ui = new SwatUI($root);
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->ui->init();
		$form = $this->ui->getWidget('search_form');
		$this->init_search_state = $form->getDescendantStates();
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		if (!$this->ui->getRoot()->isProcessed())
			$this->ui->process();

		$form = $this->ui->getWidget('search_form');
		$this->process_search_state = $form->getDescendantStates();
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildKeywords();
		$this->buildCategories();
		$this->buildPriceRanges();
		$this->buildAttributes();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		$this->build();
		$this->ui->display();
	}

	// }}}
	// {{{ public function getRoot()

	/**
	 * @return SwatContainer
	 */
	public function getRoot()
	{
		return $this->ui->getRoot();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		return $this->ui->getRoot()->getHtmlHeadEntrySet();
	}

	// }}}
	// {{{ public function setPriceRange()

	public function setPriceRange(StorePriceRange $range = null)
	{
		$this->price_range = $range;
	}

	// }}}
	// {{{ public function setCategory()

	public function setCategory(StoreCategory $category = null)
	{
		$this->category = $category;
	}

	// }}}
	// {{{ protected function buildKeywords()

	protected function buildKeywords()
	{
		$entry = $this->ui->getWidget('keywords');

		if (!$this->hasInitialState('keywords'))
			$entry->parent->classes[] = 'highlight';
	}

	// }}}
	// {{{ protected function buildCategories()

	protected function buildCategories()
	{
		$category_ids = array();
		$flydown = $this->ui->getWidget('category');
		foreach ($this->getCategories() as $category) {
			$category_ids[] = $category->id;
			$flydown->addOption($category->shortname, $category->title);
		}

		if ($this->category !== null) {
			if (!in_array($this->category->id, $category_ids)) {
				$flydown->addDivider();
				$flydown->addOption($this->category->path, $this->category->title);
			}
		}

		if (!$this->hasInitialState('category'))
			$flydown->parent->classes[] = 'highlight';
	}

	// }}}
	// {{{ protected function buildPriceRanges()

	protected function buildPriceRanges()
	{
		$flydown = $this->ui->getWidget('price');

		$shortnames = array();
		$ranges = $this->getPriceRanges();
		foreach ($ranges as $range) {
			$shortname = $range->getShortname();
			$flydown->addOption($shortname, $range->getTitle());
			$shortnames[] = $shortname;
		}

		if ($this->price_range !== null) {
			$range = $this->price_range;
			$shortname = $range->getShortname();
			if (!in_array($shortname, $shortnames)) {
				$flydown->addDivider();
				$flydown->addOption($shortname, $range->getTitle());
			}
		}

		if (!$this->hasInitialState('price'))
			$flydown->parent->classes[] = 'highlight';
	}

	// }}}
	// {{{ protected function buildAttributes()

	protected function buildAttributes()
	{
		$attributes = $this->getAttributes();

		$attr_checkbox_list = $this->ui->getWidget('attr');

		foreach ($attributes as $attribute) {
			ob_start();
			$attribute->display();
			$title = ob_get_clean();
			$attr_checkbox_list->addOption($attribute->shortname, $title, 'text/xml');
		}

		if (array_key_exists('attr', $this->init_search_state) &&
			array_key_exists('attr', $this->process_search_state)) {
				$attr_checkbox_list->highlight_values = array_diff(
					$this->process_search_state['attr'],
					$this->init_search_state['attr']);
		}
	}

	// }}}
	// {{{ protected function getPriceRanges()

	protected function getPriceRanges()
	{
		$sql = 'select * from PriceRange order by coalesce(start_price, 0)';

		$ranges = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StorePriceRangeWrapper'));

		return $ranges;
	}

	// }}}
	// {{{ protected function getCategories()

	protected function getCategories()
	{
		$sql = 'select id, title, shortname from Category
			where parent is null
			and id in
				(select category from VisibleCategoryView
				where region = %s or region is null)
			order by displayorder, title';

		$sql = sprintf($sql, $this->db->quote($this->region->id, 'integer'));
		$categories = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreCategoryWrapper'));

		return $categories;
	}

	// }}}
	// {{{ protected function getAttributes()

	protected function getAttributes($type = null)
	{
		$sql = 'select id, shortname, title, attribute_type from Attribute';

		if ($type !== null)
			$sql.= ' where (attribute_type & %s) != 0';

		$sql.= ' order by attribute_type, displayorder, id';

		if ($type !== null)
			$sql = sprintf($sql, $this->db->quote($type, 'integer'));

		$attributes = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreAttributeWrapper'));

		return $attributes;
	}

	// }}}
	// {{{ protected function hasInitialState()

	protected function hasInitialState($name)
	{
		if (!array_key_exists($name, $this->init_search_state) ||
			!array_key_exists($name, $this->process_search_state))
			return true;

		return ($this->init_search_state[$name] ==
				$this->process_search_state[$name]);
	}

	// }}}
}

?>
