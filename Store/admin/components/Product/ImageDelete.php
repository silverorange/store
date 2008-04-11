<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/dataobjects/StoreProduct.php';
require_once 'Store/dataobjects/StoreProductImage.php';

/**
 * Delete confirmation page for product images
 *
 * This delete page only deletes (removes) one image from a single product.
 * Multiple deletes are not supported. If the product image is present in more
 * than one product it is just unlinked from the product otherwise it is
 * removed from the images table and the file is deleted.
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageDelete extends AdminDBDelete
{
	// {{{ private properties

	protected $ui_xml = 'Store/admin/components/Product/image-delete.xml';

	// }}}
	// {{{ private properties

	/**
	 * @var StoreProduct
	 */
	private $product;

	/**
	 * @var StoreProductImage
	 */
	private $image;

	/**
	 * Optional id of the product's current category. This is only used to
	 * maintain the proper navbar breadcrumbs when getting to this page by
	 * browsing the categories.
	 *
	 * @var integer
	 */
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui_xml = $this->ui_xml;

		$this->category_id = SiteApplication::initVar('category');

		$product_id = SiteApplication::initVar('product');
		$class_name = SwatDBClassMap::get('StoreProduct');
		$this->product = new $class_name();
		$this->product->setDatabase($this->app->db);

		if (!$this->product->load($product_id))
			throw new AdminNotFoundException(
				sprintf('Product with id ‘%s’ not found.', $product_id));

		$image_id = $this->getFirstItem();
		$class_name = SwatDBClassMap::get('StoreProductImage');
		$this->image = new $class_name();
		$this->image->setDatabase($this->app->db);

		if (!$this->image->load($image_id))
			throw new AdminNotFoundException(
				sprintf('Product mage with id ‘%s’ not found.', $image_id));
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		// we need a count products that use this image to help decide to delete
		// the actual image or not
		$sql = sprintf('select count(product) from ProductImageBinding
			where image = %s',
			$this->app->db->quote($this->image->id, 'integer'));

		$num_products = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf('delete from ProductImageBinding where image = %s
			and product = %s',
			$this->app->db->quote($this->image->id, 'integer'),
			$this->app->db->quote($this->product->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		if ($num_products === 1) {
			$this->image->setFileBase('../images');
			$this->image->delete();
			$message_text = Store::_('One product image has been deleted.');
		} else {
			$message_text = Store::_('One product image has been removed.');
		}

		$message = new SwatMessage($message_text, SwatMessage::NOTIFICATION);
		$this->app->messages->add($message);
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

		$this->buildNavBar();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('product', $this->product->id);

		$container = $this->ui->getWidget('confirmation_container');
		$delete_view = $this->ui->getWidget('delete_view');

		$store = new SwatTableStore();
		$ds = new SwatDetailsStore();
		$ds->image = $this->image;
		$store->add($ds);
		$delete_view->model = $store;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content_type = 'text/xml';
		$message->content = sprintf('<strong>%s</strong>',
			Store::_('Are you sure you want to remove the following image?'));

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Store::_('Remove');
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->category_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry) {
				$this->title = $entry->title;
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
			}
		}

		if ($this->single_delete) {
			if ($this->category_id === null)
				$link = sprintf('Product/Details?id=%s', $this->product->id);
			else
				$link = sprintf('Product/Details?id=%s&category=%s',
					$this->product->id, $this->category_id);

			$this->navbar->addEntry(new SwatNavBarEntry($this->product->title,
				$link));

			$this->title = $this->product->title;
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Store::_('Remove Image')));
	}

	// }}}
}

?>
