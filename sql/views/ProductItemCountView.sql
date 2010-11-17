create or replace view ProductItemCountView as

select Product.id as product,
	count(distinct Item.id) as item_count

from Product
left outer join Item on Item.product = Product.id

group by Product.id;
