/**
 * Products are visible if they have at least one item that both exists for a
 * region, and that is enabled for that region and they are in a category and
 * their catalogue is available in the region (in season or out of season).
 *
 * This procedure is not recommended for use in the admin as it respects
 * catalogue availability.
 */
create or replace view VisibleProductView as
	select Product.id as product, ItemRegionBinding.region
		from Product
			inner join Item on Item.product = Product.id
			inner join ItemRegionBinding on Item.id = ItemRegionBinding.item
			inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
			inner join CatalogRegionBinding on Product.catalog = CatalogRegionBinding.catalog and
				CatalogRegionBinding.region = ItemRegionBinding.region
		where CatalogRegionBinding.available = true and ItemRegionBinding.enabled = true
		group by Product.id, ItemRegionBinding.region;
