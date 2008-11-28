<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Store/pages/StoreCheckoutEditPage.php';
require_once 'Store/dataobjects/StoreShippingTypeWrapper.php';

/**
 * Shipping type edit page of checkout
 *
 * @package   Store
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutShippingTypePage extends StoreCheckoutEditPage
{
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->ui_xml = 'Store/pages/checkout-shipping-type.xml';
	}

	// }}}

	// process phase
	// {{{ public function processCommon()

	public function processCommon()
	{
		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$class_name = SwatDBClassMap::get('StoreShippingType');
		$shipping_type = new $class_name();
		$shipping_type->setDatabase($this->app->db);
		$shortname = $this->ui->getWidget('shipping_type')->value;
		$shipping_type->loadByShortname($shortname);

		$this->app->session->order->shipping_type = $shipping_type;
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$this->buildForm();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		/*
		 * Set page to two-column layout when page is stand-alone even when
		 * there is no address list. The narrower layout of the form fields
		 * looks better even without a select list on the left.
		 */
		$this->ui->getWidget('form')->classes[] = 'checkout-no-column';
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$this->buildShippingTypes();

		if (!$this->ui->getWidget('form')->isProcessed())
			$this->loadDataFromSession();
	}

	// }}}
	// {{{ protected function buildShippingTypes()

	protected function buildShippingTypes()
	{
		$types = $this->getShippingTypes();
		$type_flydown = $this->ui->getWidget('shipping_type');

		foreach ($types as $type) {
			$title = $this->getShippingTypeTitle($type);
			$type_flydown->addOption(
				new SwatOption($type->shortname, $title, 'text/xml'));
		}
	}

	// }}}
	// {{{ protected function getShippingTypeTitle()

	protected function getShippingTypeTitle(StoreShippingType $type)
	{
		$title = $type->title;

		if (strlen($type->note) > 0) {
			$title.= sprintf('<br /><span class="swat-note">%s</span>',
				$type->note);
		}

		return $title;
	}

	// }}}
	// {{{ protected function loadDataFromSession()

	protected function loadDataFromSession()
	{
		$order = $this->app->session->order;

		if ($order->shipping_type !== null) {
			$this->ui->getWidget('shipping_type')->value =
				$order->shipping_type->shortname;
		}
	}

	// }}}
	// {{{ protected function getShippingTypes()

	/**
	 * Gets available shipping types for new shipping methods
	 *
	 * @return StoreShippingTypeWrapper
	 */
	protected function getShippingTypes()
	{
		$sql = 'select ShippingType.*
			from ShippingType
			where id in (
				select shipping_type from ShippingRate where region = %s)
			order by displayorder, title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->app->getRegion()->id, 'integer'));

		$wrapper = SwatDBClassMap::get('StoreShippingTypeWrapper');
		$types = SwatDB::query($this->app->db, $sql, $wrapper);

		return $types;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/store/styles/store-checkout-shipping-type-page.css',
			Store::PACKAGE_ID));

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
