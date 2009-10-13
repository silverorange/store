<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Catalogs group products together into management units. Catalogs are not
 * typically visible in the frontend of a store. They often represent products
 * specific to an actual physical catalog.
 *
 * Every individual product belongs to exactly one catalog.
 *
 * A catalog is enabled in a region if it has a region binding in the
 * CatalogRegionBinding table for the region. Products in disabled
 * catalogs are not visible on the front-end for the disabled regions.
 *
 * Orthogonal to availability, catalogs are in season or out of season
 * based on the in_season boolean.
 *
 * Catalogs can be cloned to create a 'working-copy' of contained products.
 * The cloned catalog can be swapped with the parent catalog when the product
 * changes are ready to go live.
 *
 * @package   Store
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCatalog extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * In season
	 *
	 * Whether the current catalog is in season. The property can be used to
	 * either hide the product, or control how it is displayed.
	 *
	 * @var boolean
	 */
	public $in_season;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('clone_of',
			SwatDBClassMap::get('StoreCatalog'));

		$this->table = 'Catalog';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadClone()

	/**
	 * Loads the clone of this catalog
	 *
	 * This is different than clone_of in that it will load a child clone or a
	 * parent clone depending on what exists for this catalog. The clone_of
	 * field only loads the parent catalog.
	 *
	 * @return StoreCatalog
	 */
	protected function loadClone()
	{
		require_once 'Store/dataobjects/StoreCatalogWrapper.php';

		$sql = sprintf('select * from Catalog where id =
			(select clone from CatalogCloneView where catalog = %s)',
			$this->db->quote($this->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('StoreCatalogWrapper');
		$clones = SwatDB::query($this->db, $sql, $wrapper_class);

		return $clones->getFirst();
	}

	// }}}
}

?>
