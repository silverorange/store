<?php

/**
 * A widget to allow selecting a catalog.
 *
 * @copyright 2005-2016 silverorange
 */
class StoreCatalogSelector extends SwatFlydown
{
    // {{{ constants

    public const ALL_CATALOGS = 1;
    public const ONE_CATALOG = 2;
    public const ALL_ENABLED_CATALOGS = 3;
    public const ALL_ENABLED_CATALOGS_IN_REGION = 4;

    // }}}
    // {{{ public properties

    public $db;

    public $scope = self::ALL_CATALOGS;
    public $catalog;
    public $region;

    // }}}
    // {{{ public function process()

    public function process()
    {
        parent::process();
        $this->parseValue($this->value);
    }

    // }}}
    // {{{ public function display()

    public function display()
    {
        $this->show_blank = false;

        $this->value =
            self::constructValue($this->scope, $this->catalog, $this->region);

        parent::display();
    }

    // }}}
    // {{{ public function setState()

    public function setState($state)
    {
        parent::setState($state);
        $this->parseValue($this->value);
    }

    // }}}
    // {{{ private static function constructValue()

    private static function constructValue(
        $scope,
        $catalog = null,
        $region = null
    ) {
        switch ($scope) {
            case self::ONE_CATALOG:
                return sprintf('%s_%s', $scope, $catalog);

            case self::ALL_ENABLED_CATALOGS_IN_REGION:
                return sprintf('%s_%s', $scope, $region);

            default:
                return $scope;
        }
    }

    // }}}
    // {{{ private function parseValue()

    private function parseValue($value)
    {
        $value_exp = explode('_', $value);

        $this->scope = $value_exp[0];
        $this->catalog = null;
        $this->region = null;

        switch ($this->scope) {
            case self::ONE_CATALOG:
                $this->catalog = (int) $value_exp[1];
                break;

            case self::ALL_ENABLED_CATALOGS_IN_REGION:
                $this->region = (int) $value_exp[1];
                break;

            case self::ALL_ENABLED_CATALOGS:
                break;

            default:
                $this->scope = self::ALL_CATALOGS;
        }
    }

    // }}}
    // {{{ protected function &getOptions()

    protected function &getOptions()
    {
        $options = [];

        $options[] = new SwatOption(
            self::constructValue(self::ALL_CATALOGS),
            Store::_('All')
        );

        $options[] = new SwatOption(
            self::constructValue(self::ALL_ENABLED_CATALOGS),
            Store::_('All Enabled')
        );

        $regions = SwatDB::getOptionArray(
            $this->db,
            'Region',
            'title',
            'id',
            'title'
        );

        foreach ($regions as $id => $title) {
            $options[] = new SwatOption(
                self::constructValue(
                    self::ALL_ENABLED_CATALOGS_IN_REGION,
                    null,
                    $id
                ),
                sprintf(Store::_('All Enabled in %s'), $title)
            );
        }

        $options[] = new SwatFlydownDivider('');

        $catalogs = SwatDB::getOptionArray(
            $this->db,
            'Catalog',
            'title',
            'id',
            'title'
        );

        foreach ($catalogs as $id => $title) {
            $options[] = new SwatOption(
                self::constructValue(self::ONE_CATALOG, $id),
                $title
            );
        }

        return $options;
    }

    // }}}
    // {{{ public function getSubQuery()

    public function getSubQuery()
    {
        switch ($this->scope) {
            case self::ALL_CATALOGS:
                return 'select Catalog.id from Catalog';

            case self::ONE_CATALOG:
                return $this->db->quote($this->catalog, 'integer');

            case self::ALL_ENABLED_CATALOGS:
                return 'select CatalogRegionBinding.catalog from
				CatalogRegionBinding';

            case self::ALL_ENABLED_CATALOGS_IN_REGION:
                return sprintf(
                    'select CatalogRegionBinding.catalog from
				CatalogRegionBinding where region = %s',
                    $this->db->quote($this->region, 'integer')
                );
        }
    }

    // }}}
}
