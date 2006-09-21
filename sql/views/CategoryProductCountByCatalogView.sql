-- used in the admin to get category product count
-- don't use on front end as no visibility rules are respected
create or replace view CategoryProductCountByCatalogView as
	select CategoryDescendents.category, Product.catalog,
		count(distinct Product.id) as product_count
	from Product 
		inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
		inner join getCategoryDescendents(NULL) as CategoryDescendents on CategoryDescendents.descendent = CategoryProductBinding.category
	group by CategoryDescendents.category, Product.catalog;
