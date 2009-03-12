<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreProduct.php';


/**
 * Order page for product images
 *
 * @package   Store
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageOrder extends AdminDBOrder
{
	// {{{ private properties

	/**
	 * @var StoreProduct
	 */
	private $product;

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

		$this->category_id = SiteApplication::initVar('category');

		$product_id = SiteApplication::initVar('product');
		$class_name = SwatDBClassMap::get('StoreProduct');
		$this->product = new $class_name();
		$this->product->setDatabase($this->app->db);

		if (!$this->product->load($product_id))
			throw new AdminNotFoundException(
				sprintf('Product with id ‘%s’ not found.', $product_id));
	}

	// }}}

	// process phase
	// {{{ protected function saveIndexes()

	/**
	 * Saves the updated ordering indexes of each option
	 *
	 * @see AdminOrder::saveIndex()
	 */
	protected function saveIndexes()
	{
		$count = 0;
		$order_widget = $this->ui->getWidget('order');

		foreach ($order_widget->values as $id) {
			$count++;
			$this->saveIndex($id, $count);
		}

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('product');
	}

	// }}}
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'ProductImageBinding',
			'integer:displayorder', $index,
			'integer:image', array($id));
	}

	// }}}
	// {{{ protected function getUpdatedMessage()

	protected function getUpdatedMessage()
	{
		return new SwatMessage(Store::_('Image order updated.'));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Store::_('Order Product Images');

		$this->ui->getWidget('options_field')->visible = false;
		$this->ui->getWidget('order')->height = '350px';
		// TODO: load this from the ImageDimension so it always fits
		$this->ui->getWidget('order')->width = '105px';
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('product', $this->product->id);
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$order_widget = $this->ui->getWidget('order');

		foreach ($this->product->images as $image) {
			$order_widget->addOption($image->id,
				$image->getImgTag('thumb', '../'),
				'text/xml');
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();

		if ($this->category_id !== null) {
			$this->navbar->addEntry(new SwatNavBarEntry(
				Store::_('Product Categories'), 'Category'));

			$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getCategoryNavbar', array($this->category_id));

			foreach ($cat_navbar_rs as $entry)
				$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
					'Category/Index?id='.$entry->id));
		}

		if ($this->category_id === null)
			$link = sprintf('Product/Details?id=%s', $this->product->id);
		else
			$link = sprintf('Product/Details?id=%s&category=%s',
				$this->product->id, $this->category_id);

		$this->navbar->addEntry(
			new SwatNavBarEntry($this->product->title, $link));

		$this->navbar->addEntry(
			new SwatNavBarEntry(Store::_('Order Product Images')));

		$this->title = $this->product->title;
	}

	// }}}
}

?>
