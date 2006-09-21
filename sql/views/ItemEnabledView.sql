-- as long as the item is enabled in one region, its considered enabled by this view
-- this is soley for the use of ProductItemCountByStatusView
create or replace view ItemEnabledView as

select Item.id from Item
	inner join ItemRegionBinding on Item.id = ItemRegionBinding.item
where ItemRegionBinding.enabled = true;
