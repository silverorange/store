<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreCategoryPath.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreCategoryImageWrapper.php';

/**
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @see       StoreStorePageFactory
 */
abstract class StoreStorePage extends StorePage
{
	// {{{ protected properties

	/**
	 * @var StoreCategoryPath
	 */
	protected $path;

	// }}}
	// {{{ public function setPath()

	public function setPath(StoreCategoryPath $path)
	{
		$this->path = $path;
	}

	// }}}

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
		$secondary_category = $this->path->get(1);

		if ($secondary_category !== null)
			return $secondary_category->id;

		return null;
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
		parent::build();

		$this->layout->navbar->createEntry('Store', 'store');
	}

	// }}}
	// {{{ protected function queryCategory()

	protected function queryCategory($category_id)
	{
		$sql = 'select * from Category where id = %s';

		$sql = sprintf($sql,
			$this->app->db->quote($category_id, 'integer'));

		$categories = SwatDB::query($this->app->db, $sql,
			'StoreCategoryWrapper');

		return $categories->getFirst();
	}

	// }}}
}

?>
