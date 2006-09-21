-- items are available if they have at least one item enabled for a region, and have status = available or limited-stock or back-ordered
create or replace view AvailableItemView as
	select Item.id as item, ItemRegionBinding.region
		from Item
			inner join ItemRegionBinding on Item.id = ItemRegionBinding.item
		where ItemRegionBinding.enabled = true
			and (Item.status = 0 or Item.status = 2 or Item.status = 3);
