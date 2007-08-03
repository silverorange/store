<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'Store/dataobjects/StoreCategoryImage.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Delete confirmation page for category images 
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCategoryImageDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->id = SiteApplication::initVar('id');

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
		$sql = sprintf('select image from Category where id in (%s)',
			$this->getItemList('integer'));

		$image_id = SwatDB::queryOne($this->app->db, $sql);

		if ($image_id !== null) {
			// remove image from category
			$sql = sprintf('update Category set image = null where id in (%s)',
				$this->getItemList('integer'));

			SwatDB::exec($this->app->db, $sql);

			// delete image
			$sql = sprintf('delete from Image where id = %s',
				$this->app->db->quote($image_id, 'integer'));

			$num = SwatDB::exec($this->app->db, $sql);

			// delete the actual files
			$category_image = SwatDBClassMap::get('StoreCategoryImage');
			$sizes = call_user_func(array($category_image, 'getSizes'));

			foreach ($sizes as $size => $dimensions)
				unlink('../images/categories/'.$size.'/'.$image_id.'.jpg');

			$message = new SwatMessage(
				Store::_('The category image has been deleted.'),
				SwatMessage::NOTIFICATION);

			$this->app->messages->add($message);
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
		$form->addHiddenField('category', $this->id);

		$message = $this->ui->getWidget('confirmation_message');
		$image_display = new SwatImageDisplay();

		$sql = sprintf('select * from Image where id in
			(select image from Category where id in (%s))',
			$this->getItemList('integer'));
			
		$images = SwatDB::query($this->app->db, $sql);

		ob_start();

		foreach ($images as $image) {
			$image_display->width = $image->small_width;
			$image_display->height = $image->small_height;
			$image_display->image =
				'../images/categories/thumb/'.$image->id.'.jpg';

			$image_display->alt = sprintf(Store::_('Image of %s'),
				$this->title);

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

		$cat_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getCategoryNavbar', array($this->id));

		foreach ($cat_navbar_rs as $entry) {
			$this->title = $entry->title;
			$this->navbar->addEntry(new SwatNavBarEntry($entry->title,
				'Category/Index?id='.$entry->id));
		}

		$this->navbar->addEntry($last_entry);
	}

	// }}}
}

?>
