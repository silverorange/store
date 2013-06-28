<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * Note: This recordset automatically loads attributes for products when
 *       constructed from a database result. If this behaviour is undesirable,
 *       set the lazy_load option to true.
 *
 * @package   Store
 * @copyright 2006-2013 silverorange
 */
class StoreProductWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public function initializeFromResultSet()

	public function initializeFromResultSet(MDB2_Result_Common $rs)
	{
		parent::initializeFromResultSet($rs);

		// efficiently load all product attributes at once
		if (!$this->getOption('lazy_load')) {
			$this->loadAttributes();
		}
	}

	// }}}
	// {{{ public function loadAttributes()

	public function loadAttributes()
	{
		if ($this->getCount() > 0) {
			$wrapper_class = SwatDBClassMap::get('StoreAttributeWrapper');

			$product_ids = array();
			foreach ($this->getArray() as $product) {
				$product_ids[] = $product->id;
				$product->attributes = new $wrapper_class();
			}
			$product_ids = $this->db->datatype->implodeArray($product_ids,
				'integer');

			$sql = sprintf('select ProductAttributeBinding.*
				from ProductAttributeBinding
				inner join Attribute
					on ProductAttributeBinding.attribute = Attribute.id
				where ProductAttributeBinding.product in (%s)
				order by ProductAttributeBinding.product,
					Attribute.displayorder',
				$product_ids);

			$bindings = SwatDB::query($this->db, $sql);

			if (count($bindings) === 0)
				return;

			$attribute_ids = array();
			foreach ($bindings as $binding) {
				$attribute_ids[] = $binding->attribute;
			}
			$attribute_ids = $this->db->datatype->implodeArray(
				$attribute_ids, 'integer');

			$sql = sprintf('select Attribute.*
				from Attribute
				where Attribute.id in (%s)',
				$attribute_ids);

			$attributes = SwatDB::query($this->db, $sql, $wrapper_class);

			foreach ($bindings as $binding) {
				$product   = $this->getByIndex($binding->product);
				$attribute = $attributes->getByIndex($binding->attribute);
				$product->attributes->add($attribute);
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
