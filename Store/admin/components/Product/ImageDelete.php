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

		$yes_button = $this->ui->getWidget('yes_button');
		$yes_button->title = Store::_('Remove');
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		// get image id from product
		$sql = sprintf('select primary_image from Product where id in (%s)',
			$this->getItemList('integer'));

		$image_id = SwatDB::queryOne($this->app->db, $sql);

		if ($image_id !== null) {
			// remove image from product
			$sql = sprintf('update Product set primary_image = null
				where id in (%s)', $this->getItemList('integer'));

			SwatDB::exec($this->app->db, $sql);

			// check if image is attached to other products
			$sql = sprintf('select count(id) from Product
				where primary_image = %s',
				$this->app->db->quote($image_id, 'integer'));

			$image_count = SwatDB::queryOne($this->app->db, $sql);

			// delete image
			if ($image_count == 0) {
				$sql = sprintf('delete from Image where id = %s',
					$this->app->db->quote($image_id, 'integer'));

				$num = SwatDB::exec($this->app->db, $sql);

				// delete the actual files
				$class_map = StoreClassMap::instance();
				$product_image = $class_map->resolveClass('StoreProductImage');
				$sizes = call_user_func(array($product_image, 'getSizes'));

				foreach ($sizes as $size => $dimensions)
					unlink('../images/products/'.$size.'/'.$image_id.'.jpg');
			}

			$msg = new SwatMessage(
				Store::_('One product image has been removed.'),
				SwatMessage::NOTIFICATION);

			$this->app->messages->add($msg);
		}
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

		$message = $this->ui->getWidget('confirmation_message');
		$image_display = new SwatImageDisplay();

		$sql = sprintf('select * from Image where id in
			(select primary_image from Product where id in (%s))',
			$this->getItemList('integer'));
			
		$images = SwatDB::query($this->app->db, $sql);

		ob_start();

		foreach ($images as $image) {
			$image_display->width = $image->small_width;
			$image_display->height = $image->small_height;
			$image_display->image =
				'../images/products/small/'.$image->id.'.jpg';

			$image_display->alt = sprintf('Image of %s', $this->product_title);
			$image_display->display();
		}

		$message->content = sprintf('<h3>%s</h3>',
			Store::ngettext('Remove the following image?',
			'Remove the following images?', count($this->items))).
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
			$id = $this->getFirstItem();
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
