create or replace view ProductItemCountByStatusView as

select Product.id as product,
	count(distinct Item.id) as count_total,
	count(distinct Item_available.id) as count_available,
	count(distinct Item_outofstock.id) as count_outofstock,
	count(distinct Item_limitedstock.id) as count_limitedstock,
	count(distinct Item_backordered.id) as count_backordered,
	count(distinct Item_disabled.id) as count_disabled
from Product
	left outer join Item on Item.product = Product.id
	left outer join Item as Item_available    on Item_available.product    = Product.id and Item_available.id    in (select id from ItemEnabledView) and Item_available.status    = 0 
	left outer join Item as Item_outofstock   on Item_outofstock.product   = Product.id and Item_outofstock.id   in (select id from ItemEnabledView) and Item_outofstock.status   = 1 
	left outer join Item as Item_limitedstock on Item_limitedstock.product = Product.id and Item_limitedstock.id in (select id from ItemEnabledView) and Item_limitedstock.status = 2 
	left outer join Item as Item_backordered  on Item_backordered.product  = Product.id and Item_backordered.id  in (select id from ItemEnabledView) and Item_backordered.status  = 3 
	left outer join Item as Item_disabled     on Item_disabled.product     = Product.id and Item_disabled.id not in (select id from ItemEnabledView)
group by Product.id;
