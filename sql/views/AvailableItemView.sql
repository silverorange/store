-- items are available if they have at least one item enabled for a region, and
-- have status = available, and they belong to a catalog that is enabled in one
-- region.
create or replace view AvailableItemView as
	select Item.id as item, ItemRegionBinding.region
		from Item
			inner join ItemRegionBinding on Item.id = ItemRegionBinding.item
			inner join Product on Item.product = Product.id
			inner join Catalog on Product.catalog = Catalog.id
			inner join CatalogRegionBinding on
				Catalog.id = CatalogRegionBinding.catalog
		where ItemRegionBinding.enabled = true
			and (Item.status = 0);
