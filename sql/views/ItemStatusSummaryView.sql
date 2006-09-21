create or replace view ItemStatusSummaryView as
	select product, status, count(id)
		from Item
		group by product, status;
