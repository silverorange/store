create or replace view CategoryVisibleItemCountByRegionView as
	select
		CategoryDescendants.category,
		ItemRegionBinding.region,
		count(distinct Item.sku) as item_count
	from ItemRegionBinding
		inner join Item on
			Item.id = ItemRegionBinding.Item
		inner join Product on
			Product.id = Item.product
		inner join Catalog on
			Catalog.id = Product.catalog
		inner join CategoryProductBinding on
			Product.id = CategoryProductBinding.product
		inner join getCategoryDescendants(NULL) as CategoryDescendants on
			CategoryDescendants.descendant = CategoryProductBinding.category
	where ItemRegionBinding.enabled = true and Catalog.in_season = true
	group by CategoryDescendants.category, ItemRegionBinding.region;
