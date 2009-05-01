<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Store/StoreCategoryPath.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/dataobjects/StoreCategoryImageWrapper.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @see       StorePageFactory
 */
abstract class StorePage extends SitePathPage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->layout->selected_top_category_id =
			$this->getSelectedTopCategoryId();

		$this->layout->selected_secondary_category_id =
			$this->getSelectedSecondaryCategoryId();

		$this->layout->selected_category_id = $this->getSelectedCategoryId();

		$this->initInternal();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
	}

	// }}}
	// {{{ protected function getSelectedTopCategoryId()

	protected function getSelectedTopCategoryId()
	{
		$category_id = null;

		if ($this->path !== null) {
			$top_category = $this->path->getFirst();
			if ($top_category !== null)
				$category_id = $top_category->id;
		}

		return $category_id;
	}

	// }}}
	// {{{ protected function getSelectedSecondaryCategoryId()

	protected function getSelectedSecondaryCategoryId()
	{
		$secondary_category_id = null;

		if ($this->path !== null) {
			$secondary_category = $this->path->get(1);
			if ($secondary_category !== null)
				$secondary_category_id = $secondary_category->id;
		}

		return $secondary_category_id;
	}

	// }}}
	// {{{ protected function getSelectedCategoryId()

	protected function getSelectedCategoryId()
	{
		return null;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		if (property_exists($this->layout, 'navbar'))
			$this->layout->navbar->createEntry(Store::_('Store'), 'store');

		parent::build();
	}

	// }}}
	// {{{ protected function queryCategory()

	protected function queryCategory($category_id)
	{
		$key = 'StorePage.category.'.$category_id;
		$category = $this->app->getCacheValue($key, 'product');
		if ($category !== false) {
			if ($category !== null) {
				$category->setDatabase($this->app->db);
				$category->setRegion($this->app->getRegion());
			}

			return $category;
		}

		$sql = sprintf('select * from Category where id = %s',
			$this->app->db->quote($category_id, 'integer'));

		$categories = SwatDB::query($this->app->db, $sql,
			'StoreCategoryWrapper');

		$category = $categories->getFirst();
		$this->app->addCacheValue($category, $key, 'product');
		return $category;
	}

	// }}}
}

?>
