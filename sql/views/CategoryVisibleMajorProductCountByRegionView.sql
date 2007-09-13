create or replace view CategoryVisibleMajorProductCountByRegionView as
	select
		CategoryDescendents.category,
		VisibleProductView.region,
		count(distinct VisibleProductView.product) as product_count
	from VisibleProductView 
		inner join CategoryProductBinding on
			VisibleProductView.product = CategoryProductBinding.product
				and CategoryProductBinding.minor = false
		inner join getCategoryDescendents(NULL) as CategoryDescendents on
			CategoryDescendents.descendent = CategoryProductBinding.category
	group by CategoryDescendents.category, VisibleProductView.region;
