-- used in the admin to get category product count
-- don't use on front end as no visibility rules are respected
create or replace view CategoryProductCountByCatalogView as
	select CategoryDescendants.category, Product.catalog,
		count(distinct Product.id) as product_count
	from Product 
		inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
		inner join getCategoryDescendants(NULL) as CategoryDescendants on CategoryDescendants.descendant = CategoryProductBinding.category
	group by CategoryDescendants.category, Product.catalog;
