/**
 * Products are visible in a region R if they have at least one item that
 * both exists for R, and that is enabled for R; they are in a category; and
 * their catalogue is has a binding to R.
 *
 * Use of this view is not recommended for use in the admin as it respects
 * catalogue availability (as determined by CatalogRegionBinding).
 */
create or replace view VisibleProductView as
	select Product.id as product, ItemRegionBinding.region
		from Product
			inner join Item on Item.product = Product.id
			inner join ItemRegionBinding on Item.id = ItemRegionBinding.item
			inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
			inner join CatalogRegionBinding on Product.catalog = CatalogRegionBinding.catalog and
				CatalogRegionBinding.region = ItemRegionBinding.region
			inner join CatalogPrimaryProductView on Product.id = CatalogPrimaryProductView.product and
				Product.catalog = CatalogPrimaryProductView.catalog
		where ItemRegionBinding.enabled = true
		group by Product.id, ItemRegionBinding.region;
