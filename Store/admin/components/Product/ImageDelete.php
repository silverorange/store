<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'Store/dataobjects/StoreProductImage.php';
require_once 'Store/StoreClassMap.php';

/**
 * Delete confirmation page for product images 
 *
 * This delete page only deletes (removes) one image from a single product.
 * Multiple deletes are not supported. If the product image is present in more
 * than one product it is just unlinked from the product otherwise it is
 * removed from the images table and the file is deleted.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class StoreProductImageDelete extends AdminDBDelete
{
	// {{{ private properties

	private $category_id = null;
	private $product_title = null;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->category_id = SiteApplication::initVar('category');
		$this->product_id = SiteApplication::initVar('product');

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Store::_('Remove');
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$image_id = $this->getFirstItem();

		// get products with this image as primary_image
		$sql = sprintf('select count(id) from Product where primary_image = %s',
			$this->app->db->quote($image_id, 'integer'));

		$products = SwatDB::queryOne($this->app->db, $sql);

		if ($products > 1) {
			$sql = sprintf('delete from ProductImageBinding where image = %s
				and product = %s',
				$this->app->db->quote($image_id, 'integer'),
				$this->app->db->quote($this->product_id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		} else {
			$sql = sprintf('delete from Image where id = %s',
				$this->app->db->quote($image_id, 'integer'));

			SwatDB::exec($this->app->db, $sql);

			// delete the actual files
			$class_map = StoreClassMap::instance();
			$product_image = $class_map->resolveClass('StoreProductImage');
			$sizes = call_user_func(array($product_image, 'getSizes'));

			foreach ($sizes as $size => $dimensions)
				unlink('../images/products/'.$size.'/'.$image_id.'.jpg');

		}

		// set the primary_image to the next image on the product, if
		// none, primary_image = null
		$sql = sprintf('update Product set primary_image =
			(select image from ProductImageBinding
				where ProductImageBinding.product = Product.id
				order by displayorder
				limit 1)
			where id = %s',
			$this->app->db->quote($this->product_id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$msg = new SwatMessage(
			Store::_('One product image has been removed.'),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($msg);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildNavBar();

		$image_id = $this->getFirstItem();

		$form = $this->ui->getWidget('confirmation_form');
		$form->addHiddenField('category', $this->category_id);
		$form->addHiddenField('product', $this->product_id);

		$message = $this->ui->getWidget('confirmation_message');
		$image_display = new SwatImageDisplay();

		$sql = sprintf('select * from Image where id = %s',
			$this->app->db->quote($image_id, 'integer'));
			
		$image = SwatDB::queryRow($this->app->db, $sql);

		ob_start();

		$image_display->width = $image->thumb_width;
		$image_display->height = $image->thumb_height;
		$image_display->image =
			'../images/products/thumb/'.$image->id.'.jpg';

		$image_display->alt = sprintf('Image of %s', $this->product_title);
		$image_display->display();

		$message->content = sprintf('<h3>%s</h3>',
			Store::_('Remove the following image?')).
			ob_get_clean();

		$message->content_type = 'text/xml';
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar() 
	{
		$last_entry = $this->navbar->popEntry();
		$last_entry->title = Store::_('Remove Image');

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
			$id = $this->product_id;
			$this->product_title = SwatDB::queryOneFromTable($this->app->db,
				'Product', 'text:title', 'id', $id);

			if ($this->category_id === null)
				$link = sprintf('Product/Details?id=%s', $id);
			else
				$link = sprintf('Product/Details?id=%s&category=%s', $id, 
					$this->category_id);

			$this->navbar->addEntry(new SwatNavBarEntry($this->product_title,
				$link));

			$this->title = $this->product_title;
		}

		$this->navbar->addEntry($last_entry);
	}

	// }}}
}

?>
