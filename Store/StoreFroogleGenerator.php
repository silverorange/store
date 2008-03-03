<?php

require_once 'Swat/SwatObject.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Store/StoreFroogleFeed.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'VanBourgondien/VanBourgondienCommandLineApplication.php';

/**
 * @package   VanBourgondien
 * @copyright 2008 silverorange
 */
class VanBourgondienFroogleGenerator extends SwatObject
{
	// {{{ private properties

	private $db;
	private $config;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new froogle generator
	 *
	 * @param MDB2_Driver_Common $db
	 * @param SiteConfigModule $config
	 */
	public function __construct(MDB2_Driver_Common $db,
		SiteConfigModule $config)
	{
		$this->db = $db;
		$this->config = $config;
	}

	// }}}
	// {{{ public function generate()

	public function generate()
	{
		$feed = new StoreFroogleFeed();

		$base_href = $this->config->uri->absolute_base;

		$feed->title = sprintf('%s Products', $this->config->site->title);
		$feed->addAuthor(new AtomFeedAuthor($this->config->site->title));
		$feed->link = new AtomFeedLink($base_href, 'self');
		$feed->id = sprintf('tag:%s,2008-01-01:/products/',
			substr($base_href, 7));

		$this->addEntries($feed);

		ob_start();
		$feed->display();
		return ob_get_clean();
	}

	// }}}
	// {{{ protected function addEntries()

	/**
	 * Add atom entries to the feed
	 */
	protected function addEntries($feed)
	{
		$date = new Date();
		$base_href = $this->config->uri->absolute_base;
		$expiration_date = new Date();
		$expiration_date->addSeconds(31536000);

		foreach ($this->getItems() as $item) {
			$id = $item->sku;
			if ($item->part_count > 1)
				$id.= '_part'.$item->part_count;

			$entry = new StoreFroogleFeedEntry($id, $item->product->title, $date);

			if ($item->product->primary_image !== null)
				$entry->image_link = $base_href.
					$item->product->primary_image->getURI('small');
			else
				$entry->image_link = '';

			$entry->summary = $this->getItemDescription($item);
			$entry->link = new AtomFeedLink($base_href.'store/'.$item->product->path);
			$entry->price = round($item->getDisplayPrice(), 2);
			$entry->product_type = 'bulbs';
			$entry->brand = $this->config->site->title;
			$entry->condition = 'new';
			$entry->expiration_date = $expiration_date;
			$entry->addAcceptedPayment('American Express');
			$entry->addAcceptedPayment('Discover');
			$entry->addAcceptedPayment('MasterCard');
			$entry->addAcceptedPayment('Visa');

			$feed->addEntry($entry);
		}
	}

	// }}}
	// {{{ private function getItemDescription()

	private function getItemDescription(StoreItem $item)
	{
		$description = array();

		if ($item->getDescription() != null)
			$description[] = $item->getDescription();

		if ($item->getBulbSize() !== null)
			$description[] = $item->getBulbSize();

		if ($item->product->description != null)
			$description[] = $item->product->description;

		if ($item->product->bodytext != null)
			$description[] = $item->product->bodytext;

		if ($item->getGroupDescription() !== null)
			$description[] = $item->getGroupDescription();

		if ($item->getPartCountDescription() !== null)
			$description[] = $item->getPartCountDescription();

		$description[] = sprintf('Item #%s', $item->sku);
		$description[] = sprintf('%s Catalog', $item->product->catalog->title);

		return implode(" - ", $description);
	}

	// }}}
	// {{{ private function getItems()

	/**
	 * Add atom entries to the feed
	 */
	private function getItems()
	{
		$class_name = SwatDBClassMap::get('StoreRegion');
		$region = new $class_name();
		$region->setDatabase($this->db);
		$region->load(VanBourgondienRegion::REGION_US);

		$sql = 'select Item.*, ItemRegionBinding.price, ItemRegionBinding.region
			from Item
			inner join AvailableItemView on AvailableItemView.item = Item.id
			inner join VisibleProductView on Item.product = VisibleProductView.product
			inner join ItemRegionBinding on ItemRegionBinding.item = Item.id';

		$class_name = SwatDBClassMap::get('StoreItemWrapper');
		$items = SwatDB::query($this->db, $sql, $class_name);
		$items->setRegion($region);

		$product_sql = 'select id, shortname, title, primary_category, catalog,
				bodytext, ProductPrimaryImageView.image as primary_image
			from Product
			left outer join ProductPrimaryImageView on
				ProductPrimaryImageView.product = Product.id
			left outer join ProductPrimaryCategoryView
				on ProductPrimaryCategoryView.product = Product.id
			where id in (%s)';

		$class_name = SwatDBClassMap::get('StoreProductWrapper');
		$products = $items->loadAllSubDataObjects('product', $this->db,
			$product_sql, $class_name);
		$products->setRegion($region);

		$items->attachSubDataObjects('product', $products);

		return $items;
	}

	// }}}
}

?>
