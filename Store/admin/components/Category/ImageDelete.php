<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/dataobjects/StoreCategory.php';

/**
 * Delete confirmation page for category images
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryImageDelete extends AdminDBDelete
{
	// {{{ private properties

	/**
	 * @var StoreCategory
	 */
	private $category;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui_xml = 'Store/admin/components/Product/image-delete.xml';
		parent::initInternal();

		$id = SiteApplication::initVar('id');

		$class_name = SwatDBClassMap::get('StoreCategory');
		$this->category = new $class_name();
		$this->category->setDatabase($this->app->db);

		if (!$this->category->load($id))
			throw new AdminNotFoundException(
				sprintf('Category with id ‘%s’ not found.', $id));
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$this->category->image->setFileBase('../images');
		$this->category->image->delete();

		$message = new SwatMessage(
			Store::_('The category image has been deleted.'),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Override the AdminDBDelete behaviour of redirecting to the component base
	 * as there is always a details page to return to.
	 */
	protected function relocate()
	{
		AdminDBConfirmation::relocate();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('id', $this->category->id);

		$container = $this->ui->getWidget('confirmation_container');
		$delete_view = $this->ui->getWidget('delete_view');

		$store = new SwatTableStore();
		$ds = new SwatDetailsStore();
		$ds->image = $this->category->image;
		$store->add($ds);
		$delete_view->model = $store;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content_type = 'text/xml';
		$message->content = sprintf('<strong>%s</strong>',
			Store::_('Are you sure you want to delete the following image?'));

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Store::_('Delete');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();

		$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getCategoryNavbar', array($this->category->id));

		foreach ($cat_navbar_rs as $entry) {
			$this->title = $entry->title;
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Delete Image')));
	}

	// }}}
}

?>
