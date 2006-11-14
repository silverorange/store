<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminDependency.php';
require_once 'Store/StoreCatalogSwitcher.php';

/**
 * Enable items confirmation page for Categories
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategorySetItemEnabled extends AdminDBConfirmation
{
	// {{{ private properties

	private $category_id;
	private $enabled;
	private $region = null;

	// }}}
	// {{{ public function setCategory()

	public function setCategory($category_id)
	{
		$this->category_id = $category_id;
	}

	// }}}
	// {{{ public function setEnabled()

	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	// }}}
	// {{{ public function setRegion()

	public function setRegion($region)
	{
		$this->region = $region;
	}

	// }}}
	
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
		$this->region = SiteApplication::initVar('region');
		$this->enabled = SiteApplication::initVar('enabled', false);

		$this->catalog_switcher = new StoreCatalogSwitcher();
		$this->catalog_switcher->db = $this->app->db;
		$this->catalog_switcher->init();
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = sprintf('update ItemRegionBinding set enabled = %s where %s
			item in (%s)', $this->app->db->quote($this->enabled, 'boolean'),
			$this->getRegionQuerySQL(), $this->getItemQuerySQL());

		SwatDB::exec($this->app->db, $sql);

		$rs = SwatDB::query($this->app->db, $this->getItemQuerySQL());
		$num = count($rs);

		$msg = new SwatMessage(sprintf(Store::ngettext(
			'One item has been “%2$s” for “%3$s”.', 
			'%s items have been “%s” for “%s”.', $num),
			SwatString::numberFormat($num), $this->getEnabledText(),
			$this->getRegionTitle()), SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}
	
	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildNavBar();

		$rs = SwatDB::query($this->app->db, $this->getItemQuerySQL());
		$count = count($rs);

		if ($count == 0) {
			$this->switchToCancelButton();
			$msg = Store::_('There are no items in the selected categories.');
		} else {
			$msg = sprintf(Store::ngettext(
				'If you proceed, one item will be “%2$s” for “%3$s”.',
				'If you proceed, %s items will be “%s” for “%s”.',
				$count), SwatString::numberFormat($count),
				$this->getEnabledText(), $this->getRegionTitle());

			$this->ui->getWidget('yes_button')->title = sprintf(
				Store::ngettext('%s Item', '%s Items', $count),
				$this->getEnabledVerb());
		}

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $msg;
		$message->content_type = 'text/xml';

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('region', $this->region);

		//since we can't preserve type information when adding hidden fields
		if ($this->enabled)
			$form->addHiddenField('enabled', $this->enabled);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->category_id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getCategoryNavbar', array($this->category_id));
			
			foreach ($navbar_rs as $row)
				$this->navbar->addEntry(new SwatNavBarEntry($row->title,
					'Category/Index?id='.$row->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(sprintf(
			Store::_('%s Items Confirmation'), $this->getEnabledVerb())));
	}

	// }}}
	// {{{ private function getItemQuerySQL()

	private function getItemQuerySQL()
	{
		$item_list = $this->getItemList('integer');

		$sql = 'select distinct Item.id
				from Item
					inner join Product on Product.id = Item.product
					inner join CategoryProductBinding on 
						CategoryProductBinding.product = Product.id
					inner join getCategoryDescendents(null) as
						category_descendents on
						category_descendents.descendent =
							CategoryProductBinding.category
				where category_descendents.category in (%s)
					and Product.catalog in (%s)';

		$sql = sprintf($sql,
			$item_list,
			$this->catalog_switcher->getSubquery());

		return $sql;
	}

	// }}}
	// {{{ private function getRegionQuerySQL()

	private function getRegionQuerySQL()
	{
		$sql = '';

		if ($this->region > 0)
			$sql = sprintf('region = %s and', 
				$this->app->db->quote($this->region, 'integer'));

		return $sql;
	}

	// }}}
	// {{{ private function getRegionTitle()

	private function getRegionTitle()
	{
		if ($this->region > 0)
			$region_title = SwatDB::queryOne($this->app->db,
				sprintf('select title from Region where id = %s', 
					$this->region));
		else 
			$region_title = Store::_('All Regions');

		return $region_title;
	}

	// }}}
	// {{{ private function getEnabledVerb()

	private function getEnabledVerb()
	{
		return ($this->enabled) ? Store::_('Enable') : Store::_('Disable');
	}

	// }}}
	// {{{ private function getEnabledText()

	private function getEnabledText()
	{
		return ($this->enabled) ? Store::_('enabled') : Store::_('disabled');
	}

	// }}}
}

?>
