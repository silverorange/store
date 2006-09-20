<?php

require_once 'AtomFeed/AtomFeedEntry.php';

/**
 * Froogle feed entry
 *
 * @packageAtomFeed
 * @copyright 2005-2006 silverorange
 * @licensehttp://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFroogleFeedEntry extends AtomFeedEntry
{
	// {{{ public properties

	public $name_space = 'g';

	public $description;
	public $expiration_date;
	public $price;

	public $actor = null;
	public $apparel_type = null;
	public $artist = null;
	public $brand = null;
	public $color = null;
	public $condition = null;
	public $delivery_notes = null;
	public $delivery_radius = null;
	public $format = null;
	public $isbn = null;
	public $manufacturer = null;
	public $manufacturer_id = null;
	public $megapixels = null;
	public $memory = null;
	public $model_number = null;
	public $payment_notes = null;
	public $pickup = null;
	public $price_type = null;
	public $processor_speed = null;
	public $product_type = null;
	public $size = null;
	public $tax_percent = null;
	public $tax_region = null;
	public $upc = null;

	// }}}
	// {{{ private properties

	private $image_links = array();
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

		//required fields (id and title are handled in the parent class)

		$entry->appendChild(AtomFeed::getTextNode($document,
			'description', $this->description, $this->name_space));

		$entry->appendChild(AtomFeed::getDateNode($document,
			'expiration_date', $this->expiration_date, $this->name_space));

		foreach ($this->image_links as $image_link)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'image_link', $image_link, $this->name_space));

		$entry->appendChild(AtomFeed::getTextNode($document,
			'price', $this->price, $this->name_space));


		// optional fields (author is handled by the parent class)

		if ($this->actor !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'actor', $this->actor, $this->name_space));

		if ($this->apparel_type !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'apparel_type', $this->apparel_type, $this->name_space));

		if ($this->artist !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'artist', $this->artist, $this->name_space));

		if ($this->brand !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'brand', $this->brand, $this->name_space));

		if ($this->color !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'color', $this->color, $this->name_space));

		if ($this->condition !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'condition', $this->condition, $this->name_space));

		if ($this->delivery_notes !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'delivery_notes', $this->delivery_notes, $this->name_space));

		if ($this->delivery_radius !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'delivery_radius', $this->delivery_radius, $this->name_space));

		if ($this->format !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'format', $this->format, $this->name_space));

		if ($this->isbn !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'isbn', $this->isbn, $this->name_space));

		if ($this->link !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'link', $this->link, $this->name_space));

		if ($this->manufacturer !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'manufacturer', $this->manufacturer, $this->name_space));

		if ($this->manufacturer_id !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'manufacturer_id', $this->manufacturer_id, $this->name_space));

		if ($this->megapixels !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'megapixels', $this->megapixels, $this->name_space));

		if ($this->memory !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'memory', $this->memory, $this->name_space));

		if ($this->model_number !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'model_number', $this->model_number, $this->name_space));

		foreach ($this->payments_accepted as $payment_accepted)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'payment_accepted', $payment_accepted, $this->name_space));

		if ($this->payment_notes !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'payment_notes', $this->payment_notes, $this->name_space));

		if ($this->pickup !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'pickup', $this->pickup, $this->name_space));

		if ($this->price_type !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'price_type', $this->price_type, $this->name_space));

		if ($this->processor_speed !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'processor_speed', $this->processor_speed, $this->name_space));

		if ($this->product_type !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'product_type', $this->product_type, $this->name_space));

		foreach ($this->shipping_types as $shipping_type)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'shipping_type', $shipping_type, $this->name_space));

		if ($this->size !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'size', $this->size, $this->name_space));

		if ($this->tax_percent !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'tax_percent', $this->tax_percent, $this->name_space));

		if ($this->tax_region !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'tax_region', $this->tax_region, $this->name_space));

		if ($this->upc !== null)
			$entry->appendChild(AtomFeed::getTextNode($document,
				'upc', $this->upc, $this->name_space));

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
