create or replace view ItemView as
	select id, sku, product, displayorder, description, unit, 
			minimum_multiple, free_shipping, pst, gst, export_unit, 
			special_shipping, status, item_group, minimum_quantity, 
			limited_stock_quantity, part_count, part_unit
		from Item
	union
	select item as id, ItemAlias.sku, product, displayorder, description, unit, 
			minimum_multiple, free_shipping, pst, gst, export_unit, 
			special_shipping, status, item_group, minimum_quantity, 
			limited_stock_quantity, part_count, part_unit
		from ItemAlias inner join Item on ItemAlias.item = Item.id;
