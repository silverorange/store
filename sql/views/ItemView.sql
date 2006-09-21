create or replace view ItemView as
	select id, sku, product, displayorder, description, status, item_group
		from Item
