create or replace view CategoryAvailableProductCountByRegionView as
	select
		CategoryDescendants.category,
		AvailableProductView.region,
		count(distinct AvailableProductView.product) as product_count
	from AvailableProductView
		inner join CategoryProductBinding on
			AvailableProductView.product = CategoryProductBinding.product
		inner join getCategoryDescendants(NULL) as CategoryDescendants on
			CategoryDescendants.descendant = CategoryProductBinding.category
	group by CategoryDescendants.category, AvailableProductView.region;
