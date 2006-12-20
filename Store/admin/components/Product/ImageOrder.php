<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for product images
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProductImageOrder extends AdminDBOrder
{
	// {{{ private properties

	private $product_id;
	private $category_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->product_id = SiteApplication::initVar('product');
		$this->category_id = SiteApplication::initVar('category');
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

		$this->ui->getWidget('order')->height = '400px';
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		parent::buildForm();

		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('product', $this->product_id);
		$form->addHiddenField('category', $this->category_id);
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{ 
		$order_widget = $this->ui->getWidget('order');

		$sql = sprintf('select id, thumb_width, thumb_height
			from Image
			inner join ProductImageBinding
				on ProductImageBinding.image = Image.id
			where product = %s
			order by displayorder',
			$this->app->db->quote($this->product_id, 'integer'));

		$images = SwatDB::query($this->app->db, $sql);
		foreach ($images as $image) {
			$widget = new SwatImageDisplay();
			$widget->image = '../images/products/thumb/'.$image->id.'.jpg';
			$widget->width = $image->thumb_width;
			$widget->height = $image->thumb_height;

			ob_start();
			$widget->display();
			$image_tag = ob_get_clean();

			$order_widget->addOption($image->id, $image_tag, 'text/xhtml');
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar() 
	{
		parent::buildNavBar();
		$last_entry = $this->navbar->popEntry();
		$this->navbar->popEntry();
 
		$this->navbar->addEntry(new SwatNavBarEntry(
			Store::_('Product Categories'), 'Category'));

		$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getCategoryNavbar', array($this->category_id));

		foreach ($cat_navbar_rs as $entry)
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));

		$product_title = SwatDB::queryOneFromTable($this->app->db, 'Product',
			'text:title', 'id', $this->product_id);

		$link = sprintf('Product/Details?id=%s&category=%s', $this->product_id, 
			$this->category_id);

		$this->navbar->addEntry(new SwatNavBarEntry($product_title, $link));
		$this->navbar->addEntry($last_entry);
		$this->title = $product_title;
	}

	// }}}
}

?>
