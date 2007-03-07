<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminDependency.php';
require_once 'Store/StoreCatalogSwitcher.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/StoreItemStatusList.php';
require_once 'Store/dataobjects/StoreItem.php';

/**
 * Item status change confirmation page for changing item status within the
 * category component 
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryChangeItemStatus extends AdminDBConfirmation
{
	// {{{ private properties

	private $category_id;

	/**
	 * @var StoreItemStatus
	 */
	private $status;

	// }}}
	// {{{ public function setCategory()

	public function setCategory($category_id)
	{
		$this->category_id = $category_id;
	}

	// }}}
	// {{{ public function setStatus()

	public function setStatus($status_id)
	{
		$this->status = StoreItemStatusList::statuses()->getById($status_id);
	}

	// }}}
	
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
		$this->setStatus(SiteApplication::initVar('status'));

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

		$item_list = $this->getItemList('integer');

		$sql = sprintf('update Item set status = %s where id in (%s)',
			$this->app->db->quote($this->status->id, 'integer'),
			$this->getItemQuerySQL());

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Store::ngettext(
			'One item has had its status set as “%s”.',
			'%s items have had their status set as “%s”.', $num),
			SwatString::numberFormat($num), $this->status->title),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
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
			$message_text = Store::_(
				'There are no items in the selected categories.');

		} else {
			$message_text = sprintf(Store::ngettext(
				'%3$sSet one item status as “%2$s”?%4$s',
				'%3$sSet %1$s item statuses as “%2$s”?%4$s', $count),
				SwatString::numberFormat($count), $this->status->title,
				'<h3>', '</h3>');

			$this->ui->getWidget('yes_button')->title =
				Store::ngettext('Change Item Status', 'Change Item Statuses',
				$count);
		}

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $message_text;
		$message->content_type = 'text/xml';

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('status', $this->status->id);
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

		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Change Item Status Confirmation')));
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
}

?>
