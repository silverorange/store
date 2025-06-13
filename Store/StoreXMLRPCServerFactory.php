<?php

/**
 * @copyright 2007-2016 silverorange
 */
class StoreXMLRPCServerFactory extends SiteXMLRPCServerFactory
{
    public function __construct(SiteApplication $app)
    {
        parent::__construct($app);

        // set location to load Store page classes from
        $this->page_class_map['Store'] = 'Store/pages';
    }

    protected function getPageMap(): array
    {
        return [
            'search-panel' => 'StoreSearchPanelServer',
            'cart'         => 'StoreCartServer',
        ];
    }
}
