create or replace view CategoryCatalogView as
	select CategoryDescendants.category, Product.catalog
	from Product 
	inner join CategoryProductBinding on Product.id = CategoryProductBinding.product
	inner join getCategoryDescendants(NULL) as CategoryDescendants on CategoryDescendants.descendant = CategoryProductBinding.category
	group by CategoryDescendants.category, Product.catalog;
