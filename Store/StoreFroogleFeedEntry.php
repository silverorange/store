<?php

require_once 'AtomFeed/AtomFeedEntry.php';
require_once 'Store/StoreFroogleFeed.php';

/**
 * Google Merchant Center Product Feed Entry (formally known as Froogle)
 *
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://support.google.com/merchants/bin/answer.py?hl=en&answer=188494#US
 */
class StoreFroogleFeedEntry extends AtomFeedEntry
{
	// {{{ public properties

	public $name_space = 'g';
	public $custom_name_space = 'c';

	// }}}
	// {{{ required attributes

	/* id, title and link are required and handled in the parent class.
	 * image_link is also required and handled by
	 * {@link StoreFroogleFeedEntry::addImageLink()}.
	 * TODO: Tax and shipping are also required and require nested attributes.
	 */
	public $description;
	public $google_product_category;
	public $condition;
	public $availability;
	public $price;

	// two of three of these Unique Product Identifiers are required, if
	// possible all 3 are recommended.
	public $brand = null;
	public $gtin = null;
	public $mpn = null;

	// }}}
	// {{{ strongly recommended attributes

	public $product_type = null;

	// }}}
	// {{{ optional attributes

	public $sale_price = null;
	public $sale_price_effective_date = null;
	public $color = null;
	public $size = null;
	public $shipping_weight = null;
	public $online_only = null;
	public $expiration_date = null;
	public $product_review_average = null;
	public $product_review_count = null;

	// }}}
	// {{{ category specific attributes

	// apparel
	// size and colour are required for apparel
	public $gender = null;
	public $age_group = null;

	// variant fields
	// size and color are also used as part of variants.
	public $item_group_id = null;
	public $material = null;
	public $pattern = null;

	// }}}
	// {{{ private properties

	private $image_links = array();

	// TODO: these appear to be no longer used. Verify it.
	private $payments_accepted = array();
	private $shipping_types = array();

	// }}}
	// {{{ public function getNode()

	/**
	 * Get DOM node
	 */
	public function getNode($document)
	{
		$entry = parent::getNode($document);

		$entry->appendChild(StoreFroogleFeed::getCDATANode($document,
			'description', $this->description, $this->name_space));

		// required fields (id, title, description and link are handled in the
		// parent class)
		$count = 0;
		foreach ($this->image_links as $image_link) {
			// only the first image should be passed as image_link.
			if ($count == 0) {
				$attribute_title = 'image_link';
			} else {
				$attribute_title = 'additional_image_link';
			}

			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				$attribute_title, $image_link, $this->name_space));

			$count++;
		}

		$entry->appendChild(StoreFroogleFeed::getTextNode($document,
			'google_product_category', $this->google_product_category,
			$this->name_space));

		$entry->appendChild(StoreFroogleFeed::getTextNode($document,
			'condition', $this->condition, $this->name_space));

		$entry->appendChild(StoreFroogleFeed::getTextNode($document,
			'availability', $this->availability, $this->name_space));

		$entry->appendChild(StoreFroogleFeed::getTextNode($document,
			'price', $this->price, $this->name_space));

		// Unique Product identifiers, two out of three required.
		if ($this->brand !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'brand', $this->brand, $this->name_space));
		}

		if ($this->gtin !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'gtin', $this->gtin, $this->name_space));
		}

		if ($this->mpn !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'mpn', $this->mpn, $this->name_space));
		}

		if ($this->product_review_average !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'product_review_average', (float)$this->product_review_average,
				$this->name_space));
		}

		if ($this->product_review_count !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'product_review_count', (float)$this->product_review_count,
				$this->name_space));
		}

		// strongly recommended.
		if ($this->product_type !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'product_type', $this->product_type, $this->name_space));
		}

		// optional fields
		if ($this->sale_price !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'sale_price', $this->price, $this->name_space));
		}

		// TODO: this is really a two part date field, handle it in a more
		// intelligent way. Also, how do we handle sales with an unknown end
		// date?
		if ($this->sale_price_effective_date !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'sale_price_effective_date', $this->price, $this->name_space));
		}

		if ($this->color !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'color', $this->color, $this->name_space));
		}

		if ($this->size !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'size', $this->size, $this->name_space));
		}

		if ($this->shipping_weight !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'shipping_weight', $this->shipping_weight, $this->name_space));
		}

		if ($this->online_only !== null) {
			$entry->appendChild(StoreFroogleFeed::getBooleanNode($document,
				'online_only', $this->online_only, $this->name_space));
		}

		if ($this->expiration_date !== null) {
			$entry->appendChild(StoreFroogleFeed::getDateNode($document,
				'expiration_date', $this->expiration_date, $this->name_space));
		}

		if ($this->gender !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'gender', $this->gender, $this->name_space));
		}

		if ($this->age_group !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'age_group', $this->age_group, $this->name_space));
		}

		if ($this->item_group_id !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'item_group_id', $this->age_group, $this->name_space));
		}

		if ($this->material !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'material', $this->age_group, $this->name_space));
		}

		if ($this->pattern !== null) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'pattern', $this->age_group, $this->name_space));
		}

		// deprecated, need to confirm.
		foreach ($this->payments_accepted as $payment_accepted) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'payment_accepted', $payment_accepted, $this->name_space));
		}

		foreach ($this->shipping_types as $shipping_type) {
			$entry->appendChild(StoreFroogleFeed::getTextNode($document,
				'shipping_type', $shipping_type, $this->name_space));
		}

		return $entry;
	}

	// }}}
	// {{{ public function addImageLink()

	/**
	 * Add image link
	 */
	public function addImageLink($uri)
	{
		$this->image_links[] = $uri;
	}

	// }}}
	// {{{ public function addAcceptedPayment()

	/**
	 * Add accepted payment type
	 */
	public function addAcceptedPayment($title)
	{
		$this->payments_accepted[] = $title;
	}

	// }}}
	// {{{ public function addShippingType()

	/**
	 * Add shipping type
	 */
	public function addShippingType($title)
	{
		$this->shipping_types[] = $title;
	}

	// }}}
}

?>
