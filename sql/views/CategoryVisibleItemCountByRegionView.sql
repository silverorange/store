create or replace view CategoryVisibleItemCountByRegionView as
	select
		CategoryDescendants.category,
		VisibleProductView.region,
		count(distinct Item.sku) as item_count
	from VisibleProductView
		inner join Product on
			Product.id = VisibleProductView.product
		inner join Item on
			Item.product = Product.id
		inner join CategoryProductBinding on
			VisibleProductView.product = CategoryProductBinding.product
		inner join getCategoryDescendants(NULL) as CategoryDescendants on
			CategoryDescendants.descendant = CategoryProductBinding.category
	group by CategoryDescendants.category, VisibleProductView.region;
