<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreProductWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function __construct()

	/**
	 * Creates a new recordset wrapper
	 *
	 * @param MDB2_Result $recordset optional. The MDB2 recordset to wrap.
	 */
	public function __construct($recordset = null)
	{
		parent::__construct($recordset);

		// efficiently load all attributes at once
		if ($this->getCount() > 0) {
			$wrapper_class =
				SwatDBClassMap::get('StoreAttributeWrapper');

			$product_ids = array();
			foreach ($this->getArray() as $product) {
				$product_ids[] = $this->db->quote($product->id, 'integer');
				$product->attributes = new $wrapper_class();
			}

			$sql = sprintf('select ProductAttributeBinding.*
				from ProductAttributeBinding
				where ProductAttributeBinding.product in (%s)
				order by product',
				implode(',', $product_ids));

			$bindings = SwatDB::query($this->db, $sql);

			if (count($bindings) == 0)
				return;

			$attribute_ids = array();
			foreach ($bindings as $binding)
				$attribute_ids[] = $this->db->quote($binding->attribute, 'integer');

			$sql = sprintf('select Attribute.*
				from Attribute
				where Attribute.id in (%s)
				order by displayorder',
				implode(',', $attribute_ids));

			$attributes = SwatDB::query($this->db, $sql, $wrapper_class);

			foreach ($attributes as $attribute) {
				foreach ($bindings as $binding) {
					if ($binding->attribute === $attribute->id) {
						$product = $this->getByIndex($binding->product);
						$product->attributes->add($attribute);
					}
				}
			}
		}
	}

	// }}}
	// {{{ public function setRegion()

	/**
	 * Sets the region for all products in this record set
	 *
	 * @param StoreRegion $region the region to use.
	 * @param boolean $limiting whether or not to not load this product if it is
	 *                           not available in the given region.
	 */
	public function setRegion(StoreRegion $region, $limiting = true)
	{
		foreach ($this as $product)
			$product->setRegion($region, $limiting);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('StoreProduct');
		$this->index_field = 'id';
	}

	// }}}
}

?>
