<?php

require_once 'Site/pages/SiteExceptionPage.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreExceptionPage extends SiteExceptionPage
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->attemptRedirection();
	}

	// }}}
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		$suggestions = array();

		$suggestions['contact'] = sprintf(Store::_(
			'If you followed a link from our site or elsewhere, please '.
			'%scontact us%s and let us know where you came from so we can do '.
			'our best to fix it.'),
			'<a href="about/contact">', '</a>');

		$suggestions['typo'] = Store::_(
			'If you typed in the address, please double check the spelling.');

		$suggestions['search'] = Store::_(
			'If you are looking for a product or product information, try '.
			'browsing the product listing to the left or using the search box '.
			'on the top right.');

		return $suggestions;
	}

	// }}}
	// {{{ protected function attemptRedirection()

	protected function attemptRedirection()
	{
		$source = SiteApplication::initVar('source', SiteApplication::VAR_GET);
		$source_exp = explode('/', $source);

		if ($this->exception instanceof SiteNotFoundException &&
			$source_exp[0] == 'store') {

			array_shift($source_exp);
			$path = $this->getNewStorePath($source_exp);

			if ($path !== null) {
				// permanent redirect to the new path
				$path = $this->app->config->store->path.$path;
				$this->app->relocate($path, null, null, true);
			}
		}
	}

	// }}}
	// {{{ protected function getNewStorePath()

	protected function getNewStorePath(array $source_array)
	{
		$path = null;

		// check if the last element in the path is a sku
		if (count($source_array) > 1) {
			$path = $this->getNewStoreProductPath($source_array);
		}

		if ($path === null) {
			$path = $this->getNewStoreCategoryPath($source_array);
		}

		return $path;
	}

	// }}}
	// {{{ protected function getNewStoreProductPath()

	protected function getNewStoreProductPath(array $source_array)
	{
		$path = null;

		$sql = sprintf('select Product.*
			from Product
			inner join VisibleProductView on
				VisibleProductView.product = Product.id
					and VisibleProductView.region = %s
			where Product.shortname = %s',
			$this->app->db->quote($this->app->getRegion()->id, 'integer'),
			$this->app->db->quote(
				$source_array[count($source_array) - 1], 'text'));

		$products = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreProductWrapper'));

		if (count($products) > 0) {
			$product = $products->getFirst();
			$path = $product->path;
		}

		return $path;
	}

	// }}}
	// {{{ protected function getNewStoreCategoryPath()

	protected function getNewStoreCategoryPath(array $source_array)
	{
		$path = null;

		while (count($source_array) > 0) {
			$current_path = implode('/', $source_array);
			$shortname = array_pop($source_array);

			$sql = sprintf('select getCategoryPath(Category.id) as path
				from Category
				inner join VisibleCategoryView on
					VisibleCategoryView.category = Category.id
				where Category.shortname = %s
					and VisibleCategoryView.region = %s',
				$this->app->db->quote($shortname, 'text'),
				$this->app->db->quote(
					$this->app->getRegion()->id, 'integer'));

			$paths = SwatDB::query($this->app->db, $sql);
			if (count($paths) > 0) {
				$path = $paths->getFirst()->path;

				// if there's more than one path with the given shortname,
				// try to match the one that has the same parent paths
				// as the source.
				foreach ($paths as $row) {
					if ($current_path == $row->path) {
						$path = $row->path;
						break;
					}
				}
			}
		}

		return $path;
	}

	// }}}
}

?>
