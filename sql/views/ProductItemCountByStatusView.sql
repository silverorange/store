create or replace view ProductItemCountByStatusView as

select Product.id as product,
	count(distinct Item.id) as item_count,

	-- available counts
	count(distinct Item_available.id) as count_available,
	count(distinct Item_available_instock.id) as count_available_instock,
	count(distinct Item_available_outofstock.id) as count_available_outofstock,
	count(distinct Item_available_limitedstock.id) as count_available_limitedstock,
	count(distinct Item_available_backordered.id) as count_available_backordered,

	-- unavailable counts
	count(distinct Item_unavailable.id) as count_unavailable,
	count(distinct Item_unavailable_instock.id) as count_unavailable_instock,
	count(distinct Item_unavailable_outofstock.id) as count_unavailable_outofstock,
	count(distinct Item_unavailable_limitedstock.id) as count_unavailable_limitedstock,
	count(distinct Item_unavailable_backordered.id) as count_unavailable_backordered

from Product
left outer join Item on Item.product = Product.id

-- available joins
left outer join Item as Item_available
	on Item_available.product = Product.id
		and Item_available.id in (select item from AvailableItemView)

left outer join Item as Item_available_instock
	on Item_available_instock.product = Product.id and Item_available_instock.id
		in (select item from AvailableItemView) and Item_available_instock.status = 0

left outer join Item as Item_available_outofstock
	on Item_available_outofstock.product = Product.id and Item_available_outofstock.id
		in (select item from AvailableItemView) and Item_available_outofstock.status   = 1

left outer join Item as Item_available_limitedstock
	on Item_available_limitedstock.product = Product.id and Item_available_limitedstock.id
		in (select item from AvailableItemView) and Item_available_limitedstock.status = 2

left outer join Item as Item_available_backordered
	on Item_available_backordered.product = Product.id and Item_available_backordered.id
		in (select item from AvailableItemView) and Item_available_backordered.status  = 3

-- unavailable joins
left outer join Item as Item_unavailable
	on Item_unavailable.product = Product.id
		and Item_unavailable.id not in (select item from AvailableItemView)

left outer join Item as Item_unavailable_instock
	on Item_unavailable_instock.product = Product.id and Item_unavailable_instock.id
		not in (select item from AvailableItemView) and Item_unavailable_instock.status = 0

left outer join Item as Item_unavailable_outofstock
	on Item_unavailable_outofstock.product = Product.id and Item_unavailable_outofstock.id
		not in (select item from AvailableItemView) and Item_unavailable_outofstock.status   = 1

left outer join Item as Item_unavailable_limitedstock
	on Item_unavailable_limitedstock.product = Product.id and Item_unavailable_limitedstock.id
		not in (select item from AvailableItemView) and Item_unavailable_limitedstock.status = 2

left outer join Item as Item_unavailable_backordered
	on Item_unavailable_backordered.product = Product.id and Item_unavailable_backordered.id
		not in (select item from AvailableItemView) and Item_unavailable_backordered.status  = 3

group by Product.id;
