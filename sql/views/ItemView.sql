create or replace view ItemView as
	select id, sku, product, displayorder, description,
			status, item_group, singular_unit, plural_unit
		from Item
	union
	select item as id, ItemAlias.sku, product, displayorder, description,
			status, item_group, singular_unit, plural_unit
		from ItemAlias inner join Item on ItemAlias.item = Item.id;
