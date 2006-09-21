create or replace view CategoryCatalogView as
	select CategoryDescendents.category, Product.catalog
	from Product 
	inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
	inner join getCategoryDescendents(NULL) as CategoryDescendents on CategoryDescendents.descendent = CategoryProductBinding.category
	group by CategoryDescendents.category, Product.catalog;
