<?php

/**
 * Cell renderer that displays regions for which a catalog is enabled.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalogStatusCellRenderer extends SwatCellRenderer
{
    /**
     * @var int
     */
    public $catalog;

    /**
     * @var MDB2_Driver_Common
     */
    public $db;

    public function __construct()
    {
        parent::__construct();
        $this->addStyleSheet(
            'packages/swat/styles/swat-null-text-cell-renderer.css'
        );
    }

    public function render()
    {
        if (!$this->visible) {
            return;
        }

        parent::render();

        $sql = sprintf(
            'select Region.title
			from Region where id in
				(select region from CatalogRegionBinding where catalog = %s)
			order by Region.title',
            $this->db->quote($this->catalog, 'integer')
        );

        $regions = SwatDB::query($this->db, $sql);

        if (count($regions) > 0) {
            $region_titles = [];
            foreach ($regions as $region) {
                $region_titles[] =
                    SwatString::minimizeEntities($region->title);
            }

            echo SwatString::toList($region_titles);
        } else {
            $span_tag = new SwatHtmlTag('span');
            $span_tag->class = 'swat-null-text-cell-renderer';
            $span_tag->setContent(sprintf('<%s>', Store::_('no regions')));
            $span_tag->display();
        }
    }
}
