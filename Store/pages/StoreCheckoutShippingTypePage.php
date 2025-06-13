<?php

/**
 * Shipping type edit page of checkout.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutShippingTypePage extends StoreCheckoutEditPage
{
    // {{{ protected function getUiXml()

    protected function getUiXml()
    {
        return __DIR__ . '/checkout-shipping-type.xml';
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
    // {{{ protected function buildForm()

    protected function buildForm()
    {
        $this->buildShippingTypes();

        if (!$this->ui->getWidget('form')->isProcessed()) {
            $this->loadDataFromSession();
        }
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
                new SwatOption($type->shortname, $title, 'text/xml')
            );
        }
    }

    // }}}
    // {{{ protected function getShippingTypeTitle()

    protected function getShippingTypeTitle(StoreShippingType $type)
    {
        $title = $type->title;

        if (mb_strlen($type->note) > 0) {
            $title .= sprintf(
                '<br /><span class="swat-note">%s</span>',
                $type->note
            );
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
     * Gets available shipping types for new shipping methods.
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

        $sql = sprintf(
            $sql,
            $this->app->db->quote($this->app->getRegion()->id, 'integer')
        );

        $wrapper = SwatDBClassMap::get('StoreShippingTypeWrapper');

        return SwatDB::query($this->app->db, $sql, $wrapper);
    }

    // }}}

    // finalize phase
    // {{{ public function finalize()

    public function finalize()
    {
        parent::finalize();

        $this->layout->addHtmlHeadEntrySet(
            $this->ui->getRoot()->getHtmlHeadEntrySet()
        );
    }

    // }}}
}
