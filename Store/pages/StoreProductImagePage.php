<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatToolLink.php';
require_once 'Store/pages/StoreStorePage.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreProductImagePage extends StoreStorePage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->back_link = new SwatToolLink();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$class_map = StoreClassMap::instance();
		$product_class = $class_map->resolveClass('StoreProduct');
		$product = new $product_class();
		$product->setDatabase($this->app->db);
		$product->setRegion($this->app->getRegion()->id);
		$product->load($this->product_id);
		$this->buildNavBar($product);
		$this->layout->data->title = sprintf('%s: Image', $product->title);

		$this->layout->addHtmlHeadEntrySet(
			$this->back_link->getHtmlHeadEntrySet());

		if ($product->primary_image === null)
			throw new SiteNotFoundException();

		$this->layout->startCapture('content');
		$this->displayImage($product);
		$this->layout->endCapture();
	}

	// }}}

	// {{{ private function buildNavBar()

	private function buildNavBar($product)
	{
		$link = 'store';

		foreach ($this->path as $path_entry) {
			$link .= '/'.$path_entry->shortname;
			$this->layout->navbar->createEntry($path_entry->title, $link);
		}

		$link .= '/'.$product->shortname;
		$this->layout->navbar->createEntry($product->title, $link);
		$this->layout->navbar->createEntry('Image');
	}

	// }}}
	// {{{ private function displayImage()

	private function displayImage($product)
	{
		$this->back_link->title = 'Back to Product Page';
		$this->back_link->link =
			$this->layout->navbar->getEntryByPosition(-1)->link;

		$this->back_link->display();

		$div = new SwatHtmlTag('div');
		$div->id = 'product_image_large';

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $product->primary_image->getURI('large');
		$img_tag->width = $product->primary_image->large_width;
		$img_tag->height = $product->primary_image->large_height;
		$img_tag->alt = 'Photo of '.$product->title;

		$div->open();
		$img_tag->display();
		$div->close();
	}

	// }}}
}

?>
