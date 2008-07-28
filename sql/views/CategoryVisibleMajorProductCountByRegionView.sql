create or replace view CategoryVisibleMajorProductCountByRegionView as
	select
		CategoryDescendants.category,
		VisibleProductView.region,
		count(distinct VisibleProductView.product) as product_count
	from VisibleProductView
		inner join CategoryProductBinding on
			VisibleProductView.product = CategoryProductBinding.product
				and CategoryProductBinding.minor = false
		inner join getCategoryDescendants(NULL) as CategoryDescendants on
			CategoryDescendants.descendant = CategoryProductBinding.category
	group by CategoryDescendants.category, VisibleProductView.region;
