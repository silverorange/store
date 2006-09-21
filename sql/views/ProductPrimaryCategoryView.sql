create or replace view ProductPrimaryCategoryView as
	select product, min(category) as primary_category
	from CategoryProductBinding
	group by product;
