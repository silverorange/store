create or replace view AvailableProductView as
	select product, region from AvailableItemView
		inner join Item on AvailableItemview.item = Item.id
	group by product, region;
